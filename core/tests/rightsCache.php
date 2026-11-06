<?php

/**
 * CLI проверки на срока на кешираните права, без база данни.
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
// php core/tests/rightsCache.php [path/to/Manager.class.php]
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
function check($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function countR($value) { return is_countable($value) ? count($value) : 0; }
class core_Mvc {}
class core_Users { public static function getCurrent() { return 11; } }
class Users { public static function haveRole($roles, $userId) { return $roles === 'allowed'; } }
class core_Cache
{
    public static $entries = array();
    public static function get($type, $key) { return self::$entries[$key] ?? false; }
    public static function set($type, $key, $value, $minutes) { self::$entries[$key] = $value; }
}
class cls
{
    public static $manager, $nested;
    public static function get($class) { return $class === 'RightsCacheNestedManager' ? self::$nested : self::$manager; }
}
require $argv[1] ?? dirname(__DIR__) . '/Manager.class.php';
class RightsCacheTestManager extends core_Manager
{
    public $cacheRightsDuration = 3600;
    public $requiredRoles = 'allowed';
    public $roleChecks = 0;
    public $checkNested = false;
    public function getRequiredRoles($action, $rec = null, $userId = null)
    {
        $this->roleChecks++;
        if ($this->checkNested) {
            RightsCacheNestedManager::haveRightFor('single', $rec, $userId);
        }

        return $this->requiredRoles;
    }
    public static function fetchRec($id) { return (object) array('id' => $id, 'state' => 'active'); }
}
class RightsCacheNestedManager extends RightsCacheTestManager {}
$memory = new ReflectionProperty('core_Manager', 'cacheRights');
$memory->setAccessible(true);
$rec = (object) array('id' => 101, 'state' => 'active');

foreach (array('memory', 'stored') as $source) {
    foreach (array(true, false) as $allowed) {
        foreach (array(-3600, 0, 3600) as $offset) {
            $memory->setValue(null, array());
            core_Cache::$entries = array();
            $mvc = cls::$manager = new RightsCacheTestManager();
            $mvc->requiredRoles = $allowed ? 'allowed' : 'no_one';
            check($mvc->haveRightFor('edit', $rec) === $allowed, 'Initial permission must be calculated');
            $entries = $memory->getValue();
            $expires = time() + $offset;
            foreach ($entries[11] as &$entry) {
                $entry = $allowed ? $expires : -$expires;
            }
            unset($entry);
            if ($source === 'stored') {
                core_Cache::$entries = $entries;
                $memory->setValue(null, array());
            } else {
                $memory->setValue(null, $entries);
            }

            $mvc->requiredRoles = $allowed ? 'no_one' : 'allowed';
            $expected = $offset <= 0 ? !$allowed : $allowed;
            $expectedChecks = $offset <= 0 ? 2 : 1;
            $label = $source . '/' . ($allowed ? 'allow' : 'deny') . '/' . $offset;
            check($mvc->haveRightFor('edit', $rec) === $expected, "{$label}: expired permissions must be recalculated");
            check($mvc->roleChecks === $expectedChecks, "{$label}: only expired permissions are recalculated");
            check($mvc->haveRightFor('edit', $rec->id ?? null) === $expected, "{$label}: record IDs reuse the same decision");
            check($mvc->roleChecks === $expectedChecks, "{$label}: the current decision is reused");

            core_Manager::on_Shutdown($mvc);
            check($mvc->haveRightFor('edit', $rec) === $expected, "{$label}: the decision survives a new request");
            check($mvc->roleChecks === $expectedChecks, "{$label}: shutdown persists the renewed expiration");
        }
    }
}

foreach (array(0, 7200) as $nestedDuration) {
    $memory->setValue(null, array());
    core_Cache::$entries = array();
    $mvc = cls::$manager = new RightsCacheTestManager();
    $mvc->checkNested = true;
    cls::$nested = new RightsCacheNestedManager();
    cls::$nested->cacheRightsDuration = $nestedDuration;
    $before = time();
    check($mvc->haveRightFor('edit', $rec) === true, 'Nested permission checks allow editing');
    $after = time();
    $entries = $memory->getValue();
    $expires = array_map('abs', array_values($entries[11] ?? array()));
    sort($expires);
    check(count($expires) === 2, 'Each nested permission has its own cache entry');
    $durations = array(3600, $nestedDuration);
    sort($durations);
    foreach ($durations as $index => $duration) {
        check($expires[$index] >= $before + $duration && $expires[$index] <= $after + $duration, 'Nested checks retain each manager\'s expiration');
    }
    check($mvc->haveRightFor('edit', $rec) === true && $mvc->roleChecks === 1, 'Nested checks do not expire the outer permission early');
}

$memory->setValue(null, array());
core_Cache::$entries = array();
$mvc = cls::$manager = new RightsCacheTestManager();
$mvc->cacheRightsDuration = 0;
check($mvc->haveRightFor('edit', $rec) === true, 'Request-only permissions are calculated');
$mvc->requiredRoles = 'no_one';
check($mvc->haveRightFor('edit', $rec) === true && $mvc->roleChecks === 1, 'Zero duration retains request-only memoization');
core_Manager::on_Shutdown($mvc);
check(core_Cache::$entries === array(), 'Request-only permissions are not persisted');
check($mvc->haveRightFor('edit', $rec) === false, 'A new request recalculates request-only permissions');

$mvc->requiredRoles = 'allowed';
check($mvc->haveRightFor('edit') === true, 'General rights are calculated without a record');
$mvc->requiredRoles = 'no_one';
check($mvc->haveRightFor('edit') === false, 'General rights are always recalculated');

echo "OK: {$checks} rights cache checks\n";
