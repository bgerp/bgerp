<?php

/**
 * Проверки без база: php core/tests/reportInfo.php
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
function expect($condition, ...$args) { if (!$condition) throw new RuntimeException('Expect'); }
function countR($value) { return is_countable($value) ? count($value) : 0; }
function tr($value) { return $value; }
class core_Mvc extends stdClass
{
    public static $records = array(), $reads = array(), $fromFullCache = false;
    public $fields = array();
    public static function fetch($id, $fields = '*', $cache = true)
    {
        self::$reads[] = $fields;
        $rec = self::$records[static::class][$id] ?? null;
        if (!$rec) return null;
        $res = clone $rec;
        if ($fields != '*') $res = (object) array_intersect_key((array) $res, array_fill_keys(is_array($fields) ? $fields : explode(',', $fields), true));
        if (!self::$fromFullCache) foreach ($res->driverRec ?? array() as $name => $value) $res->{$name} = $value;
        return $res;
    }
    public static function fetchRec($id, $fields = '*', $cache = true) { return is_object($id) ? $id : static::fetch($id, $fields, $cache); }
    public function selectFields($condition = '') { return $this->fields; }
    public function getForm($params) { return new InfoForm(); }
    public static function getVerbal_($rec, $field) { return (string) $rec->{$field}; }
    public function __call($name, $args) { return $this->{$name . '_'}(...$args); }
}
class cls
{
    public static $instances = array();
    public static function get($class, $params = array()) { return self::$instances[$class] ?? (self::$instances[$class] = new $class()); }
}
class core_Users { public static function getCurrent() { return 6; } }
class Users
{
    public static function haveRole($role, $user) { return $role != 'no_one'; }
    public static function requireRole($role, $retUrl, $action) { if ($role == 'no_one') throw new RuntimeException('denied'); return true; }
}
class core_Classes { public static function fetchField($id, $field) { return 'Фактури по контрагент'; } }
class arr
{
    public static function make($value, $keys = false) { return is_array($value) ? $value : explode(',', $value); }
}
class InfoPayload
{
    public function __sleep() { throw new RuntimeException('Резултатът не трябва да се сериализира за права'); }
}
class InfoForm
{
    public $rec, $cmd = '', $formAttr = array();
    public function __construct() { $this->rec = (object)array('id' => 1); }
    public function setAction($mvc, $action) {}
    public function FNC($name, $type, $params) {}
    public function input($fields = null, $mode = null) {}
}
class InfoQuery
{
    public $show = array('*' => true), $limit = 1;
    private $ids = array(1, 2);
    public function show($fields)
    {
        $fields = is_array($fields) ? $fields : array_fill_keys(explode(',', $fields), true);
        foreach ($fields as $field => $value) {
            if (is_int($field)) throw new RuntimeException('Числов ключ вместо име на поле');
            $this->show[$field] = $value;
        }
    }
    public function fetch() { return count($this->ids) && $this->limit-- > 0 ? InfoReport::fetch(array_shift($this->ids), isset($this->show['*']) ? '*' : array_keys($this->show)) : null; }
    public function fetchAndCache() { throw new RuntimeException('Частичен запис не се кешира като пълен'); }
}
class InfoPager
{
    public $called = false;
    public function setLimit($query) { $this->called = true; $query->limit = 1; }
}
class InfoDriver
{
    public function getTitle($rec) { return $rec->title . ' / ' . $rec->fromDate; }
    public function getClassId() { return 10; }
}
require dirname(__DIR__) . '/Manager.class.php';
require dirname(__DIR__) . '/Master.class.php';
require dirname(__DIR__, 2) . '/embed/Manager.class.php';
require dirname(__DIR__, 2) . '/frame2/Reports.class.php';
class InfoReport extends frame2_Reports
{
    public $cacheRightsDuration = 0;
    public static function getDriver($rec) { return new InfoDriver(); }
    public function getRequiredRoles($action, $rec, $user)
    {
        if (!$rec) return 'powerUser';
        return $rec->state != 'rejected' && ($rec->createdBy == $user || strpos($rec->sharedUsers, '|' . $user . '|') !== false) ? 'powerUser' : 'no_one';
    }
}
class InfoOrdinary extends core_Manager
{
    public $cacheRightsDuration = 0;
    public function getRequiredRoles($action, $rec, $user) { return 'powerUser'; }
}
$checks = 0;
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
$report = cls::get('InfoReport');
$report->className = 'InfoReport';
$record = (object)array('id' => 1, 'title' => 'Фактури по контрагент', 'driverClass' => 10, 'createdBy' => 6, 'sharedUsers' => '|7|', 'state' => 'active', 'modifiedOn' => '2026-10-18 10:00:00', 'threadId' => 3, 'containerId' => 4,
    'driverRec' => array('fromDate' => '2020-01-01', 'unpaid' => 'unpaid', 'paymentType' => 'bank'), 'data' => new InfoPayload(), 'log' => new InfoPayload());
core_Mvc::$records['InfoReport'] = array(1 => $record, 2 => clone $record);
foreach (array_keys((array)$record) as $name) $report->fields[$name] = (object)array('name' => $name, 'kind' => 'FLD');
$info = InfoReport::fetchRecForInfo(1);
check(!isset($info->data) && !isset($info->log), 'Лекото четене не зарежда резултат и лог');
check($info->fromDate == '2020-01-01' && $info->paymentType == 'bank', 'Настройките от драйвера се запазват');
check($info->sharedUsers == '|7|' && $info->state == 'active', 'Данните за права се запазват');
core_Mvc::$fromFullCache = true;
check(InfoReport::fetchRecForInfo(1)->paymentType == 'bank', 'Настройките се запазват и при проекция от пълен кеширан запис');
core_Mvc::$fromFullCache = false;
$full = InfoReport::fetch(1);
$info = InfoReport::fetchRecForInfo($full);
check(!isset($info->data) && !isset($info->log) && isset($full->data, $full->log), 'Лекото копие не променя пълния запис');
check($report->getDocumentRow_(1)->title == 'Фактури по контрагент / 2020-01-01', 'Заглавието включва настройките на драйвера');
$data = (object)array('form' => null);
$prepare = new ReflectionMethod('core_Manager', 'prepareEditForm_');
$prepare->invoke($report, $data);
check($data->form->rec->paymentType == 'bank' && !isset($data->form->rec->data), 'Редакцията получава настройките без резултата');
$data = (object)array('query' => new InfoQuery(), 'pager' => new InfoPager());
$report->prepareListRecs_($data);
check($data->pager->called && count($data->recs) == 1, 'Списъкът запазва страницирането');
check(!isset($data->recs[1]->data) && !isset($data->recs[1]->log), 'Списъкът не зарежда тежките полета');
foreach (array(1, $full) as $input) {
    check(InfoReport::haveRightFor('single', $input, 6), 'Авторът има достъп');
    check(InfoReport::haveRightFor('single', $input, 7), 'Споделеният потребител има достъп');
    check(!InfoReport::haveRightFor('single', $input, 8), 'Друг потребител няма достъп');
}
check(InfoReport::haveRightFor('list', null, 6), 'Права без конкретен запис');
core_Mvc::$records['InfoReport'][1]->sharedUsers = '';
check(!InfoReport::haveRightFor('single', 1, 7), 'Кешът на правата се променя при премахнато споделяне');
check(InfoReport::requireRightFor('edit', 1, 6), 'Изискването на права също ползва лекото четене');
core_Mvc::$records['InfoReport'][1]->state = 'rejected';
check(!InfoReport::haveRightFor('single', 1, 6), 'Кешът на правата се променя при смяна на състоянието');
$fullAgain = InfoReport::fetch(1);
check($fullAgain->data instanceof InfoPayload && $fullAgain->log instanceof InfoPayload, 'Пълното четене след списък запазва резултата и лога');
core_Mvc::$records['InfoOrdinary'][1] = (object)array('id' => 1, 'data' => 'ordinary-data', 'log' => 'ordinary-log');
check(InfoOrdinary::fetchRecForInfo(1)->data == 'ordinary-data', 'Другите мениджъри продължават да четат целия запис');
check(InfoOrdinary::haveRightFor('list', null, 6), 'Обикновени права без запис');
echo "OK: {$checks} report info checks\n";
