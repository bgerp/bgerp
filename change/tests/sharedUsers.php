<?php

/**
 * CLI регресия за получателите във версиите, без база данни
 *
 * @category bgerp
 * @package change
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function countR($value) { return is_countable($value) ? count($value) : 0; }
function expect($value) { if (!$value) throw new RuntimeException('Expectation failed'); }
function tr($value) { return $value; }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }

$root = dirname(__DIR__, 2);
foreach (array('core/BaseClass', 'core/Plugin', 'core/Type', 'core/Array', 'core/ET', 'type/Keylist', 'lib/Diff') as $file) {
    require $root . '/' . $file . '.class.php';
}
class_alias('core_Array', 'arr');
class_alias('core_ET', 'ET');
class_alias('type_Keylist', 'keylist');

class SharingFixture
{
    public static $logs = array(), $documents = array(), $checks = 0;
    public static function check($value, $message)
    {
        if (!$value) throw new RuntimeException($message);
        self::$checks++;
    }
    public static function value($value, $index)
    {
        return (object) array('value' => $value, 'version' => '0', 'subVersion' => $index + 1,
            'createdOn' => '2026-01-0' . ($index + 1) . ' 12:00:00', 'createdBy' => 9);
    }
}
class SharingQuery
{
    private $filters = array(), $rows;
    public function where($condition)
    {
        $this->filters[strpos($condition[0], '#docClass') !== false ? 'docClass' : 'docId'] = $condition[1];
    }
    public function fetch()
    {
        if (!isset($this->rows)) {
            $this->rows = array();
            foreach (SharingFixture::$logs as $rec) {
                if (($rec->docClass ?? null) == ($this->filters['docClass'] ?? null) &&
                    ($rec->docId ?? null) == ($this->filters['docId'] ?? null)) $this->rows[] = clone $rec;
            }
        }
        return array_shift($this->rows);
    }
}
class core_Mvc extends core_BaseClass {}
class core_Manager extends core_Mvc
{
    public static function getQuery() { return new SharingQuery(); }
    public static function save($rec)
    {
        $key = ($rec->docId ?? null) . '_' . ($rec->field ?? '');
        $rec->id = SharingFixture::$logs[$key]->id ?? (count(SharingFixture::$logs) + 1);
        SharingFixture::$logs[$key] = clone $rec;
        return $rec->id;
    }
}
class core_Classes { public static function getId($mvc) { return 501; } }
class cls
{
    public static $document;
    public static function get($class) { return self::$document; }
    public static function haveInterface($interface, $mvc) { return true; }
}
class Request { public static function get($key) { return null; } }
class Mode
{
    public static $values = array();
    public static function get($key) { return self::$values[$key] ?? null; }
    public static function setPermanent($key, $value) { self::$values[$key] = $value; }
    public static function is($key, $value = true) { return (self::$values[$key] ?? null) === $value; }
}
class dt
{
    public static function mysql2verbal($value, $format) { return $value; }
    public static function now() { return '2026-01-03 12:00:00'; }
    public static function verbal2Mysql() { return self::now(); }
}
class core_DateTime extends dt {}
class core_Users { public static function getCurrent() { return 9; } }
class Users extends core_Users {}
class crm_Profiles
{
    public static function createLink($id) { return '<a>User' . (int) $id . '</a>'; }
}
class SharingType
{
    public $users;
    public function __construct($users) { $this->users = $users; }
    public function toVerbal($value)
    {
        if (!$this->users) return (string) $value;
        return implode(', ', array_map(function ($id) { return 'User' . $id; }, array_keys(keylist::toArray($value))));
    }
}
class SharingDocument extends core_Mvc
{
    public $className = 'SharingDocument', $fields = array(), $changableFields = 'body';
    public function __construct($hasAssign = true)
    {
        foreach ($hasAssign ? array('body', 'sharedUsers', 'assign') : array('body', 'sharedUsers') as $field) {
            $this->fields[$field] = (object) array('type' => new SharingType($field !== 'body'));
        }
    }
    public function getForm() { return (object) array('fields' => $this->fields); }
    public function getField($field, $strict = true) { return $this->fields[$field] ?? false; }
    public function getFieldType($field) { return $this->fields[$field]->type ?? null; }
    public function fetch($id) { return clone SharingFixture::$documents[$id]; }
    public function getShared($id)
    {
        $rec = $this->fetch($id);
        return keylist::fromArray(keylist::toArray($rec->sharedUsers ?? null) + keylist::toArray($rec->assign ?? null));
    }
}
require $root . '/change/Log.class.php';
require $root . '/change/Plugin.class.php';
require $root . '/doc/SharablePlg.class.php';

function prepareSharing($id, $selection, $history, $current, $hasAssign = true)
{
    cls::$document = $mvc = new SharingDocument($hasAssign);
    $rec = (object) ($current + array('id' => $id, 'version' => '0', 'subVersion' => 3,
        'modifiedOn' => '2026-01-03 12:00:00', 'modifiedBy' => 9));
    SharingFixture::$documents[$id] = clone $rec;
    foreach ($history as $field => $values) {
        $log = (object) array('docClass' => 501, 'docId' => $id, 'field' => $field, 'value' => array());
        foreach ($values as $index => $value) $log->value[$index] = SharingFixture::value($value, $index);
        core_Manager::save($log);
    }
    $selected = array();
    foreach ($selection as $index) $selected[$index === 'current' ? $id : "501.{$id}.{$index}"] = true;
    Mode::$values['versionLog']["501_{$id}"] = $selected;
    $data = (object) array('rec' => $rec, 'row' => (object) array('body' => $rec->body ?? '', 'assign' => '<a>Current formatting</a>'));
    change_Plugin::on_AfterPrepareSingle($mvc, $data, $data);
    return $data;
}
function renderSharing($data, $reverse = false)
{
    $tpl = new ET("<!--ET_BEGIN shareLog--><div class='sharing-history'>[#shareLog#]</div><!--ET_END shareLog-->");
    if ($reverse) {
        change_Plugin::on_AfterRenderSingle(cls::$document, $tpl, $data);
        doc_SharablePlg::on_AfterRenderSingle(cls::$document, $tpl, $data);
    } else {
        doc_SharablePlg::on_AfterRenderSingle(cls::$document, $tpl, $data);
        change_Plugin::on_AfterRenderSingle(cls::$document, $tpl, $data);
    }
    return $tpl->getContent();
}

$history = array('body' => array('Old text', 'New text'), 'sharedUsers' => array('|2|', '|3|'), 'assign' => array('|4|', ''));
$current = array('body' => 'Current text', 'sharedUsers' => '|3|5|', 'assign' => '|6|',
    'sharedViews' => serialize(array(2 => '2026-01-01 12:30:00', 3 => '2026-01-02 12:30:00')));
$data = prepareSharing(10, array(0), $history, $current);
SharingFixture::check(($data->row->sharedUsers ?? null) === 'User2' && ($data->row->assign ?? null) === 'User4', 'Historical recipients must replace current ones');
SharingFixture::check(strpos(renderSharing($data), 'User2') !== false && strpos(renderSharing($data), 'User4') === false, 'Assignments must stay in their existing field');
SharingFixture::check(renderSharing($data) === renderSharing($data, true), 'Plugin order must not restore current sharing');
SharingFixture::check(($data->rec->sharedUsers ?? null) === '|3|5|' && ($data->rec->assign ?? null) === '|6|', 'Historical rendering must not mutate current recipients');

$data = prepareSharing(11, array(0, 1), $history, $current);
SharingFixture::check(strpos($data->row->sharedUsers ?? '', 'title="User2"') !== false && strpos($data->row->sharedUsers ?? '', 'User3') !== false, 'Two old versions must show the same diff as text');
SharingFixture::check(strpos($data->row->assign ?? '', 'User4') !== false && strpos($data->row->assign ?? '', 'Няма') !== false, 'Removing all assignees must be visible');
$data = prepareSharing(12, array(0, 'current'), $history, $current);
SharingFixture::check(strpos($data->row->assign ?? '', 'User4') !== false && strpos($data->row->assign ?? '', 'User6') !== false, 'Old/current assignment comparison must use consistent formatting');
SharingFixture::check(strpos(renderSharing($data), 'User5') !== false && strpos(renderSharing($data), '12:30') === false, 'The diff must exclude the live viewing history');

$expected = "<div class='sharing-history'><span style='color:black;'><a>User2</a></span> (2026-01-01 12:30:00), <span style='color:black;'><a>User3</a></span> (2026-01-02 12:30:00), <span style='color:black;'><a>User5</a></span>, <span style='color:black;'><a>User6</a></span></div>";
foreach (array(array(), array('current')) as $index => $selection) {
    $data = prepareSharing(13 + $index, $selection, $history, $current);
    SharingFixture::check(empty($data->versionUserFields), 'Latest version must retain normal sharing');
    $actual = renderSharing($data);
    SharingFixture::check($actual === $expected, 'Unexpected latest sharing: ' . $actual);
}

$data = prepareSharing(15, array(1), $history, $current);
SharingFixture::check(($data->row->assign ?? null) === 'Няма', 'Empty assignment is known, not missing history');
$data = prepareSharing(16, array(0), array('body' => array('Old text')), $current, false);
SharingFixture::check(($data->row->sharedUsers ?? null) === 'Няма данни' && !isset($data->versionUserFields['assign']), 'Missing old data must not be fabricated or add an assignment field');
Mode::$values['text'] = 'xhtml';
SharingFixture::check(strpos(renderSharing($data), 'Няма данни') === false, 'XHTML must retain existing sharing suppression');
unset(Mode::$values['text']);

$data = prepareSharing(17, array(), array('body' => array('First', 'Second')), $current);
$old = clone SharingFixture::$documents[17];
$old->subVersion = 3;
change_Log::create(501, array('body'), $old, $old);
$saved = change_Log::getRecForVersion(501, 17, '501.17.2', array('sharedUsers' => 'sharedUsers', 'assign' => 'assign'));
SharingFixture::check(($saved['sharedUsers']->value ?? null) === '|3|5|' && ($saved['assign']->value ?? null) === '|6|', 'Every new document version must snapshot recipients, including non-editable fields');
SharingFixture::check(!change_Log::getRecForVersion(501, 17, '501.17.0', 'sharedUsers'), 'Newly tracked recipients must not be attached to the first old version');
SharingFixture::check(change_Plugin::getAllowedFields(cls::$document->getForm(), 'body') === array('body' => 'body'), 'Recording recipients must not expand edit permissions');

echo 'PASS: ' . SharingFixture::$checks . " checks\n";
