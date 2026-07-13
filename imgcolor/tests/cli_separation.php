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

// ---------------------------------------------------------------------------
// 3) Transition classifier: fixture battery (ported from the calibration
//    prototype; expected rates from docs/superpowers/specs/...-design.md)
// ---------------------------------------------------------------------------

function mkImg($w, $h, $bg = array(255, 255, 255))
{
    $im = imagecreatetruecolor($w, $h);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    $c = $bg === null
        ? imagecolorallocatealpha($im, 0, 0, 0, 127)
        : imagecolorallocatealpha($im, $bg[0], $bg[1], $bg[2], 0);
    imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $c);

    return $im;
}

function setPx($im, $x, $y, $r, $g, $b, $a255 = 255)
{
    $ga = 127 - (int) round($a255 * 127 / 255);
    imagesetpixel($im, $x, $y, imagecolorallocatealpha($im, $r, $g, $b, $ga));
}

function lerpC(array $c1, array $c2, $t)
{
    return array(
        (int) round($c1[0] + ($c2[0] - $c1[0]) * $t),
        (int) round($c1[1] + ($c2[1] - $c1[1]) * $t),
        (int) round($c1[2] + ($c2[2] - $c1[2]) * $t),
    );
}

function aaDisk($size, array $fg, $bg)
{
    $ss = 4;
    $big = mkImg($size * $ss, $size * $ss, $bg);
    imagefilledellipse($big, (int) ($size * $ss / 2), (int) ($size * $ss / 2), (int) ($size * $ss * 0.75), (int) ($size * $ss * 0.75), imagecolorallocatealpha($big, $fg[0], $fg[1], $fg[2], 0));
    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagecopyresampled($out, $big, 0, 0, 0, 0, $size, $size, $size * $ss, $size * $ss);

    return $out;
}

function jpegRoundtrip($im, $q)
{
    ob_start();
    imagejpeg($im, null, $q);
    $out = imagecreatefromstring(ob_get_clean());
    imagealphablending($out, false);
    imagesavealpha($out, true);

    return $out;
}

/** @return stdClass classification for a GD image with default (or overridden) params */
function classifyImg($im, array $overrides = array())
{
    $raster = new \ImageColorAnalyzer\ImageLoader\GdRaster($im);

    return imgcolor_TransitionClassifier::classify($raster, $overrides);
}

/** transition share of analyzed pixels, percent */
function transShare($cls)
{
    return $cls->analyzedCount ? 100 * $cls->transitionCount / $cls->analyzedCount : 0.0;
}

/** transition share within a pixel rectangle, percent */
function transShareRect($cls, $x1, $y1, $x2, $y2)
{
    $n = 0;
    $t = 0;
    for ($y = $y1; $y <= $y2; $y++) {
        for ($x = $x1; $x <= $x2; $x++) {
            $b = $cls->mask[$y * $cls->width + $x];
            if ($b === imgcolor_TransitionClassifier::CLS_BG) {
                continue;
            }
            $n++;
            if ($b === imgcolor_TransitionClassifier::CLS_TRANS) {
                $t++;
            }
        }
    }

    return $n ? 100 * $t / $n : 0.0;
}

// -- solid / AA / noise fixtures: zero transitions expected ------------------

$im = mkImg(64, 64, array(190, 30, 140));
if (transShare(classifyImg($im)) != 0.0) {
    fail('solid_single must have no transitions');
}

$im = mkImg(96, 96, array(220, 40, 40));
imagefilledrectangle($im, 48, 0, 95, 95, imagecolorallocatealpha($im, 30, 60, 200, 0));
imagefilledrectangle($im, 0, 48, 47, 95, imagecolorallocatealpha($im, 30, 180, 90, 0));
if (transShare(classifyImg($im)) != 0.0) {
    fail('solid_multi (hard edges) must have no transitions');
}

if (transShare(classifyImg(aaDisk(96, array(200, 30, 30), array(255, 255, 255)))) != 0.0) {
    fail('aa disk on white must have no transitions');
}

$cls = classifyImg(aaDisk(96, array(200, 30, 30), null));
if (transShare($cls) != 0.0) {
    fail('aa disk on transparent must have no transitions');
}
if ($cls->analyzedCount >= 96 * 96) {
    fail('transparent background must not count as analyzed');
}

foreach (array(1, 2) as $aw) {
    $im = mkImg(96, 48, array(230, 30, 30));
    $blue = array(30, 60, 200);
    imagefilledrectangle($im, 48 + $aw, 0, 95, 47, imagecolorallocatealpha($im, $blue[0], $blue[1], $blue[2], 0));
    for ($i = 0; $i < $aw; $i++) {
        $c = lerpC(array(230, 30, 30), $blue, ($i + 1) / ($aw + 1));
        for ($y = 0; $y < 48; $y++) {
            setPx($im, 48 + $i, $y, $c[0], $c[1], $c[2]);
        }
    }
    if (transShare(classifyImg($im)) != 0.0) {
        fail("aa_edge_{$aw}px must have no transitions");
    }
}

$im = mkImg(96, 48, array(230, 30, 30));
imagefilledrectangle($im, 48, 0, 95, 47, imagecolorallocatealpha($im, 30, 60, 200, 0));
for ($pass = 0; $pass < 3; $pass++) {
    imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);
}
if (transShare(classifyImg($im)) != 0.0) {
    fail('blurred hard edge must not read as a design gradient');
}

$im = mkImg(128, 96, array(235, 235, 235));
imagefilledrectangle($im, 20, 20, 107, 75, imagecolorallocatealpha($im, 200, 30, 30, 0));
imagefilledellipse($im, 64, 48, 40, 30, imagecolorallocatealpha($im, 30, 60, 200, 0));
approx(transShare(classifyImg(jpegRoundtrip($im, 60))), 0.0, 0.6, 'jpeg q60 flat artwork false transitions');

$im = mkImg(128, 96, array(255, 255, 255));
imagefilledellipse($im, 64, 48, 70, 50, imagecolorallocatealpha($im, 10, 10, 10, 0));
approx(transShare(classifyImg(jpegRoundtrip($im, 55))), 0.0, 0.6, 'jpeg q55 ringing false transitions');

$im = mkImg(200, 200, array(240, 240, 240));
for ($x = 0; $x < 10; $x++) {
    $c = lerpC(array(255, 0, 0), array(0, 0, 255), $x / 9);
    for ($y = 0; $y < 10; $y++) {
        setPx($im, 95 + $x, 95 + $y, $c[0], $c[1], $c[2]);
    }
}
if (transShare(classifyImg($im)) != 0.0) {
    fail('sub-coverage gradient must be folded by the guard');
}

// -- gradient fixtures: high recall expected ---------------------------------

$im = mkImg(128, 64);
for ($x = 0; $x < 128; $x++) {
    $c = lerpC(array(200, 30, 30), array(30, 60, 200), $x / 127);
    for ($y = 0; $y < 64; $y++) {
        setPx($im, $x, $y, $c[0], $c[1], $c[2]);
    }
}
if (transShare(classifyImg($im)) < 85) {
    fail('linear gradient recall below 85%');
}

$im = mkImg(96, 96);
for ($y = 0; $y < 96; $y++) {
    for ($x = 0; $x < 96; $x++) {
        $t = ($x + $y) / 190;
        $c = lerpC(array(10, 90, 170), array(240, 240, 240), $t);
        setPx($im, $x, $y, $c[0], $c[1], $c[2]);
    }
}
if (transShare(classifyImg($im)) < 90) {
    fail('diagonal gradient recall below 90%');
}

$im = mkImg(128, 48);
for ($x = 0; $x < 128; $x++) {
    $v = (int) round(255 * $x / 127);
    for ($y = 0; $y < 48; $y++) {
        setPx($im, $x, $y, $v, $v, $v);
    }
}
if (transShare(classifyImg($im)) < 85) {
    fail('grayscale gradient recall below 85%');
}

$im = mkImg(200, 40);
$stops = array(array(228, 3, 3), array(255, 140, 0), array(255, 237, 0), array(0, 128, 38), array(36, 64, 142));
for ($x = 0; $x < 200; $x++) {
    $t = $x / 199 * 4;
    $i = min(3, (int) $t);
    $c = lerpC($stops[$i], $stops[$i + 1], $t - $i);
    for ($y = 0; $y < 40; $y++) {
        setPx($im, $x, $y, $c[0], $c[1], $c[2]);
    }
}
if (transShare(classifyImg($im)) < 85) {
    fail('multi-stop gradient recall below 85%');
}

$im = mkImg(96, 96);
for ($y = 0; $y < 96; $y++) {
    for ($x = 0; $x < 96; $x++) {
        $t = min(1.0, hypot($x - 48, $y - 48) / 48);
        $c = lerpC(array(250, 220, 40), array(140, 20, 120), $t);
        setPx($im, $x, $y, $c[0], $c[1], $c[2]);
    }
}
if (transShare(classifyImg($im)) < 70) {
    fail('radial gradient recall below 70%');
}

$im = mkImg(96, 48, array(128, 128, 128));
for ($x = 40; $x < 56; $x++) {
    $c = lerpC(array(0, 0, 0), array(255, 255, 255), ($x - 40) / 15);
    for ($y = 0; $y < 48; $y++) {
        setPx($im, $x, $y, $c[0], $c[1], $c[2]);
    }
}
$cls = classifyImg($im);
if (transShareRect($cls, 40, 0, 55, 47) < 40) {
    fail('steep 16px ramp core must be at least 40% transition');
}
if (transShareRect($cls, 0, 0, 33, 47) > 1 || transShareRect($cls, 62, 0, 95, 47) > 1) {
    fail('steep ramp must not leak transitions into the surrounding solid');
}

$im = mkImg(128, 48);
for ($x = 0; $x < 128; $x++) {
    $c = lerpC(array(255, 255, 255), array(200, 30, 30), $x / 127);
    for ($y = 0; $y < 48; $y++) {
        setPx($im, $x, $y, $c[0], $c[1], $c[2]);
    }
}
if (transShare(classifyImg($im)) < 85) {
    fail('white-containing gradient recall below 85%');
}

foreach (array(array(255, 255, 255), array(200, 30, 140)) as $fadeColor) {
    $im = mkImg(128, 48, null);
    for ($x = 0; $x < 128; $x++) {
        $a = (int) round(255 * (1 - $x / 127));
        for ($y = 0; $y < 48; $y++) {
            setPx($im, $x, $y, $fadeColor[0], $fadeColor[1], $fadeColor[2], $a);
        }
    }
    if (transShare(classifyImg($im)) < 85) {
        fail('alpha fade recall below 85%');
    }
}

$im = mkImg(96, 96);
for ($y = 0; $y < 96; $y++) {
    for ($x = 0; $x < 96; $x++) {
        $v = (sin($x / 7.3) + cos($y / 5.1) + sin(($x + $y) / 11.7)) / 3;
        $c = lerpC(array(60, 80, 60), array(200, 220, 180), ($v + 1) / 2);
        setPx($im, $x, $y, $c[0], $c[1], $c[2]);
    }
}
if (transShare(classifyImg($im)) < 80) {
    fail('continuous-tone texture must classify as transition content');
}

// -- mixed scene --------------------------------------------------------------

$im = mkImg(192, 96, array(235, 235, 235));
imagefilledrectangle($im, 0, 0, 63, 95, imagecolorallocatealpha($im, 30, 120, 60, 0));
for ($x = 64; $x < 128; $x++) {
    $c = lerpC(array(250, 210, 40), array(210, 40, 120), ($x - 64) / 63);
    for ($y = 0; $y < 96; $y++) {
        setPx($im, $x, $y, $c[0], $c[1], $c[2]);
    }
}
$disk = aaDisk(48, array(30, 60, 200), null);
imagecopy($im, $disk, 138, 24, 0, 0, 48, 48);
$cls = classifyImg($im);
if (transShareRect($cls, 66, 0, 125, 95) < 85) {
    fail('mixed: gradient band recall below 85%');
}
if (transShareRect($cls, 0, 0, 63, 95) > 1 || transShareRect($cls, 128, 0, 191, 95) > 1) {
    fail('mixed: solid zones must stay below 1% false transitions');
}

// -- degenerate sizes and parameter validation --------------------------------

foreach (array(array(1, 1), array(3, 3)) as $dim) {
    $im = mkImg($dim[0], $dim[1], array(10, 200, 30));
    $cls = classifyImg($im);
    if ($cls->transitionCount !== 0 || $cls->analyzedCount !== $dim[0] * $dim[1]) {
        fail("tiny {$dim[0]}x{$dim[1]} image must be all solid");
    }
}

try {
    imgcolor_TransitionClassifier::normalizeParams(array('span' => 0));
    fail('span=0 must be rejected');
} catch (InvalidArgumentException $e) {
    if (strpos($e->getMessage(), 'span') === false) {
        fail('span error message must name the field');
    }
}
try {
    imgcolor_TransitionClassifier::normalizeParams(array('minCoverage' => 1.0));
    fail('minCoverage=1.0 must be rejected');
} catch (InvalidArgumentException $e) {
    // expected
}

// -- debug mask render ---------------------------------------------------------

$png = imgcolor_TransitionClassifier::renderMaskPng($cls);
if (substr($png, 0, 4) !== "\x89PNG") {
    fail('renderMaskPng must return PNG bytes');
}

echo "PASS: cli_separation section 3 (classifier)\n";

// ---------------------------------------------------------------------------
// 4) Masked raster: hides only the skipped class from pixels()
// ---------------------------------------------------------------------------

require_once __DIR__ . '/../MaskedRaster.class.php';

$im = mkImg(8, 4, array(200, 30, 30));
setPx($im, 0, 0, 30, 60, 200);
setPx($im, 1, 0, 30, 60, 200);
$raster = new \ImageColorAnalyzer\ImageLoader\GdRaster($im);

// mask: two TRANS pixels at offsets 0 and 1, rest SOLID
$mask = str_repeat(imgcolor_TransitionClassifier::CLS_SOLID, 32);
$mask[0] = imgcolor_TransitionClassifier::CLS_TRANS[0];
$mask[1] = imgcolor_TransitionClassifier::CLS_TRANS[0];

$masked = new imgcolor_MaskedRaster($raster, $mask);
$histogram = new \ImageColorAnalyzer\ColorClusterer\ColorHistogram();
$full = $histogram->build($raster, 5, 8);
$part = $histogram->build($masked, 5, 8);
if ($full['total'] !== 32 || $part['total'] !== 30) {
    fail("masked histogram totals: expected 32/30, got {$full['total']}/{$part['total']}");
}
foreach ($part['colors'] as $c) {
    if ($c[2] > 150) {
        fail('masked-out blue pixels must not reach the histogram');
    }
}

// an all-solid mask is fully transparent to the histogram
$noop = new imgcolor_MaskedRaster($raster, str_repeat(imgcolor_TransitionClassifier::CLS_SOLID, 32));
if ($histogram->build($noop, 5, 8) !== $full) {
    fail('no-op mask must not change the histogram');
}

// dimensions delegate; wrong mask length rejected
if ($masked->width() !== 8 || $masked->height() !== 4) {
    fail('masked raster must delegate dimensions');
}
try {
    new imgcolor_MaskedRaster($raster, 'short');
    fail('mask length mismatch must be rejected');
} catch (InvalidArgumentException $e) {
    // expected
}

echo "PASS: cli_separation section 4 (masked raster)\n";
