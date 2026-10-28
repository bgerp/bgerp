<?php

/**
 * Потвърждението и сравнението не зависят от изминалите секунди или от стария крон.
 * Изпълнение: php planning/tests/TargetTimesPreviewRegression.php
 */
require __DIR__ . '/TargetTimePlan.php';

class PreviewClockTargetTimes extends ScenarioTargetTimes {
    public static function simulate($rec, $policy = 'keep', $resolution = 'keep', $context = null, $now = null) {
        $context = clone ($context ?? self::$context);
        $context->comparison = planning_TaskConstraints::calcPreparedTimes($context->tasks, $context->previous,
            $now ?? dt::now(), $context->prepared);
        return parent::simulate($rec, $policy, $resolution, $context, $now);
    }
}
class planning_StepConditions {
    public static $rows = array();
    public static function getQuery() { return new FixtureQuery(self::$rows); }
}
class RebuiltConstraintsTargetTimes extends PreviewClockTargetTimes {
    public static function simulate($rec, $policy = 'keep', $resolution = 'keep', $context = null, $now = null) {
        $context = clone ($context ?? self::$context);
        $context->previous = array();
        foreach (planning_TaskConstraints::calculateTaskConstraints($context->tasks ?? array()) as $constraint) {
            if (($constraint->type ?? null) === 'prevId') {
                $context->previous[$constraint->taskId ?? 0][$constraint->previousTaskId ?? 0] = $constraint;
            }
        }
        return parent::simulate($rec, $policy, $resolution, $context, $now);
    }
}
function clockContext() {
    dt::$now = '2026-10-08 08:00:00';
    core_Users::$current = 7;
    core_Cache::$values = array();
    PreviewClockTargetTimes::$context = planContext(array(1 => task(1), 2 => task(2), 3 => task(3, 3600, null, 2)));
    planning_Tasks::$records = PreviewClockTargetTimes::$context->baseline;
}
function clockPreview($start = '2026-10-08 15:00:00', $controller = 'PreviewClockTargetTimes') {
    $form = scenarioForm((object)array('id' => 2, 'assetId' => 1, 'timeStart' => $start));
    $controller::validateForm(new planning_Tasks(), $form);
    check($form->gotErrors() && !empty($form->_targetTimesReport['hash']), 'Clock preview requires confirmation');
    return $form;
}
function clockApply($preview, $controller = 'PreviewClockTargetTimes') {
    $form = scenarioForm($preview->rec);
    $form->rec->targetPreviewAccepted = 'yes';
    $controller::validateForm(new planning_Tasks(), $form);
    return $form;
}
function rebuiltConstraintsContext() {
    clockContext();
    $rows = array(1 => task(1, 3600, null, 2), 2 => task(2));
    foreach ($rows as $id => $row) {
        $row->originId = 100;
        $row->saoOrder = $id;
        $row->folderId = 1;
        $row->offsetAfter = 0;
    }
    PreviewClockTargetTimes::$context = planContext($rows);
    planning_Tasks::$records = PreviewClockTargetTimes::$context->baseline;
    planning_StepConditions::$rows = array((object)array('stepId' => 2, 'prevStepId' => 1, 'delay' => 0, 'intersect' => 'no'));
}

// Истинското изчисляване добавя нов updatedOn при всяка заявка, дори с непроменен план.
rebuiltConstraintsContext();
$preview = clockPreview('2026-10-08 15:00:00', 'RebuiltConstraintsTargetTimes');
for ($attempt = 1; $attempt <= 10; $attempt++) {
    dt::$now = dt::addSecs($attempt * 30, '2026-10-08 08:00:00');
    $accepted = clockApply($preview, 'RebuiltConstraintsTargetTimes');
    check(!$accepted->gotErrors() && !empty($accepted->rec->_targetTimesChanged),
        'Rebuilt production constraints accept the same approval on attempt ' . $attempt . ': '
        . implode(' ', $accepted->_targetTimesReport['approvalChanges'] ?? array()));
}
$simulation = RebuiltConstraintsTargetTimes::simulate($preview->rec);
$constraint = $simulation->scheduled->previousTasks[2][1] ?? null;
$expected = (array)$constraint;
unset($expected['updatedOn']);
check(($constraint->updatedOn ?? null) === dt::now()
    && ($simulation->approval['operations'][2]['previous'][1] ?? null) == $expected,
    'Approval omits only updatedOn and leaves the full original dependency unchanged');

foreach (array('delay' => 1800, 'intersect' => 'yes') as $field => $value) {
    rebuiltConstraintsContext();
    $preview = clockPreview('2026-10-08 15:00:00', 'RebuiltConstraintsTargetTimes');
    planning_StepConditions::$rows[0]->{$field} = $value;
    dt::$now = '2026-10-08 08:00:01';
    $stale = clockApply($preview, 'RebuiltConstraintsTargetTimes');
    check($stale->gotErrors() && empty($stale->rec->_targetTimesChanged)
        && strpos(implode(' ', $stale->_targetTimesReport['approvalChanges'] ?? array()), 'Opr2: променени са технологичните зависимости') !== false,
        'A real rebuilt constraint ' . $field . ' change still requires a new approval');
    dt::$now = '2026-10-08 08:00:02';
    $accepted = clockApply($stale, 'RebuiltConstraintsTargetTimes');
    check(!$accepted->gotErrors() && !empty($accepted->rec->_targetTimesChanged),
        'The refreshed ' . $field . ' proposal can be confirmed without another metadata-only loop: '
        . implode(' ', $accepted->errors ?? array()) . ' ' . implode(' ', $accepted->_targetTimesReport['approvalChanges'] ?? array()));
}

clockContext();
$preview = clockPreview();
dt::$now = '2026-10-08 08:00:01';
$accepted = clockApply($preview);
check(!$accepted->gotErrors() && !empty($accepted->rec->_targetTimesChanged), 'One elapsed second cannot invalidate Apply');
dt::$now = '2026-10-08 08:12:00';
$accepted = clockApply($preview);
check(!$accepted->gotErrors() && !empty($accepted->rec->_targetTimesChanged), 'Reading a preview for several minutes does not create a confirmation loop');
$cached = array_values(core_Cache::$values)[0][0];
check($cached['now'] === '2026-10-08 08:00:00' && count($cached['snapshot']['operations']) === 2,
    'Cache stores the clock and only related approval data, not the production plan');

clockContext();
$preview = clockPreview();
dt::$now = '2026-10-08 08:16:00';
$expired = clockApply($preview);
check($expired->gotErrors() && empty($expired->rec->_targetTimesChanged), 'Expired confirmation requires a fresh preview');
clockContext();
$preview = clockPreview();
core_Users::$current = 8;
$otherUser = clockApply($preview);
check($otherUser->gotErrors() && empty($otherUser->rec->_targetTimesChanged), 'Another user cannot reuse the cached confirmation clock');
core_Users::$current = 7;

foreach (array('progress', 'duration', 'package', 'calendar') as $change) {
    clockContext();
    $preview = clockPreview();
    dt::$now = '2026-10-08 08:01:00';
    $context = PreviewClockTargetTimes::$context;
    if ($change === 'progress') {
        $context->baseline[1]->progress = $context->tasks[1]->progress = 0.5;
    } elseif ($change === 'duration') {
        $preview->rec->timeDuration = 7200;
    } elseif ($change === 'package') {
        $context->manualRecords[0]->anchorLinks = array(2 => 1);
        $context->prepared->assets[1]->anchorLinks = array(2 => 1);
    } else {
        $context->prepared->calendarIntervals[1]->cut(strtotime('2026-10-08 08:00:00'), strtotime('2026-10-08 08:29:59'));
    }
    $stale = clockApply($preview);
    check($stale->gotErrors() && empty($stale->rec->_targetTimesChanged), 'A real ' . $change . ' change still requires a new approval');
    $reason = implode(' ', $stale->_targetTimesReport['approvalChanges'] ?? array());
    check(strpos($reason, 'Opr') !== false && strpos(implode(' ', $stale->errors), 'ново потвърждение') !== false,
        'A real ' . $change . ' change names an operation and explains why approval is repeated');
}

foreach (array('progress', 'calendar', 'order', 'note', 'notification', 'new-task', 'unplanned') as $change) {
    clockContext();
    $context = PreviewClockTargetTimes::$context;
    if ($change === 'unplanned') $context->prepared->calendarIntervals[2] = new core_Intervals();
    $preview = clockPreview();
    dt::$now = '2026-10-08 08:01:00';
    if ($change === 'progress' || $change === 'unplanned') {
        $context->baseline[3]->progress = $context->tasks[3]->progress = 0.5;
    } elseif ($change === 'calendar') {
        $context->prepared->calendarIntervals[2]->cut(strtotime('2026-10-08 08:00:00'), strtotime('2026-10-08 08:29:59'));
    } elseif ($change === 'order') {
        $context->manualRecords[1]->data = $context->prepared->assets[2]->manualOrder = array(99, 3);
    } elseif ($change === 'new-task') {
        $context->tasks[4] = task(4, 3600, null, 2);
        $context->baseline[4] = clone $context->tasks[4];
    } elseif ($change === 'note') {
        $context->baseline[1]->notes = $context->tasks[1]->notes = 'Бележката не променя плана';
    } else {
        $context->baseline[1]->targetStartConflict = array('notified' => true);
    }
    $accepted = clockApply($preview);
    check(!$accepted->gotErrors() && !empty($accepted->rec->_targetTimesChanged),
        'Unrelated ' . $change . ' change does not require another approval');
}

clockContext();
PreviewClockTargetTimes::$context->previous = array(2 => array(3 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$preview = clockPreview();
PreviewClockTargetTimes::$context->baseline[3]->progress = PreviewClockTargetTimes::$context->tasks[3]->progress = 0.5;
$stale = clockApply($preview);
check($stale->gotErrors() && empty($stale->rec->_targetTimesChanged), 'A predecessor on another machine remains protected even with spare time');
check(strpos(implode(' ', $stale->_targetTimesReport['approvalChanges']), 'Opr3: променени данни: прогрес') !== false,
    'The explanation names the changed predecessor and its progress');

clockContext();
PreviewClockTargetTimes::$context->previous = array(3 => array(2 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$preview = clockPreview();
PreviewClockTargetTimes::$context->previous[3][2]->waitingTime = 1800;
$stale = clockApply($preview);
check($stale->gotErrors() && strpos(implode(' ', $stale->_targetTimesReport['approvalChanges']), 'Opr3: променени са технологичните зависимости') !== false,
    'A downstream waiting-time change requires approval and explains the changed dependency');

clockContext();
$preview = clockPreview();
PreviewClockTargetTimes::$context->previous = array(3 => array(2 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$stale = clockApply($preview);
check($stale->gotErrors() && strpos(implode(' ', $stale->_targetTimesReport['approvalChanges']), 'Opr3: променен е обхватът') !== false,
    'A newly affected downstream operation cannot reuse a narrower old approval');

clockContext();
$preview = clockPreview();
PreviewClockTargetTimes::$context->tasks[4] = task(4, 3600, null, 1);
PreviewClockTargetTimes::$context->baseline[4] = clone PreviewClockTargetTimes::$context->tasks[4];
$stale = clockApply($preview);
check($stale->gotErrors() && strpos(implode(' ', $stale->_targetTimesReport['approvalChanges']), 'Opr4') !== false,
    'A newly queued operation on the selected machine requires a new approval');

clockContext();
$context = PreviewClockTargetTimes::$context;
$context->tasks[4] = task(4, 3600, null, 2);
$context->baseline[4] = clone $context->tasks[4];
$context->prepared->assets[2]->manualOrder = array(3, 4);
$preview = clockPreview();
foreach (array(3 => '2026-10-08 09:00:00', 4 => '2026-10-08 09:30:00') as $id => $pin) {
    $context->tasks[$id]->timeStart = $context->baseline[$id]->timeStart = $pin;
}
$accepted = clockApply($preview);
check(!$accepted->gotErrors() && empty($accepted->rec->_targetStartChanges),
    'An independent existing conflict is not repaired or used to reject the selected machine');

clockContext();
$context = PreviewClockTargetTimes::$context;
$context->previous = array(2 => array(3 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$preview = clockPreview();
$context->tasks[3]->calcedCurrentDuration = $context->baseline[3]->calcedCurrentDuration = 9 * 3600;
$stale = clockApply($preview);
check($stale->gotErrors() && empty($stale->rec->_targetTimesChanged) && !empty($stale->_targetTimesReport['conflictText']),
    'A related predecessor conflict cannot bypass full fresh safety validation');
check(strpos($stale->_targetTimesReport['conflictText'], 'Opr2') !== false,
    'The newly blocking dependency identifies the operation that cannot keep its requested start');

clockContext();
$context = PreviewClockTargetTimes::$context;
$context->tasks[4] = task(4, 3600, null, 2);
$context->baseline[4] = clone $context->tasks[4];
$context->prepared->assets[2]->manualOrder = array(3, 4);
$context->prepared->assets[2]->packageLinks = array(4 => 3);
$context->previous = array(2 => array(4 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$preview = clockPreview();
$context->tasks[3]->progress = $context->baseline[3]->progress = 0.5;
$stale = clockApply($preview);
check($stale->gotErrors() && strpos(implode(' ', $stale->_targetTimesReport['approvalChanges']), 'Opr3') !== false,
    'A package member reached through a predecessor remains inside the protected scope');

clockContext();
$context = PreviewClockTargetTimes::$context;
$context->tasks[4] = task(4, 3600, null, 2);
$context->baseline[4] = clone $context->tasks[4];
$context->prepared->assets[2]->manualOrder = array(4, 3);
$context->previous = array(2 => array(3 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$preview = clockPreview();
$context->tasks[4]->calcedCurrentDuration = $context->baseline[4]->calcedCurrentDuration = 7200;
$stale = clockApply($preview);
check($stale->gotErrors() && strpos(implode(' ', $stale->_targetTimesReport['approvalChanges']), 'Opr3: променено е планираното изпълнение') !== false,
    'An external queue change is caught through its effect on the related predecessor');

clockContext();
$context = PreviewClockTargetTimes::$context;
$context->tasks[3]->timeStart = $context->baseline[3]->timeStart = '2026-10-08 17:00:00';
$context->previous = array(3 => array(2 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$preview = clockPreview();
$context->tasks[3]->timeStart = $context->baseline[3]->timeStart = '2026-10-08 15:30:00';
$stale = clockApply($preview);
check($stale->gotErrors() && empty($stale->rec->_targetTimesChanged), 'A fixed successor on another machine still restricts approval');

clockContext();
$preview = clockPreview();
core_Cache::$values = array();
$stale = clockApply($preview);
check($stale->gotErrors() && strpos(implode(' ', $stale->_targetTimesReport['approvalChanges']), 'вече не е наличен') !== false,
    'Missing approval evidence never falls back to silently accepting the posted hash');

clockContext();
$preview = clockPreview();
PreviewClockTargetTimes::$context->baseline[2]->state = 'active';
$started = clockApply($preview);
check($started->gotErrors() && empty($started->rec->_targetTimesChanged) && strpos(implode(' ', $started->errors), 'започнала') !== false,
    'An operation started after preview remains read-only and gives the concrete state restriction');

clockContext();
$preview = clockPreview('2026-10-08 08:30:00');
dt::$now = '2026-10-08 08:31:00';
$past = clockApply($preview);
check($past->gotErrors() && empty($past->rec->_targetTimesChanged), 'An elapsed target time cannot be saved using a frozen clock');

clockContext();
PreviewClockTargetTimes::$context->tasks[2]->timeStart = '2026-10-08 15:00:00';
PreviewClockTargetTimes::$context->baseline[2]->timeStart = '2026-10-08 15:00:00';
planning_Tasks::$records[2]->timeStart = '2026-10-08 15:00:00';
$clearing = clockPreview(null);
dt::$now = '2026-10-08 08:01:00';
$cleared = clockApply($clearing);
check(!$cleared->gotErrors() && !empty($cleared->rec->_targetTimesChanged) && $cleared->rec->timeStart === null,
    'Clearing a fixed start also survives elapsed time and requests recalculation');

clockContext();
foreach (PreviewClockTargetTimes::$context->baseline as $row) {
    $row->expectedTimeStart = dt::addSecs(-600, $row->expectedTimeStart);
    $row->expectedTimeEnd = dt::addSecs(-600, $row->expectedTimeEnd);
}
$snapshot = serialize(PreviewClockTargetTimes::$context->baseline);
$preview = clockPreview();
$groups = $preview->_targetTimesReport['operationOrder'];
check(count($groups) === 1 && array_column($groups[0]['before'], 'id') === array(1, 2),
    'Old cron timestamps do not add an unrelated machine to the detailed preview');
check($groups[0]['before'][0]['start'] === '08.10.2026 08:00' && $groups[0]['after'][0]['startChange'] === 0,
    'Before and after use one clock, so an unchanged neighbor is not colored as delayed');
check(serialize(PreviewClockTargetTimes::$context->baseline) === $snapshot, 'Comparing does not mutate the stored source records');
dt::$now = '2026-10-08 08:01:00';
$accepted = clockApply($preview);
check(!$accepted->gotErrors(), 'Forecast-only cron updates do not invalidate source-data approval');

clockContext();
$rows = array(1 => task(1), 2 => task(2));
for ($id = 3; $id <= 18; $id++) $rows[$id] = task($id, 600, null, 2 + ($id % 4));
PreviewClockTargetTimes::$context = planContext($rows);
$context = PreviewClockTargetTimes::$context;
planning_Tasks::$records = $context->baseline;
$preview = clockPreview();
$originalChanges = array_column($preview->_targetTimesReport['operationChanges'], 'operation');
dt::$now = '2026-10-08 08:05:00';
// Кронът е обновил изчислените времена навсякъде, но не и входните ограничения.
foreach (array($context->tasks, $context->baseline) as $records) foreach ($records as $row) {
    $row->expectedTimeStart = dt::addSecs(300, $row->expectedTimeStart ?? '2026-10-08 08:00:00');
    $row->expectedTimeEnd = dt::addSecs(300, $row->expectedTimeEnd ?? '2026-10-08 09:00:00');
    $row->_startTimestamp = strtotime($row->expectedTimeStart);
    $row->_endTimestamp = strtotime($row->expectedTimeEnd);
}
$accepted = clockApply($preview);
check(!$accepted->gotErrors() && !empty($accepted->rec->_targetTimesChanged),
    'Cron-only timing updates on every machine between preview and Apply do not require another confirmation');
$fresh = clockPreview();
check(array_column($fresh->_targetTimesReport['operationOrder'], 'assetId') === array(1),
    'After a global cron timing refresh, unrelated machines still do not appear in the preview');
check(array_column($fresh->_targetTimesReport['operationChanges'], 'operation') === $originalChanges,
    'Clock drift and refreshed cron forecasts do not inflate the changed-operation list');
$context->tasks[1]->calcedCurrentDuration = $context->baseline[1]->calcedCurrentDuration = 7200;
$stale = clockApply($fresh);
check($stale->gotErrors() && empty($stale->rec->_targetTimesChanged)
    && strpos(implode(' ', $stale->_targetTimesReport['approvalChanges'] ?? array()), 'продължителност') !== false,
    'A genuine relevant duration change after the cron still requires confirmation with a concrete explanation');

clockContext();
PreviewClockTargetTimes::$context->previous = array(3 => array(2 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$preview = clockPreview();
$groups = $preview->_targetTimesReport['operationOrder'];
check(count($groups) === 2, 'A genuinely affected downstream machine is still shown');
check($groups[1]['after'][0]['startChange'] === 1, 'A real downstream delay remains visible');

clockContext();
$context = PreviewClockTargetTimes::$context;
$context->tasks[4] = task(4, 3600, null, 2);
$context->baseline[4] = clone $context->tasks[4];
$context->prepared->assets[2]->manualOrder = array(3, 4);
$context->prepared->assets[2]->packageLinks = array(4 => 3);
$snapshot = serialize($context);
$preview = clockPreview();
check(count($preview->_targetTimesReport['operationOrder']) === 1,
    'An unchanged effective automatic package does not add a foreign machine to the preview');
check(!in_array('Opr4', array_column($preview->_targetTimesReport['operationChanges'], 'operation')),
    'Calculated automatic links are compared with the calculated baseline, not the stored links');
check(serialize($context) === $snapshot, 'Reporting effective packages does not mutate the source context');

clockContext();
$context = PreviewClockTargetTimes::$context;
$context->tasks[1]->assetId = $context->baseline[1]->assetId = 2;
$context->tasks[2]->assetId = $context->baseline[2]->assetId = 2;
$context->tasks[3]->assetId = $context->baseline[3]->assetId = 1;
$context->prepared->assets[1]->manualOrder = array(3);
$context->prepared->assets[2]->manualOrder = array(1, 2);
$context->previous = array(3 => array(2 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$result = PreviewClockTargetTimes::simulate((object)array('id' => 2, 'assetId' => 2, 'timeStart' => '2026-10-08 15:00:00'));
$reportMethod = new ReflectionMethod('planning_TargetTimes', 'proposalReport');
$reportMethod->setAccessible(true);
$report = $reportMethod->invoke(null, $result, (object)array('id' => 2, 'assetId' => 2, 'timeStart' => '2026-10-08 15:00:00'), (object)array());
check($report['operationOrder'][0]['assetId'] === 2 && $report['operationOrder'][0]['edited'],
    'The edited machine comes first even when its numeric id is larger');
check(count($report['operationOrder']) === 2 && $report['operationOrder'][1]['changedCount'] > 0,
    'A genuine downstream effect is retained with a concrete changed-operation count');
check($report['currentStart'] === '08.10.2026 09:00', 'The summary uses the current calculated start');

$rows = array();
for ($id = 1; $id <= 40; $id++) $rows[$id] = task($id, 300);
$context = planContext($rows);
$context->comparison = $context->prepared;
$result = proposal($context, 20, '2026-10-08 09:35:00');
$report = $reportMethod->invoke(null, $result, (object)array('id' => 20, 'assetId' => 1, 'timeStart' => '2026-10-08 09:35:00'), (object)array());
$before = $report['operationOrder'][0]['before'];
$focused = array_values(array_filter($before, function ($row) { return $row['focus']; }));
check(count($focused) < count($before) && count($before) === 40, 'Compact context retains every row for explicit expansion');
$edited = array_values(array_filter($before, function ($row) { return $row['id'] === 20; }));
check($edited[0]['number'] <= count($focused), 'Short reference numbers are assigned to the visible context first');
check(count(array_filter($report['operationChanges'], function ($row) { return !empty($row['pinChanged']) && $row['operation'] !== 'Opr20'; })) === 0,
    'The edited pin is not reported as a foreign fixed-start change');

$context = planContext(array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 10:00:00')), array(2 => 1));
$context->comparison = $context->prepared;
$result = proposal($context, 1, '2026-10-08 14:00:00', 'move');
$report = $reportMethod->invoke(null, $result, (object)array('id' => 1, 'assetId' => 1, 'timeStart' => '2026-10-08 14:00:00'), (object)array());
check($report['operationOrder'][0]['foreignPinCount'] === 1 && $report['operationOrder'][0]['packageCount'] === 0,
    'An explicit foreign pin change is counted separately from the edited pin and intact packages');
$result = proposal($context, 1, '2026-10-08 14:00:00', 'keep', 'detach');
$report = $reportMethod->invoke(null, $result, (object)array('id' => 1, 'assetId' => 1, 'timeStart' => '2026-10-08 14:00:00'), (object)array());
check($report['operationOrder'][0]['packageCount'] === 1 && $report['operationOrder'][0]['foreignPinCount'] === 0,
    'A genuine package detachment remains visible without inventing a foreign pin change');

dt::$now = '2026-10-08 08:00:00';
echo "OK: {$checks} total checks including preview clock and scope\n";
