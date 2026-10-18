<?php

/**
 * Regression checks for SQL debug previews and unchanged query execution.
 *
 * @category  bgerp
 * @package   core
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// CLI regression: real query path with a synthetic connection, without a database.
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function isDebug() { return false; }
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
class Debug
{
    public static $logs = array();
    public static $time = 0;
    public static $timers = array();
    public static $started = array();
    public static function log($text) { self::$logs[] = $text; }
    public static function startTimer($name) { self::$started[$name] = self::$time; }
    public static function stopTimer($name) { self::$timers[$name] = (self::$timers[$name] ?? 0) + self::$time - self::$started[$name]; unset(self::$started[$name]); }
    public static function getExecutionTime() { return self::$time; }
}
class core_Backup
{
    public static $queries = array();
    public static function addSqlLog($sql) { self::$queries[] = $sql; }
}
class log_Mysql
{
    public static $queries = array();
    public static function add($sql, $duration) { self::$queries[] = $sql; }
}
class DebugTestConnection
{
    public $queries = array();
    public $affected_rows = 1;
    public $failure = false;
    public function query($sql, $mode) {
        $this->queries[] = $sql;
        Debug::$time += 0.25;
        if ($this->failure === 'throw') throw new RuntimeException('SQL failed');
        return $this->failure ? false : 'result';
    }
}
require dirname(__DIR__) . '/Db.class.php';
class DebugTestDb extends core_Db
{
    public $errorQueries = array();
    public $connectFailure = false;
    public function __construct() { $this->link = new DebugTestConnection(); $this->dbHost = 'primary.invalid:3307'; $this->dbName = 'actual_db'; $this->dbPass = 'secret-password'; }
    public function connect($forceDb = false) {
        Debug::$time += 0.125;
        if ($this->connectFailure) throw new RuntimeException('Connection failed');
        return $this->link;
    }
    public function checkForErrors($action, $silent = false, $link = null) { $this->errorQueries[] = $this->query; }
}
define('MIN_TIME_FOR_DB_QUERY_LOG', 0);
$db = new DebugTestDb();
$limit = core_Db::DEBUG_QUERY_MAX_LEN;
$queries = array('SELECT 1', str_repeat('a', $limit), str_repeat('b', $limit + 1),
    "UPDATE `test_items` SET `payload` = '" . str_repeat('x', 2000000) . "' WHERE id = 7",
    "INSERT INTO `test_items` SET `payload` = '" . str_repeat("\0\xff\x80", 10000) . "'",
    "UPDATE `test_items` SET `payload` = '" . str_repeat('Кирилица 😀 ', 10000) . "' WHERE id = 8");
// Exercise every possible UTF-8 boundary around the head and tail cuts.
for ($i = 0; $i < 8; $i++) $queries[] = str_repeat('😀Ж漢', 4000) . str_repeat('z', $i);
foreach ($queries as $sql) {
    $startLog = count(Debug::$logs);
    check($db->query($sql, false, true) === 'result', 'Query result changed');
    check(count(Debug::$logs) === $startLog + 2, 'Expected one BEGIN and one END per query');
    $begin = Debug::$logs[$startLog];
    check(preg_match('/^SQL #(\d+) BEGIN \[primary host=primary\.invalid:3307 db=actual_db\] /', $begin, $match) === 1, 'Actual endpoint missing at query start');
    $logged = substr($begin, strlen($match[0]));
    check(end(Debug::$logs) === 'SQL #' . $match[1] . ' END [primary host=primary.invalid:3307 db=actual_db] status=ok connect=0.12500s query=0.25000s total=0.37500s', 'Duration, status or query pairing is incorrect');
    check(strlen($logged) <= $limit, 'Debug entry exceeds its byte budget');
    if (strlen($sql) <= $limit) {
        check($logged === $sql, 'Short SQL was changed');
    } else {
        check(strpos($logged, 'original ' . strlen($sql) . ' bytes') !== false, 'Original size is missing');
        check(substr($logged, 0, 50) === substr($sql, 0, 50), 'Query prefix is missing');
        check(substr($logged, -50) === substr($sql, -50), 'Query suffix/WHERE clause is missing');
        if (preg_match('//u', $sql)) check(preg_match('//u', $logged) === 1, 'UTF-8 was split at a cut');
    }
    check(end($db->link->queries) === $sql, 'Executed SQL was truncated');
    check($db->query === $sql && end($db->errorQueries) === $sql, 'Error diagnostics lost the full SQL');
    check(end(core_Backup::$queries) === $sql, 'Backup/replication SQL was truncated');
    check(end(log_Mysql::$queries) === $sql, 'Slow-query diagnostics lost the full SQL');
}
check(Debug::$timers['DB::query() [primary host=primary.invalid:3307 db=actual_db]'] === count($queries) * 0.375, 'Endpoint timer must accumulate across queries');
$db->dbRole = 'replica';
$db->dbHost = 'replica.invalid:3308';
$db->query('SELECT 2');
$db->dbHost = 'replica.invalid:3309';
$db->query('SELECT 3');
check(count(core_Db::$debugConnections) === 3, 'Different replica ports must remain distinguishable, even for the same database');
check(Debug::$timers['DB::query() [replica host=replica.invalid:3308 db=actual_db]'] === 0.375, 'Replica has its own timer');
foreach (array('return-false', 'throw', 'connect') as $failure) {
    $db->link->failure = $failure;
    $db->connectFailure = $failure === 'connect';
    try {
        check($db->query('SELECT failure') === false && $failure === 'return-false', 'A failed query must preserve its return value');
    } catch (RuntimeException $e) {
        check($e->getMessage() === ($failure === 'connect' ? 'Connection failed' : 'SQL failed'), 'Original exception was changed');
    }
    $end = end(Debug::$logs);
    check(strpos($end, 'status=error connect=0.12500s query=' . ($failure === 'connect' ? 'not-started' : '0.25000s')) !== false, 'Failure phase/timing is missing');
    check(!Debug::$started, 'Failure left a timer running');
}
check(strpos(implode('\n', Debug::$logs), 'secret-password') === false, 'Connection password leaked into debug');
echo 'PASS: ' . count($queries) . " query cases, byte budget, UTF-8/binary, execution and full diagnostic/backup SQL\n";
echo "PASS: actual endpoints, replica identities, paired events, cumulative timers and connection/SQL failures\n";
