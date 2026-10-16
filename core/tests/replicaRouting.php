<?php

/** @copyright 2026 Experta EOOD */

// Run with PHP CLI. Uses the real routing methods and synthetic connection settings, without a database.
if (PHP_SAPI !== 'cli') exit("Run this regression test from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
$checks = 0;
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function expect($condition, ...$args) { if (!$condition) throw new RuntimeException('Expect: ' . ($args[0] ?? '')); }
class Debug
{
    public static $logs = array();
    public static function log($text) { self::$logs[] = $text; }
    public static function startTimer($name) {}
    public static function stopTimer($name) {}
    public static function getExecutionTime() { return microtime(true); }
}
class core_Mvc
{
    public $db, $fields = array(), $dbTableName = 'test_items', $dbIndexes = array(), $title = 'Test';
}
class core_FieldSet {}
class core_App { public static $error = ''; public static function isReplicationOK() { return self::$error; } }
class core_Cache { public static function get($type, $key) { return true; } }
class cls
{
    public static $db;
    public static function get($class) { if ($class !== 'core_Db') throw new RuntimeException('Unexpected class'); return self::$db; }
}
require dirname(__DIR__) . '/Manager.class.php';
require dirname(__DIR__) . '/Query.class.php';
require dirname(__DIR__) . '/Db.class.php';
class RoutingQuery extends core_Query
{
    public function select() { return $this->mvc->db->dbName; }
}

$primary = array('dbName' => 'primary', 'dbUser' => 'writer', 'dbPass' => null, 'dbHost' => 'primary.invalid');
cls::$db = (new ReflectionClass('core_Db'))->newInstanceWithoutConstructor();
foreach ($primary as $name => $value) cls::$db->{$name} = $value;
unset(cls::$db->__origDbName, cls::$db->__origDbUser, cls::$db->__origDbPass, cls::$db->__origDbHost);
$primary = (array) cls::$db;
$manager = new core_Manager();
$manager->db = cls::$db;
$query = new RoutingQuery();
$query->mvc = $manager;
check($query->selectOnReplica() === 'primary', 'Without replica configuration, queries stay on primary');
check(core_Manager::callWithoutReplica(function () use ($query) { return $query->selectOnReplica(); }) === 'primary', 'Primary routing also works without a replica');
check((array) cls::$db === $primary, 'No connection settings are added without a replica');

define('SEARCH_DB_NAME', 'replica');
define('SEARCH_DB_USER', 'reader');
define('SEARCH_DB_PASS', 'synthetic-replica-password');
define('SEARCH_DB_HOST', 'replica.invalid');
$replica = array_replace($primary, array('dbName' => SEARCH_DB_NAME, 'dbUser' => SEARCH_DB_USER, 'dbPass' => SEARCH_DB_PASS, 'dbHost' => SEARCH_DB_HOST, 'dbRole' => 'replica'));
unset($replica['replicaOwner']);
check($query->selectOnReplica() === 'replica', 'Ordinary option queries still use the replica');
check((array) cls::$db === $primary, 'Ordinary queries restore all primary settings');
check(core_Manager::callWithoutReplica(function () use ($query, $manager, $primary) {
    check((array) cls::$db === $primary, 'The callback starts on primary');
    check($query->selectOnReplica() === 'primary', 'Explicit replica SELECT stays on primary inside the scope');
    $manager->forceReplica();
    check((array) cls::$db === $primary, 'Direct forceReplica cannot override primary scope');
    $manager->unforceReplica();
    check(core_Manager::callWithoutReplica(function () use ($query) { return $query->selectOnReplica(); }) === 'primary', 'Nested primary scope stays on primary');
    check($query->selectOnReplica() === 'primary', 'Ending an inner scope preserves the outer scope');
    return 'callback-result';
}) === 'callback-result', 'The callback return value is preserved');
check($query->selectOnReplica() === 'replica', 'Replica routing resumes after the primary scope');
check((array) cls::$db === $primary, 'The callback leaves the connection unchanged');

$manager->callOnReplica(function () use ($manager, $query, $primary, $replica) {
    $outerSettings = (array) cls::$db;
    check(array_diff_key(array_intersect_key($outerSettings, $primary), array('replicaOwner' => true)) === $replica, 'The outer callback uses all replica settings');
    check(strpos($outerSettings['replicaOwner'] ?? '', 'replicaRouting.php:') !== false, 'The replica owner points to the calling code');
    check(core_Manager::callWithoutReplica(function () use ($manager, $query, $primary) {
        check(array_intersect_key((array) cls::$db, $primary) === $primary, 'Nested primary scope restores every primary credential');
        $manager->forceReplica();
        $manager->unforceReplica();
        check(isset(cls::$db->__origDbName), 'A direct force/unforce pair preserves outer connection ownership');
        return core_Manager::callWithoutReplica(function () use ($query) { return $query->selectOnReplica(); });
    }) === 'primary', 'Primary scopes also nest inside a replica callback');
    check((array) cls::$db === $outerSettings, 'All outer replica settings are restored after the scope');
    check($manager->callOnReplica(function () use ($query) { return $query->selectOnReplica(); }) === 'replica', 'Nested replica calls still use the outer replica');
    check((array) cls::$db === $outerSettings, 'Nested replica calls do not release the outer connection');
    try {
        core_Manager::callWithoutReplica(function () { throw new RuntimeException('Synthetic failure'); });
        check(false, 'The exception must propagate');
    } catch (RuntimeException $e) {
        check($e->getMessage() === 'Synthetic failure', 'The original exception propagates');
    }
    check((array) cls::$db === $outerSettings, 'An exception restores the outer replica connection');
});
check((array) cls::$db === $primary, 'Ending the outer replica scope restores primary and removes markers');
try {
    core_Manager::callWithoutReplica(function () use ($query) {
        check($query->selectOnReplica() === 'primary', 'A failing callback still starts on primary');
        throw new RuntimeException('Synthetic primary failure');
    });
} catch (RuntimeException $e) {
    check($e->getMessage() === 'Synthetic primary failure', 'Primary callback failures propagate');
}
check($query->selectOnReplica() === 'replica' && (array) cls::$db === $primary, 'A failed callback releases the primary-only scope');
core_App::$error = 'Replica is unavailable';
check($query->selectOnReplica() === 'primary', 'Failed replica health check preserves the primary route');
check(strpos(implode('\n', Debug::$logs), 'forceReplica() END status=unavailable') !== false, 'Unavailable replica outcome must be logged');
foreach (array('not-configured', 'disabled', 'selected') as $status) {
    check(strpos(implode('\n', Debug::$logs), 'forceReplica() END status=' . $status) !== false, 'Missing routing outcome: ' . $status);
}
check(strpos(implode('\n', Debug::$logs), 'callWithoutReplica() BEGIN current=[primary host=primary.invalid db=primary]') !== false, 'Primary override must be labelled primary');
check(strpos(implode('\n', Debug::$logs), 'callWithoutReplica() END current=[replica host=replica.invalid db=replica]') !== false, 'Restored replica must be labelled replica');
check(strpos(implode('\n', Debug::$logs), SEARCH_DB_PASS) === false, 'Replica password leaked into debug');
echo "OK: {$checks} replica routing checks\n";
