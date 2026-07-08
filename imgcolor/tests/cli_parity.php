<?php

// Standalone parity/smoke harness for the vendored image-color-analyzer.
// Proves the pristine library loads via the same PSR-4 mapping the BGERP
// wrapper uses, and produces a valid coverage result. Requires php-gd.
// Usage: php imgcolor/tests/cli_parity.php

$base = __DIR__ . '/../lib/image-color-analyzer/src/';

spl_autoload_register(function ($class) use ($base) {
    $prefix = 'ImageColorAnalyzer\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $rel = substr($class, strlen($prefix));
    $file = $base . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) {
        include_once $file;
    }
});

function fail($msg)
{
    fwrite(STDERR, "FAIL: {$msg}\n");
    exit(1);
}

if (!extension_loaded('gd')) {
    fail('ext-gd is not loaded in this CLI php');
}

if (!class_exists('ImageColorAnalyzer\\PublicAPI\\AnalyzerFactory')) {
    fail('PSR-4 autoload did not resolve AnalyzerFactory from lib/');
}

$analyzer = \ImageColorAnalyzer\PublicAPI\AnalyzerFactory::createDefault();

// 1) opaque fixture -> non-empty colors summing to ~100
$json = $analyzer->analyzePathAsJson(__DIR__ . '/fixtures/sample.png');
$colors = json_decode($json, true);
if (!is_array($colors) || $colors === []) {
    fail('sample.png produced no colors');
}

$sum = 0.0;
foreach ($colors as $c) {
    if (!isset($c['color'], $c['coverage_percent'])) {
        fail('color entry missing keys');
    }
    $sum += $c['coverage_percent'];
}

if (abs($sum - 100.0) > 0.11) {
    fail('coverage does not sum to ~100 (got ' . $sum . ')');
}

// 2) transparent-background fixture -> only opaque content is analyzed
$transparentBackground = json_decode($analyzer->analyzePathAsJson(__DIR__ . '/fixtures/sample_transparent.png'), true);
if (!is_array($transparentBackground) || count($transparentBackground) !== 1) {
    fail('transparent-background fixture should yield one content color');
}
if ($transparentBackground[0]['color'] !== '#BE1E8C' || abs($transparentBackground[0]['coverage_percent'] - 100.0) > 0.01) {
    fail('transparent-background fixture should ignore transparent pixels');
}

// 3) fully transparent generated image -> []
$transparentPath = tempnam(sys_get_temp_dir(), 'imgcolor-transparent-');
if ($transparentPath === false) {
    fail('unable to create temporary transparent fixture path');
}

$transparentImage = imagecreatetruecolor(4, 4);
if ($transparentImage === false) {
    fail('unable to create transparent fixture image');
}

imagealphablending($transparentImage, false);
imagesavealpha($transparentImage, true);
$transparentColor = imagecolorallocatealpha($transparentImage, 0, 0, 0, 127);
if ($transparentColor === false || !imagefilledrectangle($transparentImage, 0, 0, 3, 3, $transparentColor)) {
    fail('unable to initialize transparent fixture image');
}

if (!imagepng($transparentImage, $transparentPath)) {
    fail('unable to write transparent fixture image');
}

$empty = json_decode($analyzer->analyzePathAsJson($transparentPath), true);
unlink($transparentPath);
if ($empty !== []) {
    fail('fully transparent image should yield [] (got ' . json_encode($empty) . ')');
}

// 4) determinism -> identical bytes on a second run
if ($analyzer->analyzePathAsJson(__DIR__ . '/fixtures/sample.png') !== $json) {
    fail('non-deterministic output for identical input');
}

echo "PASS: " . count($colors) . " colors, sum=" . round($sum, 1) . "\n";
exit(0);
