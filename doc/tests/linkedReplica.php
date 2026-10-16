<?php

/**
 * Regression checks for replica routing of linked-document suggestions.
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// Real search, link, query and routing methods; synthetic result sets, no database required.
if (PHP_SAPI !== 'cli') exit("Run this regression test from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
$checks = 0;
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function expect($condition, ...$args) { if (!$condition) throw new RuntimeException('Expect: ' . ($args[0] ?? '')); }
function countR($value) { return count($value ?? array()); }
function isDebug() { return false; }
class core_FieldSet { public $fields = array(); }
class core_Mvc
{
    public $db, $fields = array(), $dbTableName = 'fixture_containers', $dbIndexes = array(), $lastQuery, $lastFetchedRec;
    public $_cachedRecords = array(), $readRoles = array();
    public function getQuery()
    {
        $query = new FixtureQuery();
        $query->mvc = $this;
        $query->fields = $this->fields;

        return $this->lastQuery = $query;
    }
    public function invoke($event, $args = array())
    {
        if ($event === 'AfterRead') $this->readRoles[] = $this->db->dbRole;

        return true;
    }
}
class Debug
{
    public static function log($text) {}
    public static function startTimer($name) {}
    public static function stopTimer($name) {}
    public static function getExecutionTime() { return microtime(true); }
}
class core_App { public static $error = ''; public static function isReplicationOK() { return self::$error; } }
class core_Cache { public static function get($type, $key) { return true; } }
class log_Mysql { public static function add($sql, $time) {} }
class doc_Threads { public static function restrictAccess($query) { $query->where('fixture-access'); } }
class plg_Search { public static function applySearch($text, $query, $field) { $query->where('fixture-search'); } }
class dt { public static function addDays($days) { return '2024-01-01 00:00:00'; } }
class str { public static function limitLen($text, $length) { return $text; } }
class cls
{
    public static $models = array();
    public static function get($name) { return self::$models[$name]; }
    public static function getClassName($object) { return get_class($object); }
}
$root = dirname(__DIR__, 2);
require $root . '/core/Manager.class.php';
require $root . '/core/Query.class.php';
require $root . '/core/Db.class.php';
require $root . '/doc/Search.class.php';
require $root . '/doc/Linked.class.php';
class FixtureQuery extends core_Query
{
    public $conditions = array();
    public function where($cond, $or = false) { if ($cond !== null) $this->conditions[] = $cond; return $this; }
    public function orderBy($fields, $direction = '', $priority = 0) { return $this; }
    public function buildQuery() { return 'SELECT id, docClass, docId FROM fixture_containers'; }
}
class FixtureType { public function fromMysql($value) { return $value; } }
class FixtureResult
{
    public $num_rows = 1;
    private $rows = array(array('id' => 101, 'docClass' => 'FixtureDocument', 'docId' => 201));
    public function fetch_array($mode) { return array_shift($this->rows); }
    public function free() {}
}
class FixtureLink
{
    public $sql = array(), $affected_rows = 0;
    public function query($sql, $mode = null) { $this->sql[] = $sql; return new FixtureResult(); }
}
class FixtureDb extends core_Db { public function checkForErrors($action, $silent = false, $link = null) {} }
class FixtureDocument
{
    public $titleRoles = array();
    public function getDocumentRow($id)
    {
        $this->titleRoles[] = cls::get('doc_Search')->db->dbRole;

        return (object) array('title' => 'Synthetic linked document');
    }
    public function getHandle($id) { return 'Fixture' . $id; }
}

define('SEARCH_DB_NAME', 'replica');
define('SEARCH_DB_USER', 'reader');
define('SEARCH_DB_PASS', 'synthetic-password');
define('SEARCH_DB_HOST', 'replica.invalid');
$primary = new FixtureLink();
$replica = new FixtureLink();
core_Db::$links = array('primary.invalid' => array('writer' => array('primary' => $primary)),
    'replica.invalid' => array('reader' => array('replica' => $replica)));
$containers = new core_Mvc();
foreach (array('id', 'docClass', 'docId') as $field) $containers->fields[$field] = (object) array('type' => new FixtureType());
cls::$models['doc_Containers'] = $containers;
$search = new doc_Search();
$search->db = (new ReflectionClass('FixtureDb'))->newInstanceWithoutConstructor();
$search->db->dbHost = 'primary.invalid';
$search->db->dbName = 'primary';
$search->db->dbUser = 'writer';
cls::$models['doc_Search'] = $search;
$document = cls::$models['FixtureDocument'] = new FixtureDocument();
$search->description();
check($search->db->dbRole === 'primary' && $search->dbTableName === $containers->dbTableName, 'Describing search does not switch its adapter');
$params = array('mvc' => 'doc_Search', 'maxSuggestions' => 10);
$result = doc_Linked::prepareLinkDocId($params, null, 'synthetic search');
check($result === array(101 => 'Synthetic linked document (Fixture201)'), 'Document suggestions retain their titles and handles');
check(count($replica->sql) === 1 && !$primary->sql, 'The suggestion SELECT runs once on replica');
check($search->readRoles === array('primary') && $document->titleRoles === array('primary'), 'Read hooks and document titles run after returning to primary');
check($search->db->dbRole === 'primary' && !isset($search->db->__origDbName), 'The search adapter stays on primary after suggestions');
check($search->lastQuery->limit === 10 && in_array('fixture-access', $search->lastQuery->conditions, true) &&
    in_array("#state != 'rejected'", $search->lastQuery->conditions, true), 'Suggestion limits and access/state filters remain in place');
check(isset($search->_cachedRecords['101|*']), 'Suggestion records are still cached');

doc_Linked::prepareLinkDocId($params, 1, '', array(101));
check(count($replica->sql) === 2 && !$primary->sql, 'Resolving a selected document also uses replica');
core_Manager::callWithoutReplica(function () use ($params) { doc_Linked::prepareLinkDocId($params, 10, 'synthetic'); });
check(count($replica->sql) === 2 && count($primary->sql) === 1, 'An explicit primary scope also applies to suggestions');
core_App::$error = 'Synthetic replica unavailable';
doc_Linked::prepareLinkDocId($params, 10, 'synthetic');
check(count($replica->sql) === 2 && count($primary->sql) === 2, 'Suggestions fall back to primary when replica is unavailable');
check(array_unique($search->readRoles) === array('primary') && array_unique($document->titleRoles) === array('primary'), 'All result processing remains on primary');
echo "OK: {$checks} linked document replica checks\n";
