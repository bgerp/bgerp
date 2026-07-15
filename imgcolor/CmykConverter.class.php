<?php


/**
 * RGB -> CMYK преобразувател за CMYK акумулатора на преливките.
 *
 * Два енджина: 'imagick-icc' (реална ICC конверсия през ext-imagick и
 * конфигурирани профили) и 'math' (документирана апроксимативна формула,
 * без цветови мениджмънт). 'auto' избира ICC когато е наличен, иначе math
 * със записан fallback. Никакви ICC профили не се разпространяват с пакета -
 * пътищата са конфигурация (IMGCOLOR_CMYK_ICC_*).
 *
 * Без bgERP зависимост - нарочно, за да е тестван директно с
 * `php imgcolor/tests/cli_separation.php` (по образец на imgcolor_Calibration).
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.4
 */
class imgcolor_CmykConverter
{
    /**
     * Версия на алгоритъма за преобразуване - записва се в метаданните
     * на резултата за възпроизводимост.
     */
    const VERSION = 1;


    /**
     * Реално избран енджин: 'math' или 'imagick-icc'
     */
    private $engine;


    /**
     * Дали 'auto' е деградирал до math (за одитируемост)
     */
    private $fallback = false;


    /**
     * Пътища до ICC профилите (при engine 'imagick-icc')
     */
    private $rgbProfilePath = '';
    private $cmykProfilePath = '';


    /**
     * @param array $cfg array('engine' => 'auto'|'math'|'imagick',
     *                         'rgbProfile' => string, 'cmykProfile' => string)
     *
     * @throws InvalidArgumentException при непознат енджин или engine=imagick
     *                                  без налични предпоставки
     */
    public function __construct(array $cfg)
    {
        $engine = isset($cfg['engine']) ? (string) $cfg['engine'] : 'auto';
        if (!in_array($engine, array('auto', 'math', 'imagick'), true)) {
            throw new InvalidArgumentException("CMYK engine must be one of auto|math|imagick: {$engine}");
        }

        $rgb = isset($cfg['rgbProfile']) ? (string) $cfg['rgbProfile'] : '';
        $cmyk = isset($cfg['cmykProfile']) ? (string) $cfg['cmykProfile'] : '';

        $iccReady = extension_loaded('imagick')
            && $rgb !== '' && is_readable($rgb)
            && $cmyk !== '' && is_readable($cmyk);

        if ($engine === 'imagick') {
            if (!$iccReady) {
                throw new InvalidArgumentException('CMYK engine "imagick" requires ext-imagick and readable RGB/CMYK ICC profile files');
            }
            $this->engine = 'imagick-icc';
        } elseif ($engine === 'math') {
            $this->engine = 'math';
        } else {
            $this->engine = $iccReady ? 'imagick-icc' : 'math';
            $this->fallback = !$iccReady;
        }

        if ($this->engine === 'imagick-icc') {
            $this->rgbProfilePath = $rgb;
            $this->cmykProfilePath = $cmyk;
        }
    }


    /**
     * Преобразува списък RGB цветове към CMYK дялове 0..1, в същия ред.
     *
     * @param array $colors list of array(r, g, b), канали 0..255
     *
     * @return array list of array(c, m, y, k), дялове 0..1
     */
    public function convert(array $colors)
    {
        if (!count($colors)) {

            return array();
        }

        return $this->engine === 'imagick-icc' ? $this->convertIcc($colors) : $this->convertMath($colors);
    }


    /**
     * Метаданни за одитируемост на резултата (записват се в cmykJson).
     *
     * @return array
     */
    public function getMetadata()
    {
        $source = 'assumed-sRGB';
        $destination = null;
        if ($this->engine === 'imagick-icc') {
            $source = 'assumed-sRGB:' . basename($this->rgbProfilePath) . ':' . md5_file($this->rgbProfilePath);
            $destination = basename($this->cmykProfilePath) . ':' . md5_file($this->cmykProfilePath);
        }

        return array(
            'engine' => $this->engine,
            'source_profile' => $source,
            'destination_profile' => $destination,
            'fallback' => $this->fallback,
            'version' => self::VERSION,
        );
    }


    /**
     * Апроксимативна математическа конверсия (не е точна за печатна преса):
     *   K = 1 - max(R', G', B'); C = (1-R'-K)/(1-K) и т.н.
     *
     * @param array $colors
     *
     * @return array
     */
    private function convertMath(array $colors)
    {
        $result = array();
        foreach ($colors as $rgb) {
            $r = $rgb[0] / 255;
            $g = $rgb[1] / 255;
            $b = $rgb[2] / 255;
            $k = 1.0 - max($r, $g, $b);
            if ($k > 1.0 - 1e-9) {
                $result[] = array(0.0, 0.0, 0.0, 1.0);
            } else {
                $d = 1.0 - $k;
                $result[] = array((1.0 - $r - $k) / $d, (1.0 - $g - $k) / $d, (1.0 - $b - $k) / $d, $k);
            }
        }

        return $result;
    }


    /**
     * ICC конверсия през Imagick: уникалните цветове се нареждат в канава
     * 1px висока, тагват се с конфигурирания RGB профил и се конвертират
     * към конфигурирания CMYK профил (LCMS, rendering intent по подразбиране
     * на ImageMagick). Цената е по един пиксел на уникален цвят.
     *
     * @param array $colors
     *
     * @throws RuntimeException при грешка на Imagick
     *
     * @return array
     */
    private function convertIcc(array $colors)
    {
        $n = count($colors);
        $bytes = '';
        foreach ($colors as $rgb) {
            $bytes .= chr($rgb[0]) . chr($rgb[1]) . chr($rgb[2]);
        }

        try {
            $img = new Imagick();
            $img->newImage($n, 1, new ImagickPixel('black'));
            $img->setImageType(Imagick::IMGTYPE_TRUECOLOR);
            $img->importImagePixels(0, 0, $n, 1, 'RGB', Imagick::PIXEL_CHAR, $bytes);
            $img->profileImage('icc', file_get_contents($this->rgbProfilePath));
            $img->profileImage('icc', file_get_contents($this->cmykProfilePath));
            $out = $img->exportImagePixels(0, 0, $n, 1, 'CMYK', Imagick::PIXEL_CHAR);
            $img->clear();
        } catch (ImagickException $e) {
            throw new RuntimeException('ICC CMYK conversion failed: ' . $e->getMessage(), 0, $e);
        }

        $result = array();
        for ($i = 0; $i < $n; $i++) {
            $result[] = array(
                $out[4 * $i] / 255,
                $out[4 * $i + 1] / 255,
                $out[4 * $i + 2] / 255,
                $out[4 * $i + 3] / 255,
            );
        }

        return $result;
    }
}
