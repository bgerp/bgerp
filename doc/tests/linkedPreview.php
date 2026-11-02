<?php

/**
 * CLI проверки на прегледа на свързани документи, без база данни
 *
 * @category  bgerp
 * @package   doc
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

/** Проверява резултата на сценарий. */
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
/** Прекратява отказаните заявки в изолирания тест. */
function expect($condition, ...$args) { if (!$condition) throw new DomainException('Denied'); }
/** Брой елементи за реалния диспечер на hooks. */
function countR($value) { return is_countable($value) ? count($value) : 0; }
/** Тестовият език не променя надписите. */
function tr($text) { return $text; }
/** Прилага синтетичните роли на текущия потребител. */
function haveRole($roles, $userId = null) { return core_Users::haveRole($roles, $userId); }

require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
class core_Mvc extends core_BaseClass
{
    public static $records = array();
    /** Чете синтетичните записи. */
    public static function fetch($id) { return self::$records[$id] ?? false; }
    /** Разрешава id или вече зареден запис за реалните проверки на права. */
    public static function fetchRec($rec) { return is_object($rec) ? $rec : static::fetch($rec); }
}
require dirname(__DIR__, 2) . '/core/Manager.class.php';
class core_Master extends core_Manager {}
require dirname(__DIR__, 2) . '/core/Plugin.class.php';
require dirname(__DIR__) . '/DocumentPlg.class.php';
require dirname(__DIR__) . '/Linked.class.php';
class core_Users
{
    /** Синтетичният потребител е вътрешен потребител без специални роли. */
    public static function getCurrent() { return 11; }
    /** Проверява единствено обичайните вътрешни роли. */
    public static function haveRole($roles, $userId = null) { return in_array($roles, array('user', 'powerUser')); }
    /** Запазва отказите на реалния requireRightFor(). */
    public static function requireRole($roles, ...$args) { expect(self::haveRole($roles)); }
}
class Users extends core_Users {}
class core_Packs
{
    /** Не зарежда незасегнати пакети. */
    public static function isInstalled($pack) { return false; }
}
class doc_Threads
{
    public static $allowed = array(101 => true);
    /** Разделя нормалния достъп до нишката от pSingle. */
    public static function haveRightFor($action, $id, $userId = null) { return self::$allowed[$id] ?? false; }
}
class Mode
{
    public static $values = array();
    /** Чете запомненото разрешение. */
    public static function get($key) { return self::$values[$key] ?? null; }
    /** Запомня стойност за следваща заявка в същата тестова сесия. */
    public static function setPermanent($key, $value) { self::$values[$key] = $value; }
    /** Не активира допълнителни режими. */
    public static function is($key, $value = true) { return self::get($key) === $value; }
}
class Request
{
    public static $values = array(), $stack = array();
    /** Връща подготвените параметри. */
    public static function get($key, $type = null) { return self::$values[$key] ?? null; }
    /** Запазва обкръжението за рендирането. */
    public static function push($values, $name) { self::$stack[$name] = self::$values; self::$values = $values + self::$values; }
    /** Възстановява обкръжението след рендирането. */
    public static function pop($name) { self::$values = self::$stack[$name]; unset(self::$stack[$name]); }
}
class core_Request extends Request {}
class str
{
    /** Изолира подписа; проверката на източника и потребителя остава реална. */
    public static function checkHash($hash, $length) { return $hash === 'invalid' ? false : $hash; }
}
class core_ET
{
    private $html;
    /** Запазва шаблона. */
    public function __construct($html) { $this->html = $html; }
    /** Поставя визуализираното съдържание. */
    public function replace($html, $place) { $this->html = str_replace('[#' . $place . '#]', $html, $this->html); }
    /** Връща крайния HTML. */
    public function getContent() { return $this->html; }
}
class ET extends core_ET {}
class cls
{
    public static $instances = array();
    /** Използва тестовите модели без bootstrap. */
    public static function get($name) { return self::$instances[$name]; }
}
class LinkedPreviewDocument extends core_Master
{
    public $canSingle = 'powerUser', $canPsingle = 'user';
    public static $denied = array();
    /** Използва реалния документен плъгин и реалния кеш за права. */
    public function __construct() { $this->_plugins = array(new doc_DocumentPlg()); }
    /** Името е общо за текущата сесия, както в core_Master. */
    public function getAllowedContainerName() { return 'allowedContainers'; }
    /** Конкретният документ може да има допълнителна забрана. */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = null)
    {
        if (isset(self::$denied[$rec->id ?? null])) $roles = 'no_one';
    }
}
class LinkedPreviewReference
{
    public $instance, $that;
    public static $renders = array(), $throwOnRender = false;
    /** Свързва контейнера със синтетичния документ. */
    public function __construct($id) { $this->instance = cls::get('LinkedPreviewDocument'); $this->that = $id; }
    /** Чете реалния за теста запис. */
    public function fetch() { return $this->instance->fetch($this->that); }
    /** Проверява през реалния Manager и DocumentPlg. */
    public function haveRightFor($action) { return $this->instance->haveRightFor($action, $this->that); }
    /** Рендира маркер само след успешното разрешение. */
    public function getInlineDocumentBody()
    {
        check(Request::get('ajax_mode') === false, 'Document rendering leaves AJAX mode');
        if (self::$throwOnRender) throw new RuntimeException('Synthetic render failure');
        self::$renders[] = $this->that;

        return 'BODY:' . $this->that;
    }
}
class doc_Containers
{
    /** Не допуска файлов идентификатор да се използва като контейнер. */
    public static function getDocument($id) { expect(isset(core_Mvc::$records[$id]->containerId)); return new LinkedPreviewReference($id); }
}
class fileman_Files
{
    /** Чете само синтетичния файл. */
    public static function fetch($id) { return $id == 201 ? (object) array('id' => 201) : false; }
    /** Файловият източник използва собствената си проверка. */
    public function requireRightFor($action, $id) { expect($id == 201); }
}

/** Подготвя независима заявка със синтетична връзка. */
function previewFixture($source = 'doc_101_11')
{
    $cache = new ReflectionProperty('core_Manager', 'cacheRights');
    $cache->setAccessible(true);
    $cache->setValue(null, array());
    Mode::$values = array('allowedContainers' => array(999 => 999));
    Request::$values = array('hash' => $source, 'rId' => 1, 'ajax_mode' => true);
    Request::$stack = array();
    doc_Threads::$allowed = array(101 => true);
    LinkedPreviewDocument::$denied = LinkedPreviewReference::$renders = array();
    LinkedPreviewReference::$throwOnRender = false;
    core_Mvc::$records[1] = (object) array('id' => 1, 'outType' => 'doc', 'outVal' => 101, 'inType' => 'doc', 'inVal' => 102, 'state' => 'active');
}

$checks = 0;
cls::$instances['LinkedPreviewDocument'] = new LinkedPreviewDocument();
cls::$instances['fileman_Files'] = new fileman_Files();
foreach (array(101, 102) as $id) {
    core_Mvc::$records[$id] = (object) array('id' => $id, 'containerId' => $id, 'threadId' => $id, 'createdBy' => 12, 'state' => 'active');
}
$mvc = new doc_Linked();
previewFixture();
check(!LinkedPreviewDocument::haveRightFor('single', 102), 'The target initially has no single access');
$result = $mvc->act_RenderView();
check(strpos($result[0]->arg['html'], 'BODY:102') !== false, 'An active link previews the target without opening pSingle separately');
check(isset(Mode::$values['allowedContainers'][102], Mode::$values['allowedContainers'][999]), 'The pSingle grant preserves earlier session grants');
check(Request::get('ajax_mode') === true && !Request::$stack, 'AJAX context is restored');

previewFixture('doc_102_11');
doc_Threads::$allowed = array(102 => true);
$result = $mvc->act_RenderView();
check(strpos($result[0]->arg['html'], 'BODY:101') !== false, 'The reverse direction also previews the linked document');

previewFixture();
doc_Threads::$allowed[102] = true;
$mvc->act_RenderView();
check(Mode::$values['allowedContainers'] === array(999 => 999), 'Ordinary access does not add a session grant');

previewFixture('file_201_11');
core_Mvc::$records[1]->outType = 'file';
core_Mvc::$records[1]->outVal = 201;
$result = $mvc->act_RenderView();
check(strpos($result[0]->arg['html'], 'BODY:102') !== false, 'An accessible linked file retains document preview access');

foreach (array('source', 'rejected', 'unrelated', 'user', 'hash') as $case) {
    previewFixture();
    if ($case == 'source') doc_Threads::$allowed = array();
    if ($case == 'rejected') core_Mvc::$records[1]->state = 'rejected';
    if ($case == 'unrelated') Request::$values['hash'] = 'doc_999_11';
    if ($case == 'user') Request::$values['hash'] = 'doc_101_12';
    if ($case == 'hash') Request::$values['hash'] = 'invalid';
    $denied = false;
    try { $mvc->act_RenderView(); } catch (DomainException $e) { $denied = true; }
    check($denied && !LinkedPreviewReference::$renders, "$case is refused before rendering");
    check(!isset(Mode::$values['allowedContainers'][102]), "$case does not grant document access");
}

previewFixture();
LinkedPreviewDocument::$denied[102] = true;
$mvc->act_RenderView();
check(!LinkedPreviewReference::$renders, 'A document-specific denial remains effective');

previewFixture();
LinkedPreviewReference::$throwOnRender = true;
$failed = false;
try { $mvc->act_RenderView(); } catch (RuntimeException $e) { $failed = $e->getMessage() == 'Synthetic render failure'; }
check($failed, 'Rendering errors propagate');
check(Request::get('ajax_mode') === true && !Request::$stack, 'Rendering errors restore AJAX context');

echo "OK: {$checks} linked preview checks\n";
