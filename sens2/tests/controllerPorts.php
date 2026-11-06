<?php

// php -n sens2/tests/controllerPorts.php - native port discovery and signal form,
// with synthetic records and drivers; no database, application or device calls.
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });

$checks = 0;
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function countR($value) { return is_array($value) || $value instanceof Countable ? count($value) : 0; }
function expect($condition, ...$context) { if (!$condition) throw new RuntimeException('Missing controller driver'); }
class TestRedirect extends RuntimeException {}
function redirect($url, $unused, $message) { throw new TestRedirect($message); }

class core_Master
{
    public static $records = array();
    public static function fetch($id) { return self::$records[$id] ?? false; }
    public static function getQuery() { return new TestControllerQuery(self::$records); }
}
class TestControllerQuery
{
    private $records;
    public function __construct($records) { $this->records = array_values($records); }
    public function fetch($condition)
    {
        check($condition === "#state = 'active'", 'Output options retain the active-controller filter');
        while ($record = array_shift($this->records)) {
            if ($record->state === 'active') return $record;
        }
        return false;
    }
}
class cls
{
    public static $drivers = array();
    public static function load($name, $silent = false) { return isset(self::$drivers[$name]); }
    public static function get($name) { return self::$drivers[$name]; }
}
class TestDriver
{
    public $driverRec;
    public $inputs = array();
    public $outputs = array();
    public function getInputPorts($config = null) { return $this->inputs; }
    public function getOutputPorts($config = null) { return $this->outputs; }
    public function readInputs() { throw new RuntimeException('Discovery must not read devices'); }
    public function writeOutputs() { throw new RuntimeException('Discovery must not control devices'); }
}
class sens2_script_Helper { public static function getSuggestions($scriptId) { return array(); } }
class TestForm
{
    public $rec;
    public $options = array();
    public function __construct() { $this->rec = (object) array('scriptId' => 1); }
    public function FLD($name, $type, $params) {}
    public function setOptions($name, $options) { $this->options[$name] = $options; }
    public function setSuggestions($name, $options) {}
}

require dirname(__DIR__) . '/Controllers.class.php';
require dirname(__DIR__) . '/script/ActionSignal.class.php';

$driver = new TestDriver();
$driver->inputs = array('temperature' => (object) array('caption' => 'Temperature', 'uom' => 'C'));
$driver->outputs = array('relay' => (object) array('caption' => 'Relay', 'uom' => 'bool'));
cls::$drivers['test_Driver'] = $driver;
core_Master::$records = array(
    1 => (object) array('id' => 1, 'name' => 'Missing', 'driver' => 'missing_Driver', 'state' => 'active', 'config' => (object) array()),
    2 => (object) array('id' => 2, 'name' => 'Room', 'driver' => 'test_Driver', 'state' => 'active', 'config' => (object) array('relay_name' => 'Door', 'relay_uom' => 'state')),
    3 => (object) array('id' => 3, 'name' => 'Closed', 'driver' => 'test_Driver', 'state' => 'closed', 'config' => (object) array()),
    4 => (object) array('id' => 4, 'name' => 'NoDriver', 'state' => 'active'),
);
$original = serialize(core_Master::$records);
check(sens2_Controllers::getActivePorts(1, 'outputs') === array(), 'Unavailable drivers do not prevent port discovery');
check(sens2_Controllers::getActivePorts(4) === array(), 'A controller without a driver has no ports');
check(sens2_Controllers::getActivePorts(99) === array(), 'A missing controller has no ports');
$strict = false;
try { sens2_Controllers::getDriver(1); } catch (RuntimeException $e) { $strict = true; }
check($strict, 'Explicit strict driver lookup still reports a missing driver');
$ports = sens2_Controllers::getActivePorts(2);
check(array_keys($ports) === array('temperature', 'relay'), 'Healthy controllers retain input and output discovery');
check($ports['relay']->title === '$Room.Door' && $ports['relay']->uom === 'state', 'Configured output aliases and units are preserved');
check($ports['temperature']->title === '$Room.temperature' && $ports['temperature']->uom === 'C', 'Unnamed inputs retain the native title and unit');
check(array_keys(sens2_Controllers::getActivePorts(2, 'inputs')) === array('temperature'), 'Input-only discovery excludes outputs');
check(sens2_script_ActionSignal::getOutputOpts() === array('$Room.Door' => '$Room.Door'), 'A missing driver does not hide healthy outputs or include inputs/closed controllers');
$form = new TestForm();
(new sens2_script_ActionSignal())->prepareActionForm($form);
check($form->options['output'] === array('' => '', '$Room.Door' => '$Room.Door'), 'The native signal form remains usable despite an unavailable driver');
check(serialize(core_Master::$records) === $original, 'Discovery does not change controllers or their configuration');
unset(core_Master::$records[2]);
check(sens2_script_ActionSignal::getOutputOpts() === array(), 'Only unavailable or closed controllers produce an empty option list');
$redirected = false;
try { (new sens2_script_ActionSignal())->prepareActionForm($form); } catch (TestRedirect $e) { $redirected = true; }
check($redirected, 'With no outputs the signal form keeps its native guidance redirect');
core_Master::$records = array();
check(sens2_script_ActionSignal::getOutputOpts() === array(), 'An installation without controllers returns an empty array');

echo "OK: {$checks} controller port checks.\n";
