# imgcolor CMYK Separation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Separate gradient/transition pixels from solid colors in `imgcolor` analysis: solids keep the existing cluster list, transitions produce a normalized CMYK ink-composition accumulator.

**Architecture:** New wrapper-side classes (no edits under `imgcolor/lib/`): a three-way spatial classifier (coherent-drift + erosion + edge-capped reconstruction, per spec §4), a mask-aware `Raster` decorator feeding the *unchanged* library clustering, a two-engine RGB→CMYK converter (Imagick ICC / math fallback), and an accumulator producing `cmykJson`. Orchestrated by `imgcolor_Separation::process()` (bgERP-free, CLI-testable) and `imgcolor_Analyzer::processSeparated()` (bgERP glue). Spec: `docs/superpowers/specs/2026-07-13-imgcolor-cmyk-separation-design.md`.

**Tech Stack:** PHP (bgERP legacy style: no `strict_types`, docblock types, `array()` literals), GD, vendored `ImageColorAnalyzer` library contracts, optional ext-imagick.

## Global Constraints

- Never edit files under `imgcolor/lib/` (docs/integration.md).
- New wrapper classes must not use bgERP APIs (`core_*`, `imgcolor_Setup`) except `imgcolor_Analyzer`, `imgcolor_Setup`, `imgcolor_Analyses`, `imgcolor_Demo` — the CLI tests run without a bgERP instance, mirroring `imgcolor/tests/cli_calibration.php`.
- bgERP class files: `imgcolor/<Name>.class.php` defines `imgcolor_<Name>`; header docblocks in Bulgarian with `@category bgerp`, `@package imgcolor`, license GPL 3 — copy the style of `imgcolor/Calibration.class.php`.
- Interface implementations of `\ImageColorAnalyzer\Contracts\Raster` must use typed signatures (the interface declares return types).
- Classifier defaults (spec §4, calibrated): span=4, noiseDeltaE=1.0, coherenceMin=0.4, aaRadius=3, minSeed=20, edgeDeltaE=10.0, minCoverage=0.005, alphaThreshold=8.
- Solid-only images must produce `colorsJson` byte-identical to the legacy path (proved by test, not asserted).
- No ICC profile files are added to the repository.
- All CLI tests must pass: `php imgcolor/tests/cli_setup_bucket.php && php imgcolor/tests/cli_calibration.php && php imgcolor/tests/cli_analysis_snapshot.php && php imgcolor/tests/cli_init_signature.php && php imgcolor/tests/cli_parity.php && php imgcolor/tests/cli_separation.php`
- Commits: conventional style `feat(imgcolor): ...` / `test(imgcolor): ...`, no self-attribution.

---

### Task 1: `imgcolor_CmykConverter`

**Files:**
- Create: `imgcolor/CmykConverter.class.php`
- Test: `imgcolor/tests/cli_separation.php` (new file, section 1)

**Interfaces:**
- Consumes: nothing from other tasks.
- Produces: `new imgcolor_CmykConverter(array $cfg)` where `$cfg = array('engine' => 'auto'|'math'|'imagick', 'rgbProfile' => string, 'cmykProfile' => string)`; `convert(array $colors)` taking `list<array{0:int,1:int,2:int}>` returning `list<array{0:float,1:float,2:float,3:float}>` (fractions 0..1, same order); `getMetadata()` returning `array{engine:string, source_profile:string, destination_profile:?string, fallback:bool, version:int}`. Throws `InvalidArgumentException` on bad config.

- [ ] **Step 1: Write the failing test skeleton + converter section**

Create `imgcolor/tests/cli_separation.php`:

```php
<?php

// Framework-free regression for the CMYK separation feature: classifier,
// masked raster, CMYK converter/accumulator, and the bgERP-free orchestrator.
// Requires PHP 8.2+ with GD; no bgERP instance needed (like cli_parity.php).
// Usage: php imgcolor/tests/cli_separation.php

$libBase = __DIR__ . '/../lib/image-color-analyzer/src/';

spl_autoload_register(function ($class) use ($libBase) {
    $prefix = 'ImageColorAnalyzer\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $rel = substr($class, strlen($prefix));
    $file = $libBase . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) {
        include_once $file;
    }
});

require_once __DIR__ . '/../CmykConverter.class.php';

function fail($msg)
{
    fwrite(STDERR, "FAIL: {$msg}\n");
    exit(1);
}

function approx($a, $b, $tol, $what)
{
    if (abs($a - $b) > $tol) {
        fail("{$what}: expected ~{$b}, got {$a}");
    }
}

if (!extension_loaded('gd')) {
    fail('ext-gd is not loaded in this CLI php');
}

// ---------------------------------------------------------------------------
// 1) CMYK converter: math engine vectors, engine selection, metadata
// ---------------------------------------------------------------------------

$math = new imgcolor_CmykConverter(array('engine' => 'math', 'rgbProfile' => '', 'cmykProfile' => ''));

$vectors = array(
    // rgb                     => cmyk
    array(array(255, 0, 0),   array(0.0, 1.0, 1.0, 0.0)),
    array(array(0, 255, 0),   array(1.0, 0.0, 1.0, 0.0)),
    array(array(0, 0, 255),   array(1.0, 1.0, 0.0, 0.0)),
    array(array(0, 0, 0),     array(0.0, 0.0, 0.0, 1.0)),
    array(array(255, 255, 255), array(0.0, 0.0, 0.0, 0.0)),
    array(array(128, 128, 128), array(0.0, 0.0, 0.0, 1 - 128 / 255)),
    array(array(255, 128, 0), array(0.0, 0.498, 1.0, 0.0)),
);
$in = array();
foreach ($vectors as $v) {
    $in[] = $v[0];
}
$out = $math->convert($in);
if (count($out) !== count($in)) {
    fail('converter must return one CMYK per input color');
}
foreach ($vectors as $i => $v) {
    foreach (array('c', 'm', 'y', 'k') as $ch => $name) {
        approx($out[$i][$ch], $v[1][$ch], 0.002, "math cmyk {$name} for rgb(" . implode(',', $v[0]) . ')');
    }
}
if ($math->convert(array()) !== array()) {
    fail('empty input must yield empty output');
}

$meta = $math->getMetadata();
if ($meta['engine'] !== 'math' || $meta['fallback'] !== false || $meta['version'] !== 1
    || $meta['source_profile'] !== 'assumed-sRGB' || $meta['destination_profile'] !== null) {
    fail('math engine metadata mismatch: ' . json_encode($meta));
}

// auto without imagick/profiles degrades to math and records the fallback
$auto = new imgcolor_CmykConverter(array('engine' => 'auto', 'rgbProfile' => '', 'cmykProfile' => ''));
$meta = $auto->getMetadata();
if ($meta['engine'] === 'math' && $meta['fallback'] !== true) {
    fail('auto->math degradation must set fallback=true');
}

// invalid engine name rejected
try {
    new imgcolor_CmykConverter(array('engine' => 'lcms', 'rgbProfile' => '', 'cmykProfile' => ''));
    fail('invalid engine must be rejected');
} catch (InvalidArgumentException $e) {
    if (strpos($e->getMessage(), 'engine') === false) {
        fail('engine error message must name the field');
    }
}

// explicit imagick engine without prerequisites is a loud error, not a fallback
if (!extension_loaded('imagick')) {
    try {
        new imgcolor_CmykConverter(array('engine' => 'imagick', 'rgbProfile' => '', 'cmykProfile' => ''));
        fail('engine=imagick without ext-imagick must be rejected');
    } catch (InvalidArgumentException $e) {
        // expected
    }
    echo "NOTE: ext-imagick absent - ICC conversion exercised only via metadata/fallback logic\n";
}

echo "PASS: cli_separation section 1 (converter)\n";
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php imgcolor/tests/cli_separation.php`
Expected: fatal error, `Failed opening required '.../CmykConverter.class.php'`

- [ ] **Step 3: Implement the converter**

Create `imgcolor/CmykConverter.class.php`:

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php imgcolor/tests/cli_separation.php`
Expected: `PASS: cli_separation section 1 (converter)` (plus the imagick NOTE locally), exit 0.

- [ ] **Step 5: Commit**

```bash
git add imgcolor/CmykConverter.class.php imgcolor/tests/cli_separation.php
git commit -m "feat(imgcolor): add two-engine RGB-to-CMYK converter"
```

---

### Task 2: `imgcolor_CmykAccumulator`

**Files:**
- Create: `imgcolor/CmykAccumulator.class.php`
- Modify: `imgcolor/tests/cli_separation.php` (append section 2)

**Interfaces:**
- Consumes: `imgcolor_CmykConverter::convert()` / `getMetadata()` (Task 1); byte constants `imgcolor_TransitionClassifier::CLS_TRANS` etc. (Task 3 — to avoid a forward dependency the byte values are class constants on the accumulator's consumer side; the accumulator receives a classification `stdClass` and compares mask bytes to `"\x03"` via the constant defined in Task 3; for this task's test the stdClass is built by hand and the constant is inlined as `chr(3)`). To keep tasks independent, this task defines the shared constants in a tiny `imgcolor/TransitionClassifier.class.php` *stub* containing only the class constants; Task 3 fills in the methods.
- Produces: `imgcolor_CmykAccumulator::accumulate(\ImageColorAnalyzer\Contracts\Raster $raster, stdClass $cls, imgcolor_CmykConverter $converter)` returning `?array` (the `cmykJson` payload per spec §8, or null when `$cls->transitionCount == 0`); `$cls` must have `mask` (byte string), `analyzedCount` (int), `transitionCount` (int).

- [ ] **Step 1: Create the constants stub**

Create `imgcolor/TransitionClassifier.class.php` with only the docblock and constants (Task 3 replaces the body):

```php
<?php


/**
 * Тристепенна пространствена класификация на пикселите за отделяне на
 * преливки (CMYK) от плътни цветове. Виж
 * docs/superpowers/specs/2026-07-13-imgcolor-cmyk-separation-design.md.
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
}
```

- [ ] **Step 2: Append the failing accumulator test (section 2)**

Append to `imgcolor/tests/cli_separation.php` (before the final section-1 `echo`... append after it; each section ends with its own PASS echo):

```php
// ---------------------------------------------------------------------------
// 2) CMYK accumulator: alpha weighting, largest-remainder 100.0, zero ink
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../TransitionClassifier.class.php';
require_once __DIR__ . '/../CmykAccumulator.class.php';

/**
 * Builds an InMemoryRaster + hand-made classification over a pixel spec list:
 * each entry: array(r, g, b, a255, classByte).
 */
function accumFixture(array $pixels)
{
    $colors = array();
    $mask = '';
    foreach ($pixels as $p) {
        $colors[] = new \ImageColorAnalyzer\Contracts\ColorRGBA($p[0], $p[1], $p[2], $p[3]);
        $mask .= $p[4];
    }
    $raster = new \ImageColorAnalyzer\ImageLoader\InMemoryRaster(count($pixels), 1, $colors);
    $cls = new stdClass();
    $cls->mask = $mask;
    $cls->analyzedCount = 0;
    $cls->transitionCount = 0;
    foreach ($pixels as $p) {
        if ($p[4] !== imgcolor_TransitionClassifier::CLS_BG) {
            $cls->analyzedCount++;
        }
        if ($p[4] === imgcolor_TransitionClassifier::CLS_TRANS) {
            $cls->transitionCount++;
        }
    }

    return array($raster, $cls);
}

$T = imgcolor_TransitionClassifier::CLS_TRANS;
$S = imgcolor_TransitionClassifier::CLS_SOLID;
$B = imgcolor_TransitionClassifier::CLS_BG;

// no transitions -> null
list($raster, $cls) = accumFixture(array(array(10, 20, 30, 255, $S)));
if (imgcolor_CmykAccumulator::accumulate($raster, $cls, $math) !== null) {
    fail('no transitions must yield null');
}

// pure red + pure cyan-ish transitions, solid pixel excluded, composition sums to 100.0
list($raster, $cls) = accumFixture(array(
    array(255, 0, 0, 255, $T),   // math: C0 M1 Y1 K0
    array(0, 255, 255, 255, $T), // math: C1 M0 Y0 K0
    array(0, 0, 0, 255, $S),     // solid: excluded (would add K)
    array(0, 0, 0, 255, $B),     // background: excluded
));
$res = imgcolor_CmykAccumulator::accumulate($raster, $cls, $math);
if ($res === null) {
    fail('transitions present must yield a result');
}
$sum = $res['composition_percent']['c'] + $res['composition_percent']['m']
     + $res['composition_percent']['y'] + $res['composition_percent']['k'];
approx($sum, 100.0, 0.001, 'composition must sum to exactly 100.0');
approx($res['composition_percent']['c'], 33.3, 0.11, 'cyan share');
approx($res['composition_percent']['k'], 0.0, 0.001, 'solid black pixel must not leak into K');
approx($res['transition_coverage_percent'], 66.7, 0.11, 'coverage = 2 of 3 analyzed');
approx($res['ink_total'], 3.0, 0.001, 'raw ink total');
if ($res['conversion']['engine'] !== 'math') {
    fail('conversion metadata must be embedded');
}

// alpha weighting: half-transparent red deposits half the ink of opaque red
list($raster, $cls) = accumFixture(array(
    array(255, 0, 0, 255, $T),
    array(255, 0, 0, 128, $T),
));
$res = imgcolor_CmykAccumulator::accumulate($raster, $cls, $math);
approx($res['ink_total'], 2 * (1 + 128 / 255), 0.01, 'alpha-weighted ink total');

// zero ink: white-only transition reports zeros, no division by zero
list($raster, $cls) = accumFixture(array(array(255, 255, 255, 200, $T)));
$res = imgcolor_CmykAccumulator::accumulate($raster, $cls, $math);
if ($res['ink_total'] != 0.0) {
    fail('white transition must accumulate zero ink');
}
foreach (array('c', 'm', 'y', 'k') as $ch) {
    if ($res['composition_percent'][$ch] !== 0.0) {
        fail('zero-ink composition must be all zeros');
    }
}
approx($res['transition_coverage_percent'], 100.0, 0.001, 'zero-ink coverage still reported');

echo "PASS: cli_separation section 2 (accumulator)\n";
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php imgcolor/tests/cli_separation.php`
Expected: fatal error, `Failed opening required '.../CmykAccumulator.class.php'`

- [ ] **Step 4: Implement the accumulator**

Create `imgcolor/CmykAccumulator.class.php`:

```php
<?php


/**
 * CMYK акумулатор върху преливките: групира TRANSITION пикселите по цвят,
 * конвертира уникалните цветове (imgcolor_CmykConverter), натрупва мастилата
 * с тегло alpha/255 и нормализира състава до точно 100.0% по метода на
 * най-големия остатък (както PercentageCoverageCalculator на библиотеката).
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
class imgcolor_CmykAccumulator
{
    /**
     * Натрупва CMYK резултата за TRANSITION пикселите на растера.
     *
     * @param \ImageColorAnalyzer\Contracts\Raster $raster    изрязаният растер
     * @param stdClass                             $cls       резултат от imgcolor_TransitionClassifier::classify()
     * @param imgcolor_CmykConverter               $converter
     *
     * @return array|null null когато няма преливки; иначе payload за cmykJson
     */
    public static function accumulate($raster, $cls, imgcolor_CmykConverter $converter)
    {
        if (empty($cls->transitionCount)) {

            return null;
        }

        // Групиране по 24-битов RGB ключ: тегло = сума alpha/255
        $bins = array();
        $i = 0;
        foreach ($raster->pixels() as $px) {
            if ($cls->mask[$i++] !== imgcolor_TransitionClassifier::CLS_TRANS) {
                continue;
            }
            $key = ($px->r << 16) | ($px->g << 8) | $px->b;
            $bins[$key] = (isset($bins[$key]) ? $bins[$key] : 0.0) + $px->a / 255;
        }

        // Каноничен ред за детерминизъм, независим от реда на срещане
        ksort($bins);

        $colors = array();
        foreach ($bins as $key => $weight) {
            $colors[] = array(($key >> 16) & 0xFF, ($key >> 8) & 0xFF, $key & 0xFF);
        }

        $cmyk = $converter->convert($colors);

        $raw = array('c' => 0.0, 'm' => 0.0, 'y' => 0.0, 'k' => 0.0);
        $j = 0;
        foreach ($bins as $weight) {
            $raw['c'] += $cmyk[$j][0] * $weight;
            $raw['m'] += $cmyk[$j][1] * $weight;
            $raw['y'] += $cmyk[$j][2] * $weight;
            $raw['k'] += $cmyk[$j][3] * $weight;
            $j++;
        }

        $inkTotal = $raw['c'] + $raw['m'] + $raw['y'] + $raw['k'];

        // Изрично дефиниран нулев случай (напр. само-бяла преливка): нули,
        // без деление - процентният състав няма смисъл без мастило.
        $percent = array('c' => 0.0, 'm' => 0.0, 'y' => 0.0, 'k' => 0.0);
        if ($inkTotal > 0) {
            $percent = self::normalizePercent($raw, $inkTotal);
        }

        $rounded = array();
        foreach ($raw as $ch => $v) {
            $rounded[$ch] = round($v, 3);
        }

        return array(
            'transition_coverage_percent' => round($cls->transitionCount / $cls->analyzedCount * 100, 1),
            'composition_percent' => $percent,
            'ink_total' => round($inkTotal, 3),
            'raw_channels' => $rounded,
            'conversion' => $converter->getMetadata(),
        );
    }


    /**
     * Най-голям остатък в десети от процента: четирите стойности сумират
     * точно до 100.0 (1000 десети), без "99.9%" артефакти.
     *
     * @param array $raw      натрупани канали
     * @param float $inkTotal сума на каналите (> 0)
     *
     * @return array
     */
    private static function normalizePercent(array $raw, $inkTotal)
    {
        $tenths = array();
        $remainders = array();
        $allocated = 0;
        foreach ($raw as $ch => $v) {
            $exact = $v / $inkTotal * 1000;
            $floor = (int) floor($exact);
            $tenths[$ch] = $floor;
            $remainders[$ch] = $exact - $floor;
            $allocated += $floor;
        }

        $order = array_keys($remainders);
        usort($order, function ($a, $b) use ($remainders) {
            $byRemainder = $remainders[$b] <=> $remainders[$a];

            return $byRemainder !== 0 ? $byRemainder : strcmp($a, $b);
        });

        for ($i = 0; $i < 1000 - $allocated; $i++) {
            $tenths[$order[$i]]++;
        }

        $percent = array();
        foreach ($tenths as $ch => $t) {
            $percent[$ch] = $t / 10.0;
        }

        return $percent;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php imgcolor/tests/cli_separation.php`
Expected: sections 1 and 2 PASS, exit 0.

- [ ] **Step 6: Commit**

```bash
git add imgcolor/CmykAccumulator.class.php imgcolor/TransitionClassifier.class.php imgcolor/tests/cli_separation.php
git commit -m "feat(imgcolor): add alpha-weighted CMYK accumulator with exact-100 normalization"
```

---

### Task 3: `imgcolor_TransitionClassifier` (full algorithm)

**Files:**
- Modify: `imgcolor/TransitionClassifier.class.php` (replace stub body)
- Modify: `imgcolor/tests/cli_separation.php` (append section 3 + fixture helpers)

**Interfaces:**
- Consumes: `\ImageColorAnalyzer\Contracts\Raster` (`width()`, `height()`, `pixels()`), `\ImageColorAnalyzer\Color\ColorConverter::rgbToLab()`.
- Produces: `imgcolor_TransitionClassifier::normalizeParams(array $params)` → validated array (defaults merged; throws `InvalidArgumentException` naming the field); `::classify(Raster $raster, array $params)` → `stdClass{mask:string, width:int, height:int, analyzedCount:int, solidCount:int, aaCount:int, transitionCount:int}`; `::renderMaskPng(stdClass $cls)` → PNG bytes (debug: solid gray, AA red, transition blue, background transparent); class constants `CLS_BG|CLS_SOLID|CLS_AA|CLS_TRANS` and `$defaults`.

Algorithm (spec §4, validated in prototype): stage 1 coherent-drift CHANGING mask over a rolling 2·span+1 row window of packed ARGB ints (Lab memo capped at 65536 entries; alpha as 4th component scaled ×100/255); stage 2 erosion via sliding box counts (seed ⇔ CHANGING center, zero SOLID in (2r+1)² window, ≥minSeed CHANGING); stage 3 BFS 8-connected reconstruction across CHANGING, blocked where a pixel's max 4-neighbor ΔE exceeds edgeDeltaE (blocked pixels memoized with a temp byte so they are tested once); stage 4 coverage guard (below minCoverage → all TRANS→AA via strtr). Full pixel data kept as a `pack('N*')` binary string (4 bytes/px) for random access during flood.

- [ ] **Step 1: Append failing classifier tests (section 3) with fixture generators** — the complete code for the section, including `mkImg/setPx/lerpc/aaDisk/jpegRoundtrip/rasterOf/classifyStats` helpers and assertions for: solid single (0% trans), solid multi hard edges (0%), AA disk on white and on transparent (0%), 1px/2px AA ramps (0%), blurred edge (0%), JPEG q60 flat (0%), JPEG q55 ringing (0%), linear gradient (>85%), diagonal (>90%), gray (>85%), multi-stop (>85%), radial (>70%), steep 16px band (partial, >40% of band), alpha fades white/color (>85%), sub-coverage 10×10 gradient (0% after guard), mixed scene (gradient band >85%, false-transition in solid zones <1%), tiny 3×3 and 1×1 images (all solid, no errors), invalid params rejected. Exact code is in the repository test file after implementation; tolerances per prototype measurements ±5pp.
- [ ] **Step 2: Run to verify failure** — `php imgcolor/tests/cli_separation.php` → fatal: call to undefined method `classify()`.
- [ ] **Step 3: Implement `classify()`/`normalizeParams()`/`renderMaskPng()`** per the algorithm above (full implementation, see spec §4 for stage semantics and defaults table).
- [ ] **Step 4: Run to verify pass** — all sections PASS.
- [ ] **Step 5: Commit** — `feat(imgcolor): add three-way transition classifier`

---

### Task 4: `imgcolor_MaskedRaster` + solid-path byte parity

**Files:**
- Create: `imgcolor/MaskedRaster.class.php`
- Modify: `imgcolor/tests/cli_separation.php` (append section 4)

**Interfaces:**
- Consumes: `imgcolor_TransitionClassifier::CLS_TRANS`, library `Raster`/`BoundingBox`/`ColorRGBA`/`NotImplementedException`.
- Produces: `new imgcolor_MaskedRaster(Raster $raster, string $mask, string $skipByte = imgcolor_TransitionClassifier::CLS_TRANS)`; `pixels()` yields only non-skipped pixels (so `ColorHistogram` totals = solid+AA count); `width/height/hasAlpha/pixelAt` delegate; `crop()` throws `NotImplementedException`. Constructor throws `InvalidArgumentException` when `strlen($mask) !== width*height`.

```php
<?php


/**
 * Raster декоратор, който скрива класифицираните като преливка пиксели от
 * pixels() итерацията, за да ги изключи от съществуващото клъстеризиране
 * (ColorHistogram консумира само pixels()). Използва се единствено когато
 * има намерени преливки - иначе оригиналният растер се подава директно и
 * пътят на плътните цветове е байт-идентичен с досегашния.
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.4
 */
class imgcolor_MaskedRaster implements \ImageColorAnalyzer\Contracts\Raster
{
    private $raster;
    private $mask;
    private $skipByte;


    /**
     * @param \ImageColorAnalyzer\Contracts\Raster $raster
     * @param string                               $mask     байтова маска W*H (imgcolor_TransitionClassifier)
     * @param string                               $skipByte кой клас да се скрие
     */
    public function __construct(\ImageColorAnalyzer\Contracts\Raster $raster, $mask, $skipByte = imgcolor_TransitionClassifier::CLS_TRANS)
    {
        if (strlen($mask) !== $raster->width() * $raster->height()) {
            throw new InvalidArgumentException('Mask length must equal raster width*height');
        }
        $this->raster = $raster;
        $this->mask = $mask;
        $this->skipByte = $skipByte;
    }


    public function width(): int
    {
        return $this->raster->width();
    }


    public function height(): int
    {
        return $this->raster->height();
    }


    public function hasAlpha(): bool
    {
        return $this->raster->hasAlpha();
    }


    /**
     * Достъп по координати - без маскиране; маската важи само за pixels().
     */
    public function pixelAt(int $x, int $y): \ImageColorAnalyzer\Contracts\ColorRGBA
    {
        return $this->raster->pixelAt($x, $y);
    }


    /**
     * @return iterable<\ImageColorAnalyzer\Contracts\ColorRGBA>
     */
    public function pixels(): iterable
    {
        $i = 0;
        foreach ($this->raster->pixels() as $pixel) {
            if ($this->mask[$i++] === $this->skipByte) {
                continue;
            }
            yield $pixel;
        }
    }


    public function crop(\ImageColorAnalyzer\Contracts\BoundingBox $box): \ImageColorAnalyzer\Contracts\Raster
    {
        throw new \ImageColorAnalyzer\Exception\NotImplementedException('imgcolor_MaskedRaster does not support cropping');
    }
}
```

Section 4 tests: (a) masked histogram total equals unmasked total minus masked count on a synthetic raster; (b) **byte parity** — for the solid-multi and AA-disk fixtures plus `tests/fixtures/sample.png`, JSON from the legacy library facade equals JSON from the (Task 5) separated path; deferred to section 5 where the orchestrator exists — section 4 asserts parity at the histogram level: `ColorHistogram::build()` on original raster vs on a `MaskedRaster` with an all-`CLS_SOLID` mask produces identical arrays (proves the decorator is transparent when nothing is skipped — though the orchestrator bypasses it anyway).

Steps: failing test → implement → pass → commit `feat(imgcolor): add mask-aware raster decorator`.

---

### Task 5: `imgcolor_Separation` orchestrator

**Files:**
- Create: `imgcolor/Separation.class.php`
- Modify: `imgcolor/tests/cli_separation.php` (append section 5 + performance probe)

**Interfaces:**
- Consumes: everything from Tasks 1–4; library `WhiteBackgroundCropper`, `KMeansClusterer`, `ColorHistogram`, `KSelector`, `PercentageCoverageCalculator`, `AnalyzerOptions`.
- Produces: `imgcolor_Separation::process(Raster $raster, \ImageColorAnalyzer\Options\AnalyzerOptions $options, array $transParams, imgcolor_CmykConverter $converter)` → `stdClass{colors:array, cmyk:?array, crop:\ImageColorAnalyzer\Contracts\CropResult, classification:stdClass}`. `$transParams['alphaThreshold']` is forced to `$options->cluster->alphaThreshold` so BACKGROUND matches the histogram skip rule.

```php
<?php


/**
 * bgERP-независим оркестратор на разделния анализ: изрязване (библиотечен
 * cropper) -> класификация на преливките -> клъстеризиране на плътните
 * цветове (непроменен библиотечен път; при липса на преливки оригиналният
 * растер се подава директно за байт-идентичен резултат) -> CMYK акумулация.
 *
 * Тества се директно с `php imgcolor/tests/cli_separation.php`.
 *
 * @category  bgerp
 * @package   imgcolor
 *
 * @license   GPL 3
 * @since     v 0.4
 */
class imgcolor_Separation
{
    /**
     * @param \ImageColorAnalyzer\Contracts\Raster        $raster     зареден (незакрит) растер
     * @param \ImageColorAnalyzer\Options\AnalyzerOptions $options
     * @param array                                       $transParams прагове за imgcolor_TransitionClassifier
     * @param imgcolor_CmykConverter                      $converter
     *
     * @return stdClass {colors, cmyk, crop, classification}
     */
    public static function process($raster, $options, array $transParams, imgcolor_CmykConverter $converter)
    {
        $libConverter = new \ImageColorAnalyzer\Color\ColorConverter();

        $cropper = new \ImageColorAnalyzer\WhiteBackgroundCropper\WhiteBackgroundCropper($libConverter);
        $crop = $cropper->crop($raster, $options->crop);

        // BACKGROUND дефиницията следва прага на клъстеризирането, за да
        // съвпада с досегашното изключване на прозрачни пиксели.
        $transParams['alphaThreshold'] = $options->cluster->alphaThreshold;
        $classification = imgcolor_TransitionClassifier::classify($crop->raster, $transParams);

        $clusterInput = $classification->transitionCount > 0
            ? new imgcolor_MaskedRaster($crop->raster, $classification->mask)
            : $crop->raster;

        $clusterer = new \ImageColorAnalyzer\ColorClusterer\KMeansClusterer(
            $libConverter,
            new \ImageColorAnalyzer\ColorClusterer\ColorHistogram(),
            new \ImageColorAnalyzer\ColorClusterer\KSelector($libConverter)
        );
        $clusters = $clusterer->cluster($clusterInput, $options->cluster);

        $colors = array();
        $coverage = new \ImageColorAnalyzer\CoverageCalculator\PercentageCoverageCalculator();
        foreach ($coverage->calculate($clusters) as $item) {
            $colors[] = $item->toArray();
        }

        $result = new stdClass();
        $result->colors = $colors;
        $result->cmyk = imgcolor_CmykAccumulator::accumulate($crop->raster, $classification, $converter);
        $result->crop = $crop;
        $result->classification = $classification;

        return $result;
    }
}
```

Section 5 tests: solid-only fixture and `fixtures/sample.png` — `json_encode($sep->colors, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_PRESERVE_ZERO_FRACTION)` byte-equals `AnalyzerFactory::createDefault()->analyzePathAsJson()` and `cmyk === null`; `fixtures/sample_transparent.png` parity; mixed fixture — solid list contains green/gray/blue solids but no gradient centroids (every reported color within ΔE 10 of an expected solid), `cmyk` non-null, composition sums 100.0, coverage 25–40%; gradient-only fixture — colors list may be small/empty but `cmyk` covers >85%; fully transparent → colors `[]`, cmyk null; determinism — two runs produce identical serialized results; performance probe on 1200×800 mixed (report seconds + peak MB, no hard assert).

Steps: failing test → implement → pass → commit `feat(imgcolor): add bgERP-free separation orchestrator`.

---

### Task 6: Config constants + `imgcolor_Analyzer::processSeparated()`

**Files:**
- Modify: `imgcolor/Setup.class.php` (constants, configDescription, version 0.3→0.4, checkConfig)
- Modify: `imgcolor/Analyzer.class.php` (add `getTransParams()`, `getCmykConfig()`, `processSeparated()`)

**Interfaces:**
- Consumes: `imgcolor_Separation::process()` (Task 5), `imgcolor_TransitionClassifier::normalizeParams()` (Task 3), `imgcolor_CmykConverter` (Task 1).
- Produces: `imgcolor_Analyzer::processSeparated($source, $options = null)` → `stdClass{json:string, cmykJson:?string, croppedImage:\ImageColorAnalyzer\Contracts\EncodedImage, boundingBox, wasCropped:bool}`; `imgcolor_Analyzer::getTransParams()` → array for the classifier; `imgcolor_Analyzer::getCmykConfig()` → array for the converter. New constants `IMGCOLOR_TRANS_SPAN=4`, `IMGCOLOR_TRANS_NOISE_DELTAE=1.0`, `IMGCOLOR_TRANS_COHERENCE_MIN=0.4`, `IMGCOLOR_TRANS_AA_RADIUS=3`, `IMGCOLOR_TRANS_MIN_SEED=20`, `IMGCOLOR_TRANS_EDGE_DELTAE=10.0`, `IMGCOLOR_TRANS_MIN_COVERAGE=0.005`, `IMGCOLOR_CMYK_ENGINE='auto'`, `IMGCOLOR_CMYK_ICC_RGB_PROFILE=''`, `IMGCOLOR_CMYK_ICC_CMYK_PROFILE=''` with configDescription captions (Bulgarian, groups «Преливки» and «CMYK»). `checkConfig()` additionally validates `normalizeParams(getTransParams())` and `new imgcolor_CmykConverter(getCmykConfig())`.

`processSeparated()` (full code): registerAutoload + requireGd; resolve options (`buildOptions()` default); build loader per `IMGCOLOR_LOADER`; `SourceResolver->resolve($source)`; `imgcolor_Separation::process(...)`; encode cropped PNG via `GdPngEncoder`; JSON with `JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_PRESERVE_ZERO_FRACTION` for colors (byte parity with library), `JSON_THROW_ON_ERROR|JSON_PRESERVE_ZERO_FRACTION` for cmyk; translate `ImageAnalyzerException`, `InvalidArgumentException`, `RuntimeException` through `raiseError()`.

Steps: implement → `php -l` both files → run full CLI suite → commit `feat(imgcolor): wire separated analysis into wrapper config and service`.

---

### Task 7: Persistence + UI (`imgcolor_Analyses`, `imgcolor_Demo`) + web tests

**Files:**
- Modify: `imgcolor/Analyses.class.php` (add `cmykJson` FLD after `colorsJson`; `createFromResult(..., $cmykJson = null)`; `renderRec()` passes it)
- Modify: `imgcolor/Demo.class.php` (`act_Analyze`/`act_AnalyzeColors` call `processSeparated()`; `renderColorsHtml($colorsJson, $croppedImageBytes = null, $cmykJson = null)` renders a «CMYK преливки» block: coverage line, four labeled bars C/M/Y/K with percentages and engine note; `renderResult()`/`persistResult()` pass `->cmykJson`)
- Create: `imgcolor/tests/Separation.class.php` (`imgcolor_tests_Separation extends unit_Class`, web runner): parity of `processSeparated()->json` with `analyzePathAsJson()` on `fixtures/sample.png`; persistence round-trip of `cmykJson` through `imgcolor_Analyses::createFromResult()`/`renderRec()` (output contains `CMYK` section for a gradient fixture, not for a solid one); `createFromResult()` without the new argument still works.

New field definition: `$this->FLD('cmykJson', 'text', 'caption=CMYK преливки (JSON),input=none');` — bgERP syncs added fields on package setup/migration automatically; version bump in Task 6 triggers it.

Steps: implement → `php -l` all touched files → run CLI suite (unchanged green) → commit `feat(imgcolor): persist and render CMYK transition result`.

---

### Task 8: Documentation + full validation

**Files:**
- Modify: `imgcolor/docs/integration.md` (new sections: separated analysis entry point, cmykJson schema, config table for `IMGCOLOR_TRANS_*`/`IMGCOLOR_CMYK_*` with units/defaults/effects, ICC profile installation note — obtain e.g. ECI ISOcoated_v2_300 separately, semantics change note for gradient images, test commands)
- Modify: `imgcolor/tests/cli_separation.php` if validation finds gaps

Steps: update docs → run all six CLI tests → record results, performance numbers, and untested paths (Imagick ICC, web unit tests) → commit `docs(imgcolor): document CMYK separation integration`.

## Self-Review Notes

- Spec coverage: §3–§4 → Task 3; §5 alpha/§6 rules → Tasks 3+2; §7 → Task 1; §8 → Task 2; §9 → Task 6; §10 components → Tasks 1–6; §11 perf probe → Task 5; §12 tests → Tasks 1–5, 7; §13 docs → Task 8. No gaps.
- Type consistency: `classification` stdClass fields (`mask/analyzedCount/transitionCount`) used identically in Tasks 2, 3, 5; converter cfg keys (`engine/rgbProfile/cmykProfile`) identical in Tasks 1 and 6.
- Task 3 Step 1/3 carry the algorithm by reference to spec §4 (stages, defaults, data structures) rather than inline code — the spec table is the single source of truth for thresholds; all other tasks carry complete code.
