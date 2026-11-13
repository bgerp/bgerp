<?php

/**
 * Проверки на полетата, записите и фоновото попълване на plg_ExpandInput.
 *
 * @category  bgerp
 * @package   plg
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function expect($ok, ...$args) { if (!$ok) throw new RuntimeException('Expect failed'); }
function error(...$args) { throw new RuntimeException(implode(' ', array_filter($args, 'is_string'))); }
function countR($v) { return is_countable($v) ? count($v) : 0; }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function setPartIfNot(&$object, $key, ...$values) {
    foreach ($values as $v) {
        if (is_array($object)) { if (!isset($object[$key]) && isset($v)) $object[$key] = $v; }
        elseif (!isset($object->$key) && isset($v)) $object->$key = $v;
    }
}
function haveRole($role) { return false; }
function tr($text) { return $text; }
class core_Debug {
    public static $timers = array();
    public static function startTimer($n) {}
    public static function stopTimer($n) {}
    public static function log($m) {}
}
class_alias('core_Debug', 'Debug');
class core_App { public static function setTimeLimit($n) {} }
class core_Permanent {
    const FOREVER_VALUE = 0;
    public static $values = array();
    public static function get($key) { return self::$values[$key] ?? null; }
    public static function set($key, $value, $ttl) { self::$values[$key] = $value; }
}
class dt { public static function addSecs($n) { return $n; } }
class core_CallOnTime {
    public static $calls = array();
    public static function setOnce($c, $m, $data, $when) { self::$calls[serialize($data)] = clone $data; }
}
class bgerp_Setup { public static $search = 'yes'; public static function get($key) { return self::$search; } }
class cls {
    public static $models = array();
    public static function get($name, $params = null) {
        if (is_object($name)) return $name;
        if (isset(self::$models[$name])) return self::$models[$name];
        $object = new $name();
        if ($params !== null && method_exists($object, 'init')) $object->init($params);
        return $object;
    }
    public static function getClassName($o) { return is_object($o) ? get_class($o) : $o; }
    public static function isSubclass($o, $c) { return is_a($o, $c); }
}
$root = dirname(__DIR__, 2);
foreach (array('core/BaseClass', 'core/Array', 'core/String', 'core/Type', 'core/FieldSet', 'core/Mvc', 'core/Manager', 'core/Master', 'embed/Manager', 'type/Keylist', 'type/Keylist2', 'type/UserList', 'type/Users', 'type/Treelist', 'type/Text', 'type/Int') as $file) {
    require $root . '/' . $file . '.class.php';
}
class_alias('core_Array', 'arr');
class_alias('core_String', 'str');
class_alias('type_Keylist', 'keylist');
class core_Plugin extends core_BaseClass {}
require $root . '/plg/ExpandInput.class.php';
require $root . '/crm/ContragentGroupsPlg.class.php';

class ExpandGroups {
    public static function fetch($id) {
        $parents = array(10 => null, 20 => null, 30 => 20, 40 => 20);
        return array_key_exists($id, $parents) ? (object) array('id' => $id, 'parentId' => $parents[$id]) : false;
    }
}
class ExpandDb {
    public $sql = array(), $beforeWrite, $fail = false, $nextId = 100;
    public function escape($v) { return addslashes((string) $v); }
    public function query($sql, $silent = false, $replication = null) {
        if ($this->beforeWrite) { $cb = $this->beforeWrite; $this->beforeWrite = null; $cb(); }
        if ($this->fail) throw new RuntimeException('Synthetic database failure');
        $this->sql[] = $sql;
        return true;
    }
    public function insertId() { return ++$this->nextId; }
}
class ExpandQuery {
    public $mvc, $fields = array(), $where = array(), $shown, $cap = PHP_INT_MAX, $result, $cursor = 0;
    public function __construct($m) { $this->mvc = $m; }
    public function show($fields) { $this->shown = array_map('trim', explode(',', $fields)); }
    public function orderBy($f, $d) { check($f === 'id' && $d === 'ASC', 'Stable cursor order'); }
    public function limit($n) { $this->cap = (int) $n; }
    public function where($cond) { $this->where[] = $cond; }
    public function orWhere($cond) { $this->where[] = array('OR', $cond); }
    public function count() { throw new RuntimeException('Do not count the whole table'); }
    public function likeKeylist($f, $v) { $this->where[] = array('LOCATE', $f, $v); }
    public function notLikeKeylist($f, $v) { $this->where[] = array('NOT LOCATE', $f, $v); }
    public function EXT($f, $m, $p) { $this->fields[$f] = $p; }
    public function fetch($condition = null, $preventEvent = false) {
        if (!isset($this->result)) {
            $this->result = array();
            foreach ($this->mvc->rows as $r) {
                foreach ($this->where as $w) {
                    if (is_array($w)) {
                        if ($w[0] === "#id > '[#1#]'" && $r->id <= $w[1]) continue 2;
                        if ($w[0] === "#id = '[#1#]'" && $r->id != $w[1]) continue 2;
                    } elseif (strpos($w, 'IS NULL OR') !== false) {
                        $f = $this->mvc->expandFieldName;
                        if (($r->$f ?? '') === '' || ($r->{$f . '36'} ?? '') !== '') continue 2;
                    } else {
                        throw new RuntimeException('Unexpected condition');
                    }
                }
                $c = clone $r;
                if ($this->shown) foreach (array_keys((array) $c) as $f) if (!in_array($f, $this->shown)) unset($c->$f);
                $this->result[] = $c;
                if (count($this->result) >= $this->cap) break;
            }
        }
        $rec = $this->result[$this->cursor++] ?? false;
        if ($rec && !$preventEvent) $this->mvc->invoke('AfterRead', array(&$rec));
        return $rec;
    }
}
class ExpandFixture extends embed_Manager {
    public $rows = array(), $queries = 0, $writes = 0;
    public function __construct($field = 'groups', $visibility = null, $predeclared = false) {
        $this->className = get_class($this);
        $this->dbTableName = 'test_expand_items';
        $this->db = new ExpandDb();
        $this->expandFieldName = $field;
        $this->expandInputFieldName = $field . 'Input';
        $this->_plugins['plg_ExpandInput'] = new plg_ExpandInput();
        $this->FLD('id', 'int');
        $this->FLD('state', 'text');
        $this->FLD('driverClass', 'text');
        $this->FLD($field, 'keylist(mvc=ExpandGroups,select=id)', 'caption=Groups');
        if ($visibility) $this->setParams($field, array('input' => $visibility, 'column' => 'none', 'single' => 'none'));
        if ($predeclared) $this->FLD($field . 'Input', 'keylist(mvc=ExpandGroups,select=id)', 'caption=Existing,input=hidden');
        cls::$models[$this->className] = $this;
        plg_ExpandInput::on_AfterDescription($this);
    }
    public static function fetch($id, $fields = '*', $cache = true) { return cls::$models[get_called_class()]->rows[$id] ?? false; }
    public function getQuery_($params = array()) { $this->queries++; return new ExpandQuery($this); }
    public static function getDriver($rec) { return !empty($rec->driverClass) ? new $rec->driverClass() : false; }
    public static function getDriverFields($d, $single = false, $fieldset = false) { return array(); }
    public function dbTableUpdated_() { $this->writes++; }
    public static function logDebug($msg, $objectId = null, $lifeDays = 7) {}
    public function addTestPlugin($p) { $this->_plugins[] = $p; }
}
function testRow($id, $f, $input, $expanded, $encoded) {
    return (object) array('id' => $id, $f . 'Input' => $input, $f => $expanded, $f . '36' => $encoded, 'state' => 'closed');
}
function lastSql($m) { return end($m->db->sql); }

// The inherited hidden transport also keeps keylist2's array-based input contract.
foreach (array(new type_Keylist(), new type_Keylist2(), new type_UserList(), new type_Treelist()) as $type) {
    foreach (array(null, '', '|10|', '|10|30|') as $value) {
        $request = array();
        parse_str(http_build_query(array('tags' => $type->toHidden($value))), $request);
        check(type_Keylist::toArray($type->fromVerbal($request['tags'] ?? null)) == type_Keylist::toArray($value), 'Keylist and keylist2 hidden input round trip without ExpandInput');
    }
}

class ExpandUsersType extends type_Users {
    public function prepareOptions($value = null) { $this->options = array('team_10' => (object) array('keylist' => '|10|30|')); }
}
$usersType = new ExpandUsersType();
foreach (array(null, '', '|10|', '|10|30|', 'team_10') as $value) {
    $request = array();
    parse_str(http_build_query(array('users' => $usersType->toHidden($value))), $request);
    check(!is_array($usersType->toHidden($value)), 'Users keeps its scalar hidden input contract');
    check($usersType->fromVerbal($request['users'] ?? null) === $usersType->fromVerbal($value), 'Users hidden keylist or team survives the request unchanged');
}

// Actual field definitions, framework visibility selection and every native write entry point.
foreach (array('groups', 'groupList', 'tags') as $f) {
    foreach (array(null, 'hidden', 'none') as $visibility) {
        $m = new ExpandFixture($f, $visibility);
        check(($m->getField($f . 'Input')->input ?? null) === $visibility, 'Preserve original input visibility');
        check(!isset($m->selectFields("#column != 'none'")[$f . '36']), 'No fulltext column in automatic list');
        check(!isset($m->selectFields("#single != 'none'")[$f . '36']), 'No fulltext field in automatic single');
        $form = new core_FieldSet();
        $m->setExpandInputField($form, $f . 'Input', $f);
        check(($form->getField($f . 'Input')->input ?? null) === $visibility, 'Separate form keeps the input visibility');
    }
    $m = new ExpandFixture($f, null, true);
    check($m->getField($f . '36', false) !== null, 'Create fulltext even with predeclared input');
    check($m->getField($f . 'Input')->caption === 'Existing', 'Preserve existing input metadata');
    check($m->getField($f . 'Input')->input === 'hidden', 'Preserve predeclared hidden input');
    $hiddenType = $m->getFieldType($f . 'Input');
    foreach (array('|30|', '|20|30|', '') as $hiddenValue) {
        $request = array();
        parse_str(http_build_query(array($f . 'Input' => $hiddenType->toHidden($hiddenValue))), $request);
        $roundTrip = $hiddenType->fromVerbal($request[$f . 'Input'] ?? null);
        check(type_Keylist::toArray($roundTrip) == type_Keylist::toArray($hiddenValue), 'Hidden keylist survives HTML request decoding');
    }
    $form = new core_FieldSet();
    $m->setExpandInputField($form, $f . 'Input', $f);
    check($form->getField($f . 'Input')->caption === 'Existing', 'Separate form uses the predeclared input caption');

    $r = (object) array($f . 'Input' => '|30|');
    $m->save($r);
    check($r->$f === '|20|30|' && $r->{$f . '36'} === '00kz 00uz', 'Normal save expands and encodes');
    check(strpos(lastSql($m), str::phpToMysqlName($f . '36')) !== false, 'Normal save persists the index');
    $r = testRow(1, $f, '|30|', '|10|', 'stale');
    $m->save_($r, $f);
    check($r->{$f . '36'} === '00az', 'Direct save synchronizes the final expanded value');
    $r->{$f . '36'} = 'keep';
    $m->save_($r, 'state');
    check($r->{$f . '36'} === 'keep' && strpos(lastSql($m), '36') === false, 'Unrelated partial save is untouched');
    $m->save_($r, $f . 'Input');
    check(strpos(lastSql($m), '36') === false, 'Input-only raw save preserves automatic groups');
    foreach (array('saveArray', 'saveArray_') as $method) {
        $r = testRow(1, $f, '|30|', '|30|', 'stale');
        $m->$method(array($r), 'id,' . $f);
        check($r->{$f . '36'} === '00uz' && strpos(lastSql($m), '00uz') !== false, 'Bulk write synchronizes derived value');
        $r->$f = '';
        $m->$method(array($r), 'id,' . $f);
        check($r->{$f . '36'} === '', 'Bulk clear removes old index');
        $r->{$f . '36'} = 'keep';
        $m->$method(array($r), array('id' => true, 'state' => true, $f => false));
        check($r->{$f . '36'} === 'keep' && strpos(lastSql($m), str::phpToMysqlName($f . '36')) === false, 'Bulk excluded source field does not change the index');
    }
    $m->rows = array(1 => testRow(1, $f, '|30|', '|20|30|', 'stale'));
    $res = null;
    $m->recalcExpandedInput(1);
    check(strpos(lastSql($m), '00kz 00uz') !== false, 'Recalculate index even when expanded groups did not change');
    $m->rows[1]->{$f . 'Input'} = null;
    $r = (object) array('id' => 1, 'state' => 'active');
    $m->save($r);
    check($r->$f === '|20|30|', 'Saving before background initialization preserves legacy groups');
    $read = testRow(1, $f, null, '|30|', null);
    $m->invoke('AfterRead', array(&$read));
    check($read->{$f . 'Input'} === '|30|', 'Legacy groups are available to forms before backfill');
    $read->{$f . 'Input'} = '';
    $m->_expandInput36Ready = true;
    $m->invoke('AfterRead', array(&$read));
    check($read->{$f . 'Input'} === '', 'Empty input after completed initialization is preserved');
    $m->_expandInput36Ready = false;
    $m->invoke('AfterRead', array(&$read));
    check($read->{$f . 'Input'} === '|30|', 'New empty-string input preserves legacy groups before initialization');
    $m->save($read);
    check($read->$f === '|20|30|', 'Editing another field before initialization retains legacy groups');
    $read->{$f . 'Input'} = '';
    $m->save($read, $f . 'Input');
    check($read->$f === '' && $read->{$f . '36'} === '', 'Explicitly clearing the input still clears groups while initialization is pending');
    $r = testRow(1, $f, '|30|', '|30|', 'stale');
    $m->save($r, array('state' => true, $f . 'Input' => true));
    check(strpos(lastSql($m), '`state`') !== false && $r->{$f . '36'} === '00kz 00uz', 'Normal save accepts associative field masks');

    $m->setParams($f, array('notNull' => true, 'value' => '|30|'));
    $r = (object) array('id' => 1, $f => null);
    $m->save_($r, $f);
    check($r->{$f . '36'} === '00uz', 'Direct save encodes the effective NOT NULL default');
    $m->saveArray(array($r), 'id,' . $f);
    check($r->{$f . '36'} === '00uz', 'Bulk save encodes the effective NOT NULL default');

    $fieldset = new core_FieldSet();
    $fieldset->fields = array($f => $m->getField($f), $f . '36' => $m->getField($f . '36'));
    plg_ExpandInput::on_AfterGetCsvFieldSetForExport($m, $fieldset);
    check(isset($fieldset->fields[$f . 'Input']) && !isset($fieldset->fields[$f . '36']), 'CSV uses input and excludes the service field');
}

// The real embedded driver runs after the plugin's BeforeSave, even for a nonempty initial index.
class cat_Products extends ExpandFixture {}
class cat_GeneralProductDriver extends core_BaseClass { public function addFields_($fieldset) {} }
class cat_Groups { public static function fetchField($cond) { return 40; } }
require $root . '/planning/interface/StepProductDriver.class.php';
$m = new cat_Products();
foreach (array('', '|10|') as $input) {
    $r = (object) array('groupsInput' => $input, 'driverClass' => 'planning_interface_StepProductDriver');
    $m->save($r);
    check(strpos($r->groups, '|40|') !== false && strpos($r->groups36, '015z') !== false, 'Index includes the late production-step group');
}

// Record-aware recalculation keeps automatic CRM groups without putting them in the input.
class drdata_CountryGroups {
    public static function getGroupsArr($country) { return $country ? array(1 => (object) array('id' => 1)) : array(); }
    public static function getQuery() { return new ExpandCountryQuery(); }
}
class ExpandCountryQuery {
    private $read = false;
    public function show($fields) {}
    public function fetch() {
        if ($this->read) return false;
        $this->read = true;
        return (object) array('id' => 1, 'name' => 'Test country group');
    }
}
class crm_Groups { public static function force($path) { return 40; } }
$m = new ExpandFixture('groupList');
$m->fixExpandFieldOnSetup = false;
$m->countryFieldName = 'country';
$m->groupFieldName = 'groupListInput';
$m->FLD('country', 'int');
$m->addTestPlugin(new crm_ContragentGroupsPlg());
$m->rows = array(1 => testRow(1, 'groupList', '|10|', '|10|20|40|', 'stale'));
$m->rows[1]->country = 1;
$m->recalcExpandedInput(1);
check(strpos(lastSql($m), '00az 00kz 015z') !== false, 'CRM recalc retains automatic country groups and parents');
check($m->rows[1]->groupListInput === '|10|', 'CRM input is not polluted by automatic groups');
$read = testRow(1, 'groupList', null, '|20|40|', null);
$m->invoke('AfterRead', array(&$read));
check($read->groupListInput === null, 'Disabled input initialization remains disabled in forms');
$r = (object) array('id' => 1, 'state' => 'active');
$m->save($r, 'state');
check(strpos(lastSql($m), 'group_list_input') === false, 'Partial CRM state save does not erase manual groups');
$r = (object) array('id' => 1, 'country' => 1);
$m->save($r, 'country');
check($r->groupListInput === '|10|' && $r->groupList === '|10|20|40|' && $r->groupList36 === '00az 00kz 015z', 'Country-only CRM save loads manual groups and rebuilds derived fields');
$r = (object) array('id' => 1, 'country' => null);
$m->save($r, 'country');
check($r->groupListInput === '|10|' && $r->groupList === '|10|' && $r->groupList36 === '00az', 'Clearing the country retains manual groups and removes automatic ones');
$r = (object) array('id' => 1, 'state' => 'active');
$m->save($r);
check($r->groupListInput === '|10|' && $r->groupList === '|10|20|40|', 'Sparse full CRM save retains manual and automatic groups');

// Repeated setup never reads business records, including an already populated table.
$m = new ExpandFixture();
$m->rows = array(1 => testRow(1, 'groups', '|30|', '|20|30|', 'stale'));
$html = '';
plg_ExpandInput::on_AfterSetupMVC($m, $html);
plg_ExpandInput::on_AfterSetupMVC($m, $html);
check($m->queries === 0 && count(core_CallOnTime::$calls) === 1, 'Setup only queues one job, with no table scan');
$job = reset(core_CallOnTime::$calls);
$q = new ExpandQuery($m);
$q->fields = $m->fields;
plg_ExpandInput::applyExtendedInputSearch($m, $q, 30);
check($q->where[0][0] === 'LOCATE', 'Search is complete while the index is being built');
plg_ExpandInput::callback_recalcExpand36Input($job);
check(strpos(lastSql($m), 'UPDATE `test_expand_items`') === 0 && strpos(lastSql($m), '<=>') !== false, 'Backfill uses an atomic conditional update');
check(strpos(lastSql($m), '00kz 00uz') !== false, 'Backfill repairs nonempty stale values');
check(strpos(lastSql($m), 'groups_input` =') === false, 'Backfill preserves populated input');
$next = end(core_CallOnTime::$calls);
check($next->lastId === 1 && core_Permanent::get($job->setupKey) === 1, 'Persist the successful cursor');
plg_ExpandInput::callback_recalcExpand36Input($next);
check(core_Permanent::get($job->setupKey) === 'done', 'Mark complete only after processing');
$calls = count(core_CallOnTime::$calls);
check(plg_ExpandInput::scheduleRebuild($m) === false && count(core_CallOnTime::$calls) === $calls, 'Completed model does not requeue on initialization');
unset($m->_expandInput36Ready);
$q = new ExpandQuery($m); $q->fields = $m->fields;
plg_ExpandInput::applyExtendedInputSearch($m, $q, 30);
check(strpos($q->where[0], 'MATCH') === 0, 'Use fulltext after successful rebuild');
$m = new ExpandFixture('tags');
check(plg_ExpandInput::scheduleRebuild($m), 'A different field has its own initialization state');
$m->rows = array(1 => testRow(1, 'tags', null, '|30|', null));
$job = end(core_CallOnTime::$calls);
plg_ExpandInput::callback_recalcExpand36Input($job);
check(strpos(lastSql($m), '`tags_input` = \'|30|\'') !== false && strpos(lastSql($m), '`tags` = \'|20|30|\'') !== false, 'Initialize legacy input and expand parents');
check(strpos(lastSql($m), '`tags_input` <=> NULL') !== false, 'Do not overwrite a concurrent input edit');
$m->fixExpandFieldOnSetup = false;
$m->db->sql = array();
plg_ExpandInput::callback_recalcExpand36Input((object) array('mvc' => $m->className));
check(strpos(lastSql($m), '`tags_input` =') === false, 'Legacy full rebuild does not alter input');
$m->rows[1]->tags36 = 'stale';
$m->db->sql = array();
plg_ExpandInput::callback_recalcExpand36Input((object) array('mvc' => $m->className, 'onlyEmpty' => true));
check(!$m->db->sql, 'Keep compatibility with queued onlyEmpty jobs');

// A failed write must not advance the managed cursor.
foreach (array(30, '30', '|30|', array(30), array(30 => 30)) as $value) {
    $m = new ExpandFixture('tags');
    $m->_expandInput36Ready = false;
    $q = new ExpandQuery($m); $q->fields = $m->fields;
    plg_ExpandInput::applyExtendedInputSearch($m, $q, $value);
    check($q->where[0] === array('LOCATE', 'tags', '|30|'), 'Every accepted value format has the same fallback filter');
    $q = new ExpandQuery($m);
    plg_ExpandInput::applyExtendedInputSearch($m, $q, $value, 'itemId', true);
    check($q->where[0] === array('NOT LOCATE', 'tags', '|30|') && $q->where[1] === array('OR', '#tags IS NULL'), 'Negative external filter retains MATCH null semantics');
    check(strpos($q->fields['tags'], 'externalKey=itemId') !== false, 'External fallback uses the supplied foreign key');
}
foreach (array(false, true) as $not) {
    $q = new ExpandQuery($m); $q->fields = $m->fields;
    plg_ExpandInput::applyExtendedInputSearch($m, $q, array(), null, $not);
    check(strpos($q->where[0], "AGAINST('' IN BOOLEAN MODE)") !== false, 'Empty search retains the original MATCH semantics during backfill');
}

core_Permanent::$values = array();
core_CallOnTime::$calls = array();
$m = new ExpandFixture();
$m->rows = array(1 => testRow(1, 'groups', '|30|', '|30|', 'stale'));
plg_ExpandInput::scheduleRebuild($m);
$job = reset(core_CallOnTime::$calls);
$m->db->fail = true;
try { plg_ExpandInput::callback_recalcExpand36Input($job); throw new RuntimeException('Expected failure'); }
catch (RuntimeException $e) { check($e->getMessage() === 'Synthetic database failure', 'Propagate write failure'); }
check(core_Permanent::get($job->setupKey) === null && count(core_CallOnTime::$calls) === 1, 'Failed batch stays retryable');

// Cursor boundaries, repair of nonempty indexes and a resumable repeated setup on a large model.
core_Permanent::$values = array();
core_CallOnTime::$calls = array();
$m = new ExpandFixture();
for ($i = 1; $i <= 50001; $i++) $m->rows[$i] = testRow($i, 'groups', '|30|', '|20|30|', 'stale');
plg_ExpandInput::scheduleRebuild($m);
$job = reset(core_CallOnTime::$calls);
plg_ExpandInput::callback_recalcExpand36Input($job);
check(core_Permanent::get($job->setupKey) === 50000 && count($m->db->sql) === 50, 'Bounded batch groups identical updates into 1000-row queries');
$qCount = $m->queries;
plg_ExpandInput::scheduleRebuild($m);
check($m->queries === $qCount, 'Repeated initialization does not scan the unfinished large table');
$job = end(core_CallOnTime::$calls);
check($job->lastId === 50000, 'Repeated initialization resumes its persisted cursor');
plg_ExpandInput::callback_recalcExpand36Input($job);
check(core_Permanent::get($job->setupKey) === 50001, 'Next batch includes the boundary record');
$job = end(core_CallOnTime::$calls);
plg_ExpandInput::callback_recalcExpand36Input($job);
check(core_Permanent::get($job->setupKey) === 'done', 'Large table finishes without a COUNT query');
$qCount = $m->queries;
plg_ExpandInput::callback_recalcExpand36Input(reset(core_CallOnTime::$calls));
check($m->queries === $qCount, 'A stale duplicate callback does not rescan a completed model');

// Models without this plugin keep the original save behavior.
$m = new ExpandFixture();
$m->unloadPlugin('plg_ExpandInput');
$r = testRow(1, 'groups', '|30|', '|30|', 'unchanged');
$m->save_($r, 'groups');
check($r->groups36 === 'unchanged' && strpos(lastSql($m), 'groups36') === false, 'New persistence event does not affect unrelated models');

echo "PASS: {$checks} ExpandInput checks on PHP " . PHP_VERSION . "\n";
