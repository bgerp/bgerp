<?php

/**
 * CLI регресия на филтъра по зона и група, без база данни.
 * php rack/tests/ZoneGrouping.php [път/до/предишен/Zones.class.php]
 */
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function tr($text) { return $text; }
function expect($condition) { if (!$condition) throw new RuntimeException('Expectation failed'); }

class ZoneGroupingQuery
{
    public $conditions = array();
    public function where($condition) { $this->conditions[] = $condition; return $this; }
    public function orderBy($field, $direction) { return $this; }
    public function fetch() { return false; }
}

class core_Master
{
    public $currentTab;
    public static function getQuery() { return new ZoneGroupingQuery(); }
}

class Request
{
    public static $terminal;
    public static function get($name, $type) { return self::$terminal; }
}

class store_Stores
{
    public static function getCurrent() { return 2; }
    public static function getHyperlink($id, $short) { return 'Test store'; }
}

class rack_ZoneGroups
{
    public static function getQuery() { return new ZoneGroupingQuery(); }
}

class arr
{
    public static function placeInAssocArray(&$fields, $additional, $after) { $fields += $additional; }
}

class ZoneGroupingToolbar
{
    public function addSbBtn($title, $command, $id, $params) {}
}

class ZoneGroupingForm
{
    public $rec;
    public $toolbar;
    public $showFields;
    public $view;
    public function FLD($name, $type, $params) {}
    public function FNC($name, $type, $params) {}
    public function setOptions($name, $options) {}
    public function setDefault($name, $value) {}
    public function input($fields = null, $mode = null) {}
}

require $argv[1] ?? dirname(__DIR__) . '/Zones.class.php';
$hook = new ReflectionMethod('rack_Zones', 'on_AfterPrepareListFilter');
$hook->setAccessible(true);
$checks = $failures = 0;
$cases = array(
    array(null, null),
    array('', null),
    array('no', '#groupId IS NULL'),
    array('free', '#containerId IS NULL'),
    array('notfree', '#containerId IS NOT NULL'),
    array('1', '#groupId = 1'),
    array('2', '#groupId = 2'),
    array('214', '#groupId = 214'),
    array('s1', '#id = 1'),
    array('s214', '#id = 214'),
    array('s321', '#id = 321'),
);

foreach (array(0, 1) as $terminal) {
    Request::$terminal = $terminal;
    foreach ($cases as list($grouping, $expected)) {
        $form = new ZoneGroupingForm();
        $form->rec = (object) array('additional' => 'all');
        if (isset($grouping)) $form->rec->grouping = $grouping;
        $form->toolbar = new ZoneGroupingToolbar();
        $query = new ZoneGroupingQuery();
        $data = (object) array('query' => $query, 'listFilter' => $form, 'listFields' => array());
        $hook->invoke(null, new rack_Zones(), $data);
        $conditions = array('#storeId = 2');
        if (isset($expected)) $conditions[] = $expected;
        $checks++;
        if ($query->conditions !== $conditions) {
            $failures++;
            echo 'FAIL terminal=' . $terminal . ' grouping=' . var_export($grouping, true)
                . ': ' . json_encode($query->conditions) . ' expected ' . json_encode($conditions) . "\n";
        }
    }
}

echo "Zone grouping: {$checks} checks, {$failures} failures (PHP " . PHP_VERSION . ")\n";
exit($failures ? 1 : 0);
