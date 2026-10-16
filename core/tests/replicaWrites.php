<?php

/**
 * Regression checks: writes never reach the replica, heavy reads are routed per query.
 *
 * @category  bgerp
 * @package   core
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// Run with PHP CLI. Real core_Manager, core_Query and core_Db with synthetic links, without a database.
// As in production, every manager has its own core_Db object; only the connections are shared.
if (PHP_SAPI !== 'cli') exit("Run this regression test from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
$checks = 0;
$reports = array();
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function isDebug() { return false; }
function expect($condition, ...$args) { if (!$condition) throw new RuntimeException('Expect: ' . ($args[0] ?? '')); }
function wp(...$args) { global $reports; $reports[] = $args; }
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
    public function invoke($event, $args = array())
    {
        // BeforeCount връща ролята на връзката, на която се брои
        if ($event === 'BeforeCount') {
            $args[0] = $this->db->dbRole;

            return false;
        }

        return true;
    }
}
class core_FieldSet {}
class core_App { public static function isReplicationOK() { return ''; } }
class core_Cache { public static function get($type, $key) { return true; } }
class log_Mysql { public static function add($sql, $time) {} }
class core_Backup { public static function addSqlLog($sql) {} }
class cls
{
    // Както в cls::get(): core_Db не е singleton и всяко извикване дава нов обект
    public static function get($class) { if ($class !== 'core_Db') throw new RuntimeException('Unexpected class'); return newDb(); }
}
require dirname(__DIR__) . '/Manager.class.php';
require dirname(__DIR__) . '/Query.class.php';
require dirname(__DIR__) . '/Db.class.php';
class FakeLink
{
    public $sql = array(), $affected_rows = 1;
    public function query($sql, $mode = null) { $this->sql[] = $sql; return true; }
    public function multi_query($sql) { $this->sql[] = $sql; return true; }
    public function store_result() { return false; }
    public function more_results() { return false; }
}
class TestDb extends core_Db
{
    public function checkForErrors($action, $silent = false, $link = null) {}
}
class RoutingQuery extends core_Query
{
    public $seen = array();
    public function select() { $this->seen[] = $this->mvc->db->dbRole; return 0; }
}
function newDb()
{
    $db = (new ReflectionClass('TestDb'))->newInstanceWithoutConstructor();
    $db->dbHost = 'primary.invalid'; $db->dbUser = 'writer'; $db->dbName = 'primary';
    unset($db->__origDbName, $db->__origDbUser, $db->__origDbPass, $db->__origDbHost);

    return $db;
}
function newManager() { $m = new core_Manager(); $m->db = cls::get('core_Db'); return $m; }
function newQuery($mvc) { $q = new RoutingQuery(); $q->mvc = $mvc; return $q; }
function routingSettings($db)
{
    return array_intersect_key((array) $db, array_flip(array('dbHost', 'dbUser', 'dbPass', 'dbName', 'dbRole', 'replicaOwner',
        '__origDbHost', '__origDbUser', '__origDbPass', '__origDbName')));
}

define('SEARCH_DB_NAME', 'replica');
define('SEARCH_DB_USER', 'reader');
define('SEARCH_DB_PASS', 'synthetic-replica-password');
define('SEARCH_DB_HOST', 'replica.invalid');
$primaryLink = new FakeLink(); $replicaLink = new FakeLink();
core_Db::$links = array('primary.invalid' => array('writer' => array('primary' => $primaryLink)),
    'replica.invalid' => array('reader' => array('replica' => $replicaLink)));
$threads = newManager();
$notifications = newManager();
check($threads->db !== $notifications->db, 'Every manager has its own connection settings');

// 1. Пропуснато unforceReplica() засяга само този мениджър, а неговите записи отиват в основната база
$threads->forceReplica();
$threads->db->query('SELECT 1 /* leaked read */');
check(end($replicaLink->sql) === 'SELECT 1 /* leaked read */', 'Reads of the forced manager use the replica');
$notifications->db->query("UPDATE bgerp_notifications SET state = 'closed'");
check(end($primaryLink->sql) === "UPDATE bgerp_notifications SET state = 'closed'" && !$reports, 'Other managers are not affected');
$threads->db->query('UPDATE doc_threads SET all_doc_cnt = 2');
check(end($primaryLink->sql) === 'UPDATE doc_threads SET all_doc_cnt = 2', 'A write of the forced manager goes to primary');
check(!in_array('UPDATE doc_threads SET all_doc_cnt = 2', $replicaLink->sql, true), 'The write never reached the replica');
check($threads->db->dbRole === 'primary' && !isset($threads->db->__origDbName) && $threads->db->replicaOwner === null, 'The guard closes the replica scope');
check(count($reports) === 1 && strpos($reports[0][1]['replicaSelectedBy'] ?? '', 'replicaWrites.php:') !== false, 'The report names the code that left the replica on');
$threads->db->query('SELECT 2 /* after write */');
check(end($primaryLink->sql) === 'SELECT 2 /* after write */', 'Reads after a redirected write see the primary');
$threads->forceReplica();
$threads->db->query('INSERT INTO doc_threads SET id = 1');
check(end($primaryLink->sql) === 'INSERT INTO doc_threads SET id = 1' && count($reports) === 1, 'Further leaks are redirected and reported once per hit');

// 2. Запис вътре в callOnReplica() също отива в основната база
$threads->callOnReplica(function () use ($threads) {
    $threads->db->query('SELECT 3 /* scoped read */');
    $threads->db->query('DELETE FROM doc_threads WHERE id = 7');
});
check(end($replicaLink->sql) === 'SELECT 3 /* scoped read */' && end($primaryLink->sql) === 'DELETE FROM doc_threads WHERE id = 7', 'A scoped callback cannot write to the replica');
check($threads->db->dbRole === 'primary' && !isset($threads->db->__origDbName), 'The scope ends on primary');

// 3. Само четенето може да остане на репликата
$reads = array('SELECT 1', "  /* c */ select x FROM t", "(SELECT 1) UNION (SELECT 2)", 'SHOW TABLES', 'DESCRIBE t', 'DESC t',
    'EXPLAIN SELECT 1', 'SET SESSION group_concat_max_len = 100000', 'SET @a = 1', 'WITH c AS (SELECT 1) SELECT * FROM c',
    "-- note\nSELECT 1", "# note\nSELECT 1", 'SELECT FOUND_ROWS()', 'SELECT * FROM t INTO @total',
    "SELECT 'FOR UPDATE', 'GLOBAL', 'GET_LOCK(', '/*! DELETE */', ';', '-- x'", 'SELECT "LOCK IN SHARE MODE"',
    "SELECT 'it''s a comment: /*'", 'SELECT `FOR UPDATE`, `x``y` FROM t', "# note\rSELECT 1; -- tail\n",
    'SELECT /*+ MAX_EXECUTION_TIME(1000) */ 1;', 'SET /* session */ SESSION wait_timeout = 60, @@SESSION.sql_mode = DEFAULT',
    "SET @note = 'GLOBAL', @other = 'FOR UPDATE'", "SELECT '" . str_repeat('x', 20000) . "'",
    'WITH c AS (SELECT \'UPDATE\' AS `DELETE`) SELECT * FROM c', "SELECT 'escaped\\'quote'",
    "SELECT id FROM t WHERE name = 'ET \\\"Test\\\" O\\'Brien'",
    "SELECT id FROM t WHERE (LOWER(name) REGEXP '(\\\\\\||\\\\\\'|\\\\_| ){1}kabel')");
foreach ($reads as $sql) check(core_Db::isReadOnlySql($sql), 'Read classified as write: ' . $sql);
$writes = array('INSERT INTO t SET a = 1', 'UPDATE t SET a = 1', 'DELETE FROM t', 'REPLACE t SET a = 1', 'TRUNCATE t',
    'CREATE TEMPORARY TABLE x (id int)', 'ALTER TABLE t ADD c int', 'LOCK TABLES t WRITE', 'START TRANSACTION', 'COMMIT',
    'SELECT * FROM t FOR UPDATE', 'SELECT * FROM t LOCK IN SHARE MODE', "SELECT GET_LOCK('x', 1)",
    "SELECT * FROM t INTO OUTFILE '/tmp/x'", 'SET GLOBAL read_only = 1', 'WITH c AS (SELECT 1) UPDATE t SET a = 1',
    'CALL proc()', 'DO SLEEP(1)', '',
    'SET /* comment */ GLOBAL max_connections = 123', 'SET SESSION wait_timeout = 60, GLOBAL max_connections = 123',
    'SET SESSION wait_timeout = 60, @@GLOBAL.max_connections = 123', 'SET @@ /* c */ GLOBAL.max_connections = 123',
    'SET PERSIST max_connections = 123', 'SET PERSIST_ONLY max_connections = 123', 'SET DEFAULT ROLE ALL TO test_user',
    "SET PASSWORD = 'synthetic'", 'SET STATEMENT max_statement_time = 1 FOR UPDATE t SET a = 1',
    'SELECT * FROM t FOR /* comment */ UPDATE', "SELECT * FROM t FOR -- comment\nSHARE",
    'SELECT * FROM t LOCK /* comment */ IN SHARE MODE', "SELECT * FROM t INTO /* comment */ OUTFILE 'synthetic.csv'",
    "SELECT GET_LOCK /* c */ ('fixture', 0)", "SET @lock = GET_LOCK('fixture', 0)", "SELECT `GET_LOCK`('fixture', 0)",
    "SELECT RELEASE_LOCK('fixture')", 'SELECT RELEASE_ALL_LOCKS()', 'SELECT /*! SQL_NO_CACHE */ 1',
    'SELECT /*M! SQL_NO_CACHE */ 1', 'SELECT 1; UPDATE t SET a = 1', 'SELECT 1;;', "SELECT 'unterminated", 'SELECT /* unterminated',
    'SELECT * FROM t FOR ' . str_repeat(' ', 2100) . 'UPDATE',
    'WITH c AS (SELECT 1) DELETE FROM t', 'EXPLAIN ANALYZE DELETE t1 FROM t1 JOIN t2 ON t1.id = t2.id');
foreach ($writes as $sql) check(!core_Db::isReadOnlySql($sql), 'Write classified as read: ' . $sql);
foreach ($writes as $sql) {
    $threads->forceReplica();
    $replicaCount = count($replicaLink->sql);
    $threads->db->query($sql);
    check(end($primaryLink->sql) === $sql && count($replicaLink->sql) === $replicaCount, 'Non-read SQL must reach only primary: ' . $sql);
}

// 4. Флагът на заявката насочва само нейните SELECT и COUNT и връща основната база
$q = newQuery($threads);
$q->onReplica = true;
$q->fetch();
check($q->seen === array('replica') && $threads->db->dbRole === 'primary', 'A flagged SELECT uses the replica and returns to primary');
check($q->count() === 'replica' && $threads->db->dbRole === 'primary', 'A flagged COUNT uses the replica and returns to primary');
$plain = newQuery($threads);
$plain->fetch();
check($plain->seen === array('primary') && $plain->count() === 'primary', 'Unflagged queries stay on primary');
$clone = clone $q;
check($clone->onReplica === true, 'Clones made by pagers and list filters inherit the flag');
check(core_Manager::callWithoutReplica(function () use ($threads) {
    $inner = newQuery($threads);
    $inner->onReplica = true;
    $inner->fetch();

    return $inner->seen;
}) === array('primary'), 'callWithoutReplica() keeps flagged queries on primary');

// 5. Без данни за основната база записът спира, вместо да отиде в репликата
$threads->db->dbRole = 'replica';
$before = count($replicaLink->sql) + count($primaryLink->sql);
try {
    $threads->db->query('UPDATE t SET a = 1');
    check(false, 'An unrecoverable replica write must be refused');
} catch (RuntimeException $e) {
    check(strpos($e->getMessage(), 'Expect') === 0 && count($replicaLink->sql) + count($primaryLink->sql) === $before, 'The refused write executed nowhere');
}
$threads->db->dbRole = 'primary';

// 6. Пакетите използват същата защита преди установяване на връзка.
$batch = 'UPDATE test_items SET id = 2; DELETE FROM test_items WHERE id = 3';
$threads->forceReplica();
$replicaCount = count($replicaLink->sql);
$threads->db->multyQuery($batch);
check(end($primaryLink->sql) === $batch && count($replicaLink->sql) === $replicaCount, 'A batch never reaches the replica');
check($threads->db->dbRole === 'primary' && !isset($threads->db->__origDbName), 'A batch closes the replica scope');
$threads->db->dbRole = 'replica';
$before = count($primaryLink->sql);
try {
    $threads->db->multyQuery($batch, true);
    check(false, 'An unrecoverable batch must be refused even when silent');
} catch (RuntimeException $e) {
    check(strpos($e->getMessage(), 'Expect') === 0 && count($primaryLink->sql) === $before && count($replicaLink->sql) === $replicaCount, 'The refused batch executed nowhere');
}
$threads->db->dbRole = 'primary';

// 7. Временното изключване обхваща всички активни адаптери, включително при вложени извиквания.
$threads->forceReplica();
$notifications->forceReplica();
$outerThreads = routingSettings($threads->db);
$outerNotifications = routingSettings($notifications->db);
check(core_Manager::callWithoutReplica(function () use ($threads, $notifications, $primaryLink, $replicaLink) {
    foreach (array($threads, $notifications) as $manager) {
        check($manager->db->dbRole === 'primary' && $manager->db->dbHost === 'primary.invalid' && $manager->db->replicaOwner === null, 'Every active adapter switches to its primary');
        $sql = 'SELECT 10 /* primary scope */';
        $before = count($replicaLink->sql);
        $manager->db->query($sql);
        check(end($primaryLink->sql) === $sql && count($replicaLink->sql) === $before, 'Primary scope reads use the actual primary link');
        $manager->forceReplica();
        $manager->unforceReplica();
        check($manager->db->dbRole === 'primary' && isset($manager->db->__origDbName), 'A force/unforce pair preserves suspended ownership');
    }
    core_Manager::callWithoutReplica(function () use ($threads, $notifications) {
        check($threads->db->dbRole === 'primary' && $notifications->db->dbRole === 'primary', 'Nested primary scope keeps all adapters on primary');
    });
    check($threads->db->dbRole === 'primary' && $notifications->db->dbRole === 'primary', 'Leaving an inner scope preserves the outer primary scope');
    $new = newManager();
    check($new->callOnReplica(function () use ($new) { return $new->db->dbRole; }) === 'primary', 'New managers also respect the primary scope');
    check($threads->callOnReplica(function () use ($threads) { return $threads->db->dbRole; }) === 'primary', 'Replica callbacks cannot override a primary scope');

    return 'result';
}) === 'result', 'Primary scope returns the callback value');
check(routingSettings($threads->db) === $outerThreads && routingSettings($notifications->db) === $outerNotifications, 'Every adapter restores its full replica settings and owner');

require dirname(__DIR__) . '/exception/Redirect.class.php';
foreach (array(new RuntimeException('fixture'), new Error('fixture'), new core_exception_Redirect('/synthetic-target')) as $failure) {
    try {
        core_Manager::callWithoutReplica(function () use ($failure, $threads, $notifications) {
            check($threads->db->dbRole === 'primary' && $notifications->db->dbRole === 'primary', 'A failing callback runs on primary');
            throw $failure;
        });
        check(false, 'The failure must propagate');
    } catch (Throwable $caught) {
        // redirect-catch: terminal - test boundary verifies propagation of the original redirect.
        check($caught === $failure, 'The original failure or redirect propagates unchanged');
    }
    check(routingSettings($threads->db) === $outerThreads && routingSettings($notifications->db) === $outerNotifications, 'Failure cleanup restores all adapters');
}
$threads->unforceReplica();
$notifications->unforceReplica();
check($threads->db->dbRole === 'primary' && $notifications->db->dbRole === 'primary', 'Outer scopes still return to primary');
$threads->callOnReplica(function () use ($threads) {
    check($threads->db->dbRole === 'replica', 'A failed primary scope releases the disable flag');
    core_Manager::callWithoutReplica(function () use ($threads) {
        check($threads->db->dbRole === 'primary', 'Primary callbacks suspend an active callOnReplica adapter');
    });
    check($threads->db->dbRole === 'replica', 'The outer callback resumes on replica');
});
echo "OK: {$checks} replica write checks\n";
