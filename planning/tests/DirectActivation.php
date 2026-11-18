<?php

/**
 * Директна активация през реалните документни hooks, без база и външни ефекти.
 *
 * @category  bgerp
 * @package   planning
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// php planning/tests/DirectActivation.php [previous-StateManager.class.php]
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
function checkActivation($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function expect($ok, ...$args) { if (!$ok) throw new RuntimeException('Expectation failed'); }
function countR($value) { return is_array($value) ? count($value) : 0; }
function setPartIfNot($object, $property, $value) { $object->{$property} = $object->{$property} ?? $value; }
require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
require dirname(__DIR__, 2) . '/core/Plugin.class.php';
class core_Mvc extends core_BaseClass {}
class core_Manager extends core_Mvc {}
class core_Master extends core_Manager {}
class arr
{
    public static function make($value, $assoc = false)
    {
        $result = is_array($value) ? $value : ($value ? array_map('trim', explode(',', $value)) : array());
        return $assoc && $result ? array_combine($result, $result) : $result;
    }
}
class dt { public static function now() { return '2026-01-01 12:00:00'; } }
class core_Users { public static function getCurrent() { return 7; } }
class core_Locks { public static function obtain(...$args) {} }
class core_Settings { public static function fetchUsers(...$args) { return array(); } }
class doc_Folders { public static function getSettingsKey($id) { return 'fixture'; } }
class doc_Threads { public static function restoreThread($id) {} }
class str { public static function mbUcfirst($value) { return ucfirst($value); } }
class Mode { public static function push(...$args) {} public static function pop(...$args) {} }
class core_Statuses { public static function newStatus(...$args) {} }
class cat_products_Packagings
{
    public static function checkRemoteQuantity(...$args) { return array(); }
    public static function updateFirstDocument(...$args) {}
    public static function checkQuantity(...$args) { return array(); }
}
class doc_Containers
{
    public static $records = array(), $notifications = array();
    public static function update($id, $all, $notModified)
    {
        self::$records[$id]->docId = self::$records[$id]->savedDocumentId ?? null;
    }
    public static function getSubscribedUsers($id, $ignorePartners, $ignoreCurrent)
    {
        checkActivation(!empty(self::$records[$id]->docId), 'Notifications require a completed container');
        return array(9 => 9);
    }
    public static function notifyToSubscribedUsers($id, ...$args) { self::$notifications[] = $id; }
    public static function touchDocumentsByOrigin($id) {}
}
require dirname(__DIR__, 2) . '/doc/DocumentPlg.class.php';
require dirname(__DIR__, 2) . '/doc/ActivatePlg.class.php';
require $argv[1] ?? dirname(__DIR__) . '/plg/StateManager.class.php';
require dirname(__DIR__) . '/Jobs.class.php';

class ActivationForm
{
    public $rec, $cmd = 'active', $submitted = true;
    public function __construct($rec) { $this->rec = $rec; }
    public function isSubmitted() { return $this->submitted; }
}
class ActivationDocument extends core_Manager
{
    public $className = 'ActivationDocument', $mustUpdateUsed = false, $canEditActivated = false;
    public $saveFileArr = array(), $notifyActionNamesArr = array('active' => 'Активиране');
    public $events = array(), $logs = array(), $stored = array(), $activate = true, $nested = false;

    public function __construct($plugins)
    {
        $this->_plugins = $plugins;
    }
    public function activateNow($rec, &$msg) { return $this->activate; }
    public function fetchRec($rec) { return $rec; }
    public function persist($rec)
    {
        $id = $rec->id ?? null;
        $this->stored[$id] = clone $rec;
        $this->invoke('AfterSave', array(&$id, $rec));
    }
    public function save_($rec, $fields = null)
    {
        $id = $rec->id ?? null;
        $this->stored[$id] = clone $rec;
        return $id;
    }
    public function getRecTitle($rec) { return 'Synthetic task'; }
    public function logWrite($message, $id) { $this->logs[] = array($message, $id); }
    public static function on_BeforeActivation($mvc, &$rec) { $mvc->events[] = 'before'; }
    public static function on_AfterChangeState($mvc, &$rec, $action)
    {
        $mvc->events[] = 'state';
        // Историята се записва от самия обработчик на производствените задания.
        $method = new ReflectionMethod('planning_Jobs', 'on_AfterChangeState');
        $method->setAccessible(true);
        $method->invokeArgs(null, array($mvc, &$rec, $action));
        if ($mvc->nested) {
            $mvc->nested = false;
            $mvc->persist($rec);
        }
    }
    public static function on_AfterActivation($mvc, &$rec)
    {
        checkActivation(!empty(doc_Containers::$records[$rec->containerId ?? null]->docId), 'Activation requires a completed container');
        $mvc->events[] = 'activation';
    }
}
function activationRecord($id, $existing = false)
{
    $rec = (object) array('id' => $id, 'containerId' => $id + 100, 'threadId' => $id + 200,
        'folderId' => 1, 'state' => 'draft', 'modifiedOn' => dt::now(), 'modifiedBy' => 7);
    doc_Containers::$records[$rec->containerId ?? null] = (object) array('docId' => $existing ? $id : null, 'savedDocumentId' => $id);
    return $rec;
}
function activationModel($reverse = false, $legacy = false)
{
    $plugins = array(new doc_DocumentPlg(), new planning_plg_StateManager());
    if ($reverse) $plugins = array_reverse($plugins);
    if ($legacy) array_unshift($plugins, new doc_ActivatePlg());
    return new ActivationDocument($plugins);
}

foreach (array(false, true) as $reverse) {
    foreach (array(false, true) as $existing) {
        $mvc = activationModel($reverse);
        $mvc->nested = true;
        $rec = activationRecord(11, $existing);
        $form = new ActivationForm($rec);
        planning_plg_StateManager::on_AfterInputEditForm($mvc, $form);
        $notifications = count(doc_Containers::$notifications);
        $mvc->persist($rec);
        checkActivation($mvc->events === array('before', 'state', 'activation'), 'One ordered state change and activation, including nested saves');
        checkActivation($mvc->logs === array(array('Активиране', 11)), 'Exactly one activation log');
        checkActivation(count(doc_Containers::$notifications) === $notifications + 1, 'Exactly one notification dispatch');
        checkActivation(($mvc->stored[11]->history ?? null) === array(array('action' => 'Активиране', 'date' => dt::now(), 'user' => 7, 'engaction' => 'active')), 'Native job activation history is persisted once');
        checkActivation(($rec->lastChangeStateOn ?? null) === dt::now() && ($rec->lastChangeStateBy ?? null) === 7, 'Last state change date and author are preserved');
        checkActivation(!isset($rec->_isActivated), 'Activation flag is consumed');
        $mvc->persist($rec);
        checkActivation($mvc->events === array('before', 'state', 'activation') && count($rec->history ?? array()) === 1, 'Subsequent saves do not repeat history or activation');
    }
}

$mvc = activationModel(false, true);
$rec = activationRecord(12);
planning_plg_StateManager::on_AfterInputEditForm($mvc, new ActivationForm($rec));
$mvc->persist($rec);
checkActivation($mvc->events === array('before', 'state', 'activation') && count($mvc->logs) === 1, 'Combined planning and legacy activation plugins do not activate twice');

foreach (array('draft', 'waiting', 'pending') as $state) {
    $mvc = activationModel();
    $rec = activationRecord(13);
    $rec->state = $state;
    $mvc->persist($rec);
    checkActivation(empty($mvc->events) && empty($mvc->logs) && empty($rec->history), 'Ordinary ' . $state . ' save does not activate');
}
$mvc = activationModel();
$mvc->activate = false;
$rec = activationRecord(14);
planning_plg_StateManager::on_AfterInputEditForm($mvc, new ActivationForm($rec));
$mvc->persist($rec);
checkActivation(($rec->state ?? null) === 'waiting' && empty($mvc->events) && empty($mvc->logs), 'Deferred activation does not run active-state callbacks');

// Документите само с doc_ActivatePlg запазват досегашното си поведение.
$mvc = new ActivationDocument(array(new doc_ActivatePlg(), new doc_DocumentPlg()));
$rec = activationRecord(15);
$rec->_isActivated = true;
$mvc->persist($rec);
checkActivation($mvc->events === array('activation') && count($mvc->logs) === 1, 'Legacy activation still consumes its own flag');
echo "OK: {$checks} direct activation checks\n";
