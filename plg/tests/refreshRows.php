<?php

/**
 * CLI regression: filtered refresh hashes and Request cleanup.
 *
 * @category  ef
 * @package   plg
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Use the real Request, refresh plugin and planning hash, with no application database.
function countR($value) { return is_countable($value) ? count($value) : 0; }
function toUrl($url) { return is_array($url) ? json_encode($url) : $url; }
class core_BaseClass extends stdClass {}
class core_Master extends core_BaseClass {}
class core_Plugin extends stdClass {}
class core_HackDetector { public static function check($value, $level) {} }
class Debug { public static function log($text) {} }
class Mode { public static function get($name) { return $name === 'hitTime' ? 100 : null; } }
class arr { public static function make($value, $keys = false) { return $value; } }
class core_Users { public static function getCurrent() { return 7; } }
class core_App { public static function parseLocalUrl($url) { return json_decode($url, true); } }
class core_Cache
{
    public static $values = array();
    public static function get($type, $key) { return self::$values[$type][$key] ?? false; }
    public static function set($type, $key, $value, $ttl) { self::$values[$type][$key] = $value; }
}
class core_ET
{
    public function getBlock($name) { return $this; }
    public function removePlaces() {}
    public function getContent() { return 'rendered rows'; }
    public function getArray($name) { return array(); }
}
class jquery_Jquery { public static function runAfterAjax($tpl, $name) {} }
class cls
{
    public static $mvc;
    public static function &get($name)
    {
        if ($name === 'core_Request') {
            static $request;
            if (!$request) $request = new core_Request();

            return $request;
        }

        return self::$mvc;
    }
    public static function getClassName($name) { return is_object($name) ? get_class($name) : $name; }
    public static function load($name, $silent = false) { return class_exists($name); }
}

$root = dirname(__DIR__, 2);
require $root . '/core/Request.class.php';
class_alias('core_Request', 'Request');
require $root . '/planning/Tasks.class.php';
require $root . '/plg/RefreshRows.class.php';

class RefreshRowsQuery
{
    private $rows;
    public function __construct($rows) { $this->rows = $rows; }
    public function orderBy($field, $direction = null) {}
    public function show($fields) {}
    public function limit($count) {}
    public function where($condition)
    {
        preg_match('/#(assetId|folderId)/', $condition[0], $matches);
        $field = $matches[1];
        $value = $condition[1];
        $this->rows = array_filter($this->rows, function ($row) use ($field, $value) {
            return ($row->$field ?? null) == $value;
        });
    }
    public function fetch()
    {
        usort($this->rows, function ($a, $b) {
            return strcmp($b->modifiedOn ?? '', $a->modifiedOn ?? '');
        });

        return $this->rows[0] ?? false;
    }
}

class RefreshRowsTasks extends planning_Tasks
{
    public $records = array();
    public $renders = 0;
    public $hashCalls = 0;
    public $hashInputs = array();
    public $hashFilters = array();
    public $renderFilters = array();
    public $throwOnHash = 0;
    public $contentHash = false;
    public $normalizeContent = false;
    public $manualRefreshCnt;
    public $duringRender;

    public function getQuery() { return new RefreshRowsQuery($this->records); }
    public function getContentHash(&$status)
    {
        $this->hashCalls++;
        $this->hashInputs[] = $status;
        $this->hashFilters[] = array(Request::get('assetId'), Request::get('folderId'));
        if ($this->hashCalls === $this->throwOnHash) throw new RuntimeException('hash failure');
        $res = null;
        if ($this->contentHash) {
            if ($this->normalizeContent) $status = strtoupper($status);
            $plugin = new plg_RefreshRows();
            $plugin->on_AfterGetContentHash($this, $res, $status);
        } else {
            parent::on_AfterGetContentHash($this, $res, $status);
        }

        return $res;
    }
    public static function checkTimeForRefresh($hitTime, $url) { return false; }
    public function stopManualRefresh($name)
    {
        $res = false;
        plg_RefreshRows::on_AfterStopManualRefresh($this, $res, $name);

        return $res;
    }
    public function manualRefreshRes($url, $type) { return array('manual refresh'); }
    public function action($action)
    {
        $this->renders++;
        $this->renderFilters[] = array(Request::get('assetId'), Request::get('folderId'));
        if ($this->duringRender) call_user_func($this->duringRender, $this);

        return new core_ET();
    }
}

function setupCase($filters = array())
{
    $mvc = new RefreshRowsTasks();
    cls::$mvc = $mvc;
    $mvc->records = array(
        (object) array('assetId' => 1, 'folderId' => 10, 'modifiedOn' => '2026-01-01 10:00:00'),
        (object) array('assetId' => 2, 'folderId' => 20, 'modifiedOn' => '2026-01-01 12:00:00'),
    );
    $url = array('Ctr' => 'RefreshRowsTasks', 'Act' => 'list') + $filters;
    Request::$vars = array('base' => array(
        'Ctr' => 'RefreshRowsTasks', 'Act' => 'ajaxrefreshrows', 'ajax_mode' => 1,
        'refreshUrl' => json_encode($url), 'hitTime' => 100, 'divId' => 'syntheticRows',
    ));
    core_Cache::$values = array();

    return $mvc;
}

function seedHash($mvc)
{
    $url = core_App::parseLocalUrl(Request::get('refreshUrl'));
    Request::push($url, 'initialList');
    $status = 'rendered rows';
    try {
        $hash = $mvc->getContentHash($status);
    } finally {
        Request::pop('initialList');
    }
    $name = plg_RefreshRows::getNameHash(Request::get('refreshUrl'), 100);
    core_Cache::set('plg_RefreshRows', $name, $hash, 60);
}

function poll($mvc)
{
    $res = null;
    plg_RefreshRows::on_BeforeAction($mvc, $res, 'ajaxrefreshrows');

    return $res;
}

$checks = 0;
function check($ok, $message)
{
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}

$mvc = setupCase();
seedHash($mvc);
check(poll($mvc) === array() && $mvc->renders === 0, 'Unchanged rows skip rendering');
$mvc->records[1]->modifiedOn = '2026-01-01 13:00:00';
$res = poll($mvc);
check($mvc->renders === 1 && count($res) === 2 && ($res[0]->func ?? null) === 'html', 'Changed rows are returned');

foreach (array(array('assetId' => 1), array('folderId' => 10), array('assetId' => '1_hash', 'folderId' => '10_hash')) as $filters) {
    $mvc = setupCase($filters);
    $before = Request::$vars;
    check(count(poll($mvc)) === 2 && $mvc->renders === 1, 'A filtered cache miss renders rows');
    check(poll($mvc) === array() && $mvc->renders === 1, 'A filtered cache miss must not cause repeated rendering');
    check(Request::$vars === $before, 'Successful hashes restore Request');
    check($mvc->renderFilters[0] === array($filters['assetId'] ?? null, $filters['folderId'] ?? null), 'Forward keeps the filters');

    $mvc = setupCase($filters);
    seedHash($mvc);
    check(poll($mvc) === array() && $mvc->renders === 0, 'Initial filtered hash skips rendering');
    $mvc->records[0]->modifiedOn = '2026-01-01 13:00:00';
    $mvc->records[1]->modifiedOn = '2026-01-01 14:00:00';
    check(count(poll($mvc)) === 2 && $mvc->renders === 1, 'Changes in two machines refresh the selected machine');
    check(poll($mvc) === array() && $mvc->renders === 1, 'A newer row outside the filter does not cause repeated rendering');
    $mvc->records[1]->modifiedOn = '2026-01-01 15:00:00';
    check(poll($mvc) === array() && $mvc->renders === 1, 'Changes outside the filter skip rendering');
}

$mvc = setupCase(array('assetId' => 99));
check(count(poll($mvc)) === 2, 'An empty filtered list is returned on a cache miss');
check(poll($mvc) === array() && $mvc->renders === 1, 'An empty filtered list has a stable hash');

// The final hash must still be computed after rendering, rather than reusing the early hash.
$mvc = setupCase(array('assetId' => 1));
$mvc->duringRender = function ($mvc) { $mvc->records[0]->modifiedOn = '2026-01-01 13:00:00'; };
poll($mvc);
check($mvc->hashInputs === array('', 'rendered rows'), 'Both hash hooks run with their original content');
check(poll($mvc) === array() && $mvc->renders === 1, 'The cached hash reflects the state after rendering');

foreach (array(1, 2) as $throwOnHash) {
    $mvc = setupCase(array('assetId' => 1));
    $before = Request::$vars;
    $mvc->throwOnHash = $throwOnHash;
    $caught = false;
    try {
        poll($mvc);
    } catch (RuntimeException $e) {
        $caught = $e->getMessage() === 'hash failure';
    }
    check($caught, 'Early and final hash failures propagate');
    check(Request::$vars === $before, 'Early and final hash failures restore Request');
}

// Legacy managers still hash rendered content in the original Request context.
foreach (array(false, null) as $optIn) {
    $mvc = setupCase(array('assetId' => 1));
    $mvc->refreshRowsHashWithoutContent = $optIn;
    if ($optIn === null) unset($mvc->refreshRowsHashWithoutContent);
    $mvc->contentHash = true;
    $mvc->normalizeContent = true;
    $res = poll($mvc);
    check(count($res) === 2 && ($res[0]->arg['html'] ?? null) === 'RENDERED ROWS', 'Legacy hashes can still change content by reference');
    check(poll($mvc) === array() && $mvc->renders === 2 && $mvc->hashCalls === 2, 'Legacy managers render once per poll');
    check($mvc->hashInputs === array('rendered rows', 'rendered rows'), 'Legacy hashes receive the rendered content');
    check($mvc->hashFilters === array(array(null, null), array(null, null)), 'Legacy hashes keep their Request context');
}

$mvc = setupCase();
$mvc->refreshRowsHashWithoutContent = false;
$mvc->contentHash = true;
$mvc->manualRefreshCnt = 1;
check(poll($mvc) === array('manual refresh'), 'Manual refresh returns a notice');
check(poll($mvc) === array() && $mvc->renders === 1, 'Manual refresh limit still stops polling');
$name = plg_RefreshRows::getNameHash(Request::get('refreshUrl'), 100);
check(core_Cache::get('plg_RefreshRows', $name) === false, 'Manual refresh does not cache unseen content');

echo "PASS: {$checks} refresh rows checks\n";
