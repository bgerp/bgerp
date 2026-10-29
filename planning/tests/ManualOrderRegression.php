<?php

/**
 * Старите режими и общата подготовка се проверяват без приложение и база.
 * Изпълнение: php planning/tests/ManualOrderRegression.php
 */
require __DIR__ . '/TargetTimePlan.php';

class ht {
    public static function createLink($title, $url, $warning = false, $attr = array()) {
        return new class($title) {
            private $title;
            public function __construct($title) { $this->title = $title; }
            public function getContent() { return $this->title; }
        };
    }
}

function regressionContext($rows, $links = array(), $anchors = array(), $frames = null) {
    $context = planContext($rows, $links, $frames);
    foreach ($context->prepared->assets as $asset) {
        $asset->anchorLinks = $anchors;
        $asset->scheduleName = 'TEST';
    }
    foreach ($context->tasks as $row) $row->modifiedOn = '2026-10-07 08:00:00';
    return $context;
}

function regressionPlan($context, $options = array()) {
    $snapshot = serialize($context);
    $result = planning_TaskConstraints::calcPreparedTimes($context->tasks, $context->previous,
        $context->now, $context->prepared, $options);
    check(serialize($context) === $snapshot, 'Calculation never changes the source tasks, packages or calendar');
    return $result;
}

function chronologicalIds($result, $assetId = 1) {
    $rows = $result->tasks[$assetId] ?? array();
    uasort($rows, function ($a, $b) {
        return ($a->_startTimestamp ?? strtotime($a->expectedTimeStart ?? '')) <=> ($b->_startTimestamp ?? strtotime($b->expectedTimeStart ?? ''));
    });
    return array_keys($rows);
}

function safeMachinePlan($result, $message) {
    foreach ($result->tasks as $assetId => $rows) {
        $lastEnd = null;
        foreach (chronologicalIds($result, $assetId) as $id) {
            $row = $rows[$id];
            check(($row->expectedTimeStart ?? planning_TaskConstraints::NOT_FOUND_DATE) < planning_TaskConstraints::NOT_FOUND_DATE, $message . ': scheduled');
            $start = $row->_startTimestamp ?? strtotime($row->expectedTimeStart ?? '');
            $end = $row->_endTimestamp ?? (strtotime($row->expectedTimeEnd ?? '') + 1);
            check($lastEnd === null || $start >= $lastEnd, $message . ': no overlap or interruption');
            $lastEnd = $end;
        }
    }
}

function compareExistingEngine($context) {
    $prepared = clone $context->prepared;
    $prepared->assets = array_map(function ($asset) { return clone $asset; }, $prepared->assets);
    $intervals = array_map(function ($interval) { return clone $interval; }, $prepared->calendarIntervals);
    $tasks = array_map(function ($row) { return clone $row; }, $context->tasks);
    $engine = planning_Setup::get('MANUAL_ORDER_IN_ASSET') == 'yes' ? 'manualPlanning' : 'smartPlanningGraph';
    $method = new ReflectionMethod('planning_TaskConstraints', $engine);
    $method->setAccessible(true);
    $planned = $notPlanned = array();
    $args = array(&$planned, &$notPlanned, &$intervals, $prepared->assets, $tasks, $context->now, $context->previous);
    $method->invokeArgs(null, $args);
    $actual = regressionPlan($context);
    check(serialize($actual->tasks) === serialize($planned) && serialize($actual->notPlanned) === serialize($notPlanned),
        'The extracted preparation returns the same operations as the existing ' . $engine . ' engine');
}

$extraStart = $checks;
$rows = array(1 => task(1, 900), 2 => task(2, 1800), 3 => task(3, 2700), 4 => task(4, 3600));
$context = regressionContext($rows);
compareExistingEngine($context);
foreach (array(1, 2, 3, 4) as $a) foreach (array(1, 2, 3, 4) as $b) {
    if ($a == $b) continue;
    $rest = array_values(array_diff(array(1, 2, 3, 4), array($a, $b)));
    foreach (array($rest, array_reverse($rest)) as $tail) {
        $order = array_merge(array($a, $b), $tail);
        $result = regressionPlan($context, array('manualOrderOverrides' => array(1 => $order)));
        check(chronologicalIds($result) === $order, 'Every permutation of free operations keeps the submitted manual order');
        safeMachinePlan($result, 'Manual permutation');
    }
}

$context = regressionContext($rows, array(3 => 2));
compareExistingEngine($context);
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => array(4, 2, 3, 1))));
check(chronologicalIds($result) === array(4, 2, 3, 1) && $result->assets[1]->packageLinks === array(3 => 2), 'Moving an entire manual package preserves its internal order and link');
safeMachinePlan($result, 'Manual package');
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => array(2, 3, 4, 1)), 'packageLinkOverrides' => array(1 => array())));
check(empty($result->assets[1]->packageLinks) && isset($result->assets[1]->excludedAutoGroupTasks[3]), 'Explicit detachment is remembered and is not immediately regrouped');
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => array(4, 1, 2, 3)), 'packageLinkOverrides' => array(1 => array(2 => 1, 3 => 2))));
check($result->assets[1]->packageLinks === array(2 => 1, 3 => 2), 'Joining manual packages preserves the complete chain');

$context = regressionContext($rows, array(), array(3 => 2));
compareExistingEngine($context);
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => array(4, 2, 3, 1))));
check($result->assets[1]->anchorLinks === array(3 => 2) && empty($result->assets[1]->packageLinks), 'Anchor links remain anchors, not package links');
check(chronologicalIds($result) === array(4, 2, 3, 1), 'Anchor continuation survives whole-block reordering');

$context = regressionContext($rows);
$context->prepared->assets[1]->autoPackageLinks = $context->prepared->assets[1]->packageLinks = array(3 => 2);
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => array(4, 2, 3, 1))));
check($result->assets[1]->autoPackageLinks === array(3 => 2), 'Automatic link provenance survives manual reordering');
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => array(2, 3, 4, 1)), 'packageLinkOverrides' => array(1 => array())));
check(empty($result->assets[1]->autoPackageLinks) && isset($result->assets[1]->excludedAutoGroupTasks[3]), 'Removing an automatic link remembers the user exclusion');

$same = array(1 => task(1), 2 => task(2), 3 => task(3));
foreach ($same as $id => $row) {
    $row->_planningParamValues = array(7 => $id == 2 ? 'B' : 'A');
    $row->_planningParamSignature = md5(serialize($row->_planningParamValues));
}
$context = regressionContext($same);
$context->prepared->assets[1]->autoGroupVersion = 0;
$result = regressionPlan($context, array('regroupAssetIds' => array(1)));
check($result->assets[1]->packageLinks === array(3 => 1) && $result->assets[1]->autoPackageLinks === array(3 => 1), 'Initial parameter grouping creates a persistent automatic package');
check(!empty($result->assets[1]->_saveAutomaticPackages), 'Automatic package changes are returned for the existing save path');
$context->prepared->assets[1]->excludedAutoGroupTasks = array(3 => 3);
$result = regressionPlan($context, array('regroupAssetIds' => array(1)));
check(empty($result->assets[1]->packageLinks) && isset($result->assets[1]->excludedAutoGroupTasks[3]), 'Explicit user exclusion survives forced regrouping');
$context->prepared->assets[1]->excludedAutoGroupTasks = array();
$context->prepared->assets[1]->packageLinks = array(2 => 1);
$result = regressionPlan($context, array('regroupAssetIds' => array(1)));
check(($result->assets[1]->packageLinks[2] ?? null) === 1, 'Forced regrouping does not split a user package with different parameters');

$context = regressionContext(array(1 => task(1), 2 => task(2), 3 => task(3)));
$result = regressionPlan($context, array('committedTaskIdOverrides' => array(1 => 3)));
check(chronologicalIds($result)[0] === 3 && $result->assets[1]->committedTaskId === 3, 'Engaged next operation remains the first candidate');
$context->prepared->assets[1]->committedTaskId = 3;
$result = regressionPlan($context, array('committedTaskIdOverrides' => array(1 => null)));
check($result->assets[1]->committedTaskId === null && chronologicalIds($result)[0] === 1, 'Clearing the engagement restores the ordinary queue');

$active = task(1);
$active->state = 'active';
$active->actualStart = '2026-10-08 07:00:00';
$stopped = task(2);
$stopped->state = 'stopped';
$stopped->actualStart = '2026-10-08 07:00:00';
$context = regressionContext(array(1 => $active, 2 => $stopped, 3 => task(3)));
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => array(3, 2, 1))));
check(chronologicalIds($result) === array(1, 3, 2), 'Started operation stays protected while stopped operation remains movable');
safeMachinePlan($result, 'Started and stopped operations');

$previous = array(2 => array(1 => (object)array('intersect' => 'no', 'waitingTime' => 1800)));
$context = regressionContext(array(1 => task(1, 3600, null, 2), 2 => task(2), 3 => task(3)));
$context->previous = $previous;
$result = regressionPlan($context);
check($result->tasks[1][2]->expectedTimeStart >= dt::addSecs(1800, $result->tasks[2][1]->expectedTimeEnd), 'Cross-machine predecessor and technological waiting time remain mandatory');
safeMachinePlan($result, 'Cross-machine dependency');

$context = regressionContext(array(1 => task(1, 7200), 2 => task(2)), array(), array(),
    array(array('08:00:00', '08:59:59'), array('10:00:00', '10:59:59'), array('+1 day 08:00:00', '+1 day 16:59:59')));
$result = regressionPlan($context);
check($result->tasks[1][1]->expectedTimeStart === '2026-10-08 08:00:00' && $result->tasks[1][2]->expectedTimeStart === '2026-10-09 08:00:00', 'Breaks and the next working day are preserved without interruption by another operation');
safeMachinePlan($result, 'Calendar breaks');

$context = regressionContext(array(1 => task(1), 2 => task(2), 3 => task(3)));
planning_Setup::$values['MANUAL_ORDER_IN_ASSET'] = 'yes';
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => array(3, 1, 2))));
check(chronologicalIds($result) === array(3, 1, 2) && strpos($result->debug, 'РЪЧНО ПЛАНИРАНЕ') !== false, 'Legacy manual mode still uses the same queue and engine');
compareExistingEngine($context);
$context->tasks[2]->timeStart = '2026-10-08 12:00:00';
$result = regressionPlan($context);
check($result->tasks[1][2]->expectedTimeStart === '2026-10-08 12:00:00', 'Fixed starts remain exact even when legacy manual mode is configured');
compareExistingEngine($context);
planning_Setup::$values = array();
check(planning_TaskManualOrderPerAssets::getPackageLinks(null) === array()
    && planning_TaskManualOrderPerAssets::getAnchorLinks(0) === array(), 'No machine never produces a malformed package query');

$links = array(2 => 1, 4 => 3);
$context = regressionContext(array(1 => task(1), 2 => task(2, 3600, '2026-10-08 11:00:00'),
    3 => task(3, 7200), 4 => task(4), 5 => task(5, 3600, null, 2)), $links);
$context->previous = array(3 => array(5 => (object)array('intersect' => 'no', 'waitingTime' => 3600)));
$order = array(3, 4, 1, 2);
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => $order)));
check(chronologicalIds($result) === array(1, 2, 3, 4), 'Reproduce the safe but silently reordered package before a fixed member');
check($result->tasks[1][2]->expectedTimeStart === '2026-10-08 11:00:00'
    && $result->assets[1]->packageLinks === $links, 'Reproduction keeps fixed starts and both packages intact');
$conflicts = planning_TargetTimes::collectNewConflicts($result, $context->baseline);
$conflicts += planning_TaskConstraints::getManualOrderTimeConflicts(1, $order, $links, $context->tasks, $result);
check(!empty($conflicts), 'The impossible submitted package order must report a conflict instead of accepting the fallback');
$reason = $conflicts[3]['reason'] ?? '';
check(strpos($reason, 'Opr3') !== false && strpos($reason, 'Opr4') !== false && strpos($reason, 'Opr2') !== false,
    'Conflict identifies both moving package members and the fixed member of the other package');
check(strpos($reason, '08.10.2026 10:00') !== false && strpos($reason, '08.10.2026 11:00') !== false,
    'Conflict distinguishes the complete reservation start from its fixed non-head member');
$snapshot = serialize(array($context, $result));
planning_TaskConstraints::getManualOrderTimeConflicts(1, $order, $links, $context->tasks, $result);
check(serialize(array($context, $result)) === $snapshot, 'Rejecting the order never retimes pins, splits packages or mutates the simulated plan');
check(!planning_TaskConstraints::getManualOrderTimeConflicts(1, array(1, 2, 3, 4), $links, $context->tasks, $result),
    'The feasible original package order remains valid');
check(!planning_TaskConstraints::getManualOrderTimeConflicts(2, array(5), array(), $context->tasks, $result),
    'A fixed reservation on another machine does not reject an unrelated resource');
safeMachinePlan($result, 'Rejected manual package order still has a safe unchanged reservation');

$context->tasks[2] = clone $context->tasks[2];
$context->tasks[2]->timeStart = '2026-10-08 15:00:00';
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => $order)));
check(chronologicalIds($result) === $order, 'The same complete package fits before a later fixed reservation');
check(!planning_TaskConstraints::getManualOrderTimeConflicts(1, $order, $links, $context->tasks, $result),
    'Feasible movement before a fixed package is not rejected');
check($result->tasks[1][2]->expectedTimeStart === '2026-10-08 15:00:00' && $result->assets[1]->packageLinks === $links,
    'Accepting the feasible order also preserves the fixed start and both package chains');

$context = regressionContext(array(1 => task(1), 2 => task(2, 3600, '2026-10-08 11:00:00'),
    3 => task(3), 4 => task(4)), $links);
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => $order)));
check(($result->tasks[1][4]->_endTimestamp ?? null) === ($result->tasks[1][1]->_startTimestamp ?? null),
    'Whole package can end exactly at the following fixed package boundary');
check(!planning_TaskConstraints::getManualOrderTimeConflicts(1, $order, $links, $context->tasks, $result),
    'An exact exclusive-end boundary is not a false overlap');

$context->tasks[3] = clone $context->tasks[3];
$context->tasks[3]->calcedCurrentDuration = 14400;
$singleOrder = array(3, 1, 2, 4);
$singleLinks = array(2 => 1);
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => $singleOrder), 'packageLinkOverrides' => array(1 => $singleLinks)));
$conflicts = planning_TaskConstraints::getManualOrderTimeConflicts(1, $singleOrder, $singleLinks, $context->tasks, $result);
check(strpos($conflicts[3]['reason'] ?? '', 'Операцията Opr3') !== false,
    'The same protection explains an individual operation that cannot fit before the fixed package');
$context->tasks[2] = clone $context->tasks[2];
$context->tasks[2]->timeStart = null;
$result = regressionPlan($context, array('manualOrderOverrides' => array(1 => $singleOrder), 'packageLinkOverrides' => array(1 => $singleLinks)));
check(!planning_TaskConstraints::getManualOrderTimeConflicts(1, $singleOrder, $singleLinks, $context->tasks, $result),
    'Queues without fixed reservations retain their existing scheduling behavior');

echo 'OK: ' . ($checks - $extraStart) . " additional manual/package regression checks; {$checks} total checks\n";
