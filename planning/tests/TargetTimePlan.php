<?php

/**
 * Предложенията се проверяват с истинския планировчик, без приложение и база.
 * Изпълнение: php planning/tests/TargetTimePlan.php
 */
require __DIR__ . '/TargetTimes.php';
require dirname(__DIR__) . '/TargetTimePlan.class.php';
class planning_Centers { public static function getQuery() { return new FixtureQuery(array()); } }
class keylist { public static function toArray($value) { return array(); } }

function planContext($rows, $links = array(), $frames = null, $previous = array()) {
    $calendar = new core_Intervals();
    foreach ($frames ?? array(array('08:00:00', '19:59:59')) as $frame) {
        $calendar->add(strtotime('2026-10-08 ' . $frame[0]), strtotime('2026-10-08 ' . $frame[1]));
    }
    $assets = $calendars = $manuals = array();
    foreach ($rows as $row) {
        $assetId = $row->assetId ?? 1;
        if (isset($assets[$assetId])) continue;
        $order = array_keys(array_filter($rows, function($task) use ($assetId) { return ($task->assetId ?? null) == $assetId; }));
        $assets[$assetId] = (object)array('id' => $assetId, 'code' => 'TEST', 'packageLinks' => $assetId == 1 ? $links : array(),
            'anchorLinks' => array(), 'manualOrder' => $order, 'autoGroupVersion' => 1, 'planningParams' => null);
        $calendars[$assetId] = clone $calendar;
        $manuals[] = (object)array('assetId' => $assetId, 'data' => $order, 'packageLinks' => $assetId == 1 ? $links : array());
    }
    $prepared = (object)array('assets' => $assets, 'calendarIntervals' => $calendars);
    $before = planning_TaskConstraints::calcPreparedTimes($rows, $previous, '2026-10-08 08:00:00', $prepared);
    $baseline = array();
    foreach ($rows as $id => $row) {
        $baseline[$id] = clone $row;
        $baseline[$id]->expectedTimeStart = $before->tasks[$row->assetId ?? 1][$id]->expectedTimeStart ?? null;
        $baseline[$id]->expectedTimeEnd = $before->tasks[$row->assetId ?? 1][$id]->expectedTimeEnd ?? null;
    }
    return (object)array('tasks' => $rows, 'previous' => $previous, 'baseline' => $baseline,
        'prepared' => $before, 'manualRecords' => $manuals,
        'now' => '2026-10-08 08:00:00');
}
function proposal($context, $id, $date, $mode = 'keep', $policy = 'keep') {
    $context = clone $context;
    $context->tasks = array_map(function($row) { return clone $row; }, $context->tasks);
    $context->tasks[$id]->timeStart = $date;
    return planning_TargetTimes::simulate((object)array('id' => $id, 'assetId' => 1, 'timeStart' => $date), $policy, $mode, $context);
}
function feasible($result, $message) { check(empty($result->error) && empty($result->conflicts), $message . ': ' . ($result->error ?? json_encode($result->conflicts ?? array()))); }

$context = planContext(array(1 => task(1), 2 => task(2, 3600, '2026-10-08 10:00:00'), 3 => task(3, 3600, '2026-10-08 12:00:00')));
$snapshot = serialize($context);
$result = proposal($context, 1, '2026-10-08 15:00:00');
feasible($result, 'Later free pin is moved after existing targets');
check($result->changes[1]['order'] === array(2, 3, 1), 'Manual order is proposed together with the target start');
check(!$result->pinChanges, 'Unrelated fixed starts are not changed');
check(serialize($context) === $snapshot, 'All proposals leave their source snapshot unchanged');
$unchanged = proposal($context, 2, '2026-10-08 10:00:00');
feasible($unchanged, 'An unchanged existing target remains valid');
check(empty($unchanged->changes) && empty($unchanged->pinChanges), 'Different array keys do not create a false manual-order change');

$context = planContext(array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 10:00:00')), array(2 => 1));
$result = proposal($context, 1, '2026-10-08 14:00:00');
check(!empty($result->conflicts), 'Package with conflicting pins cannot be saved silently');
$result = proposal($context, 1, '2026-10-08 14:00:00', 'move');
feasible($result, 'Package can explicitly move its successor pin');
check($result->pinChanges === array(2 => '2026-10-08 16:00:00'), 'Successor new pin is computed from the whole package');
$result = proposal($context, 1, '2026-10-08 14:00:00', 'nearest');
feasible($result, 'Package can preserve the successor pin');
check($result->proposedStart === '2026-10-08 08:00:00' && !$result->pinChanges, 'Alternative head start preserves every foreign pin');
$result = proposal($context, 1, '2026-10-08 14:00:00', 'keep', 'detach');
feasible($result, 'Detaching a voluntary package remains available');
check(!$result->changes[1]['links'], 'Detaching does not create another package link');

$context = planContext(array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 10:00:00'), 3 => task(3, 3600, '2026-10-08 11:00:00'), 4 => task(4, 3600, '2026-10-08 17:00:00')));
$result = proposal($context, 1, '2026-10-08 10:00:00', 'move');
feasible($result, 'Free pin can explicitly displace several conflicting targets');
check($result->pinChanges === array(2 => '2026-10-08 12:00:00', 3 => '2026-10-08 13:00:00'), 'Only the collision cascade changes its fixed starts');
$result = proposal($context, 1, '2026-10-08 10:00:00', 'nearest');
feasible($result, 'Free pin has a nearby nonconflicting alternative');
check($result->proposedStart === '2026-10-08 08:00:00' && !$result->pinChanges, 'Equidistant alternatives prefer the earlier start');

$context = planContext(array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 10:00:00'), 3 => task(3, 3600)), array(3 => 2));
$result = proposal($context, 1, '2026-10-08 11:00:00', 'move');
feasible($result, 'Collision with an unfixed tail moves its entire package');
check($result->pinChanges === array(2 => '2026-10-08 13:00:00')
    && $result->scheduled->tasks[1][3]->expectedTimeStart === '2026-10-08 14:00:00', 'Foreign package remains intact');
$result = proposal($context, 1, '2026-10-08 11:00:00', 'nearest');
feasible($result, 'Nearby alternative avoids the whole foreign package');
check($result->proposedStart === '2026-10-08 12:00:00', 'Nearest start considers package tail, not only its fixed head');

$context = planContext(array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 15:00:00')), array(2 => 1),
    array(array('08:00:00', '11:59:59'), array('14:00:00', '17:59:59'), array('+1 day 08:00:00', '+1 day 17:59:59')));
$result = proposal($context, 1, '2026-10-08 17:00:00', 'move');
feasible($result, 'Retimed pins follow working intervals, including overnight pauses');
check($result->pinChanges === array(2 => '2026-10-09 09:00:00'), 'Successor pin is not shifted by a naive wall-clock offset');
$result = proposal($context, 1, '2026-10-08 17:00:00', 'nearest');
feasible($result, 'Preserved tail pin can place the head across a lunch break');
check($result->proposedStart === '2026-10-08 11:00:00', 'Backward calculation uses working time');

$context = planContext(array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 10:00:00'), 3 => task(3, 3600, '2026-10-08 17:00:00')), array(2 => 1, 3 => 2));
$result = proposal($context, 1, '2026-10-08 14:00:00', 'nearest');
check(!empty($result->error), 'Inconsistent existing pins do not produce a false nearest alternative');
$started = task(2, 7200);
$started->state = 'active';
$started->actualStart = '2026-10-08 08:00:00';
$context = planContext(array(2 => $started, 1 => task(1)));
$result = proposal($context, 1, '2026-10-08 09:00:00', 'move');
check(!empty($result->error) || !empty($result->conflicts), 'Started operation cannot be displaced by a new fixed start');
$result = proposal($context, 1, '2026-10-08 09:00:00', 'nearest');
feasible($result, 'Nearest alternative can follow a started operation');
check($result->proposedStart === '2026-10-08 10:00:00', 'Started operation boundary is used exactly');
$context->baseline[2]->expectedTimeEnd = '2026-10-08 10:00:00';
$result = proposal($context, 1, '2026-10-08 10:00:00');
feasible($result, 'Aligned end of a started operation permits the next fixed start');
check($result->options['manualOrderOverrides'][1] === array(2, 1), 'An unstarted operation is never inserted before the started prefix');
$context = planContext(array(2 => $started, 1 => task(1)), array(1 => 2));
$result = proposal($context, 1, '2026-10-08 10:00:00');
feasible($result, 'A pin on a started package continuation retains the package');
check($result->options['manualOrderOverrides'][1] === array(2, 1), 'A package with a started prefix is never relocated');

$rows = array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 10:00:00'));
$rows[2]->originId = 1;
$rows[2]->saoOrder = 2;
$context = planContext($rows, array(2 => 1));
$result = proposal($context, 1, '2026-10-08 14:00:00', 'keep', 'detach');
check(!empty($result->error), 'Mandatory technological package cannot be detached');

$context = planContext(array(1 => task(1)), array(), array(array('08:00:00', '11:59:59'), array('14:00:00', '17:59:59')));
$result = proposal($context, 1, '2026-10-08 12:30:00', 'nearest');
feasible($result, 'A request during nonworking hours has a nearby working-time alternative');
check($result->proposedStart === '2026-10-08 11:59:00', 'Nearest search includes both edges of a lunch break');
$previous = array(1 => array(2 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$context = planContext(array(2 => task(2, 7200), 1 => task(1)), array(), null, $previous);
$result = proposal($context, 1, '2026-10-08 09:00:00', 'move');
check(!empty($result->conflicts), 'Moving foreign targets cannot bypass a technological predecessor');
$result = proposal($context, 1, '2026-10-08 09:00:00', 'nearest');
feasible($result, 'Closest alternative incorporates technological readiness');
check($result->proposedStart === '2026-10-08 10:00:00', 'Technological predecessor must finish before the proposed start');

$frames = array();
for ($day = 0; $day <= 80; $day++) {
    if ((int)date('N', strtotime("2026-10-08 +{$day} days")) <= 5) {
        $frames[] = array("+{$day} days 08:00:00", "+{$day} days 16:59:59");
    }
}
$previous = array(1 => array(2 => (object)array('intersect' => 'no', 'waitingTime' => 1800)));
$context = planContext(array(2 => task(2, 8 * 60, '2026-12-08 14:18:00', 2), 1 => task(1, 45 * 60)), array(), $frames, $previous);
$snapshot = serialize($context);
$direct = proposal($context, 1, '2026-12-08 14:56:00');
feasible($direct, 'A distant technological readiness boundary is actually feasible');
$result = proposal($context, 1, '2026-10-30 10:00:00', 'nearest');
feasible($result, 'Nearby calendar edges must not starve the scheduler-provided distant boundary');
check($result->proposedStart === '2026-12-08 14:56:00' && !$result->pinChanges,
    'The distant alternative preserves the predecessor fixed start');
check(serialize($context) === $snapshot, 'Distant nearest search does not mutate its context');
$result = proposal($context, 1, '2026-10-30 10:00:00', 'move');
$reason = $result->conflicts[1]['reason'] ?? '';
check(strpos($reason, 'Opr2') !== false && strpos($reason, '08.12.2026 14:25') !== false,
    'Technological conflict names the blocking predecessor and its planned end');
check(strpos($reason, '30 мин.') !== false && strpos($reason, '08.12.2026 14:56') !== false,
    'Technological conflict explains the wait and the exact readiness boundary');
check(strpos($reason, '14:26') === false && substr_count($reason, '08.12.2026 14:56') === 1,
    'Conflict shows the correct final readiness once without the intermediate resource boundary');
$context = planContext(array(2 => task(2, 8 * 60, '2026-12-08 14:18:00', 2), 1 => task(1, 45 * 60),
    3 => task(3, 3600, '2026-10-30 11:00:00')), array(3 => 1), $frames, $previous);
$snapshot = serialize($context);
$result = proposal($context, 1, '2026-10-30 10:00:00', 'nearest');
check(!empty($result->error) && strpos($result->error, 'Opr2') !== false,
    'An impossible anchored alternative retains the concrete technological blocker');
check(serialize($context) === $snapshot, 'Unsuccessful nearest search preserves every fixed start and package link');

$context = planContext(array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 11:00:00')), array(), array(array('08:00:00', '11:59:59')));
$result = proposal($context, 1, '2026-10-08 10:00:00', 'move');
check(!empty($result->error), 'Exhausted calendar does not offer partially scheduled displaced operations');
$result = proposal($context, 1, '2026-10-08 10:00:00', 'nearest');
feasible($result, 'Closest alternative can use the space before a fixed reservation');
check($result->proposedStart === '2026-10-08 09:00:00', 'Whole operation must fit before the next reservation');

$previous = array(2 => array(1 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$context = planContext(array(1 => task(1), 2 => task(2, 3600, '2026-10-08 10:00:00', 2)), array(), null, $previous);
$result = proposal($context, 1, '2026-10-08 10:00:00', 'nearest');
feasible($result, 'Nearest alternative also preserves a fixed successor on another machine');
check($result->proposedStart === '2026-10-08 09:00:00' && !$result->pinChanges, 'A fixed successor provides the latest safe predecessor start');

class ScenarioTargetTimes extends planning_TargetTimes {
    public static $context;
    public static function simulate($rec, $policy = 'keep', $resolution = 'keep', $context = null, $now = null) {
        $context = clone ($context ?? self::$context);
        $context->now = $now ?? dt::now();
        $context->tasks = array_map(function($row) { return clone $row; }, $context->tasks);
        foreach (get_object_vars($rec) as $field => $value) $context->tasks[$rec->id ?? -1]->{$field} = $value;
        $context->tasks[$rec->id ?? -1]->timeStart = $rec->timeStart ?? null;
        return parent::simulate($rec, $policy, $resolution, $context);
    }
}
function scenarioForm($rec) {
    $form = new FixtureForm();
    $form->rec = clone $rec;
    return $form;
}
ScenarioTargetTimes::$context = planContext(array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 10:00:00')), array(2 => 1));
planning_Tasks::$records = ScenarioTargetTimes::$context->baseline;
$rec = (object)array('id' => 1, 'assetId' => 1, 'timeStart' => '2026-10-08 14:00:00');
$form = scenarioForm($rec);
$savedBefore = count(planning_Tasks::$saved);
ScenarioTargetTimes::validateForm(new planning_Tasks(), $form);
$choices = $form->_targetTimesReport['alternatives'] ?? array();
check(count($choices) === 3 && $choices[0]['available'] && $choices[1]['available'] && $choices[2]['available'], 'Conflict dialog offers move, nearest, and voluntary detachment');
check(($form->_targetTimesReport['editedOperation'] ?? null) === 'Opr1', 'Conflict preview identifies the edited operation before choosing an alternative');
check(($form->_targetTimesReport['requestedStart'] ?? null) === '08.10.2026 14:00', 'The conflict header receives the original requested start before choosing an alternative');
check(array_column(array_column($choices, 'report'), 'editedOperation') === array('Opr1', 'Opr1', 'Opr1'), 'Every alternative identifies the edited operation');
check(count(planning_Tasks::$saved) === $savedBefore && empty($form->rec->_targetTimesChanged), 'Opening alternatives does not save any operation');
$foreignRow = array_values(array_filter($choices[0]['report']['operationChanges'], function($row) { return $row['operation'] === 'Opr2'; }))[0];
check($foreignRow['pinBefore'] === '08.10.2026 10:00' && $foreignRow['pinAfter'] === '08.10.2026 16:00' && $foreignRow['pinChanged'], 'Dialog explicitly lists foreign fixed start before and after');
$order = $choices[0]['report']['operationOrder'][0];
check(array_column($order['before'], 'id') === array(1, 2) && array_column($order['after'], 'id') === array(1, 2), 'Comparison contains complete chronological machine lists');
check($order['after'][1]['previous'] === 1 && $order['after'][1]['pin'] === '08.10.2026 16:00', 'Comparison includes package and fixed-start markers');
check($order['after'][0]['startChange'] === 1 && $order['after'][1]['endChange'] === 1, 'Comparison marks later starts and ends');
$detachedOrder = $choices[2]['report']['operationOrder'][0];
check(array_column($detachedOrder['after'], 'number') === array(2, 1), 'Comparison numbers follow operations after reordering');
check($detachedOrder['before'][1]['previous'] === 1 && $detachedOrder['after'][0]['previous'] === null, 'Detachment is visible without a separate link column');
$accepted = scenarioForm($rec);
$accepted->rec->targetResolution = 'move';
$accepted->rec->targetPreviewAccepted = 'yes';
$accepted->rec->targetPreviewHash = $choices[0]['report']['hash'];
ScenarioTargetTimes::validateForm(new planning_Tasks(), $accepted);
check(!$accepted->gotErrors() && $accepted->rec->_targetStartChanges === array(2 => '2026-10-08 16:00:00'), 'Apply accepts only the freshly rechecked selected alternative');
$nearest = scenarioForm($rec);
$nearest->rec->targetResolution = 'nearest';
$nearest->rec->targetPreviewAccepted = 'yes';
$nearest->rec->targetPreviewHash = $choices[1]['report']['hash'];
ScenarioTargetTimes::validateForm(new planning_Tasks(), $nearest);
check(!$nearest->gotErrors() && $nearest->rec->timeStart === '2026-10-08 08:00:00' && !$nearest->rec->_targetStartChanges, 'Apply writes the proposed nearby start, not the original conflicting input');
planning_Tasks::$editDenied[2] = true;
$denied = scenarioForm($rec);
ScenarioTargetTimes::validateForm(new planning_Tasks(), $denied);
check(!$denied->_targetTimesReport['alternatives'][0]['available'] && $denied->_targetTimesReport['alternatives'][1]['available'], 'Lack of foreign edit rights disables only the destructive alternative');
planning_Tasks::$editDenied = array();
ScenarioTargetTimes::$context->baseline[2] = clone ScenarioTargetTimes::$context->baseline[2];
ScenarioTargetTimes::$context->tasks[2] = clone ScenarioTargetTimes::$context->tasks[2];
ScenarioTargetTimes::$context->baseline[2]->timeStart = ScenarioTargetTimes::$context->tasks[2]->timeStart = '2026-10-08 11:00:00';
$stale = scenarioForm($rec);
$stale->rec->targetResolution = 'move';
$stale->rec->targetPreviewAccepted = 'yes';
$stale->rec->targetPreviewHash = $choices[0]['report']['hash'];
ScenarioTargetTimes::validateForm(new planning_Tasks(), $stale);
check($stale->gotErrors() && empty($stale->rec->_targetTimesChanged), 'Concurrent target edits require a new preview');
planning_TargetTimes::applyPackageChanges((object)array('_targetStartChanges' => array(2 => '2026-10-08 16:00:00')));
check(planning_Tasks::$records[2]->timeStart === '2026-10-08 16:00:00' && planning_Tasks::$records[2]->targetStartBy === 7, 'Confirmed foreign pin changes are saved and attributed to the approving user');
check(count(planning_Tasks::$logs) === 1, 'Confirmed foreign pin changes are logged');

ScenarioTargetTimes::$context = planContext(array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 10:00:00')), array(2 => 1));
ScenarioTargetTimes::$context->prepared->calendarIntervals[1] = clone ScenarioTargetTimes::$context->prepared->calendarIntervals[1];
ScenarioTargetTimes::$context->prepared->calendarIntervals[1]->cut(strtotime('2026-10-08 14:00:00'), strtotime('2026-10-08 14:29:59'));
$changedCalendar = scenarioForm($rec);
$changedCalendar->rec->targetResolution = 'move';
$changedCalendar->rec->targetPreviewAccepted = 'yes';
$changedCalendar->rec->targetPreviewHash = $choices[0]['report']['hash'];
ScenarioTargetTimes::validateForm(new planning_Tasks(), $changedCalendar);
check($changedCalendar->gotErrors() && empty($changedCalendar->rec->_targetTimesChanged), 'Changed working calendar cannot reuse a previous approval');

ScenarioTargetTimes::$context = planContext(array(1 => task(1), 2 => task(2, 3600, '2026-10-08 10:00:00'),
    3 => task(3, 3600, '2026-10-08 12:00:00'), 4 => task(4, 3600, null, 2)));
planning_Tasks::$records = ScenarioTargetTimes::$context->baseline;
$contextForm = scenarioForm((object)array('id' => 1, 'assetId' => 1, 'timeStart' => '2026-10-08 15:00:00'));
ScenarioTargetTimes::validateForm(new planning_Tasks(), $contextForm);
$order = $contextForm->_targetTimesReport['operationOrder'];
check(count($order) === 1 && count($order[0]['before']) === 3, 'Comparison keeps unchanged neighbors and omits unaffected machines');
check(array_column($order[0]['after'], 'number') === array(2, 3, 1), 'Stable numbers expose placement after unchanged fixed operations');
check($order[0]['after'][0]['startChange'] === 0 && $order[0]['after'][2]['startChange'] === 1, 'Unchanged times remain uncolored');
ScenarioTargetTimes::$context->baseline[1]->expectedTimeStart = '2026-10-08 16:00:00';
ScenarioTargetTimes::$context->baseline[1]->expectedTimeEnd = '2026-10-08 16:59:59';
$earlierForm = scenarioForm((object)array('id' => 1, 'assetId' => 1, 'timeStart' => '2026-10-08 08:00:00'));
ScenarioTargetTimes::validateForm(new planning_Tasks(), $earlierForm);
check($earlierForm->_targetTimesReport['operationOrder'][0]['after'][0]['startChange'] === -1, 'Earlier proposed dates are distinguished from later dates');
ScenarioTargetTimes::$context->tasks[1]->assetId = 2;
$machineForm = scenarioForm((object)array('id' => 1, 'assetId' => 2, 'timeStart' => '2026-10-08 15:00:00'));
ScenarioTargetTimes::validateForm(new planning_Tasks(), $machineForm);
$order = $machineForm->_targetTimesReport['operationOrder'];
check(count($order) === 2, 'A machine change shows both the old and the new machine');
$oldNumber = $newNumber = null;
foreach ($order as $group) {
    foreach ($group['before'] as $item) if ($item['id'] === 1) $oldNumber = $item['number'];
    foreach ($group['after'] as $item) if ($item['id'] === 1) $newNumber = $item['number'];
}
check($oldNumber !== null && $oldNumber === $newNumber, 'Comparison number also survives a machine change');

foreach (array(1800, 3600, 5400) as $duration) foreach (array('09:00:00', '10:00:00', '11:00:00', '13:00:00', '14:00:00') as $time) {
    $context = planContext(array(1 => task(1, $duration), 2 => task(2, 3600, '2026-10-08 10:00:00'), 3 => task(3, 3600, '2026-10-08 12:00:00')));
    foreach (array('move', 'nearest') as $mode) {
        $result = proposal($context, 1, '2026-10-08 ' . $time, $mode);
        feasible($result, 'Duration/start matrix produces a feasible ' . $mode . ' proposal');
        foreach ($result->tasks as $id => $row) {
            if (empty($row->timeStart)) continue;
            check($result->scheduled->tasks[1][$id]->expectedTimeStart === $row->timeStart, 'Every accepted fixed start is honored in the duration/start matrix');
        }
    }
}

$rows = array(3 => task(3, 7200, '2026-10-08 10:00:00', 2), 1 => task(1), 2 => task(2));
foreach ($rows as $id => $row) {
    $row->originId = 10;
    $row->saoOrder = $id == 3 ? 1 : $id + 1;
}
$context = planContext($rows, array(2 => 1), null, array(1 => array(3 => (object)array('intersect' => 'no', 'waitingTime' => 1800))));
$context->prepared->assets[1]->code = 'PACK';
$context->prepared->assets[2]->code = 'PREVIOUS';
$snapshot = serialize($context);
$result = proposal($context, 2, '2026-10-08 09:00:00');
$reason = $result->conflicts[2]['reason'] ?? '';
check(strpos($reason, 'Opr1 е преди Opr2 в същия пакет на машина „PACK“') !== false,
    'A package conflict explains the blocked member relative to the edited operation');
check(strpos($reason, 'Двете операции са от едно задание') !== false,
    'The job relationship between the edited operation and its package member is explicit');
check(strpos($reason, 'За да се спази въведеният час за Opr2, Opr1 трябва да започне на 08.10.2026 08:00') !== false,
    'The explanation includes the package-member start required to honor the edited target');
check(strpos($reason, 'Opr1 зависи от Opr3 — предходна технологична операция в същото задание') !== false
    && strpos($reason, 'друга машина „PREVIOUS“') !== false,
    'The third operation has an explicit technological, job and machine relationship');
check(strpos($reason, 'едва след приключването ѝ и още 30 мин.') !== false,
    'A no-overlap dependency explains why the operations cannot run together');
check(serialize($context) === $snapshot, 'Explanatory diagnostics never modify tasks, pins or package links');
$limits = array(3 => array('end' => '2026-10-08 11:59:00', 'waitingTime' => 86400, 'intersect' => 'no'));
$reason = planning_TargetTimes::describeDependencyConflict(2, 1, $limits, '2026-10-09 12:00:00', $rows, $context->prepared->assets, array(1, 2));
check(strpos($reason, '24 часа календарно изчакване') !== false && strpos($reason, '1440') === false,
    'A full-day wait is explained in calendar hours, not as a cryptic minute count');
$rows[3]->originId = 20;
$limits[3]['intersect'] = 'yes';
$reason = planning_TargetTimes::describeDependencyConflict(1, 2, $limits, '2026-10-09 12:00:00', $rows, $context->prepared->assets, array(1, 2));
check(strpos($reason, 'Opr2 е след Opr1') !== false && strpos($reason, 'от друго задание') !== false,
    'A following package member and a different job are not described as a preceding member in the same job');
check(strpos($reason, 'допуска застъпване') !== false && strpos($reason, 'едва след приключването') === false,
    'An overlap-permitted dependency is not falsely explained as requiring completion');

// Прякото ограничение остава заглавие и два смислово свързани реда.
$rows = array(321204 => task(321204), 321199 => task(321199, 3600, null, 2));
foreach ($rows as $row) $row->originId = 10;
$limits = array(321199 => array('end' => '2026-11-06 18:17:00', 'ready' => '2026-11-06 18:18:00',
    'waitingTime' => 0, 'intersect' => 'no'));
$reason = planning_TargetTimes::describeDependencyConflict(321204, 321204, $limits, '2026-11-06 18:18:00',
    $rows, array(1 => (object)array('code' => 'К1 / КА12'), 2 => (object)array('code' => 'К1 / КА13')),
    array(321204), '2026-10-30 10:00:00');
check(count(explode("\n", $reason)) === 3 && strpos($reason, 'За да се спази въведеният час') === false,
    'A direct dependency has no tautological requested-start sentence');
check(strpos($reason, 'друга машина „К1 / КА13“, с очакван край: 06.11.2026 18:17.') !== false,
    'The predecessor relationship, machine and expected end share one sentence');
check(substr_count($reason, 'Opr321199') === 1 && substr_count($reason, '06.11.2026 18:17') === 1,
    'The predecessor handle and expected end are not repeated');
check(strpos($reason, 'Може да започне едва след приключването ѝ и затова не може да започне преди 06.11.2026 18:18.') !== false,
    'The completion rule and resulting start limit share one sentence');
check(strpos($reason, 'освобождава') === false && strpos($reason, 'Само по това технологично условие') === false,
    'Internal interval release details and the repeated limit are omitted');
$limits[321199]['waitingTime'] = 1800;
$limits[321199]['ready'] = '2026-11-06 18:48:00';
$reason = planning_TargetTimes::describeDependencyConflict(321204, 321204, $limits, '2026-11-06 18:48:00', $rows, array(), array(321204));
check(strpos($reason, 'и още 30 мин. календарно изчакване и затова не може да започне преди 06.11.2026 18:48.') !== false,
    'A real calendar wait remains visible together with its resulting start limit');
$limits[321199] = array('ready' => planning_TaskConstraints::NOT_FOUND_DATE);
$reason = planning_TargetTimes::describeDependencyConflict(321204, 321204, $limits, planning_TaskConstraints::NOT_FOUND_DATE,
    $rows, array(), array(321204));
check(strpos($reason, 'няма изчислен край и не може да се определи допустимо начало') !== false
    && strpos($reason, 'не може да започне преди') === false,
    'An unplanned predecessor has a concise explanation without inventing a usable start');

echo "OK: {$checks} total checks including target-time proposals\n";
