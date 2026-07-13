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
