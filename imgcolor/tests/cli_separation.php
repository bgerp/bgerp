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
    array(array(255, 0, 0), array(0.0, 1.0, 1.0, 0.0)),
    array(array(0, 255, 0), array(1.0, 0.0, 1.0, 0.0)),
    array(array(0, 0, 255), array(1.0, 1.0, 0.0, 0.0)),
    array(array(0, 0, 0), array(0.0, 0.0, 0.0, 1.0)),
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

// pure red + pure cyan transitions, solid/background pixels excluded
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
