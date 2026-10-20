<?php

/**
 * Проверки без база: php frame2/tests/lightFetch.php
 *
 * @category  bgerp
 * @package   frame2
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function expect($condition, ...$args) { if (!$condition) throw new RuntimeException('Expect'); }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function setIfNot(&$value, $default) { if (!isset($value)) $value = $default; }
function countR($value) { return is_countable($value) ? count($value) : 0; }
function tr($value) { return $value; }
class ht { public static function createLink($title, $url, $unused = null, $attr = array()) { return array('title' => $title, 'url' => $url, 'attr' => $attr); } }
class str { public static function limitLen($value, $length) { return mb_substr($value, 0, $length); } }
class doc_Threads { public static function haveRightFor($action, $id) { return true; } }
class arr
{
    public static function make($value, $keys = false) { return is_array($value) ? $value : explode(',', $value); }
}
class Mode
{
    private static $values = array(), $stack = array();
    public static function get($name) { return self::$values[$name] ?? null; }
    public static function is($name, $value = true) { return self::get($name) == $value; }
    public static function push($name, $value) { self::$stack[$name][] = self::get($name); self::$values[$name] = $value; }
    public static function pop($name) { self::$values[$name] = array_pop(self::$stack[$name]); }
}
class cls
{
    public static $instances = array();
    public static function load($class, $silent = false) { return class_exists($class); }
    public static function get($class, $params = array())
    {
        if ($class === 'LightFetchDriver') return new LightFetchDriver();
        return self::$instances[$class] ?? (self::$instances[$class] = new $class());
    }
}
class core_Classes
{
    public static function fetchField($id, $field) { return 'Фактури по контрагент'; }
}
class tags_Logs
{
    public static $calls = 0;
    public static function getTagsFor($class, $id)
    {
        self::$calls++;
        return array(array('span' => '<b>Важен</b>', 'spanNoName' => '<i></i>'));
    }
}

// Истинските dispatch, fetch/cache и AfterRead остават част от проверявания път.
require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
require dirname(__DIR__, 2) . '/core/FieldSet.class.php';
require dirname(__DIR__, 2) . '/core/Mvc.class.php';
require dirname(__DIR__, 2) . '/core/Manager.class.php';
require dirname(__DIR__, 2) . '/core/Master.class.php';
require dirname(__DIR__, 2) . '/core/Plugin.class.php';
require dirname(__DIR__, 2) . '/embed/Manager.class.php';
require dirname(__DIR__) . '/Reports.class.php';
require dirname(__DIR__, 2) . '/tags/plg/Add.class.php';
require dirname(__DIR__, 2) . '/doc/DocumentPlg.class.php';

class LightFetchQuery
{
    public static $records = array(), $reads = array();
    private $mvc, $fields = '*';
    public function __construct($mvc) { $this->mvc = $mvc; }
    public function show($fields) { $this->fields = $fields; }
    public function limit($limit) {}
    public function fetch($id)
    {
        self::$reads[] = $this->fields;
        $rec = self::$records[$id] ?? null;
        if (!$rec) return null;
        $rec = clone $rec;
        if ($this->fields != '*') {
            $rec = (object) array_intersect_key((array) $rec, array_fill_keys(explode(',', $this->fields), true));
        }
        $this->mvc->invoke('AfterRead', array(&$rec));
        return $rec;
    }
}
class LightFetchReport extends frame2_Reports
{
    public function __construct() { $this->_plugins = array(new tags_plg_Add(), new doc_DocumentPlg()); }
    public function getQuery() { return new LightFetchQuery($this); }
    public function selectFields($where = '', $fieldsArr = null) { return $this->fields; }
    public static function getClassId() { return 1; }
    public static function getVerbal_($rec, $fieldName) { return (string) ($rec->{$fieldName} ?? ''); }
    public function getSingleUrlArray($id) { return array('LightFetchReport', 'single', $id); }
    public function getHandle($id) { return 'Rpt' . $id; }
    public function getUrlForDblClick($id, $url) { return null; }
}
class LightFetchDriver
{
    public static $iconArgument, $exception;
    public function invoke($event, $args = array()) { return -1; }
    public function getClassId() { return 2; }
    public function getTitle($rec) { return ($rec->title ?? '') . ' / ' . ($rec->fromDate ?? ''); }
    public function getIcon($id)
    {
        self::$iconArgument = $id;
        if (self::$exception) throw self::$exception;
        // Драйверът може да прочете отново записа, дори когато вече е инстанциран.
        LightFetchReport::fetch(is_object($id) ? $id->id : $id, '*', false);
        return 'img/16/report.png';
    }
}

$checks = $failures = 0;
function check($condition, $message)
{
    global $checks, $failures;
    $checks++;
    if (!$condition) { $failures++; fwrite(STDERR, "FAIL: {$message}\n"); }
}
function resetReads($mvc)
{
    $mvc->_cachedRecords = array();
    LightFetchQuery::$reads = array();
}
function noPayloadRead()
{
    foreach (LightFetchQuery::$reads as $fields) {
        if ($fields === '*' || array_intersect(explode(',', $fields), array('data', 'log'))) return false;
    }
    return true;
}

$report = cls::get('LightFetchReport');
$record = (object) array('id' => 1, 'title' => 'Неплатени фактури', 'driverClass' => 'LightFetchDriver',
    'createdBy' => 6, 'state' => 'active', 'threadId' => 2, 'driverRec' => array('fromDate' => '2020-01-01'),
    'data' => (object) array('result' => 'large report'), 'log' => array('history'));
LightFetchQuery::$records = array(1 => $record);
foreach (array_keys((array) $record) as $name) $report->fields[$name] = (object) array('kind' => 'FLD');

$row = $report->getDocumentRow(1);
check(noPayloadRead(), 'Заглавието с тагове не зарежда data/log при празен кеш');
check($row->title === 'Неплатени фактури / 2020-01-01', 'Заглавието запазва настройките на драйвера');
check($row->authorId === 6 && $row->state === 'active', 'Авторът и състоянието се запазват');
check(strpos($row->subTitle, 'Фактури по контрагент') !== false && strpos($row->subTitle, '<b>Важен</b>') !== false,
    'Истинският AfterGetDocumentRow запазва типа и таговете');
check(!Mode::is('lightFetch'), 'Заглавието възстановява режима');

resetReads($report);
check($report->getIcon(1) === 'img/16/report.png', 'Иконата идва от драйвера');
check(LightFetchDriver::$iconArgument === 1, 'Драйверът получава оригиналния аргумент');
check(noPayloadRead(), 'Иконата и повторното четене в драйвера не зареждат data/log');
check(!Mode::is('lightFetch'), 'Иконата възстановява режима');

Mode::push('lightFetch', true);
$report->getIcon(1);
check(Mode::is('lightFetch'), 'Външният lightFetch се запазва');
Mode::pop('lightFetch');
Mode::push('lightFetch', false);
LightFetchDriver::$exception = new RuntimeException('driver failed');
$caught = null;
try { $report->getIcon(1); } catch (RuntimeException $e) { $caught = $e; }
check($caught === LightFetchDriver::$exception, 'Изключението от драйвера се предава непроменено');
check(Mode::get('lightFetch') === false, 'При изключение се възстановява предишният режим');
Mode::pop('lightFetch');
LightFetchDriver::$exception = null;
check($report->getIcon(999) === 'img/16/error-red.png' && !Mode::is('lightFetch'), 'Липсващ запис запазва резервната икона и режима');

resetReads($report);
$link = $report->getLink(1, 64, array('Q' => 'test'));
check(noPayloadRead(), 'Линкът през истинския doc_DocumentPlg не зарежда data/log');
check(strpos($link['title'], '#Rpt1') === 0 && $link['url']['Q'] === 'test' && isset($link['attr']['ef_icon']),
    'Линкът запазва хендъла, търсенето и иконата');
check(!Mode::is('lightFetch'), 'Линкът възстановява режима');
LightFetchDriver::$exception = new RuntimeException('link icon failed');
$caught = null;
try { $report->getLink(1); } catch (RuntimeException $e) { $caught = $e; }
check($caught === LightFetchDriver::$exception && !Mode::is('lightFetch'), 'Грешка при линк не оставя включен lightFetch');
LightFetchDriver::$exception = null;
resetReads($report);
$link = LightFetchReport::getLink(1, 0);
check(noPayloadRead() && strpos($link['title'], '#Rpt1') === 0 && !Mode::is('lightFetch'), 'Статичното извикване на линка работи както преди');
check(isset($report->fetch(1)->data), 'Пълното четене след линк запазва резултата');
resetReads($report);
$report->getDocumentRow(1);
$report->getIcon(1);
$full = $report->fetch(1);
check(isset($full->data, $full->log), 'След заглавие/икона пълното четене все още зарежда резултата и историята');
LightFetchQuery::$reads = array();
$report->getDocumentRow(1);
$light = $report->fetchRecWithoutData(1);
$fullAgain = $report->fetch(1);
check(!LightFetchQuery::$reads, 'Пълният кеш обслужва заглавието и лекото четене без нова заявка');
check(!isset($light->data, $light->log) && $fullAgain->data === $full->data && $fullAgain->log === $full->log,
    'Лекото копие не поврежда пълния кеш');
$report->getIcon($full);
check(LightFetchDriver::$iconArgument === $full && isset($full->data, $full->log), 'Подаден запис не се променя и се предава на драйвера');

resetReads($report);
$row = (object) array('subTitle' => 'Тип');
$report->showTagsName = false;
$report->addTagsToSubtitle = 'before';
tags_plg_Add::on_AfterGetDocumentRow($report, $row, 1);
check(LightFetchQuery::$reads === array('id'), 'Таговете сами четат само id');
check(strpos($row->subTitle, '<i></i>') < strpos($row->subTitle, 'Тип') && strpos($row->subTitle, 'Важен') === false,
    'Запазват се таговете без име и поставянето преди подзаглавието');
$before = tags_Logs::$calls;
tags_plg_Add::on_AfterGetDocumentRow($report, $row, 999);
check(tags_Logs::$calls === $before, 'Липсващ запис не води до търсене на тагове');
resetReads($report);
$report->addTagsToSubtitle = false;
tags_plg_Add::on_AfterGetDocumentRow($report, $row, 1);
check(!LightFetchQuery::$reads && tags_Logs::$calls === $before, 'Изключените тагове не четат записи');

echo "{$checks} checks, {$failures} failures\n";
exit($failures ? 1 : 0);
