<?php

/**
 * Сесийните настройки на процеса важат за всяка връзка - и за отделната връзка към репликата
 *
 * BGERP_SUMMARY_TEST_SOCKET=/path/my.sock php core/tests/dbSessionVariables.php
 * Иска изолирана MariaDB (root без парола); създава и трие базите test_session_*.
 */
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
$socket = getenv('BGERP_SUMMARY_TEST_SOCKET');
if (!$socket) exit("Set BGERP_SUMMARY_TEST_SOCKET to an isolated MariaDB socket.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
ini_set('mysqli.default_socket', $socket);
function countR($value) { return is_array($value) ? count($value) : 0; }
function expect($value) { if (!$value) throw new RuntimeException('Expectation failed'); }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function isDebug() { return false; }
class Request { public static function get($name) { return null; } }
class DEBUG
{
    public static function startTimer($name) {}
    public static function stopTimer($name) {}
    public static function log($text) {}
    public static function getExecutionTime() { return microtime(true); }
}
define('EF_DB_NAME', 'test');
define('EF_DB_USER', 'root');
define('EF_DB_PASS', '');
define('EF_DB_HOST', 'localhost');

$root = getenv('BGERP_TEST_ROOT') ?: dirname(__DIR__, 2);
foreach (array('Array', 'exception/Expect', 'exception/Db', 'Db') as $class) {
    require $root . '/core/' . $class . '.class.php';
}
class_alias('core_Array', 'arr');

$checks = 0;
function checkDb($condition, $label)
{
    if (!$condition) throw new RuntimeException($label);
    $GLOBALS['checks']++;
}
// Всяка база е отделна връзка на процеса - като основната база и репликата
function connectTo($dbName)
{
    $db = new core_Db();
    $db->init(array('dbName' => $dbName));

    return $db;
}
function sessionValue($db, $name)
{
    $row = $db->fetchArray($db->query("SELECT @@SESSION.{$name} AS `v`"));

    return $row['v'];
}

$names = array('primary' => 'test_session_primary', 'replica' => 'test_session_replica',
    'later' => 'test_session_later', 'fresh' => 'test_session_fresh');
$admin = connectTo('test');
foreach ($names as $name) {
    $admin->query("CREATE DATABASE IF NOT EXISTS `{$name}`");
}

try {
    $primary = connectTo($names['primary']);
    $replica = connectTo($names['replica']);
    checkDb((float) sessionValue($replica, 'max_statement_time') !== 7.0, 'Преди настройката връзката е със стойността на сървъра');

    checkDb($primary->setSessionVariable('max_statement_time', 7) === true, 'Поддържаната настройка се приема');
    checkDb((float) sessionValue($primary, 'max_statement_time') === 7.0, 'Текущата връзка получава настройката');
    checkDb((float) sessionValue($replica, 'max_statement_time') === 7.0, 'Вече отворената друга връзка (репликата) също');

    // Пренасочване към непозната досега връзка - като core_Manager::forceReplica()
    $later = connectTo($names['primary']);
    $later->dbName = $names['later'];
    checkDb((float) sessionValue($later, 'max_statement_time') === 7.0, 'Връзка, отворена след настройката, я получава при свързването');

    checkDb($primary->setSessionVariable('time_zone', '+02:00') === true && sessionValue($later, 'time_zone') === '+02:00',
        'Текстовата стойност се ескейпва и също стига до другите връзки');

    $failed = $result = null;
    try {
        $result = $primary->setSessionVariable('no_such_session_variable', 1);
    } catch (core_exception_Db $e) {
        $failed = $e;
    }
    checkDb($failed !== null || $result === false, 'Неподдържаната настройка не се приема');
    $fresh = connectTo($names['fresh']);
    checkDb((float) sessionValue($fresh, 'max_statement_time') === 7.0, 'Неприетата настройка не пречи на следващите връзки');
} finally {
    foreach ($names as $name) {
        $admin->query("DROP DATABASE IF EXISTS `{$name}`");
    }
}

echo "OK: {$checks} session variable checks with the real core_Db and MariaDB.\n";
