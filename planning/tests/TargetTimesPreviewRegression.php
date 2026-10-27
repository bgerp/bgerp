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
PreviewClockTargetTimes::$context->previous = array(3 => array(2 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$preview = clockPreview();
$groups = $preview->_targetTimesReport['operationOrder'];
check(count($groups) === 2, 'A genuinely affected downstream machine is still shown');
check($groups[1]['after'][0]['startChange'] === 1, 'A real downstream delay remains visible');

dt::$now = '2026-10-08 08:00:00';
echo "OK: {$checks} total checks including preview clock and scope\n";
