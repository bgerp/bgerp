<?php

// Standalone smoke test for imgcolor_Calibration - the framework-free
// mapping between calibration field values and the vendored library's
// AnalyzerOptions. Requires no bgERP bootstrap (mirrors cli_parity.php).
// Usage: php imgcolor/tests/cli_calibration.php

class imgcolor_Setup
{
    public static function get($key)
    {
        $values = array(
            'CROP_LIGHTNESS_MIN' => 95.0,
            'CROP_CHROMA_MAX' => 5.0,
            'CROP_LINE_CONTENT_FRACTION' => 0.002,
            'CROP_ALPHA_THRESHOLD' => 8,
            'CLUSTER_FIXED_K' => '',
            'CLUSTER_KMAX' => 8,
            'CLUSTER_HISTOGRAM_BITS' => 5,
            'CLUSTER_MERGE_DELTAE' => 3.0,
            'CLUSTER_MIN_COVERAGE' => 0.01,
            'CLUSTER_SEED' => 1,
            'CLUSTER_ALPHA_THRESHOLD' => 8,
        );

        return $values[$key];
    }
}

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

// 1) setup defaults are mapped to the authoritative eleven calibration fields
$defaults = imgcolor_Calibration::getDefaultValues();
if ($defaults !== defaultValues()) {
    fail('getDefaultValues did not return the expected setup mapping: ' . var_export($defaults, true));
}

// 2) records are reduced to calibration fields only, preserving optional fixedK
$record = (object) (defaultValues() + array('id' => 42, 'name' => 'Ignored'));
$record->clusterFixedK = null;
$recordValues = imgcolor_Calibration::getValues($record);
$expectedRecordValues = defaultValues();
$expectedRecordValues['clusterFixedK'] = null;
if ($recordValues !== $expectedRecordValues) {
    fail('getValues did not extract the exact calibration fields');
}
if (array_key_exists('id', $recordValues) || array_key_exists('name', $recordValues)) {
    fail('getValues leaked unrelated record fields');
}

// 3) array sources use the same extraction contract
$arrayValues = imgcolor_Calibration::getValues(defaultValues() + array('notes' => 'Ignored'));
if ($arrayValues !== defaultValues()) {
    fail('getValues did not extract calibration values from an array');
}

// 4) applying calibration changes only calibration fields on a target record
$target = (object) array('name' => 'Keep name', 'notes' => 'Keep notes');
imgcolor_Calibration::applyValues($target, $recordValues + array('name' => 'Replace name'));
if ($target->name !== 'Keep name' || $target->notes !== 'Keep notes') {
    fail('applyValues changed unrelated target fields');
}
if (imgcolor_Calibration::getValues($target) !== $recordValues) {
    fail('applyValues did not copy the exact calibration values');
}

// 5) defaults matching today's IMGCOLOR_* constants build valid options
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

// 6) clusterFixedK: '', null and 0 all normalize to automatic (null)
foreach (array('', null, 0, '0') as $empty) {
    $values = defaultValues();
    $values['clusterFixedK'] = $empty;
    $options = imgcolor_Calibration::buildOptions($values);
    if ($options->cluster->fixedK !== null) {
        fail('clusterFixedK ' . var_export($empty, true) . ' should normalize to null, got ' . var_export($options->cluster->fixedK, true));
    }
}

// 7) clusterFixedK: a real positive value passes through as int
$values = defaultValues();
$values['clusterFixedK'] = '4';
$options = imgcolor_Calibration::buildOptions($values);
if ($options->cluster->fixedK !== 4) {
    fail('clusterFixedK "4" should map to int 4, got ' . var_export($options->cluster->fixedK, true));
}

// 8) out-of-range crop value is rejected with a message naming the field
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

// 9) out-of-range cluster value is rejected with a message naming the field
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
