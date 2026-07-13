<?php


/**
 * Тристепенна пространствена класификация на пикселите за отделяне на
 * преливки (CMYK) от плътни цветове.
 *
 * Алгоритъм (виж docs/superpowers/specs/2026-07-13-imgcolor-cmyk-separation-design.md §4):
 *  1. "Кохерентен дрейф": за всяка ос пикселът се сравнява с двете си
 *     проби на разстояние span в CIELAB+alpha; той е CHANGING само ако
 *     двете полу-разлики сочат в една посока (dot cosine >= coherenceMin)
 *     и средната им дължина е >= noiseDeltaE. Градиентите акумулират
 *     сигнал линейно със span, а JPEG шум/ringing са некохерентни и отпадат.
 *  2. Ерозия: seed е CHANGING пиксел, чийто (2r+1)^2 прозорец не съдържа
 *     SOLID и има поне minSeed CHANGING - тесните AA/blur ленти изчезват.
 *  3. Реконструкция: 8-свързан flood от seeds по CHANGING, блокиран при
 *     твърд ръб (макс. съседна разлика > edgeDeltaE) - не изтича по контури.
 *  4. Пазач: под minCoverage дял всичко се връща към AA (складира се при
 *     плътните цветове), за да не регистрират шумови петна CMYK съдържание.
 *
 * Останалите CHANGING пиксели са клас AA/EDGE и остават в пътя на плътните
 * цветове, където съществуващото сливане по deltaE ги поглъща - както днес.
 *
 * Без bgERP зависимост - тества се директно с
 * `php imgcolor/tests/cli_separation.php`.
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.4
 */
class imgcolor_TransitionClassifier
{
    /**
     * Класове пиксели - байтови стойности в маската (ред по редове, W*H байта)
     */
    const CLS_BG = "\x00";
    const CLS_SOLID = "\x01";
    const CLS_AA = "\x02";
    const CLS_TRANS = "\x03";


    /**
     * Временен маркер по време на реконструкцията: AA пиксел, вече отхвърлен
     * като твърд ръб - да не се тества повторно от друг съсед.
     */
    const CLS_BLOCKED = "\x04";


    /**
     * Горна граница на Lab кеша по 24-битов RGB ключ (по образец на
     * WhiteBackgroundCropper::MEMO_CAP) - отвъд нея се смята без кеш.
     */
    const LAB_MEMO_CAP = 65536;


    /**
     * Прагове по подразбиране - калибрирани върху фикстурите на прототипа
     * (виж спецификацията, таблицата в §4).
     */
    public static $defaults = array(
        'span' => 4,
        'noiseDeltaE' => 1.0,
        'coherenceMin' => 0.4,
        'aaRadius' => 3,
        'minSeed' => 20,
        'edgeDeltaE' => 10.0,
        'minCoverage' => 0.005,
        'alphaThreshold' => 8,
    );


    /**
     * @var \ImageColorAnalyzer\Color\ColorConverter|null
     */
    private static $converter;


    /**
     * @var array rgb24 => array(L, a, b)
     */
    private static $labMemo = array();


    /**
     * Валидира и попълва праговете; непознатите ключове се игнорират.
     *
     * @param array $params
     *
     * @throws InvalidArgumentException с името на полето при стойност извън диапазона
     *
     * @return array
     */
    public static function normalizeParams(array $params)
    {
        $p = array_merge(self::$defaults, array_intersect_key($params, self::$defaults));

        $p['span'] = (int) $p['span'];
        if ($p['span'] < 1 || $p['span'] > 8) {
            throw new InvalidArgumentException('Transition span must be in the range 1..8');
        }

        $p['noiseDeltaE'] = (float) $p['noiseDeltaE'];
        if ($p['noiseDeltaE'] <= 0 || $p['noiseDeltaE'] > 20) {
            throw new InvalidArgumentException('Transition noiseDeltaE must be in the range (0..20]');
        }

        $p['coherenceMin'] = (float) $p['coherenceMin'];
        if ($p['coherenceMin'] < -1 || $p['coherenceMin'] > 1) {
            throw new InvalidArgumentException('Transition coherenceMin must be in the range -1..1');
        }

        $p['aaRadius'] = (int) $p['aaRadius'];
        if ($p['aaRadius'] < 1 || $p['aaRadius'] > 8) {
            throw new InvalidArgumentException('Transition aaRadius must be in the range 1..8');
        }

        $p['minSeed'] = (int) $p['minSeed'];
        if ($p['minSeed'] < 1 || $p['minSeed'] > 289) {
            throw new InvalidArgumentException('Transition minSeed must be in the range 1..289');
        }

        $p['edgeDeltaE'] = (float) $p['edgeDeltaE'];
        if ($p['edgeDeltaE'] <= 0 || $p['edgeDeltaE'] > 200) {
            throw new InvalidArgumentException('Transition edgeDeltaE must be in the range (0..200]');
        }

        $p['minCoverage'] = (float) $p['minCoverage'];
        if ($p['minCoverage'] < 0 || $p['minCoverage'] >= 1) {
            throw new InvalidArgumentException('Transition minCoverage must be in the range 0..<1');
        }

        $p['alphaThreshold'] = (int) $p['alphaThreshold'];
        if ($p['alphaThreshold'] < 0 || $p['alphaThreshold'] > 255) {
            throw new InvalidArgumentException('Transition alphaThreshold must be in the range 0..255');
        }

        return $p;
    }


    /**
     * Класифицира всички пиксели на растера.
     *
     * @param \ImageColorAnalyzer\Contracts\Raster $raster
     * @param array                                $params виж self::$defaults
     *
     * @return stdClass {mask, width, height, analyzedCount, solidCount, aaCount, transitionCount}
     */
    public static function classify($raster, array $params = array())
    {
        $p = self::normalizeParams($params);

        $w = $raster->width();
        $h = $raster->height();
        $n = $w * $h;
        $span = $p['span'];
        $alphaThr = $p['alphaThreshold'];

        // Пас A: пълните пикселни данни като бинарен низ (4 байта/пиксел,
        // big-endian ARGB) - позволява произволен достъп без PHP масив за
        // всеки пиксел (паметта следва 4N байта).
        $data = '';
        $rowBuf = array();
        $x = 0;
        foreach ($raster->pixels() as $px) {
            $rowBuf[] = ($px->a << 24) | ($px->r << 16) | ($px->g << 8) | $px->b;
            if (++$x === $w) {
                $data .= pack('N*', ...$rowBuf);
                $rowBuf = array();
                $x = 0;
            }
        }

        // Етап 1: кохерентен дрейф -> CHANGING (маркиран като CLS_AA)
        $stage1 = str_repeat(self::CLS_SOLID, $n);
        $analyzed = 0;
        $rows = array();
        $floor = $p['noiseDeltaE'];
        $cosMin = $p['coherenceMin'];

        for ($y = 0; $y < $h; $y++) {
            if (!isset($rows[$y])) {
                $rows[$y] = array_values(unpack('N*', substr($data, $y * $w * 4, $w * 4)));
            }
            $rowC = $rows[$y];
            $rowU = null;
            $rowD = null;
            if ($y - $span >= 0 && $y + $span < $h) {
                if (!isset($rows[$y + $span])) {
                    $rows[$y + $span] = array_values(unpack('N*', substr($data, ($y + $span) * $w * 4, $w * 4)));
                }
                $rowU = $rows[$y - $span];
                $rowD = $rows[$y + $span];
            }
            $base = $y * $w;

            for ($x = 0; $x < $w; $x++) {
                $vc = $rowC[$x];
                if (($vc >> 24) < $alphaThr) {
                    $stage1[$base + $x] = self::CLS_BG;
                    continue;
                }
                $analyzed++;

                $changing = false;
                if ($x - $span >= 0 && $x + $span < $w) {
                    $va = $rowC[$x - $span];
                    $vb = $rowC[$x + $span];
                    if (($va >> 24) >= $alphaThr && ($vb >> 24) >= $alphaThr && !($va === $vc && $vb === $vc)) {
                        $changing = self::coherentDrift($va, $vc, $vb, $floor, $cosMin);
                    }
                }
                if (!$changing && $rowU !== null) {
                    $va = $rowU[$x];
                    $vb = $rowD[$x];
                    if (($va >> 24) >= $alphaThr && ($vb >> 24) >= $alphaThr && !($va === $vc && $vb === $vc)) {
                        $changing = self::coherentDrift($va, $vc, $vb, $floor, $cosMin);
                    }
                }
                if ($changing) {
                    $stage1[$base + $x] = self::CLS_AA;
                }
            }

            unset($rows[$y - $span]);
        }

        // Етап 2: ерозия с плъзгащи се суми по колони -> seeds
        $mask = $stage1;
        $r = $p['aaRadius'];
        $minSeed = $p['minSeed'];
        $queue = array();
        $transitionCount = 0;

        $colChg = array_fill(0, $w, 0);
        $colSol = array_fill(0, $w, 0);
        for ($yy = 0; $yy <= $r && $yy < $h; $yy++) {
            self::tallyRow($stage1, $yy * $w, $w, $colChg, $colSol, 1);
        }

        for ($y = 0; $y < $h; $y++) {
            if ($y > 0) {
                if ($y + $r < $h) {
                    self::tallyRow($stage1, ($y + $r) * $w, $w, $colChg, $colSol, 1);
                }
                if ($y - $r - 1 >= 0) {
                    self::tallyRow($stage1, ($y - $r - 1) * $w, $w, $colChg, $colSol, -1);
                }
            }

            $sumChg = 0;
            $sumSol = 0;
            for ($x = 0; $x <= $r && $x < $w; $x++) {
                $sumChg += $colChg[$x];
                $sumSol += $colSol[$x];
            }
            $base = $y * $w;
            for ($x = 0; $x < $w; $x++) {
                if ($x > 0) {
                    if ($x + $r < $w) {
                        $sumChg += $colChg[$x + $r];
                        $sumSol += $colSol[$x + $r];
                    }
                    if ($x - $r - 1 >= 0) {
                        $sumChg -= $colChg[$x - $r - 1];
                        $sumSol -= $colSol[$x - $r - 1];
                    }
                }
                $i = $base + $x;
                if ($stage1[$i] === self::CLS_AA && $sumSol === 0 && $sumChg >= $minSeed) {
                    $mask[$i] = self::CLS_TRANS;
                    $queue[] = $i;
                    $transitionCount++;
                }
            }
        }

        // Етап 3: 8-свързана реконструкция от seeds по CHANGING, блокирана
        // при твърд ръб; блокираните се маркират, за да се тестват веднъж.
        $edgeCap = $p['edgeDeltaE'];
        $head = 0;
        while ($head < count($queue)) {
            $i = $queue[$head++];
            $cy = intdiv($i, $w);
            $cx = $i - $cy * $w;
            for ($dy = -1; $dy <= 1; $dy++) {
                $ny = $cy + $dy;
                if ($ny < 0 || $ny >= $h) {
                    continue;
                }
                for ($dx = -1; $dx <= 1; $dx++) {
                    if ($dx === 0 && $dy === 0) {
                        continue;
                    }
                    $nx = $cx + $dx;
                    if ($nx < 0 || $nx >= $w) {
                        continue;
                    }
                    $j = $ny * $w + $nx;
                    if ($mask[$j] !== self::CLS_AA) {
                        continue;
                    }
                    if (self::isHardEdge($data, $nx, $ny, $w, $h, $alphaThr, $edgeCap)) {
                        $mask[$j] = self::CLS_BLOCKED;
                        continue;
                    }
                    $mask[$j] = self::CLS_TRANS;
                    $queue[] = $j;
                    $transitionCount++;
                }
            }
        }
        if ($head > 0) {
            $mask = strtr($mask, array(self::CLS_BLOCKED => self::CLS_AA));
        }

        // Етап 4: пазач по минимално покритие
        if ($analyzed === 0 || $transitionCount / $analyzed < $p['minCoverage']) {
            if ($transitionCount > 0) {
                $mask = strtr($mask, array(self::CLS_TRANS => self::CLS_AA));
            }
            $transitionCount = 0;
        }

        $result = new stdClass();
        $result->mask = $mask;
        $result->width = $w;
        $result->height = $h;
        $result->analyzedCount = $analyzed;
        $result->solidCount = substr_count($mask, self::CLS_SOLID);
        $result->aaCount = substr_count($mask, self::CLS_AA);
        $result->transitionCount = $transitionCount;

        return $result;
    }


    /**
     * Диагностична визуализация на маската: плътно=сиво, AA=червено,
     * преливка=синьо, фон=прозрачно. Само за тестове/CLI инспекция.
     *
     * @param stdClass $cls резултат от classify()
     *
     * @return string PNG байтове
     */
    public static function renderMaskPng($cls)
    {
        $im = imagecreatetruecolor($cls->width, $cls->height);
        imagealphablending($im, false);
        imagesavealpha($im, true);

        $colors = array(
            self::CLS_BG => imagecolorallocatealpha($im, 0, 0, 0, 127),
            self::CLS_SOLID => imagecolorallocatealpha($im, 200, 200, 200, 0),
            self::CLS_AA => imagecolorallocatealpha($im, 220, 40, 40, 0),
            self::CLS_TRANS => imagecolorallocatealpha($im, 40, 80, 220, 0),
        );

        $i = 0;
        for ($y = 0; $y < $cls->height; $y++) {
            for ($x = 0; $x < $cls->width; $x++) {
                imagesetpixel($im, $x, $y, $colors[$cls->mask[$i++]]);
            }
        }

        ob_start();
        imagepng($im);

        return ob_get_clean();
    }


    /**
     * Кохерентен дрейф по една ос: двете полу-разлики около центъра трябва
     * да са достатъчно големи средно и еднопосочни в Lab+alpha пространството.
     *
     * @param int   $va     опакован ARGB на пробата "преди"
     * @param int   $vc     опакован ARGB на центъра
     * @param int   $vb     опакован ARGB на пробата "след"
     * @param float $floor  noiseDeltaE
     * @param float $cosMin coherenceMin
     *
     * @return bool
     */
    private static function coherentDrift($va, $vc, $vb, $floor, $cosMin)
    {
        $la = self::lab($va & 0xFFFFFF);
        $lc = self::lab($vc & 0xFFFFFF);
        $lb = self::lab($vb & 0xFFFFFF);
        $aa = ($va >> 24) * 100 / 255;
        $ac = ($vc >> 24) * 100 / 255;
        $ab = ($vb >> 24) * 100 / 255;

        $v10 = $lc[0] - $la[0];
        $v11 = $lc[1] - $la[1];
        $v12 = $lc[2] - $la[2];
        $v13 = $ac - $aa;
        $v20 = $lb[0] - $lc[0];
        $v21 = $lb[1] - $lc[1];
        $v22 = $lb[2] - $lc[2];
        $v23 = $ab - $ac;

        $n1 = sqrt($v10 * $v10 + $v11 * $v11 + $v12 * $v12 + $v13 * $v13);
        $n2 = sqrt($v20 * $v20 + $v21 * $v21 + $v22 * $v22 + $v23 * $v23);

        if (($n1 + $n2) / 2 < $floor || $n1 < 1e-9 || $n2 < 1e-9) {

            return false;
        }

        $dot = $v10 * $v20 + $v11 * $v21 + $v12 * $v22 + $v13 * $v23;

        return $dot / ($n1 * $n2) >= $cosMin;
    }


    /**
     * Дали пикселът лежи на твърд ръб: максималната разлика (deltaE или
     * alpha, в проценти) до 4-те му съседа надхвърля $edgeCap.
     *
     * @param string $data     опакованите пикселни данни (4 байта/пиксел)
     * @param int    $x
     * @param int    $y
     * @param int    $w
     * @param int    $h
     * @param int    $alphaThr
     * @param float  $edgeCap
     *
     * @return bool
     */
    private static function isHardEdge($data, $x, $y, $w, $h, $alphaThr, $edgeCap)
    {
        $vc = unpack('N', substr($data, ($y * $w + $x) * 4, 4))[1];
        $lc = self::lab($vc & 0xFFFFFF);
        $acAlpha = ($vc >> 24) * 100 / 255;

        foreach (array(array(-1, 0), array(1, 0), array(0, -1), array(0, 1)) as $d) {
            $nx = $x + $d[0];
            $ny = $y + $d[1];
            if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
                continue;
            }
            $vn = unpack('N', substr($data, ($ny * $w + $nx) * 4, 4))[1];
            if (($vn >> 24) < $alphaThr) {
                continue;
            }
            $alphaDiff = abs(($vn >> 24) * 100 / 255 - $acAlpha);
            if ($alphaDiff > $edgeCap) {

                return true;
            }
            $ln = self::lab($vn & 0xFFFFFF);
            $dE = sqrt(($lc[0] - $ln[0]) ** 2 + ($lc[1] - $ln[1]) ** 2 + ($lc[2] - $ln[2]) ** 2);
            if ($dE > $edgeCap) {

                return true;
            }
        }

        return false;
    }


    /**
     * CIELAB на 24-битов RGB с ограничен кеш.
     *
     * @param int $rgb24
     *
     * @return array array(L, a, b)
     */
    private static function lab($rgb24)
    {
        if (isset(self::$labMemo[$rgb24])) {

            return self::$labMemo[$rgb24];
        }

        if (self::$converter === null) {
            self::$converter = new \ImageColorAnalyzer\Color\ColorConverter();
        }

        $lab = self::$converter->rgbToLab(new \ImageColorAnalyzer\Contracts\ColorRGBA(
            ($rgb24 >> 16) & 0xFF,
            ($rgb24 >> 8) & 0xFF,
            $rgb24 & 0xFF
        ));

        if (count(self::$labMemo) < self::LAB_MEMO_CAP) {
            self::$labMemo[$rgb24] = $lab;
        }

        return $lab;
    }


    /**
     * Добавя/изважда броенията на един ред от колонните суми на ерозията.
     *
     * @param string $stage1 маската от етап 1
     * @param int    $offset начало на реда в маската
     * @param int    $w
     * @param array  $colChg колонни суми CHANGING (по референция)
     * @param array  $colSol колонни суми SOLID (по референция)
     * @param int    $sign   +1 добавяне, -1 изваждане
     */
    private static function tallyRow($stage1, $offset, $w, array &$colChg, array &$colSol, $sign)
    {
        for ($x = 0; $x < $w; $x++) {
            $b = $stage1[$offset + $x];
            if ($b === self::CLS_AA) {
                $colChg[$x] += $sign;
            } elseif ($b === self::CLS_SOLID) {
                $colSol[$x] += $sign;
            }
        }
    }
}
