<?php

// Standalone smoke test for imgcolor_Calibration - the framework-free
// mapping between calibration field values and the vendored library's
// AnalyzerOptions. Requires no bgERP bootstrap (mirrors cli_parity.php).
// Usage: php imgcolor/tests/cli_calibration.php

require_once __DIR__ . '/../Calibration.class.php';

function fail($msg)
{
    fwrite(STDERR, "FAIL: {$msg}\n");
    exit(1);
}

function defaultValues()
{
    return array(
        'cropLightnessMin' => 95.0,
        'cropChromaMax' => 5.0,
        'cropLineContentFraction' => 0.002,
        'cropAlphaThreshold' => 8,
        'clusterFixedK' => '',
        'clusterKMax' => 8,
        'clusterHistogramBits' => 5,
        'clusterMergeDeltaE' => 3.0,
        'clusterMinCoverage' => 0.01,
        'clusterSeed' => 1,
        'clusterAlphaThreshold' => 8,
    );
}

// 1) defaults matching today's IMGCOLOR_* constants build valid options
$options = imgcolor_Calibration::buildOptions(defaultValues());
if (!($options instanceof \ImageColorAnalyzer\Options\AnalyzerOptions)) {
    fail('buildOptions did not return an AnalyzerOptions instance');
}
if ($options->crop->lightnessMin !== 95.0) {
    fail('crop->lightnessMin not mapped correctly, got ' . var_export($options->crop->lightnessMin, true));
}
if ($options->cluster->kMax !== 8) {
    fail('cluster->kMax not mapped correctly, got ' . var_export($options->cluster->kMax, true));
}

// 2) clusterFixedK: '', null and 0 all normalize to automatic (null)
foreach (array('', null, 0, '0') as $empty) {
    $values = defaultValues();
    $values['clusterFixedK'] = $empty;
    $options = imgcolor_Calibration::buildOptions($values);
    if ($options->cluster->fixedK !== null) {
        fail('clusterFixedK ' . var_export($empty, true) . ' should normalize to null, got ' . var_export($options->cluster->fixedK, true));
    }
}

// 3) clusterFixedK: a real positive value passes through as int
$values = defaultValues();
$values['clusterFixedK'] = '4';
$options = imgcolor_Calibration::buildOptions($values);
if ($options->cluster->fixedK !== 4) {
    fail('clusterFixedK "4" should map to int 4, got ' . var_export($options->cluster->fixedK, true));
}

// 4) out-of-range crop value is rejected with a message naming the field
$values = defaultValues();
$values['cropLightnessMin'] = 150.0;
try {
    imgcolor_Calibration::buildOptions($values);
    fail('cropLightnessMin=150 should have been rejected');
} catch (InvalidArgumentException $e) {
    if (strpos($e->getMessage(), 'lightnessMin') === false) {
        fail('exception message should mention lightnessMin, got: ' . $e->getMessage());
    }
}

// 5) out-of-range cluster value is rejected with a message naming the field
$values = defaultValues();
$values['clusterHistogramBits'] = 9;
try {
    imgcolor_Calibration::buildOptions($values);
    fail('clusterHistogramBits=9 should have been rejected');
} catch (InvalidArgumentException $e) {
    if (strpos($e->getMessage(), 'histogramBitsPerChannel') === false) {
        fail('exception message should mention histogramBitsPerChannel, got: ' . $e->getMessage());
    }
}

echo "PASS: imgcolor_Calibration mapping + validation\n";
exit(0);
