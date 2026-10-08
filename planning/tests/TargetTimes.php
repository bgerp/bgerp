<?php

/**
 * Изолирани регресии на планировчика, без приложение и без записи в база.
 * Изпълнение: php planning/tests/TargetTimes.php
 */
error_reporting(E_ALL);
set_error_handler(function($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
date_default_timezone_set('Europe/Sofia');
class core_BaseClass extends stdClass {}
class core_Master extends core_BaseClass {}
class Mode { public static function is($name) { return false; } }
class core_Debug {
    public static $timers = array();
    public static function startTimer($name) {}
    public static function stopTimer($name) {}
    public static function log($message) {}
}
class planning_Steps { public static function getInterruptionArr($tasks) { return array(); } }
class FixtureQuery {
    private $rows;
    private $index = 0;
    public function __construct($rows) { $this->rows = $rows; }
    public function in($field, $values) {}
    public function where($condition) {}
    public function show($fields) {}
    public function EXT($name, $class, $params) {}
    public function groupBy($field) {}
    public function fetchAll() { return $this->rows; }
    public function fetch() { $rows = array_values($this->rows); return $rows[$this->index++] ?? false; }
}
class core_App { public static function setTimeLimit($seconds, $reset, $minimum) {} }
class planning_Setup { public static function get($key) { return $key == 'MIN_TASK_DURATION' ? 60 : null; } }
class planning_AssetResources { public static function getQuery() { return new FixtureQuery(array(1 => (object)array('id' => 1, 'simultaneity' => 2))); } }
class planning_Jobs { public static function getQuery() { return new FixtureQuery(array()); } }
class cat_products_Packagings { public static function getQuery() { return new FixtureQuery(array()); } }
class planning_ProductionTaskProducts {
    public static $rows = array();
    public static function getQuery() { return new FixtureQuery(self::$rows); }
}
class planning_type_ProductionRate { public static function getInSecsByQuantity($rate, $quantity) { return $rate * $quantity; } }
class planning_Tasks {
    public static $records = array();
    public static $saved = array();
    public static $reorderAllowed = true;
    public static function fetch($id) { return isset(self::$records[$id]) ? clone self::$records[$id] : null; }
    public static function getQuery() { return new FixtureQuery(self::$records); }
    public static function haveRightFor($action, $rec, $userId = 7) { return $action == 'savereordertasks' ? self::$reorderAllowed : $userId > 0; }
    public static function getTargetTimesPreviewReport($tasks, $scheduled, $now, $assetId, $baseline) { return array(); }
    public function save_(&$rec, $fields = null, $mode = null) {
        self::$saved[] = array(clone $rec, $fields);
        self::$records[$rec->id ?? null]->targetStartConflict = $rec->targetStartConflict ?? null;
    }
}
class Request { public static function get($name) { return null; } }
class cls { public static function get($name) { return new $name(); } }
class core_Users {
    public static $systemDepth = 0;
    public static $inactive = array();
    public static function getByRole($role) { return array(7 => 7); }
    public static function isActiveUserId($id) { return !in_array($id, self::$inactive); }
    public static function forceSystemUser() { self::$systemDepth++; }
    public static function cancelSystemUser() { self::$systemDepth--; }
}
class bgerp_Notifications {
    public static $added = array();
    public static $cleared = 0;
    public static function add($message, $url, $user, $priority, $singleUrl, $once) { self::$added[] = array($message, $user, $once); }
    public static function clear($url, $user) { self::$cleared++; }
}
class dt {
    public static function addSecs($secs, $date = null) { return date('Y-m-d H:i:s', strtotime($date) + $secs); }
    public static function mysql2UnixDays($date) { return (int)(strtotime($date) / 86400); }
    public static function mysql2verbal($date, $format) { return date($format, strtotime($date)); }
}
class arr {
    public static function make($value, $keys = false) {
        if (is_array($value)) return $value;
        $result = array();
        foreach (explode(',', $value ?? '') as $v) if (trim($v) !== '') $result[trim($v)] = trim($v);
        return $result;
    }
    public static function sortObjects(&$values, $field, $direction = 'ASC') {
        uasort($values, function($a, $b) use ($field, $direction) { return (($a->{$field} ?? null) <=> ($b->{$field} ?? null)) * ($direction == 'ASC' ? 1 : -1); });
    }
    public static function extractValuesFromArray($rows, $field) {
        $result = array();
        foreach ($rows as $row) if (isset($row->{$field})) $result[$row->{$field}] = $row->{$field};
        return $result;
    }
    public static function sumValuesArray($rows, $field) { return array_sum(array_column($rows, $field)); }
}
function countR($value) { return is_countable($value) ? count($value) : 0; }
function expect($condition, ...$values) { if (!$condition) throw new RuntimeException('expect failed'); }
function getRetUrl() { return array(); }
function toUrl($url) { return '/'; }
require dirname(__DIR__, 2) . '/core/Intervals.class.php';
require dirname(__DIR__) . '/TargetTimes.class.php';
require dirname(__DIR__) . '/TaskManualOrderPerAssets.class.php';
require dirname(__DIR__) . '/TaskConstraints.class.php';

class FixtureTargetTimes extends planning_TargetTimes {
    public static $simulation;
    public static $calls = 0;
    public static function simulate($rec, $policy = 'keep') { self::$calls++; return self::$simulation; }
}
class FixtureForm extends stdClass {
    public $errors = array();
    public $warnings = array();
    public $ignore = true;
    public $cmd = 'save_pending';
    public function setError($field, $message) { $this->errors[$field] = $message; }
    public function setWarning($field, $message) { $this->warnings[$field] = $message; }
    public function gotErrors() { return !empty($this->errors) || (!$this->ignore && !empty($this->warnings)); }
    public function setField($field, $params) {}
}

$checks = 0;
function check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function task($id, $duration = 3600, $pin = null, $asset = 1) {
    return (object)array('id' => $id, 'assetId' => $asset, 'productId' => $id, 'originId' => $id, 'saoOrder' => 1,
        'state' => 'pending', 'actualStart' => null, 'timeStart' => $pin, 'calcedCurrentDuration' => $duration,
        'dueDate' => '2026-10-09', '_planningParamValues' => array());
}
function graph($tasks, $links = array(), $previous = array(), $frames = null, $anchors = array(), $manualOrder = true) {
    $assets = $intervals = array();
    foreach ($tasks as $task) {
        $assets[$task->assetId] = (object)array('id' => $task->assetId, 'code' => 'TEST', 'packageLinks' => $links,
            'anchorLinks' => $anchors, 'manualOrder' => $manualOrder === true ? array_keys($tasks) : $manualOrder, 'autoGroupVersion' => 1,
            '_currentAutoGroupSettingsHash' => 'test', 'autoGroupSettingsHash' => 'test', 'planningParams' => null);
        if (!isset($intervals[$task->assetId])) {
            $intervals[$task->assetId] = new core_Intervals();
            foreach ($frames ?? array(array('08:00:00', '20:00:00')) as $frame) {
                $intervals[$task->assetId]->add(strtotime('2026-10-08 ' . $frame[0]), strtotime('2026-10-08 ' . $frame[1]));
            }
        }
    }
    $method = new ReflectionMethod('planning_TaskConstraints', 'smartPlanningGraph');
    $method->setAccessible(true);
    $planned = $notPlanned = array();
    $args = array(&$planned, &$notPlanned, &$intervals, $assets, $tasks, '2026-10-08 08:00:00', $previous);
    $method->invokeArgs(null, $args);
    $result = array();
    foreach ($planned as $assetTasks) foreach ($assetTasks as $id => $task) $result[$id] = $task;
    return $result;
}
function startAt($result, $id, $time) { check(($result[$id]->expectedTimeStart ?? null) == '2026-10-08 ' . $time, "Opr{$id} must start {$time}"); }

$result = graph(array(2 => task(2), 1 => task(1)));
startAt($result, 2, '08:00:00');
startAt($result, 1, '09:00:00');
$result = graph(array(1 => task(1, 7200), 2 => task(2, 3600, '2026-10-08 09:00:00')));
startAt($result, 2, '09:00:00');
startAt($result, 1, '10:00:00');
$result = graph(array(1 => task(1, 1800), 2 => task(2, 3600, '2026-10-08 09:00:00')));
startAt($result, 1, '08:00:00');

// Преместването след фиксиран пакет не е пакетна връзка, но е задължителен ред.
$startedRow = task(2);
$startedRow->state = 'active';
$startedRow->actualStart = '2026-10-08 08:00:00';
$rows = array(2 => $startedRow, 4 => task(4), 8 => task(8, 3600, '2026-10-08 15:00:00'), 3 => task(3), 7 => task(7));
$links = array(3 => 8, 7 => 3);
$beforeMove = graph($rows, $links);
startAt($beforeMove, 4, '09:00:00');
$movedRows = array(2 => $rows[2], 8 => $rows[8], 3 => $rows[3], 7 => $rows[7], 4 => $rows[4]);
$afterMove = graph($movedRows, $links);
startAt($afterMove, 4, '18:00:00');
check($afterMove[4]->expectedTimeEnd > $beforeMove[7]->expectedTimeEnd, 'Moving a free task after a fixed package extends the plan horizon');
check(empty($rows[4]->timeStart) && $links === array(3 => 8, 7 => 3), 'Manual position does not create a target start or a package link');
$rowsWithGapTask = array(2 => $rows[2], 5 => task(5, 1800), 8 => $rows[8], 3 => $rows[3], 7 => $rows[7], 4 => $rows[4]);
$result = graph($rowsWithGapTask, $links);
startAt($result, 5, '09:00:00');
startAt($result, 4, '18:00:00');
$result = graph($movedRows + array(5 => task(5)), $links + array(5 => 4));
startAt($result, 4, '18:00:00');
startAt($result, 5, '19:00:00');
$tailPinnedRows = array(2 => $rows[2], 8 => task(8), 3 => task(3), 7 => task(7, 3600, '2026-10-08 17:00:00'), 4 => $rows[4]);
$result = graph($tailPinnedRows, $links);
startAt($result, 8, '15:00:00');
startAt($result, 4, '18:00:00');
$result = graph($movedRows, $links, array(), null, array(), false);
startAt($result, 4, '09:00:00');
$result = graph($movedRows, $links, array(), array(array('08:00:00', '17:59:59'), array('+1 day 08:00:00', '+1 day 17:59:59')));
check($result[4]->expectedTimeStart == '2026-10-09 08:00:00', 'After-package task respects the next working day');
$rows[4]->_manualOrderReadyAfter = '2026-10-08 19:00:00';
$result = graph($rows, $links);
startAt($result, 4, '09:00:00');
unset($rows[4]->_manualOrderReadyAfter);
$clearedRows = $movedRows;
$clearedRows[8] = clone $clearedRows[8];
$clearedRows[8]->timeStart = null;
$result = graph($clearedRows, $links);
startAt($result, 8, '09:00:00');
startAt($result, 4, '12:00:00');
$previousForPin = array(8 => array(4 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$result = graph($movedRows + array(5 => task(5, 3600, null, 2)), $links, $previousForPin);
check(!empty($result[8]->targetStartConflict), 'Manual order conflicting with a technological predecessor is diagnosed');
check($result[4]->expectedTimeEnd <= $result[8]->expectedTimeStart, 'Technological dependencies survive conflicting manual order');
startAt($result, 5, '08:00:00');
$result = graph(array(1 => task(1, 3600, '2026-10-08 12:00:00'), 2 => task(2, 3600, '2026-10-08 09:00:00')));
check(!empty($result[2]->targetStartConflict) && empty($result[1]->targetStartConflict), 'Two targets contradicting manual order are diagnosed');
startAt($result, 2, '13:00:00');
// Секундите не създават фалшив конфликт при точно съседни фиксирани пакети.
$result = graph(array(1 => task(1, 40, '2026-10-08 09:00:00'), 2 => task(2, 20), 3 => task(3, 60, '2026-10-08 09:01:00')), array(3 => 2));
check(empty($result[1]->targetStartConflict) && empty($result[3]->targetStartConflict), 'Adjacent pinned packages preserve raw interval precision');

$result = graph(array(1 => task(1), 2 => task(2, 3600, '2026-10-08 09:00:00')), array(2 => 1));
startAt($result, 1, '08:00:00');
startAt($result, 2, '09:00:00');
check(empty($result[2]->targetStartConflict), 'Whole package meets tail pin');
$result = graph(array(1 => task(1), 2 => task(2, 3600, '2026-10-08 12:00:00')), array(2 => 1), array(), array(array('08:00:00', '08:59:59'), array('12:00:00', '16:00:00')));
startAt($result, 1, '08:00:00');
startAt($result, 2, '12:00:00');
$previous = array(2 => array(1 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$result = graph(array(1 => task(1, 7200, null, 2), 2 => task(2, 3600, '2026-10-08 09:00:00')), array(), $previous);
startAt($result, 2, '10:00:00');
check(!empty($result[2]->targetStartConflict), 'Dependency causes safe fallback');
$result = graph(array(3 => task(3, 7200, null, 2), 1 => task(1, 3600, null, 2), 2 => task(2, 3600, '2026-10-08 09:00:00')), array(), $previous);
startAt($result, 1, '08:00:00');
startAt($result, 2, '09:00:00');
check(empty($result[2]->targetStartConflict), 'Predecessor can move earlier');
$result = graph(array(1 => task(1, 3600, '2026-10-08 09:00:00'), 2 => task(2, 3600, '2026-10-08 09:30:00')));
startAt($result, 1, '09:00:00');
check(!empty($result[2]->targetStartConflict), 'Overlapping fixed reservations are diagnosed');
check($result[2]->expectedTimeStart >= $result[1]->expectedTimeEnd, 'Conflicting targets do not overlap');
$active = task(1, 7200);
$active->state = 'active';
$active->actualStart = '2026-10-08 07:00:00';
$result = graph(array(1 => $active, 2 => task(2, 3600, '2026-10-08 09:00:00')));
startAt($result, 1, '08:00:00');
check(!empty($result[2]->targetStartConflict), 'Started operation stays protected');
$stopped = clone $active;
$stopped->state = 'stopped';
$result = graph(array(2 => task(2, 3600, '2026-10-08 08:00:00'), 1 => $stopped));
startAt($result, 2, '08:00:00');
$a = task(1, 3600, '2026-10-08 09:00:00');
$b = task(2, 3600, '2026-10-08 10:30:00');
$result = graph(array(1 => $a, 2 => $b), array(2 => 1));
check(!empty($result[1]->targetStartConflict) && empty($result[2]->targetStartConflict), 'Contradictory package pins are diagnosed without releasing every reservation');
startAt($result, 2, '10:30:00');
$previous = array(1 => array(3 => (object)array('intersect' => 'no', 'waitingTime' => 0)));
$result = graph(array(1 => task(1, 7200, '2026-10-08 09:00:00'), 2 => task(2, 3600, '2026-10-08 10:00:00'), 3 => task(3, 7200, null, 2)), array(), $previous);
check(!empty($result[1]->targetStartConflict) && empty($result[2]->targetStartConflict), 'Impossible predecessor reservation does not displace another feasible target');
startAt($result, 2, '10:00:00');
$cycle = array(1 => array(2 => (object)array('intersect' => 'no')), 2 => array(1 => (object)array('intersect' => 'no')));
$result = graph(array(1 => task(1, 3600, '2026-10-08 09:00:00'), 2 => task(2, 3600, null, 2), 3 => task(3)), array(), $cycle);
check(!empty($result[1]->targetStartConflict), 'Cycle is diagnosed');
startAt($result, 3, '08:00:00');
check(planning_TargetTimes::needsDurationWarning(100, 151, 'stopped'), 'Over 50 percent warning');
check(!planning_TargetTimes::needsDurationWarning(100, 150, 'stopped'), 'Exactly 50 percent no warning');
check(!planning_TargetTimes::needsDurationWarning(100, 49, 'pending'), 'Threshold applies only to stopped');
check(planning_TargetTimes::needsDurationWarning(null, 100, 'pending'), 'Entering whole duration warns');
check(planning_TargetTimes::needsDurationWarning(100, null, 'pending'), 'Returning to norm warns');
check(planning_TargetTimes::getJobTime((object)array('state' => 'closed', 'timeClosed' => '2026-10-08 11:00:00'))[0] == '2026-10-08 11:00:00', 'Closed displays actual end');
check(planning_TargetTimes::getJobTime((object)array('state' => 'stopped', 'activatedOn' => '2026-10-08 10:00:00', 'firstProgress' => '2026-10-08 09:00:00'))[0] == '2026-10-08 09:00:00', 'Earliest factual start is retained');

$result = graph(array(1 => $active, 2 => task(2, 3600, '2026-10-08 11:00:00'), 3 => task(3)), array(2 => 1));
check(!empty($result[2]->targetStartConflict), 'Pin cannot interrupt a package with a started prefix');
startAt($result, 2, '10:00:00');
startAt($result, 3, '11:00:00');
$result = graph(array(1 => $active, 2 => task(2, 3600, '2026-10-08 10:00:00')), array(2 => 1));
check(empty($result[2]->targetStartConflict), 'Pin immediately after started package is feasible');
$shortActive = clone $active;
$shortActive->calcedCurrentDuration = 7205;
$result = graph(array(1 => $shortActive, 2 => task(2, 3600, '2026-10-08 10:01:00'), 3 => task(3, 30)), array(2 => 1));
check(empty($result[2]->targetStartConflict), 'Next whole minute after started prefix is feasible');
startAt($result, 3, '11:01:00');
$result = graph(array(1 => task(1, 3600, '2026-10-08 07:00:00')));
check(!empty($result[1]->targetStartConflict), 'Past target is diagnosed');
startAt($result, 1, '08:00:00');
$result = graph(array(1 => task(1, 3600, '2026-10-08 09:00:00'), 2 => task(2, 3600, '2026-10-08 10:00:00')), array(2 => 1));
check(empty($result[1]->targetStartConflict) && empty($result[2]->targetStartConflict), 'Consistent package pins are kept');
$calendar = new core_Intervals();
$calendar->add(strtotime('2026-10-08 08:00:00'), strtotime('2026-10-08 08:59:59'));
$reservations = array();
$before = serialize($calendar);
check(planning_TargetTimes::reserveRun(array(1), array(1 => task(1, 7200, '2026-10-08 08:00:00')), $calendar, $reservations, '2026-10-08 08:00:00') === false, 'Insufficient calendar capacity rejects exact reservation');
check(serialize($calendar) === $before && !$reservations, 'Failed reservation is atomic');
$normalized = planning_TargetTimes::normalizeManualTimes(array('expectedTimeStart' => array(1 => '2026-10-08T09:30', 2 => null)));
check($normalized['expectedTimeStart'][1] === '2026-10-08 09:30:00' && $normalized['expectedTimeStart'][2] === null, 'Manual ISO date and removal are normalized');

$pin = task(2, 3600, '2026-10-08 12:00:00');
$pin->originId = 1;
$pin->saoOrder = 2;
$prev = task(1);
$prev->originId = 1;
planning_Tasks::$records = array(1 => $prev, 2 => $pin);
$tasks = planning_Tasks::$records;
$previous = array(2 => array(1 => (object)array('waitingTime' => 7200, 'intersect' => 'yes')));
planning_TargetTimes::addJobPredecessors($tasks, $previous);
check($previous[2][1]->waitingTime == 7200 && $previous[2][1]->intersect == 'no', 'Job predecessor preserves technological waiting time');
planning_Tasks::$records[1]->state = 'closed';
planning_Tasks::$records[1]->timeClosed = '2026-10-08 11:00:00';
planning_TargetTimes::addJobPredecessors($tasks, $previous);
check($tasks[2]->_targetReadyAfter == '2026-10-08 13:00:00', 'Closed predecessor uses actual end and technological wait');

$conflict = array('requested' => '2026-10-08 09:00:00', 'reason' => 'test', 'earliest' => '2026-10-08 10:00:00');
planning_Tasks::$records[2] = (object)array('id' => 2, 'state' => 'pending', 'targetStartConflict' => $conflict, 'targetStartBy' => 7);
planning_TargetTimes::updateNotification(2);
check(planning_Tasks::$records[2]->targetStartConflict === $conflict + array('notified' => true), 'Notification marker is saved without changing conflict details');
check(count(planning_Tasks::$saved) == 1 && planning_Tasks::$saved[0][1] === 'targetStartConflict'
    && get_object_vars(planning_Tasks::$saved[0][0]) === array('id' => 2, 'targetStartConflict' => $conflict + array('notified' => true)), 'Notification save updates only the conflict field of the correct operation');
planning_TargetTimes::updateNotification(2);
check(count(planning_Tasks::$saved) == 1, 'Repeated notification check does not rewrite the saved marker');
check(count(bgerp_Notifications::$added) == 1, 'Repeated recalculation does not duplicate a conflict notification');
check(bgerp_Notifications::$added[0][0] === 'Opr2 - желаното начало 08.10.2026 09:00 не може да бъде спазено.', 'Notification uses agreed text and requested time');
check(core_Users::$systemDepth === 0, 'System user is restored after notification');
planning_Tasks::$records[2]->targetStartConflict = null;
planning_TargetTimes::updateNotification(2);
check(bgerp_Notifications::$cleared == 1, 'Resolved conflict clears notification');
planning_Tasks::$records[2]->targetStartConflict = $conflict;
planning_TargetTimes::updateNotification(2);
check(count(bgerp_Notifications::$added) == 2, 'New conflict episode notifies again');
planning_Tasks::$records[2]->state = 'closed';
planning_TargetTimes::updateNotification(2);
check(bgerp_Notifications::$cleared == 2, 'Closed operation clears notification');
planning_Tasks::$records[2]->state = 'pending';
planning_Tasks::$records[2]->targetStartConflict = $conflict;
planning_Tasks::$records[2]->targetStartBy = 5;
planning_Tasks::$records[2]->modifiedBy = 6;
core_Users::$inactive = array(5);
planning_TargetTimes::updateNotification(2);
check(end(bgerp_Notifications::$added)[1] == 6, 'Inactive target setter does not swallow the notification');
core_Users::$inactive = array();

$duration = task(1);
$duration->timeDuration = 36000;
$duration->progress = 0.9;
$duration->measureId = $duration->indPackagingId = 2;
$duration->plannedQuantity = 100;
$duration->indTime = 300;
$duration->simultaneity = null;
planning_ProductionTaskProducts::$rows = array((object)array('taskId' => 1, 'productId' => 5, 'plannedQuantity' => 1, 'indTime' => 3600, 'totalTime' => 2400));
$durationTasks = array(1 => $duration);
planning_TaskConstraints::calculateTaskDurations($durationTasks);
check($duration->calcedDuration == 39600 && $duration->calcedCurrentDuration == 4800, 'Manual whole duration overrides norm, deducts progress, and adds only remaining actions');
$duration->timeDuration = null;
$duration->progress = 0.5;
planning_TaskConstraints::calculateTaskDurations($durationTasks);
check($duration->calcedDuration == 18600 && $duration->calcedCurrentDuration == 8700, 'Clearing target duration restores norm and machine simultaneity');
$duration->_newPlanningActions = array(7 => 600);
planning_TaskConstraints::calculateTaskDurations($durationTasks);
check($duration->calcedDuration == 19200 && $duration->calcedCurrentDuration == 9300, 'Preview includes actions of a new operation without storing them');
planning_TaskConstraints::calculateTaskDurations($durationTasks);
check($duration->calcedCurrentDuration == 9300, 'Repeated duration calculation does not compound actions');

check(planning_TargetTimes::getJobTime((object)array('state' => 'pending', 'expectedTimeStart' => '2026-10-08 12:00:00'))[2] === 'Планирано начало', 'Unstarted operation displays planned start');
check(planning_TargetTimes::getJobTime((object)array('state' => 'closed'))[0] === null, 'Missing actual end is not invented');
// Потвърждението се валидира повторно на сървъра, без запис и без крон.
$form = new FixtureForm();
$form->rec = (object)array('assetId' => 1, 'timeDuration' => 3600);
FixtureTargetTimes::$simulation = (object)array('hash' => 'snapshot-a', 'conflicts' => array(), 'changes' => array(),
    'tasks' => array(), 'baseline' => array(), 'now' => '2026-10-08 08:00:00',
    'scheduled' => (object)array('tasks' => array(1 => array(task(-1)))));
FixtureTargetTimes::$simulation->scheduled->tasks[1][0]->expectedTimeStart = '2026-10-08 08:00:00';
FixtureTargetTimes::$simulation->scheduled->tasks[1][0]->expectedTimeEnd = '2026-10-08 09:00:00';
FixtureTargetTimes::validateForm(new planning_Tasks(), $form);
check($form->gotErrors() && !empty($form->_targetTimesReport) && empty($form->rec->_targetTimesChanged), 'First submission cannot save before preview');
check($form->_targetTimesReport['ignoreWarnings'] === true, 'Preview preserves acknowledged duration warning');
$accepted = new FixtureForm();
$accepted->rec = clone $form->rec;
$accepted->rec->targetPreviewAccepted = 'yes';
FixtureTargetTimes::validateForm(new planning_Tasks(), $accepted);
check(!$accepted->gotErrors() && !empty($accepted->rec->_targetTimesChanged) && FixtureTargetTimes::$calls == 2, 'Apply rechecks fresh simulation and accepts unchanged snapshot');
planning_Tasks::$records[8] = task(8, 3600, '2026-10-08 09:00:00');
$clearing = new FixtureForm();
$clearing->rec = clone planning_Tasks::$records[8];
$clearing->rec->timeStart = null;
FixtureTargetTimes::validateForm(new planning_Tasks(), $clearing);
check($clearing->gotErrors() && !empty($clearing->_targetTimesReport) && empty($clearing->rec->_targetTimesChanged), 'Clearing target start still requires a preview before save');
$clearingAccepted = new FixtureForm();
$clearingAccepted->rec = clone $clearing->rec;
$clearingAccepted->rec->targetPreviewAccepted = 'yes';
FixtureTargetTimes::validateForm(new planning_Tasks(), $clearingAccepted);
check(!$clearingAccepted->gotErrors() && !empty($clearingAccepted->rec->_targetTimesChanged), 'Confirmed removal of target start requests the same recalculation as setting it');
$stale = new FixtureForm();
$stale->rec = clone $form->rec;
$stale->rec->targetPreviewAccepted = 'yes';
FixtureTargetTimes::$simulation->hash = 'snapshot-b';
FixtureTargetTimes::validateForm(new planning_Tasks(), $stale);
check($stale->gotErrors() && $stale->rec->targetPreviewAccepted == 'no' && $stale->rec->targetPreviewHash == 'snapshot-b', 'Concurrent planning change requires a new preview');
$conflicting = new FixtureForm();
$conflicting->rec = clone $accepted->rec;
unset($conflicting->rec->_targetTimesChanged);
FixtureTargetTimes::$simulation->conflicts = array(-1 => $conflict);
FixtureTargetTimes::validateForm(new planning_Tasks(), $conflicting);
check($conflicting->gotErrors() && empty($conflicting->rec->_targetTimesChanged), 'A new conflict blocks Apply even after previous acceptance');
$unauthorized = new FixtureForm();
$unauthorized->rec = (object)array('assetId' => 1, 'timeDuration' => 3600, 'targetPackagePolicy' => 'detach');
planning_Tasks::$reorderAllowed = false;
FixtureTargetTimes::validateForm(new planning_Tasks(), $unauthorized);
check(isset($unauthorized->errors['targetPackagePolicy']), 'Operation edit rights cannot bypass package reorder rights');
planning_Tasks::$reorderAllowed = true;

echo "OK: {$checks} checks\n";
