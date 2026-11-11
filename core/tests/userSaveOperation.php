<?php

/**
 * Проверява обвиването на native потребителски запис без база или мрежа.
 *
 * @category  bgerp
 * @package   core
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// Run: php core/tests/userSaveOperation.php. Real dispatcher and user role preparation.
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function countR($value) { return is_array($value) ? count($value) : 0; }
function expect($ok) { if (!$ok) throw new RuntimeException('Fixture expectation'); }
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
define('EF_SALT', 'synthetic-save-operation');
require dirname(__DIR__) . '/BaseClass.class.php';
require dirname(__DIR__) . '/exception/Expect.class.php';
require dirname(__DIR__) . '/exception/Redirect.class.php';
class core_Type {}
class arr
{
    /** @return array */
    public static function make($value) { return is_array($value) ? $value : explode(',', $value ?? ''); }
}
require dirname(__DIR__, 2) . '/type/Keylist.class.php';
class_alias('type_Keylist', 'keylist');

/** Паметта замества само физическия запис, без да имитира събитията на save. */
class core_Manager extends core_BaseClass
{
    public static $events = array();
    public static $writes = array();
    public static $exception;
    public static $returnFalse = false;
    public $core_Roles;

    /** @return int|false */
    public function save_(&$rec, $fields = null, $mode = null)
    {
        self::$events[] = 'persist';
        if (self::$exception) throw self::$exception;
        if (self::$returnFalse) return false;
        if (empty($rec->id)) $rec->id = 41;
        self::$writes[] = array(clone $rec, $fields, $mode);

        return $rec->id;
    }
}
/** Малък каталог; native core_Users извършва самото подготвяне на ролите. */
class core_Roles
{
    /** @return int */
    public static function fetchByName($name)
    {
        return array('user' => 1, 'partner' => 2, 'ai' => 3, 'aiMaster' => 4,
            'executive' => 5, 'officer' => 6, 'manager' => 7, 'ceo' => 8)[$name];
    }

    /** @return array */
    public static function expand($roles)
    {
        if (isset($roles[4])) $roles[3] = 3;

        return $roles;
    }
}
class core_Cache { public static function remove($type, $key) {} }
class bgerp_Menu { public static function clearCache($id) {} }
class core_RoleLogs { public static function add($roles, $state, $id) {} }
require $argv[1] ?? dirname(__DIR__) . '/Users.class.php';

/** Добавя единствено тестови слушатели към реалния dispatcher. */
class SaveOperationUsers extends core_Users
{
    /** @return void */
    public function attach($name, $plugin) { $this->_plugins[$name] = $plugin; }
}
/** Брои обичайните събития, включително окончателното разширяване на ролите. */
class SaveOperationObserver
{
    /** @return void */
    public function on_BeforeSave($mvc, &$id, &$rec, $fields = null, $mode = null) { core_Manager::$events[] = 'before'; }

    /** @return void */
    public function on_AfterPrepareUserRoles($mvc, &$roles, $rec, $fields = null) { core_Manager::$events[] = 'roles'; }

    /** @return void */
    public function on_AfterSave($mvc, &$id, $rec, $fields = null, $mode = null) { core_Manager::$events[] = 'after'; }
}
/** Синтетична политика със същия callback/finally договор като реално заключване. */
class SaveOperationWrapper
{
    public $depth = 0;
    public $exception;
    public $seen;

    /** @return void */
    public function on_PrepareSaveOperation($mvc, &$operation, $rec, $fields = null, $mode = null)
    {
        core_Manager::$events[] = 'prepare';
        $this->seen = array(clone $rec, $fields, $mode);
        $native = $operation;
        $operation = function () use ($native) {
            $this->depth++;
            core_Manager::$events[] = 'lock';
            try {
                if ($this->exception) throw $this->exception;

                return $native();
            } finally {
                $this->depth--;
                core_Manager::$events[] = 'unlock';
            }
        };
    }
}

/** @return SaveOperationUsers */
function fixture($wrapper = null, $wrapperFirst = false)
{
    core_Manager::$events = core_Manager::$writes = array();
    core_Manager::$exception = null;
    core_Manager::$returnFalse = false;
    $mvc = new SaveOperationUsers();
    $mvc->core_Roles = new core_Roles();
    if ($wrapper && $wrapperFirst) $mvc->attach('wrapper', $wrapper);
    $mvc->attach('observer', new SaveOperationObserver());
    if ($wrapper && !$wrapperFirst) $mvc->attach('wrapper', $wrapper);

    return $mvc;
}

$checks = 0;
$mvc = fixture();
$rec = (object) array('nick' => 'synthetic', 'state' => 'active', 'rolesInput' => '|4|');
$id = $mvc->save($rec);
check($id === 41 && $rec->id === 41 && count(core_Manager::$writes) === 1, 'Without a wrapper native result and record mutation survive');
check(core_Manager::$events === array('before', 'roles', 'persist', 'after'), 'No listener preserves the existing event order');
check(keylist::isIn(3, $rec->roles), 'Native role preparation retains inherited ai');

foreach (array(false, true) as $wrapperFirst) {
    $wrapper = new SaveOperationWrapper();
    $mvc = fixture($wrapper, $wrapperFirst);
    $rec = (object) array('nick' => 'synthetic', 'state' => 'active', 'rolesInput' => '|4|');
    $id = $mvc->save($rec);
    check($id === 41 && count(core_Manager::$writes) === 1, 'Wrapped operation writes exactly once');
    check(core_Manager::$events === array('before', 'roles', 'prepare', 'lock', 'persist', 'unlock', 'after'), 'Before/After hooks are never repeated, regardless of listener order');
    check(keylist::isIn(1, $wrapper->seen[0]->roles) && keylist::isIn(3, $wrapper->seen[0]->roles), 'Wrapper sees the final base and inherited roles');
    check($wrapper->depth === 0, 'Successful operation releases its scope');
}

$wrapper = new SaveOperationWrapper();
$mvc = fixture($wrapper);
$rec = (object) array('id' => 41, 'names' => 'Synthetic change');
$fields = array('names');
$id = $mvc->save($rec, $fields, 'ignore');
check($id === 41 && $wrapper->seen[1] === $fields && $wrapper->seen[2] === 'ignore', 'Partial save scope and mode reach the wrapper unchanged');
check(core_Manager::$writes[0][1] === $fields && core_Manager::$writes[0][2] === 'ignore', 'Native persistence keeps exactly the original fields and mode');

foreach (array('deny', 'native-error', 'redirect') as $scenario) {
    $wrapper = new SaveOperationWrapper();
    $mvc = fixture($wrapper);
    $exception = $scenario === 'redirect' ? new core_exception_Redirect('/synthetic', 'Expected redirect') : new RuntimeException('Synthetic failure');
    if ($scenario === 'native-error') core_Manager::$exception = $exception;
    else $wrapper->exception = $exception;
    $rec = (object) array('id' => 41, 'names' => 'Synthetic change');
    $caught = null;
    try {
        $mvc->save($rec, 'names');
    } catch (Throwable $e) {
        // redirect-catch: terminal - compare identity at this isolated test boundary.
        $caught = $e;
    }
    check($caught === $exception, 'Exception/redirect identity is unchanged: ' . $scenario);
    check($wrapper->depth === 0 && end(core_Manager::$events) === 'unlock', 'Finally releases the scope after ' . $scenario);
    check(core_Manager::$writes === array() && !in_array('after', core_Manager::$events, true), 'Failed operation produces no successful write/AfterSave: ' . $scenario);
}

$wrapper = new SaveOperationWrapper();
$mvc = fixture($wrapper);
core_Manager::$returnFalse = true;
$rec = (object) array('id' => 41, 'names' => 'Synthetic change');
check($mvc->save($rec, 'names') === false && $wrapper->depth === 0, 'False native result is preserved and scope is released');
check(end(core_Manager::$events) === 'after', 'False result retains the framework existing AfterSave behavior');
echo "PASS: {$checks} user save operation checks\n";
