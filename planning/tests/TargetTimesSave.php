<?php

/**
 * Проверява преизчисляването преди пренасочване, без приложение и база.
 * Изпълнение: php planning/tests/TargetTimesSave.php
 */
error_reporting(E_ALL);
set_error_handler(function($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
class core_Master extends stdClass {}
class core_Debug {
    public static function startTimer($name) {}
    public static function stopTimer($name) {}
}
class planning_AssetResources {
    public static $calls = array();
    public function cron_RecalcTaskTimes($options) { self::$calls[] = $options; }
}
class cls { public static function get($name) { return new $name(); } }
function countR($value) { return is_countable($value) ? count($value) : 0; }
function getRetUrl() { return array(); }
require dirname(__DIR__) . '/Tasks.class.php';

class SavedTargetForm extends stdClass {
    public $submitted = true;
    public function isSubmitted() { return $this->submitted; }
}
class SavedTargetTasks extends planning_Tasks {
    public function __construct() {
        $this->forceCalcTimes = true;
        $this->optimizeAssetIds = array(1 => 1);
        $this->commitAfterOptimizeAssetIds = array(1 => 1);
    }
    public function queued() { return !empty($this->forceCalcTimes); }
}
$checks = 0;
function check($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function savedData($start, $changed = true) {
    $form = new SavedTargetForm();
    $form->rec = (object)array('id' => 8, 'timeStart' => $start, '_targetTimesChanged' => $changed);
    return (object)array('form' => $form);
}

$mvc = new SavedTargetTasks();
$data = savedData(null);
planning_Tasks::on_AfterPrepareRetUrl($mvc, null, $data);
check(count(planning_AssetResources::$calls) == 1, 'Clearing target start recalculates before redirect');
check(planning_AssetResources::$calls[0] == array('optimizeAssetIds' => array(1), 'commitAfterOptimizeAssetIds' => array(1)), 'Queued planning options are preserved');
check(!$mvc->queued(), 'Immediate recalculation consumes the shutdown request');
planning_Tasks::on_Shutdown($mvc);
check(count(planning_AssetResources::$calls) == 1, 'Shutdown does not repeat the immediate recalculation');
planning_Tasks::on_AfterPrepareRetUrl($mvc, null, $data);
check(count(planning_AssetResources::$calls) == 1, 'Repeated return URL preparation does not recalculate twice');

foreach (array('2026-10-23 08:00:00', '2026-10-26 08:00:00') as $start) {
    $mvc = new SavedTargetTasks();
    $before = count(planning_AssetResources::$calls);
    planning_Tasks::on_AfterPrepareRetUrl($mvc, null, savedData($start));
    check(count(planning_AssetResources::$calls) == $before + 1 && !$mvc->queued(), 'Adding or changing target start also recalculates before redirect');
}
$mvc = new SavedTargetTasks();
$data = savedData(null);
$data->form->submitted = false;
$before = count(planning_AssetResources::$calls);
planning_Tasks::on_AfterPrepareRetUrl($mvc, null, $data);
check(count(planning_AssetResources::$calls) == $before && $mvc->queued(), 'Unconfirmed preview does not trigger recalculation');

$mvc = new SavedTargetTasks();
planning_Tasks::on_AfterPrepareRetUrl($mvc, null, savedData(null, false));
check(count(planning_AssetResources::$calls) == $before && $mvc->queued(), 'Ordinary saves keep the existing deferred workflow');
planning_Tasks::on_Shutdown($mvc);
check(count(planning_AssetResources::$calls) == $before + 1 && !$mvc->queued(), 'Ordinary deferred recalculation still runs at shutdown');

echo "OK: {$checks} save lifecycle checks\n";
