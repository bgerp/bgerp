<?php

/**
 * Ръчният режим запазва машинните опашки и защитите при фиксирано начало.
 * Изпълнение: php planning/tests/ManualOnlyTargetTimes.php
 */
require __DIR__ . '/ManualOrderRegression.php';

$manualChecks = $checks;
planning_Setup::$values['MANUAL_ORDER_IN_ASSET'] = 'yes';
core_Cache::$values = array();
dt::$now = '2026-10-08 08:00:00';

function manualOnlyContext($rows, $links = array(), $frames = null) {
    planning_Setup::$values['MANUAL_ORDER_IN_ASSET'] = 'no';
    $context = regressionContext($rows, $links, array(), $frames);
    planning_Setup::$values['MANUAL_ORDER_IN_ASSET'] = 'yes';
    $scheduled = regressionPlan($context);
    foreach ($context->baseline as $id => $row) {
        $row->expectedTimeStart = $scheduled->tasks[$row->assetId][$id]->expectedTimeStart ?? null;
        $row->expectedTimeEnd = $scheduled->tasks[$row->assetId][$id]->expectedTimeEnd ?? null;
    }
    $context->comparison = $scheduled;
    return $context;
}

// Фиксирането не включва технологични зависимости или групиране на друга машина.
$context = manualOnlyContext(array(1 => task(1), 2 => task(2), 3 => task(3, 7200, null, 2),
    4 => task(4, 3600, null, 2), 5 => task(5, 3600, null, 2)));
$context->previous = array(1 => array(3 => (object)array('intersect' => 'no', 'waitingTime' => 86400)),
    4 => array(3 => (object)array('intersect' => 'no', 'waitingTime' => 86400)));
foreach ($context->tasks as $id => $row) {
    $row->_planningParamValues = array(10 => $id == 4 ? 'blue' : 'red');
    $row->_planningParamSignature = $id == 4 ? 'blue' : 'red';
}
$context->prepared->assets[2]->manualOrder = array(3, 4);
$before = regressionPlan($context);
$result = proposal($context, 1, '2026-10-08 12:00:00');
feasible($result, 'Manual-only target ignores technological predecessors');
check($result->scheduled->tasks[1][1]->expectedTimeStart === '2026-10-08 12:00:00', 'Manual-only target remains exact');
check($before->tasks[2] == $result->scheduled->tasks[2], 'A target on another machine cannot change this machine times');
check($result->scheduled->assets[2]->packageLinks === array()
    && chronologicalIds($result->scheduled, 2) === array(3, 4, 5), 'Other machine does not group a matching new task');
check($result->scheduled->previousTasks === array(), 'Ignored technological dependencies do not enter the approval plan');
$context->prepared->assets[1]->manualOrder = array(1);
$context->tasks[2]->timeStart = '2026-10-08 12:00:00';
$sameMachine = regressionPlan($context);
check($sameMachine->assets[1]->packageLinks === array(), 'A target never enables automatic grouping on its own machine');
$sameJob = array(1 => task(1), 2 => task(2));
foreach ($sameJob as $id => $row) { $row->originId = 100; $row->saoOrder = $id; }
check(planning_TaskConstraints::getSameResourceJobPackageLinks($sameJob) === array(), 'Manual-only does not invent technological packages');

// Вмъкването оставя свободен интервал, но не допуска прескачане от следваща операция.
$context = manualOnlyContext(array(1 => task(1, 7200), 2 => task(2, 10800), 3 => task(3, 7200), 4 => task(4)));
$result = proposal($context, 4, '2026-10-08 12:00:00');
feasible($result, 'Manual target is inserted after the last operation ending before it');
check(chronologicalIds($result->scheduled) === array(1, 4, 2, 3), 'The remaining operations retain their relative order');
check($result->scheduled->tasks[1][2]->expectedTimeStart === '2026-10-08 13:00:00'
    && $result->scheduled->tasks[1][3]->expectedTimeStart === '2026-10-08 16:00:00', 'A smaller later operation never fills the gap before the fixed start');
check($result->scheduled->assets[1]->packageLinks === array(), 'Insertion never creates a package');
safeMachinePlan($result->scheduled, 'Manual insertion');

// Потвърденият ред е същият при следващо преизчисляване и повторно зареждане.
ScenarioTargetTimes::$context = $context;
planning_Tasks::$records = $context->baseline;
$form = scenarioForm((object)array('id' => 4, 'assetId' => 1, 'timeStart' => '2026-10-08 12:00:00'));
ScenarioTargetTimes::validateForm(new planning_Tasks(), $form);
check($form->gotErrors() && !empty($form->_targetTimesReport['hash']), 'Manual-only save requires preview approval');
$accepted = scenarioForm($form->rec);
$accepted->rec->targetPreviewAccepted = 'yes';
ScenarioTargetTimes::validateForm(new planning_Tasks(), $accepted);
check(!$accepted->gotErrors() && !empty($accepted->rec->_targetTimesChanged), 'Manual-only Apply accepts the displayed preview');
$stored = clone $context;
$stored->tasks = $result->tasks;
$stored->prepared = $result->scheduled;
$recalculated = regressionPlan($stored);
check($recalculated->tasks == $result->scheduled->tasks, 'Persisted target and order reproduce the preview on cron');
$stored->prepared = $recalculated;
check(regressionPlan($stored)->tasks == $recalculated->tasks, 'Reload and repeated cron do not reorder the queue');
$stored->tasks = array_map(function($row) { return clone $row; }, $stored->tasks);
$stored->tasks[4]->timeStart = null;
check(chronologicalIds(regressionPlan($stored)) === array(1, 4, 2, 3), 'Removing the final target retains the confirmed manual order');

// Чуждите фиксирани начала и пакетните връзки се променят само с изрично избран вариант.
$context = manualOnlyContext(array(1 => task(1), 2 => task(2, 3600, '2026-10-08 11:00:00'), 3 => task(3)), array(2 => 1));
$snapshot = serialize($context);
$blocked = proposal($context, 3, '2026-10-08 10:30:00');
check(!empty($blocked->conflicts), 'An overlapping fixed package refuses the default proposal');
check(serialize($context) === $snapshot, 'Refused proposal leaves foreign pins and packages unchanged');
$conflictText = planning_TargetTimes::formatConflicts($blocked->conflicts);
check(strpos($conflictText, 'Opr3') !== false && (strpos($conflictText, 'Opr1') !== false || strpos($conflictText, 'Opr2') !== false),
    'Conflict identifies the edited operation and the blocking package member');
$nearest = proposal($context, 3, '2026-10-08 10:30:00', 'nearest');
feasible($nearest, 'Manual-only can offer the closest nonconflicting start');
check(!$nearest->pinChanges && $nearest->scheduled->assets[1]->packageLinks === array(2 => 1), 'Closest proposal preserves foreign fixed start and package');
$moved = proposal($context, 3, '2026-10-08 10:30:00', 'move');
feasible($moved, 'Foreign fixed package may move only in the explicit move variant');
check(!empty($moved->pinChanges[2]) && $moved->scheduled->assets[1]->packageLinks === array(2 => 1), 'Explicit variant lists the foreign pin change without splitting its package');
$ownPackage = proposal($context, 1, '2026-10-08 14:00:00');
check(!empty($ownPackage->conflicts), 'Fixing a package head after its fixed successor is refused');
$ownNearest = proposal($context, 1, '2026-10-08 14:00:00', 'nearest');
feasible($ownNearest, 'Package head can receive a close alternative without moving the fixed successor');
check(!$ownNearest->pinChanges && $ownNearest->scheduled->assets[1]->packageLinks === array(2 => 1),
    'Package-internal alternative preserves the fixed successor and package link');

// Фиксирането на член на пакет премества целия пакет, а не отделния ред.
$context = manualOnlyContext(array(1 => task(1), 2 => task(2), 3 => task(3), 4 => task(4)), array(2 => 1));
$result = proposal($context, 2, '2026-10-08 14:00:00');
feasible($result, 'A fixed non-head member moves its whole manual package');
check($result->scheduled->tasks[1][1]->expectedTimeStart === '2026-10-08 13:00:00'
    && $result->scheduled->tasks[1][2]->expectedTimeStart === '2026-10-08 14:00:00'
    && $result->scheduled->assets[1]->packageLinks === array(2 => 1), 'Package prefix and fixed member remain contiguous');

// Реално започната операция не може да бъде изместена или застъпена.
$started = task(1, 10800);
$started->state = 'active';
$started->actualStart = '2026-10-08 07:00:00';
$context = manualOnlyContext(array(1 => $started, 2 => task(2)));
$snapshot = serialize($context);
$blocked = proposal($context, 2, '2026-10-08 09:00:00');
check(!empty($blocked->conflicts), 'A running operation blocks an overlapping manual target');
check(serialize($context) === $snapshot, 'Running operation is never changed by the refused proposal');
$nearest = proposal($context, 2, '2026-10-08 09:00:00', 'nearest');
feasible($nearest, 'A running operation permits a later safe alternative');
check($nearest->scheduled->tasks[1][2]->_startTimestamp >= $nearest->scheduled->tasks[1][1]->_endTimestamp,
    'Safe alternative begins after the complete running operation');

// Промяна между прегледа и Приложи се проверява отново, без мълчаливо записване.
$context = manualOnlyContext(array(1 => task(1), 2 => task(2), 3 => task(3, 3600, '2026-10-08 15:00:00'),
    4 => task(4, 3600, null, 2)));
ScenarioTargetTimes::$context = $context;
planning_Tasks::$records = $context->baseline;
$preview = scenarioForm((object)array('id' => 2, 'assetId' => 1, 'timeStart' => '2026-10-08 12:00:00'));
ScenarioTargetTimes::validateForm(new planning_Tasks(), $preview);
check(!empty($preview->_targetTimesReport['hash']), 'Concurrent-conflict scenario starts with a valid preview');
$context->tasks[4] = clone $context->tasks[4];
$context->baseline[4] = clone $context->baseline[4];
$context->tasks[4]->calcedCurrentDuration = $context->baseline[4]->calcedCurrentDuration = 7200;
$context->comparison = regressionPlan($context);
$unrelated = scenarioForm($preview->rec);
$unrelated->rec->targetPreviewAccepted = 'yes';
ScenarioTargetTimes::validateForm(new planning_Tasks(), $unrelated);
check(!$unrelated->gotErrors(), 'Unrelated machine change does not invalidate manual-only approval');
$context->tasks[3] = clone $context->tasks[3];
$context->baseline[3] = clone $context->baseline[3];
$context->tasks[3]->timeStart = $context->baseline[3]->timeStart = '2026-10-08 12:30:00';
$savedCount = count(planning_Tasks::$saved);
$stale = scenarioForm($preview->rec);
$stale->rec->targetPreviewAccepted = 'yes';
ScenarioTargetTimes::validateForm(new planning_Tasks(), $stale);
check($stale->gotErrors() && empty($stale->rec->_targetTimesChanged), 'A new same-machine fixed-start conflict refuses the old approval');
check(strpos(json_encode($stale->_targetTimesReport ?? array()), 'Opr3') !== false,
    'Refused Apply identifies the operation whose new fixed start causes the conflict');
check($context->tasks[3]->timeStart === '2026-10-08 12:30:00' && count(planning_Tasks::$saved) === $savedCount,
    'Refused Apply never changes the concurrent fixed start or saves operations');

// Неработното време остава твърдо ограничение и в ръчния режим.
$context = manualOnlyContext(array(1 => task(1), 2 => task(2)), array(),
    array(array('08:00:00', '11:59:59'), array('14:00:00', '19:59:59')));
$blocked = proposal($context, 2, '2026-10-08 12:00:00');
check(!empty($blocked->conflicts), 'Manual-only refuses a target in a calendar break');
$nearest = proposal($context, 2, '2026-10-08 12:00:00', 'nearest');
feasible($nearest, 'Manual-only offers a working-calendar alternative');
safeMachinePlan($nearest->scheduled, 'Manual target across a calendar break');

planning_Setup::$values = array();
echo 'OK: ' . ($checks - $manualChecks) . " additional manual-only fixed-start checks; {$checks} total checks\n";
