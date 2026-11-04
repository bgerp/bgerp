<?php

/**
 * Export lifecycle events with native dispatch and isolated persistence fixtures.
 *
 * @category  bgerp
 * @package   export
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// php export/tests/lifecycleHooks.php. No database, filesystem or network changes.
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
function checkLifecycle($value, $message) { global $checks; if (!$value) throw new RuntimeException($message); $checks++; }
function expect($value, $message = '') { if (!$value) throw new RuntimeException($message); }
function countR($value) { return is_array($value) ? count($value) : 0; }
class core_exception_Redirect extends Exception {}
class core_Redirect {}
require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
class core_Mvc extends core_BaseClass
{
    public function __construct($plugins = array()) { $this->_plugins = $plugins; }
}
class core_Master extends core_Mvc {}
class core_Users
{
    public static $current = 7, $sudoCalls = 0;
    public static function getCurrent() { return self::$current; }
    public static function sudo($user) { $old = self::$current; self::$current = $user; self::$sudoCalls++; return $old; }
    public static function exitSudo($user) { self::$current = $user; }
}
class Request
{
    public static $values = array();
    public static function setProtected($fields) {}
    public static function get($name, $type = null) { return self::$values[$name] ?? null; }
    public static function forward($url) { return array($url, core_Users::$current); }
}
class Mode { public static function set($name, $value) {} }
class cls
{
    public static $instances = array();
    public static function get($name) { return is_object($name) ? $name : self::$instances[$name]; }
    public static function getInterface($name, $class)
    {
        $interface = new $name();
        $interface->class = self::get($class);

        return $interface;
    }
}
class doclog_Documents
{
    public static $valid = true, $sender = 19;
    public static function opened($container, $mid)
    {
        return self::$valid ? (object) array('createdBy' => self::$sender) : null;
    }
    public static function popAction() {}
}
class SourceDocument
{
    public function fetch($id) { return (object) array('id' => $id, 'containerId' => 11, 'state' => 'active'); }
    public function getForm() { return new stdClass(); }
}
class fileman_Buckets { public static function fetchByName($name) { return 23; } }
class fileman_Data
{
    public static $new = true;
    public static function absorb($source, $type) { return (object) array('id' => 29, 'new' => self::$new); }
}
class fileman_Log
{
    public static $uploads = array();
    public static function updateLogInfo($fh, $action) { self::$uploads[] = array($fh, $action); }
}
require dirname(__DIR__) . '/ExportTypeIntf.class.php';
require dirname(__DIR__) . '/Export.class.php';
require dirname(__DIR__, 2) . '/fileman/Files.class.php';
class LifecycleFiles extends fileman_Files
{
    public static $created = 0;
    protected static function createFile($name, $bucketId, $dataId) { self::$created++; return 'created-file'; }
    public static function checkFileNameExist($dataId, $bucketId, $inputFileName) { return 'existing-file'; }
}
class LifecycleHooks
{
    public static $active = null, $events = array(), $beginError = null, $externalError = null;
    public static function on_BeginExport($mvc, $scope, $form, $classId, $objectId)
    {
        $scope->previous = self::$active;
        self::$active = is_object($objectId) ? ($objectId->id ?? null) : $objectId;
        self::$events[] = array('begin', self::$active, core_Users::$current);
        if (self::$beginError) throw self::$beginError;
    }
    public static function on_EndExport($mvc, $scope, &$result)
    {
        self::$events[] = array('end', self::$active, $result, core_Users::$current);
        self::$active = $scope->previous ?? null;
    }
    public static function on_BeforeExternalExport($mvc, $source, $rec)
    {
        self::$events[] = array('external', $rec->id ?? null, core_Users::$current);
        if (self::$externalError) throw self::$externalError;
    }
    public static function on_AfterAbsorbFile($mvc, $fh, $bucketId, $dataId)
    {
        self::$events[] = array('file', $fh, $bucketId, $dataId, self::$active);
    }
}
class LifecycleExporter
{
    public $callback, $calls = array();
    public function makeExport($form, $classId, $objectId)
    {
        $this->calls[] = array($form, $classId, $objectId, core_Users::$current);

        return ($this->callback)($form, $classId, $objectId);
    }
    public function logInfo($message) {}
}
function assertLifecycleException($expected, $callback, $message)
{
    $actual = null;
    try { $callback(); } catch (Exception $error) {
        // redirect-catch: terminal - this test checks that the exact redirect propagates.
        $actual = $error;
    }
    checkLifecycle($actual === $expected, $message);
}
$hook = new LifecycleHooks();
$exporter = new LifecycleExporter();
$interface = cls::getInterface('export_ExportTypeIntf', $exporter);
$form = new stdClass();
$record = (object) array('id' => 41);
cls::$instances['export_Export'] = new export_Export();
foreach (array(null, 'cached-file', new core_Redirect()) as $result) {
    $exporter->callback = function () use ($result) { return $result; };
    checkLifecycle($interface->makeExport($form, 31, $record) === $result, 'No-plugin export preserves the exact result');
    $call = end($exporter->calls);
    checkLifecycle($call[0] === $form && $call[1] === 31 && $call[2] === $record, 'No-plugin export keeps its original arguments');
}
checkLifecycle(LifecycleHooks::$events === array(), 'No-plugin dispatch has no policy side effects');
cls::$instances['export_Export'] = new export_Export(array($hook));
cls::$instances['LifecycleFiles'] = new LifecycleFiles(array($hook));
$exporter->callback = function () {
    fileman_Data::$new = true;
    $first = LifecycleFiles::absorb('synthetic-content', 'synthetic-bucket', 'synthetic.txt', 'string');
    fileman_Data::$new = false;
    $second = LifecycleFiles::absorb('synthetic-content', 'synthetic-bucket', 'synthetic.txt', 'string');
    checkLifecycle($first === 'created-file' && $second === 'existing-file', 'Absorption preserves new and deduplicated handles');

    return 'cached-final';
};
checkLifecycle($interface->makeExport($form, 31, 41) === 'cached-final', 'A cached final file is available to the end hook');
checkLifecycle(LifecycleHooks::$events === array(array('begin', 41, 7), array('file', 'created-file', 23, 29, 41),
    array('file', 'existing-file', 23, 29, 41), array('end', 41, 'cached-final', 7)),
    'New, deduplicated and cached artifacts all belong to the active export scope');
checkLifecycle(LifecycleFiles::$created === 1 && count(fileman_Log::$uploads) === 2, 'File creation and existing upload logging are unchanged');
checkLifecycle(LifecycleHooks::$active === null, 'Successful export restores its previous context');

$inner = new LifecycleExporter();
$inner->callback = function () { checkLifecycle(LifecycleHooks::$active === 43, 'Nested exporter receives its own context'); return 'inner-file'; };
$exporter->callback = function () use ($inner, $form) {
    cls::getInterface('export_ExportTypeIntf', $inner)->makeExport($form, 31, 43);
    checkLifecycle(LifecycleHooks::$active === 41, 'Nested export restores the outer context');

    return 'outer-file';
};
checkLifecycle($interface->makeExport($form, 31, 41) === 'outer-file' && LifecycleHooks::$active === null,
    'Nested scopes preserve the outer result and restore the caller context');
foreach (array(new RuntimeException('synthetic failure'), new core_exception_Redirect('synthetic redirect')) as $error) {
    foreach (array('begin', 'body') as $phase) {
        LifecycleHooks::$events = array();
        LifecycleHooks::$beginError = $phase === 'begin' ? $error : null;
        $exporter->callback = function () use ($error) { throw $error; };
        $before = count($exporter->calls);
        assertLifecycleException($error, function () use ($interface, $form) { $interface->makeExport($form, 31, 41); },
            'The original ' . $phase . ' failure/redirect propagates');
        checkLifecycle(LifecycleHooks::$active === null && end(LifecycleHooks::$events) === array('end', 41, null, 7),
            'The end hook restores even a partially entered scope');
        checkLifecycle(count($exporter->calls) === $before + ($phase === 'body' ? 1 : 0), 'A denied begin never runs the exporter');
    }
}
LifecycleHooks::$beginError = null;
cls::$instances[31] = new SourceDocument();
cls::$instances['syntheticExporter'] = $exporter;
Request::$values = array('objId' => 41, 'clsId' => 31, 'mid' => 'synthetic-mid', 'typeCls' => 'syntheticExporter');
$dispatcher = cls::$instances['export_Export'];
LifecycleHooks::$events = array();
$exporter->callback = function () { return 'external-file'; };
$forward = $dispatcher->act_ExportInExternal();
checkLifecycle(LifecycleHooks::$events === array(array('external', 41, 7), array('begin', 41, 19), array('end', 41, 'external-file', 19)),
    'External access hook sees the requester before export runs with the existing sender identity');
checkLifecycle($forward === array(array('fileman_Download', 'download', 'fh' => 'external-file', 'forceDownload' => true), 7),
    'External download forwarding occurs only after restoring the requester');
foreach (array(new RuntimeException('external failure'), new core_exception_Redirect('external redirect')) as $error) {
    $exporter->callback = function () use ($error) { throw $error; };
    assertLifecycleException($error, function () use ($dispatcher) { $dispatcher->act_ExportInExternal(); },
        'External errors and redirects retain their exact identity');
    checkLifecycle(core_Users::$current === 7 && LifecycleHooks::$active === null, 'External failure restores sudo and export context');
}
$denied = new RuntimeException('external denied');
LifecycleHooks::$externalError = $denied;
$sudoBefore = core_Users::$sudoCalls;
$callsBefore = count($exporter->calls);
assertLifecycleException($denied, function () use ($dispatcher) { $dispatcher->act_ExportInExternal(); }, 'External policy denial propagates before sudo');
checkLifecycle(core_Users::$sudoCalls === $sudoBefore && count($exporter->calls) === $callsBefore,
    'External policy refusal neither impersonates the sender nor generates a file');
LifecycleHooks::$externalError = null;
LifecycleHooks::$events = array();
doclog_Documents::$valid = false;
$invalidMid = false;
try { $dispatcher->act_ExportInExternal(); } catch (RuntimeException $error) { $invalidMid = true; }
checkLifecycle($invalidMid && LifecycleHooks::$events === array() && core_Users::$sudoCalls === $sudoBefore
    && count($exporter->calls) === $callsBefore, 'Invalid external links remain blocked by native validation before hooks and sudo');
echo "PASS: {$checks} export lifecycle checks\n";
