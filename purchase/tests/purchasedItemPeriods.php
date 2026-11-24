<?php

// Date calculation regression. Run from CLI without connecting to a database.
if (PHP_SAPI !== 'cli') exit;
date_default_timezone_set('Europe/Sofia');
function expect($condition, $message) { if (!$condition) throw new RuntimeException($message); }
class frame2_driver_TableData {}
require dirname(__DIR__, 2) . '/core/DateTime.class.php';
class_alias('core_DateTime', 'dt');
require dirname(__DIR__) . '/reports/PurchasedItems.class.php';

class PurchasedItemsPeriodProbe extends purchase_reports_PurchasedItems
{
    public static function bounds($from, $seconds, $compare, $start)
    {
        return parent::getPeriodBounds((object) array('from' => $from, 'duration' => $seconds, 'compare' => $compare, 'compareStart' => $start));
    }
}

$month = dt::SECONDS_IN_MONTH;
$day = 86400;
$cases = array(
    array('2026-10-01', $month, 'previous', null, array('2026-10-31', '2026-09-01', '2026-09-30')),
    array('2026-09-01', $month, 'previous', null, array('2026-09-30', '2026-08-01', '2026-08-31')),
    array('2024-02-01', $month, 'year', null, array('2024-02-29', '2023-02-01', '2023-02-28')),
    array('2026-10-01', 12 * $month, 'no', null, array('2027-09-30', null, null)),
    array('2026-03-31', $month, 'previous', null, array('2026-04-29', '2026-02-28', '2026-03-27')),
    array('2026-03-27', 7 * $day, 'previous', null, array('2026-04-02', '2026-03-20', '2026-03-26')),
    array('2026-10-01', 31 * $day, 'previous', null, array('2026-10-31', '2026-08-31', '2026-09-30')),
    array('2026-10-01', $day, 'checked', '2026-09-01', array('2026-10-01', '2026-09-01', '2026-09-01')),
    array('2026-10-01', 2 * $month, 'checked', '2026-01-01', array('2026-11-30', '2026-01-01', '2026-02-28')),
    array('2026-02-01', $month + $day, 'no', null, array('2026-03-01', null, null)),
);
foreach ($cases as list($from, $seconds, $compare, $start, $expected)) {
    $actual = PurchasedItemsPeriodProbe::bounds($from, $seconds, $compare, $start);
    expect($actual === $expected, json_encode(array($from, $seconds, $expected, $actual)));
}
echo 'OK: ' . count($cases) . " purchase report periods\n";
