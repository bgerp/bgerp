<?php


/**
 * Standalone regression for exact calibration snapshots in analysis history.
 *
 * Run: php imgcolor/tests/cli_analysis_snapshot.php
 */

class core_Manager
{
    public static $savedRec;


    public static function save($rec)
    {
        $rec->id = 1;
        self::$savedRec = clone $rec;

        return $rec->id;
    }
}


function fail($message)
{
    fwrite(STDERR, "FAIL: {$message}\n");
    exit(1);
}


require_once dirname(__DIR__) . '/Calibration.class.php';
require_once dirname(__DIR__) . '/Analyses.class.php';

$calibration = array(
    'cropLightnessMin' => 90.0,
    'cropChromaMax' => 4.0,
    'cropLineContentFraction' => 0.003,
    'cropAlphaThreshold' => 10,
    'clusterFixedK' => null,
    'clusterKMax' => 6,
    'clusterHistogramBits' => 4,
    'clusterMergeDeltaE' => 2.5,
    'clusterMinCoverage' => 0.02,
    'clusterSeed' => 7,
    'clusterAlphaThreshold' => 12,
);

imgcolor_Analyses::createFromResult('sourceFh', 42, '[]', null, $calibration + array('ignored' => true));
$saved = core_Manager::$savedRec;

if (!isset($saved->calibrationJson)) {
    fail('analysis did not persist a calibration snapshot');
}

$decoded = json_decode($saved->calibrationJson, true);
if ($decoded !== $calibration) {
    fail('snapshot did not preserve exactly the authoritative calibration values');
}

if ($saved->profileId !== 42) {
    fail('selected profile id was not preserved independently');
}

imgcolor_Analyses::createFromResult('legacyFh', null, '[]');
$legacy = core_Manager::$savedRec;
if (isset($legacy->calibrationJson) && $legacy->calibrationJson !== null) {
    fail('legacy-compatible call should leave the calibration snapshot empty');
}
if (isset($legacy->cmykJson) && $legacy->cmykJson !== null) {
    fail('legacy-compatible call should leave the CMYK payload empty');
}

imgcolor_Analyses::createFromResult('cmykFh', null, '[]', null, null, '{"ink_total":1.5}');
$withCmyk = core_Manager::$savedRec;
if ($withCmyk->cmykJson !== '{"ink_total":1.5}') {
    fail('CMYK payload must be persisted verbatim');
}

echo "PASS: analysis history stores exact calibration snapshots\n";
