<?php
/**
 * CLI регресии за поправката с реалните методи и изолирано хранилище в паметта.
 * Базата, cron dispatcher-ът и външните ефекти не се стартират.
 *
 * @category bgerp
 * @package doc
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
$root = $argv[1] ?? dirname(__DIR__, 2);
error_reporting(E_ALL);
date_default_timezone_set('UTC');
require $root . '/core/BaseClass.class.php';
require $root . '/core/FieldSet.class.php';
require $root . '/core/Query.class.php';
function countR($v) { return is_countable($v) ? count($v) : 0; }
function defIfNot($k, $v) { if (!defined($k)) define($k, $v); }
function expect($v) { if (!$v) throw new core_exception_Expect('Expectation failed'); }
function reportException($e) { Audit::$errors[] = get_class($e) . ': ' . $e->getMessage(); }
class core_exception_Expect extends ErrorException {}
class core_exception_Redirect extends Exception {}
class Audit
{
    public static $tests = 0, $failures = 0, $syncDocument = false, $failSave = false, $throwDiscovery = false;
    public static $failClass = null, $throwQuery = null, $throwSave = null, $excludeKeywords = false;
    public static $rows = array(), $writes = array(), $deletes = array(), $errors = array(), $queries = array();
    public static function reset()
    {
        self::$rows = self::$writes = self::$deletes = self::$errors = self::$queries = array();
        cls::$instances = array();
        self::$syncDocument = self::$failSave = self::$throwDiscovery = false;
        self::$failClass = self::$throwQuery = self::$throwSave = null;
        self::$excludeKeywords = false;
        Mode::$flags = array('isMigrate' => true, 'dontUpdateThread' => true);
        core_Users::$current = 1;
        core_Debug::$isLogging = true;
        doc_Setup::$all = 'yes';
        foreach (array('doc_Threads' => 'updateQueue', 'doc_Folders' => 'updateByContentOnShutdown') as $class => $property) {
            if (property_exists($class, $property)) {
                $p = new ReflectionProperty($class, $property); $p->setAccessible(true); if ($p->isStatic()) $p->setValue(null, array());
            }
        }
        self::put('doc_Folders', array('id' => 1, 'coverClass' => 90, 'coverId' => 1));
        self::put('doc_UnsortedFolders', array('id' => 1, 'folderId' => 1));
    }
    public static function put($class, $values)
    {
        $r = (object) array_merge(array('id' => 1, 'createdOn' => '2026-01-01 11:55:00', 'modifiedOn' => '2026-01-01 11:55:00', 'createdBy' => 1,
            'state' => 'active', 'folderId' => 1, 'threadId' => 10, 'containerId' => 100, 'originId' => null,
            'docClass' => 20, 'docId' => 1, 'visibleForPartners' => 'no', 'searchKeywords' => 'fixture',
            'firstContainerId' => 100, 'firstDocClass' => 20, 'firstDocId' => 1, 'allDocCnt' => 1, 'partnerDocCnt' => 0,
            'lastAuthor' => 1, 'lastState' => 'active', 'partnerDocLast' => null, 'activatedOn' => null, 'activatedBy' => 1,
            'last' => '2026-01-01 11:55:00', 'shared' => '', 'modifiedBy' => 1,
            'inCharge' => 1, 'title' => 'Synthetic fixture', 'coverClass' => 90, 'coverId' => 1, 'lastUsedOn' => null), $values);
        self::$rows[$class][$r->id] = $r;
        return $r;
    }
    public static function matches($rec, $condition)
    {
        $q = new AuditQuery('doc_Containers');
        if (is_array($condition)) $condition = $q->substituteArray($condition);
        if ($condition === null || $condition === '') return true;
        if (is_numeric($condition)) return ($rec->id ?? null) == $condition;
        // Compile only the small SQL predicate subset used by the inspected methods.
        $expr = preg_replace_callback('/#(\w+)\s+IS\s+(NOT\s+)?NULL/i', function ($m) use ($rec) {
            $v = ($rec->{$m[1]} ?? null) === null;
            if (!empty($m[2])) $v = !$v;
            return $v ? 'true' : 'false';
        }, $condition);
        $expr = preg_replace_callback('/#(\w+)\s*(>=|<=|!=|<>|=|>|<)\s*(\x27[^\x27]*\x27|-?\d+)/', function ($m) use ($rec) {
            $a = $rec->{$m[1]} ?? null;
            $b = $m[3][0] === "'" ? substr($m[3], 1, -1) : (int) $m[3];
            if ($a === null) return 'false';
            // MySQL compares an integer column against an empty string as zero.
            if (is_int($a) && $b === '') $b = 0;
            switch ($m[2]) {
                case '=': $v = $a == $b; break;
                case '!=': case '<>': $v = $a != $b; break;
                case '>=': $v = $a >= $b; break;
                case '<=': $v = $a <= $b; break;
                case '>': $v = $a > $b; break;
                case '<': $v = $a < $b; break;
            }
            return $v ? 'true' : 'false';
        }, $expr);
        $expr = preg_replace_callback('/#interfaces LIKE \'%\|(\d+)\|%\'/i', function ($m) use ($rec) {
            return strpos($rec->interfaces ?? '', '|' . $m[1] . '|') !== false ? 'true' : 'false';
        }, $expr);
        $expr = str_ireplace(array('AND', 'OR'), array('&&', '||'), $expr);
        if (!preg_match('/^(?:true|false|\s|\(|\)|&|\|)+$/', $expr)) throw new RuntimeException('Unsupported fixture predicate: ' . $condition);
        return eval('return ' . $expr . ';');
    }
}
class AuditDb { public function escape($s) { return addslashes((string) $s); } }
class AuditQuery extends core_Query
{
    public $model, $position = 0, $snapshot = null, $sort = array();
    public function __construct($model) { $this->model = $model; $this->mvc = (object) array('db' => new AuditDb()); }
    public function show($fields) { $this->show = array_fill_keys(explode(',', $fields), true); return $this; }
    public function orderBy($fields, $direction = '', $priority = 0) { $this->sort[] = array(ltrim($fields, '#'), $direction); return $this; }
    public function selected($cond = null)
    {
        $rows = array();
        foreach (Audit::$rows[$this->model] ?? array() as $r) {
            if (!Audit::matches($r, $cond)) continue;
            foreach ($this->where as $w) if (!Audit::matches($r, $w)) continue 2;
            $copy = clone $r;
            if (Audit::$excludeKeywords && empty($this->show['*']) && empty($this->show['searchKeywords'])) unset($copy->searchKeywords);
            $rows[] = $copy;
        }
        if ($this->sort) usort($rows, function ($a, $b) {
            foreach ($this->sort as list($f, $direction)) {
                $cmp = ($a->{$f} ?? null) <=> ($b->{$f} ?? null);
                if ($cmp) return strtoupper($direction) === 'DESC' ? -$cmp : $cmp;
            }
            return 0;
        });
        return $this->limit ? array_slice($rows, 0, $this->limit) : $rows;
    }
    public function fetch($cond = null, $preventEvent = false)
    {
        if (Audit::$throwQuery === $this->model) throw new core_exception_Redirect('Fixture query redirect');
        if ($this->snapshot === null) {
            Audit::$queries[] = array($this->model, $this->where);
            $this->snapshot = $this->selected($cond);
        }
        return $this->snapshot[$this->position++] ?? false;
    }
    public function count($cond = null, $limit = 0) { return count($this->selected($cond)); }
}
class core_Mvc extends core_BaseClass
{
    public static function getQuery() { return new AuditQuery(get_called_class()); }
    public static function fetch($cond, $fields = '*', $cache = true)
    {
        expect($cond !== null && (is_int($cond) || is_string($cond) || is_array($cond)), $cond);
        return static::getQuery()->fetch($cond);
    }
    public static function fetchField($cond, $field = 'id', $cache = true) { $r = static::fetch($cond); return $r->{$field} ?? null; }
    public static function save($rec, $fields = null, $mode = null)
    {
        $c = get_called_class();
        if (Audit::$throwSave === $c) throw new core_exception_Redirect('Fixture save redirect');
        if ((Audit::$failSave && $c === 'FixtureDocument') || Audit::$failClass === $c) return false;
        if (empty($rec->id)) $rec->id = count(Audit::$rows[$c] ?? array()) + 1000;
        $old = Audit::$rows[$c][$rec->id] ?? (object) array('id' => $rec->id);
        $names = $fields === null ? array_keys((array) $rec) : (is_array($fields) ? $fields : explode(',', $fields));
        foreach ($names as $f) { $f = trim($f); $old->{$f} = $rec->{$f} ?? null; }
        Audit::$rows[$c][$rec->id] = clone $old;
        Audit::$writes[] = array($c, $rec->id, $fields);
        // DocumentPlg извиква update след save; тук изпълняваме реалния update метод.
        if (Audit::$syncDocument && $c === 'FixtureDocument') doc_Containers::update_($rec->containerId ?? null, false, true);
        return $rec->id;
    }
    public function save_($rec, $fields = null, $mode = null) { return static::save($rec, $fields, $mode); }
    public static function delete($cond)
    {
        $c = get_called_class(); $n = 0;
        foreach (static::getQuery()->selected($cond) as $rec) { unset(Audit::$rows[$c][$rec->id]); Audit::$deletes[] = array($c, $rec->id); $n++; }
        return $n;
    }
    public static function count() { return count(Audit::$rows[get_called_class()] ?? array()); }
    public static function logNotice($message, $id = null) {}
    public static function logWarning($message, $id = null) {}
    public static function logInfo($message, $id = null) {}
    public static function __callStatic($method, $args) { return call_user_func_array(array(get_called_class(), $method . '_'), $args); }
}
class core_Manager extends core_Mvc {}
class core_Master extends core_Manager {}
class cls
{
    public static $instances = array();
    public static function name($n) { return core_Classes::$map[$n] ?? $n; }
    public static function get($n) { $n = self::name($n); if (!isset(self::$instances[$n])) self::$instances[$n] = new $n(); return self::$instances[$n]; }
    public static function load($n, $silent = false) { return $n && class_exists(self::name($n)); }
    public static function haveInterface($i, $o) { return $o instanceof FixtureDocument; }
}
class core_Interfaces { public function fetchByName($name) { return 7; } }
class core_Classes extends core_Manager
{
    public static $map = array(20 => 'FixtureDocument', 90 => 'doc_UnsortedFolders');
    public static function getId($c) { return array_search(is_object($c) ? get_class($c) : $c, self::$map); }
    public static function getOptionsByInterface($i, $field = null) { if (Audit::$throwDiscovery) throw new core_exception_Redirect('Fixture redirect'); return $i === 'doc_DocumentIntf' ? array(20 => 'FixtureDocument') : array(90 => 'doc_UnsortedFolders'); }
}
class FixtureDocument extends core_Master
{
    public $fields = array('modifiedOn' => true), $details = '';
    public function isVisibleForPartners($r) { return ($r->visibleForPartners ?? 'no') === 'yes'; }
    public function getSearchKeywords($r) { return 'fixture'; }
    public function getShared($id) { return array(); }
}
class doc_UnsortedFolders extends core_Master { public function getFolderTitle($id, $flag) { return 'Fixture'; } }
class core_Debug { public static $isLogging = true; }
class core_Users { public static $current = 1; public static function getCurrent() { return self::$current; } public static function getFirstAdmin() { return 1; } public static function haveRole($role, $id = null) { return false; } }
class doc_Setup { public static $all = 'yes'; public static function get($k) { return $k === 'REPAIR_ALL' ? self::$all : 120; } }
class core_Packs { public static function getConfig($p) { return (object) array('DOC_REPAIR_DELAY' => 120); } }
class core_Cron { public static function getPeriod($id) { return 300; } }
class dt
{
    public static function now() { return '2026-01-01 12:00:00'; }
    public static function subtractSecs($s, $time = null) { return date('Y-m-d H:i:s', strtotime($time ?? self::now()) - $s); }
}
class Mode { public static $flags = array(); public static function is($k) { return !empty(self::$flags[$k]); } }
class arr { public static function make($v, $assoc = false) { return $v ? explode(',', $v) : array(); } }
class doc_ThreadUsers { public static function addShared(...$args) {} public static function addSubscribed(...$args) {} public static function removeContainer(...$args) {} public static function getShared($id) { return array(); } }
class core_Locks { public static function obtain(...$args) {} public static function release($id) {} }
class keylist { public static function fromArray($v) { return implode('|', $v); } }
class core_ObjectReference { public function __construct(...$args) {} public function getThreadState() { return null; } }
require $root . '/doc/Folders.class.php';
require $root . '/doc/Threads.class.php';
require $root . '/doc/Containers.class.php';
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
function verify($v, $message) { if (!$v) throw new RuntimeException($message); }
function runCase($title, $fn)
{
    Audit::reset();
    Audit::$tests++;
    try {
        $fn();
        verify(!Audit::$errors, implode('; ', Audit::$errors));
        echo "PASS: {$title}\n";
    } catch (Throwable $e) {
        // redirect-catch: terminal - самостоятелният CLI тест отчита неприхваната грешка.
        Audit::$failures++;
        echo "FAIL: {$title}: " . get_class($e) . ': ' . $e->getMessage() . "\n";
    }
}
function normalGraph()
{
    Audit::put('doc_Threads', array('id' => 10));
    Audit::put('doc_Containers', array('id' => 100));
    Audit::put('FixtureDocument', array('id' => 1));
}
runCase('cron time window, normal records, dispatch scope', function () {
    normalGraph(); $res = (new doc_Containers())->cron_Repair();
    verify(array_keys($res) === array('folders', 'threads', 'containers'), 'cron scope changed');
    verify(!Audit::$writes && !Audit::$deletes && !Audit::$errors, 'healthy records were changed');
    $seen = array();
    foreach (Audit::$queries as list($class, $conditions)) {
        if (!in_array($class, array('doc_Folders', 'doc_Threads', 'doc_Containers')) || count($conditions) < 2) continue;
        if (strpos($conditions[0], '11:48:00') === false) continue;
        $seen[$class] = array_slice($conditions, 0, 2);
    }
    verify(count($seen) === 3, 'missing query bounds');
});
runCase('inclusive boundaries and recent/old exclusion', function () {
    $q = new AuditQuery('doc_Containers'); doc_Folders::prepareRepairDateQuery($q, '2026-01-01 11:50:00', null, 120);
    $cases = array('11:47:59' => false, '11:48:00' => true, '11:50:00' => true, '11:58:00' => true, '11:58:01' => false, '12:00:00' => false);
    foreach ($cases as $time => $wanted) {
        $r = (object) array('modifiedOn' => '2026-01-01 ' . $time); $ok = true;
        foreach ($q->where as $w) $ok = $ok && Audit::matches($r, $w);
        verify($ok === $wanted, $time);
    }
    $q->where('#folderId IS NULL'); $q->orWhere('#threadId IS NULL');
    verify(count($q->where) === 3, 'OR escaped date predicates');
});
runCase('thread first container points at another rejected document', function () {
    normalGraph(); Audit::$rows['doc_Threads'][10]->firstContainerId = 200;
    Audit::put('doc_Containers', array('id' => 200, 'threadId' => 99, 'docId' => 2, 'state' => 'rejected'));
    Audit::put('FixtureDocument', array('id' => 2, 'containerId' => 200, 'threadId' => 99, 'state' => 'rejected'));
    $res = doc_Threads::repairAll(); $r = Audit::$rows['doc_Threads'][10];
    verify($r->firstContainerId === 100 && $r->firstDocId === 1 && $r->state === 'active', 'unexpected repair result');
});
runCase('thread first container ID is non-null but missing', function () {
    normalGraph(); Audit::$rows['doc_Threads'][10]->firstContainerId = 999;
    doc_Threads::repair();
    verify(Audit::$rows['doc_Threads'][10]->firstContainerId === 100, 'unexpected repair result');
});
runCase('thread repairAll counters survive outer repair', function () {
    normalGraph(); Audit::$rows['doc_Threads'][10]->firstDocId = 99;
    $res = doc_Threads::repair();
    verify(Audit::$rows['doc_Threads'][10]->firstDocId === 1 && $res['firstDocId'] === 1, 'unexpected repair result');
});
runCase('container folder repair counters', function () {
    normalGraph(); Audit::$rows['doc_Containers'][100]->folderId = 99;
    $res = doc_Containers::repair();
    verify(Audit::$rows['doc_Containers'][100]->folderId === 1 && $res['folderId'] === 1, 'unexpected repair result');
});
runCase('repairCover restores debug logging even on empty set', function () {
    Audit::$rows['doc_UnsortedFolders'] = array();
    doc_Folders::repairCover();
    verify(core_Debug::$isLogging === true, 'unexpected repair result');
});
foreach (array('containerId', 'threadId', 'folderId') as $field) {
    foreach (array('active', 'rejected') as $state) runCase("recoverable document: {$state}, missing {$field}", function () use ($field, $state) {
        normalGraph(); $r = Audit::$rows['FixtureDocument'][1]; $r->{$field} = null; $r->state = $state;
        $res = doc_Containers::repairDoc();
        verify(isset(Audit::$rows['FixtureDocument'][1]) && Audit::$rows['FixtureDocument'][1]->{$field}, 'recovery failed');
        verify(!Audit::$deletes, 'recoverable document deleted');
        verify(Audit::$rows['FixtureDocument'][1]->state === $state, 'document state changed');
        verify(($res[$field] ?? 0) === 1, 'recovery count');
    });
}
runCase('container points to existing wrong document ID', function () {
    normalGraph(); Audit::$rows['doc_Containers'][100]->docId = 2;
    Audit::put('FixtureDocument', array('id' => 2, 'containerId' => 200, 'threadId' => 99));
    doc_Containers::repairAll();
    verify(Audit::$rows['doc_Containers'][100]->docId === 1, 'unexpected repair result');
});
runCase('wrong document ID with mismatched state propagates wrong structure', function () {
    normalGraph(); Audit::$rows['doc_Containers'][100]->docId = 2;
    Audit::put('FixtureDocument', array('id' => 2, 'containerId' => 200, 'threadId' => 99, 'folderId' => 9, 'state' => 'draft'));
    doc_Containers::repairAll(); $r = Audit::$rows['doc_Containers'][100];
    verify($r->threadId === 10 && $r->folderId === 1 && $r->state === 'active', 'unexpected repair result');
});
runCase('NULL modifiedOn and old folder edited recently', function () {
    normalGraph(); Audit::$rows['doc_Containers'][100]->modifiedOn = null; Audit::$rows['doc_Containers'][100]->folderId = null;
    Audit::$rows['doc_Folders'][1]->createdOn = '2025-01-01 00:00:00'; Audit::$rows['doc_Folders'][1]->inCharge = null;
    (new doc_Containers())->cron_Repair();
    verify(Audit::$rows['doc_Containers'][100]->folderId === null && Audit::$rows['doc_Folders'][1]->inCharge === null, 'unexpected repair result');
});
runCase('empty repair then repeated healthy repair', function () {
    $a = (new doc_Containers())->cron_Repair(); normalGraph();
    $a = (new doc_Containers())->cron_Repair(); $b = (new doc_Containers())->cron_Repair();
    verify($a === $b && !Audit::$writes && !Audit::$errors, 'healthy repetition failed');
    verify(core_Debug::$isLogging === true, 'cron logging not restored');
});

runCase('counts from both repair phases are added', function () {
    normalGraph();
    Audit::$rows['doc_Threads'][10]->firstDocId = null;
    Audit::put('doc_Threads', array('id' => 11, 'firstContainerId' => 101, 'firstDocId' => 99));
    Audit::put('doc_Containers', array('id' => 101, 'threadId' => 11, 'docId' => 2));
    Audit::put('FixtureDocument', array('id' => 2, 'containerId' => 101, 'threadId' => 11));
    $res = doc_Threads::repair();
    verify(($res['firstDocId'] ?? 0) === 2, 'both phases must contribute');
    verify(Audit::$rows['doc_Threads'][10]->firstDocId === 1 && Audit::$rows['doc_Threads'][11]->firstDocId === 2, 'both records repaired');
});
runCase('multiple folder repairs are counted', function () {
    Audit::put('doc_Folders', array('id' => 2, 'coverId' => 999));
    Audit::put('doc_Folders', array('id' => 3, 'coverId' => 998));
    $res = doc_Folders::repair();
    verify(($res['coverId'] ?? 0) === 2, 'each repaired folder must be counted');
    verify(Audit::$rows['doc_Folders'][2]->coverId != 999 && Audit::$rows['doc_Folders'][3]->coverId != 998, 'folder references repaired');
});
runCase('ambiguous existing document reference is preserved', function () {
    normalGraph(); unset(Audit::$rows['FixtureDocument'][1]);
    Audit::$rows['doc_Containers'][100]->docId = 2;
    Audit::put('FixtureDocument', array('id' => 2, 'containerId' => 200, 'threadId' => 99, 'folderId' => 9, 'state' => 'draft'));
    $before = clone Audit::$rows['doc_Containers'][100];
    doc_Containers::repairAll();
    verify(Audit::$rows['doc_Containers'][100] == $before && !Audit::$deletes, 'ambiguous link must not propagate or delete');
});
runCase('partial container also validates existing document ID', function () {
    normalGraph(); Audit::$rows['doc_Containers'][100]->docId = 2; Audit::$rows['doc_Containers'][100]->searchKeywords = '';
    Audit::put('FixtureDocument', array('id' => 2, 'containerId' => 200, 'threadId' => 99));
    $res = doc_Containers::repair();
    verify(Audit::$rows['doc_Containers'][100]->docId === 1 && Audit::$rows['doc_Containers'][100]->threadId === 10, 'partial repair validated identity');
    verify(($res['docId'] ?? 0) === 1, 'identity repair counted');
});
runCase('unrecoverable missing document removes only its orphan container', function () {
    normalGraph(); unset(Audit::$rows['FixtureDocument'][1]);
    $res = doc_Containers::repairAll();
    verify(!isset(Audit::$rows['doc_Containers'][100]) && ($res['del_cnt'] ?? 0) === 1, 'existing orphan cleanup retained');
    verify(count(Audit::$deletes) === 1, 'single deletion');
});
runCase('missing document backlink does not delete container', function () {
    normalGraph(); Audit::$rows['FixtureDocument'][1]->containerId = null;
    doc_Containers::repairAll();
    verify(isset(Audit::$rows['doc_Containers'][100]) && !Audit::$deletes, 'recoverable backlink preserved');
});
foreach (array('active', 'rejected') as $state) {
    runCase('all missing links are saved together before container update: ' . $state, function () use ($state) {
        normalGraph(); Audit::$syncDocument = true;
        $rec = Audit::$rows['FixtureDocument'][1];
        $rec->containerId = $rec->threadId = $rec->folderId = null; $rec->state = $state;
        $res = doc_Containers::repairDoc();
        $rec = Audit::$rows['FixtureDocument'][1]; $container = Audit::$rows['doc_Containers'][100];
        verify($rec->containerId === 100 && $rec->threadId === 10 && $rec->folderId === 1 && $rec->state === $state, 'document links/state');
        verify($container->threadId === 10 && $container->folderId === 1 && $container->state === $state, 'container links/state');
        verify(!Audit::$deletes && ($res['containerId'] ?? 0) === 1 && ($res['threadId'] ?? 0) === 1 && ($res['folderId'] ?? 0) === 1, 'one count per corrected field');
        $writes = count(Audit::$writes); $again = doc_Containers::repairDoc();
        verify(!$again && count(Audit::$writes) === $writes, 'second run does not write');
    });
}
runCase('failed document save is not reported as repaired', function () {
    normalGraph(); Audit::$rows['FixtureDocument'][1]->threadId = null; Audit::$failSave = true;
    $res = doc_Containers::repairDoc();
    verify(!$res && Audit::$rows['FixtureDocument'][1]->threadId === null && !Audit::$deletes, 'unsaved repair must not be counted/deleted');
});
runCase('limited repair mode preserves complete documents', function () {
    normalGraph(); doc_Setup::$all = 'no';
    doc_Containers::repairDoc(); doc_Threads::repair(); doc_Containers::repair();
    verify(!Audit::$writes && !Audit::$deletes, 'limited mode changed healthy records');
});
foreach (array('repairDoc', 'repairCover') as $method) {
    runCase('debug logging restored after redirect: ' . $method, function () use ($method) {
        Audit::$throwDiscovery = true;
        $caught = false;
        try {
            if ($method === 'repairDoc') doc_Containers::repairDoc();
            else doc_Folders::repairCover();
        } catch (core_exception_Redirect $e) { $caught = true; }
        verify($caught && core_Debug::$isLogging === true, 'redirect must propagate and restore logging');
    });
}
runCase('disabled debug logging stays disabled', function () {
    core_Debug::$isLogging = false;
    doc_Folders::repairCover();
    verify(core_Debug::$isLogging === false, 'previous logging state preserved');
});

runCase('cron does not reject a thread from a conflicting document ID', function () {
    normalGraph(); Audit::$rows['doc_Containers'][100]->docId = 2;
    Audit::put('FixtureDocument', array('id' => 2, 'containerId' => 200, 'threadId' => 99, 'state' => 'rejected'));
    (new doc_Containers())->cron_Repair();
    verify(Audit::$rows['doc_Containers'][100]->docId === 1, 'document identity repaired');
    verify(Audit::$rows['doc_Threads'][10]->state === 'active', 'thread state must come from its own document');
});
runCase('own rejected first document still rejects its thread', function () {
    normalGraph(); Audit::$rows['FixtureDocument'][1]->state = 'rejected';
    $res = doc_Threads::repairAll();
    verify(Audit::$rows['doc_Threads'][10]->state === 'rejected' && ($res['firstContainerIdState'] ?? 0) === 1, 'existing rejected-state synchronization retained');
});

runCase('missing class resolves the document ID together with its class', function () {
    normalGraph(); Audit::$rows['doc_Containers'][100]->docClass = null; Audit::$rows['doc_Containers'][100]->docId = 77;
    Audit::put('core_Classes', array('id' => 20, 'name' => 'FixtureDocument', 'interfaces' => '|7|'));
    $res = doc_Containers::repair();
    $rec = Audit::$rows['doc_Containers'][100];
    verify($rec->docClass === 20 && $rec->docId === 1 && !Audit::$deletes, 'class and document must be consistent');
    verify(($res['docClass'] ?? 0) === 1 && ($res['docId'] ?? 0) === 1, 'both repairs counted');
});
runCase('deleted unresolved class is not updated afterwards', function () {
    normalGraph(); Audit::$rows['doc_Containers'][100]->docClass = null;
    unset(Audit::$rows['FixtureDocument'][1]);
    $res = doc_Containers::repair();
    verify(!isset(Audit::$rows['doc_Containers'][100]) && ($res['del_cnt'] ?? 0) === 1, 'orphan deletion is counted once');
});

runCase('limited mode does not expand orphan deletion', function () {
    normalGraph(); doc_Setup::$all = 'no';
    Audit::$rows['doc_Containers'][100]->searchKeywords = '';
    unset(Audit::$rows['FixtureDocument'][1]);
    doc_Containers::repair();
    verify(isset(Audit::$rows['doc_Containers'][100]) && !Audit::$deletes, 'limited mode must not expand cleanup');
});

require __DIR__ . '/repairMatrix.php';

echo Audit::$tests . ' scenarios, ' . Audit::$failures . " failures\n";
exit(Audit::$failures ? 1 : 0);
