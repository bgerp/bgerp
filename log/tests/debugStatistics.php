<?php

/**
 * Самостоятелна проверка: php log/tests/debugStatistics.php
 *
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}

error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});

class core_BaseClass {}
require dirname(__DIR__) . '/DebugStatistics.class.php';

function checkDebugStats($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function debugStatsFor($names, $search = array())
{
    return log_DebugStatistics::summarize($names, log_DebugStatistics::prepareFilter($search));
}

$names = array(
    '200_00007_08_00_00_1001_100000.debug',
    '805|2_000-2_08_30_00_1002_300000.debug',
    '500|1_00007_09_00_00_1003_500001.debug',
    '000_09_15_00_1004.debug',
    '150_000-1_10_00_00_1005_0.debug',
    '404_00000_10_30_00_1006_100.debug',
    '0_00007_11_00_00_1007_99.debug',
    '302_00007_12_00_00_1008_999.debug',
    '220_00007_23_59_59_1009_300001.debug',
    '850_00007_00_00_00_1010_900000.debug',
    '500_00007_24_00_00_1011_10.debug',
    'unknown.debug', 'unrelated.txt', '.', '..',
);
$stats = debugStatsFor($names);
checkDebugStats($stats['totals'] === array('total' => 10, 'success' => 4, 'errors' => 3, 'pending' => 1, 'observations' => 1, 'other' => 1), 'Status groups, including 0 versus 000');
checkDebugStats($stats['ajax'] === 2 && $stats['ignored'] === 2, 'AJAX and malformed names');
checkDebugStats(array_values($stats['durations']) === array(1, 1, 1, 1, 6), 'Execution categories and missing duration');
checkDebugStats($stats['intervals'][8]['size'] === array('count' => 2, 'sum' => 400000, 'max' => 300000), 'Sizes from names accumulate per interval');
checkDebugStats($stats['intervals'][9]['size']['count'] === 1 && $stats['intervals'][9]['size']['sum'] === 500001, 'Initial names without size excluded from size average');
checkDebugStats($stats['intervals'][10]['size']['count'] === 2 && $stats['intervals'][10]['size']['sum'] === 100, 'Zero size is a known size');
$durationBreakdown = log_DebugStatistics::getDurationBreakdown($stats['totals'] + array('durations' => $stats['durations']));
checkDebugStats($durationBreakdown['errors'] === 3 && $durationBreakdown['pending'] === 1 && $durationBreakdown['unknown'] === 2 && array_sum($durationBreakdown) === 10, 'Duration chart separates errors, unfinished and remaining unknown records without double counting');
$pendingStats = debugStatsFor($names, array('status' => '000'));
$pendingMetrics = log_DebugStatistics::getIntervalMetrics($pendingStats['intervals'][9]);
checkDebugStats($pendingStats['totals']['errors'] === 0 && $pendingMetrics['errorPercent'] == 0 && $pendingMetrics['fastPercent'] === null, 'Unfinished records are neither errors nor known-duration requests');
checkDebugStats(count($stats['intervals']) === 24 && $stats['intervals'][7]['total'] === 0 && abs($stats['average'] - 10 / 24) < 0.000001, 'Empty hours contribute to the average');
foreach (array('2xx' => 2, '8xx' => 2, '000' => 1, '500|505|510|0' => 2, '404' => 1, '150' => 1) as $status => $count) {
    checkDebugStats(debugStatsFor($names, array('status' => $status))['totals']['total'] === $count, 'Status filter: ' . $status);
}
foreach (array('7' => 6, '-2' => 1, '-1' => 1, '0' => 1, 'registered' => 6, 'service' => 2, 'withoutSystem' => 9, 'withoutSystemAndAnonymous' => 7) as $user => $count) {
    checkDebugStats(debugStatsFor($names, array('user' => $user))['totals']['total'] === $count, 'User filter: ' . $user);
}
checkDebugStats(debugStatsFor($names, array('user' => PHP_INT_MAX))['totals']['total'] === 10, 'All users');
foreach (array('small' => 5, 'big' => 2, 'veryBig' => 4, 'veryBig5' => 2) as $size => $count) {
    checkDebugStats(debugStatsFor($names, array('execSize' => $size))['totals']['total'] === $count, 'Size bounds and missing size: ' . $size);
}
foreach (array('fast', 'slow', 'verySlow', 'overTime') as $duration) {
    checkDebugStats(debugStatsFor($names, array('execTime' => $duration))['totals']['total'] === 1, 'Duration filter: ' . $duration);
}
$range = debugStatsFor($names, array('execTimeFrom' => '9', 'execTimeTo' => '08:30'));
checkDebugStats($range['totals']['total'] === 2 && array_keys($range['intervals']) === array(8, 9) && $range['average'] == 1, 'Reversed range and inclusive bounds');
checkDebugStats(debugStatsFor($names, array('execTimeTo' => '00:00:00'))['totals']['total'] === 1, 'Midnight upper bound');
checkDebugStats(debugStatsFor($names, array('execTimeFrom' => '23:59:59'))['totals']['total'] === 1, 'Last second and missing upper bound');
checkDebugStats(debugStatsFor($names, array('search' => '|2', 'user' => -2, 'execTime' => 'slow', 'execSize' => 'big', 'status' => '8xx', 'execTimeFrom' => '08', 'execTimeTo' => '09'))['totals']['total'] === 1, 'Combined filters');
checkDebugStats(debugStatsFor($names, array('search' => 'missing'))['totals']['total'] === 0, 'Empty result');
checkDebugStats(debugStatsFor(array())['average'] == 0, 'Empty directory');
checkDebugStats(debugStatsFor(array('10001_00007_08_00_00_1012_100.debug', '-1_00007_08_00_00_1013_100.debug'))['totals']['other'] === 2, 'Other numeric exception codes');
checkDebugStats(debugStatsFor($names, array('status' => '.*'))['totals']['total'] === 0, 'Status is not arbitrary regular expression');
foreach (array('24:00', '12:60', '12:00:60', 'invalid') as $value) {
    checkDebugStats(log_DebugStatistics::parseTime($value) === false, 'Invalid time: ' . $value);
}
$invalidRejected = false;
try {
    log_DebugStatistics::prepareFilter(array('execTimeFrom' => 'invalid'));
} catch (InvalidArgumentException $e) {
    $invalidRejected = true;
}
checkDebugStats($invalidRejected, 'Invalid range must not silently broaden selection');
$excluded = log_DebugStatistics::summarize($names, log_DebugStatistics::prepareFilter(array()), $names[3]);
checkDebugStats($excluded['totals']['pending'] === 0, 'Exclude the statistics request itself');

$metrics = log_DebugStatistics::getIntervalMetrics($stats['intervals'][8]);
checkDebugStats($metrics['fastPercent'] == 50 && $metrics['errorPercent'] == 0 && $metrics['sizeAverage'] == 200000, 'Shared chart and export calculations');
checkDebugStats(log_DebugStatistics::getIntervalMetrics($stats['intervals'][7]) === array('fastPercent' => null, 'errorPercent' => null, 'sizeAverage' => null), 'Missing interval metrics are null, not zero');
$export = log_DebugStatistics::exportText($stats, log_DebugStatistics::prepareFilter(array()), array('search' => "quoted\nfilter"));
checkDebugStats(strpos($export, 'quoted\\nfilter') !== false && strpos($export, 'точна средна продължителност') !== false, 'Export keeps filter values quoted and explains duration limits');
checkDebugStats(strpos($export, '"user":"all"') !== false && strpos($export, '"execTimeTo":"23:59:59"') !== false, 'Export describes effective all-user and full-day filters');
checkDebugStats(strpos($export, 'Недовършени (000): 1') !== false && strpos($export, 'other_no_duration') !== false, 'Export labels unfinished records and explains the duration breakdown');
preg_match('/```tsv\n(.*?)```/s', $export, $table);
$exportRows = explode("\n", trim($table[1] ?? ''));
checkDebugStats(count($exportRows) === 25 && count(explode("\t", $exportRows[0])) === 18, 'Export includes a header and all 24 intervals');
$exportHour = explode("\t", $exportRows[9]);
checkDebugStats($exportHour[0] === '08:00' && $exportHour[12] === '50' && $exportHour[14] === '2' && $exportHour[15] === '400000' && $exportHour[16] === '200000', 'Exported percentages, counts and exact byte sizes');
$exportMixed = explode("\t", $exportRows[10]);
checkDebugStats($exportMixed[0] === '09:00' && $exportMixed[3] === '1' && $exportMixed[4] === '1' && $exportMixed[11] === '0', 'Export keeps errors and unfinished records in separate columns without duplicating them as other unknown durations');
checkDebugStats(strpos($exportRows[8], "NA\tNA\t0\t0\tNA\tNA") !== false, 'Export distinguishes no observations from zero');

foreach (array(1, 5, 30, 60) as $interval) {
    $grouped = debugStatsFor($names, array('statInterval' => $interval));
    checkDebugStats(count($grouped['intervals']) === 1440 / $interval, 'Intervals fill the whole day: ' . $interval);
    checkDebugStats($grouped['totals'] === $stats['totals'] && $grouped['durations'] === $stats['durations'], 'Grouping preserves all totals');
    checkDebugStats(abs($grouped['average'] - 10 / (1440 / $interval)) < 0.000001, 'Average per selected interval');
    foreach ($grouped['intervals'] as $values) {
        checkDebugStats(array_sum($values['durations']) === $values['total'], 'Known and unknown durations cover each interval');
        $breakdown = log_DebugStatistics::getDurationBreakdown($values);
        checkDebugStats(array_sum($breakdown) === $values['total'] && min($breakdown) >= 0, 'Separated duration chart preserves every interval, including empty intervals');
    }
    $sizes = array_column($grouped['intervals'], 'size');
    checkDebugStats(array_sum(array_column($sizes, 'count')) === 9 && array_sum(array_column($sizes, 'sum')) === 2101200, 'Size statistics preserved at every resolution');
}
$boundaryNames = array(
    '200_00007_08_04_59_2001_100.debug',
    '205_00007_08_05_00_2002_100.debug',
    '820|1_00007_08_09_59_2003_100.debug',
    '250_00007_08_10_00_2004_100.debug',
    '500_00007_08_10_01_2005_100.debug',
    '201_00007_08_10_02_2006_100.debug',
);
$grouped = debugStatsFor($boundaryNames, array('statInterval' => 5, 'execTimeFrom' => '08:04:59', 'execTimeTo' => '08:10:01'));
checkDebugStats(array_keys($grouped['intervals']) === array(96, 97, 98), 'Partial intervals align to the clock');
checkDebugStats(array_column($grouped['intervals'], 'total') === array(1, 2, 2), 'Exact five-minute boundaries');
checkDebugStats($grouped['intervals'][97]['durations']['05'] === 1 && $grouped['intervals'][97]['durations'][20] === 1, 'Regular and AJAX duration categories per interval');
checkDebugStats($grouped['intervals'][98]['durations'][50] === 1 && $grouped['intervals'][98]['durations']['unknown'] === 1, 'Errors are not treated as fast requests');
checkDebugStats(debugStatsFor($boundaryNames)['durations']['unknown'] === 2, 'Unknown successful codes have no duration');
$lastMinute = debugStatsFor($names, array('statInterval' => 1, 'execTimeFrom' => '23:59:59'));
checkDebugStats(array_keys($lastMinute['intervals']) === array(1439) && $lastMinute['intervals'][1439]['durations'][20] === 1, 'Last minute of the day');
foreach (array(0, 2, 15, 61, '5foo', '') as $interval) {
    $invalidRejected = false;
    try {
        log_DebugStatistics::prepareFilter(array('statInterval' => $interval));
    } catch (InvalidArgumentException $e) {
        $invalidRejected = true;
    }
    checkDebugStats($invalidRejected, 'Unsupported interval rejected');
}

/**
 * Виртуална директория, която забранява четене на съдържание и метаданни.
 */
class DebugStatisticsNamesStream
{
    public $context;
    public static $closed = false;
    private $position = 0;

    public function dir_opendir($path, $options) { return true; }
    public function dir_readdir()
    {
        if ($this->position === 300000) return false;
        $i = $this->position++;
        $codes = array('200', '500', '150');

        return sprintf('%s_00007_%02d_%02d_00_%d_100.debug', $codes[$i % 3], $i % 24, (int) floor($i / 24) % 60, $i);
    }
    public function dir_closedir() { self::$closed = true; return true; }
    public function stream_open($path, $mode, $options, &$openedPath) { throw new RuntimeException('File content read'); }
    public function url_stat($path, $flags) { throw new RuntimeException('File metadata read'); }
}

stream_wrapper_register('debugnames', 'DebugStatisticsNamesStream');
$memory = memory_get_usage(true);
$start = microtime(true);
$large = log_DebugStatistics::scan('debugnames://fixture', log_DebugStatistics::prepareFilter(array('statInterval' => 1)));
$elapsed = microtime(true) - $start;
checkDebugStats($large['totals']['total'] === 300000 && $large['totals']['success'] === 100000 && $large['totals']['errors'] === 100000 && count($large['intervals']) === 1440 && abs($large['average'] - 300000 / 1440) < 0.000001, '300000 streamed names at one-minute resolution');
checkDebugStats(DebugStatisticsNamesStream::$closed, 'Directory handle closed');
checkDebugStats(memory_get_peak_usage(true) - $memory < 8 * 1024 * 1024, 'Bounded aggregation memory');
checkDebugStats(substr_count(log_DebugStatistics::exportText($large, log_DebugStatistics::prepareFilter(array('statInterval' => 1)), array('statInterval' => 1)), "\n") > 1440, 'Full one-minute export remains bounded by interval count');
stream_wrapper_unregister('debugnames');
echo 'OK: filters, boundaries, categories, sizes, LLM export and 300000 names without content or stat; ', round($elapsed, 3), ' s; peak ', memory_get_peak_usage(true), " bytes.\n";
