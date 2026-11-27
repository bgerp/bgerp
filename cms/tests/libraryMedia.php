<?php

/**
 * Достъп до кеширан библиотечен таг при смяна на зрителя.
 *
 * @category  bgerp
 * @package   cms
 * @author    Mustafa Mustafov <mmustafov084@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function tr($s) { return $s; }
function toUrl($parts) { return '/embedded?elm=' . $parts['elm']; }
class core_Plugin { public $mvc; }
class cls
{
    public static function load($name, $silent) { return class_exists($name); }
    public static function get($name) { return new $name(); }
}
class FixtureSource
{
    public static $viewer = 1, $exists = true, $enabled = true, $shared = array();
    public function haveRightFor($action, $rec, $userId = null) { return $action === 'single' && in_array($userId ?? self::$viewer, array_merge(array(1), self::$shared), true); }
}
class SourceQuery
{
    public $mvc;
    public function __construct() { $this->mvc = new FixtureSource(); }
    public function fetch($id) { return FixtureSource::$exists && $id === 27 ? (object) array('id' => $id) : null; }
}
class embed_Manager
{
    public static $records = array();
    public function invoke($event, $args) {
        if ($event === 'GetLibraryAccessQueries' && FixtureSource::$enabled) $args[0][3] = array('query' => new SourceQuery(), 'drivers' => array(5));
    }
    public static function fetch($key, $fields = '*') {
        if (!is_array($key)) return self::$records[$key];
        foreach (self::$records as $rec) {
            if ($rec->name === $key[1] && $rec->tag === $key[2]) return (object) array_intersect_key((array) $rec, array_flip(explode(',', $fields)));
        }
        return null;
    }
    public static function fetchRec($rec) { return is_object($rec) ? $rec : self::fetch($rec); }
    public static function fetchField($id, $field) { return self::$records[$id]->{$field}; }
    public static function getDriver($rec) { return new FixtureDriver(); }
    public static function getDriverFields($driver, $onlySingle, $returnFields) {
        return array('source' => (object) array('type' => new fileman_FileType()),
            'images' => (object) array('type' => new fileman_type_Files()),
            'body' => (object) array('type' => new type_Richtext()));
    }
    public function getRequiredRoles($action, $rec, $userId = null) {
        $roles = $this->getRequiredRoles_($action, $rec, $userId);
        return !empty($rec->driverClass) && !empty($rec->denyDriverAccess) ? 'no_one' : $roles;
    }
    public function getRequiredRoles_(&$action, $rec = null, $userId = null) { return 'cms'; }
}
class FixtureDriver { public function render($rec) { return $rec->data; } }
class core_Classes { public static function getOptionsByInterface($interface, $field) { return array(5 => 'FixtureDriver', 6 => 'FixtureDriver'); } }
class fileman_FileType {}
class fileman_type_Files {}
class type_Richtext {}
class keylist { public static function toArray($value) { return explode('|', trim($value, '|')); } }
class fileman_RichTextPlg { public static function getFiles($value) { return array('VideoFile' => 'Example'); } }
class fileman_Files
{
    public static $viewer = 1;
    public static function fetchByFh($fh) { return $fh === 'VideoFile' ? (object) array('bucketId' => 1, 'fileHnd' => $fh) : null; }
    public static function fetchField($id, $field) { return $id == 3 ? 'VideoFile' : null; }
    public static function haveRightFor($action, $file, $userId = null) { return $action === 'single' || ($userId ?? self::$viewer) === 1; }
}
class Request { public static $code; public static function get($name, $type) { return self::$code; } }
class Mode { public static function set($name, $value) {} public static function is($name, $value = null) { return false; } }
class jquery_Jquery { public static function run($tpl, $js) {} }
class ET
{
    public $media, $headers = array();
    public function replace($value, $place) { $this->media = $value; }
    public function append($value, $place) {}
    public function push($value, $place) { $this->headers[] = $value; }
}
class ht
{
    public static $attributes;
    /** Capture the frame attributes alongside the synthetic markup. */
    public static function createElement($tag, $attr, $body) {
        self::$attributes = $attr;
        return '<iframe src="' . $attr['src'] . '"></iframe>';
    }
}
class MediaRichtext
{
    public $params = array(), $_htmlBoard = array();
    public function getPlace() { return 'fixture'; }
}

require dirname(__DIR__) . '/Library.class.php';
require dirname(__DIR__) . '/LibraryRichTextPlg.class.php';
$library = new cms_Library();
$plugin = new cms_LibraryRichTextPlg();
$richtext = new MediaRichtext();
$public = (object) array('id' => 1, 'accessClassId' => null, 'accessObjectId' => null, 'driverClass' => 5, 'name' => 'public', 'tag' => 'Ab23', 'data' => 'Public preview');
$private = (object) array('id' => 2, 'accessClassId' => 3, 'accessObjectId' => 27, 'driverClass' => 5, 'name' => 'private', 'tag' => 'Qt56', 'data' => 'Private chart figures');
embed_Manager::$records = array(1 => $public, 2 => $private);
foreach (array($public, $private) as $rec) {
    $html = cms_Library::getEmbedTag($rec);
    $plugin->on_AfterCatchRichElements($richtext, $html);
    check($richtext->_htmlBoard['fixture'] === ($rec === $public ? $rec->data : '<iframe src="/embedded?elm=private-Qt56"></iframe>'), 'Cached HTML must preserve public previews and omit private data');
}
Request::$code = 'private-Qt56';
$action = 'single';
$partial = (object) array('id' => 2);
foreach (array(1, 2, 1) as $viewer) {
    FixtureSource::$viewer = $viewer;
    $visible = cms_Library::getVisibleEmbedded(cms_Library::getEmbedTag($private));
    check($viewer === 1 ? $visible === $private : $visible === null, 'Tag lookup shares the viewer check and returns all fields');
    $result = $library->act_Embedded();
    check($result->media === ($viewer === 1 ? $private->data : 'Недостъпен обект'), 'Frame must recheck every viewer');
    check(in_array('Cache-Control: private, no-store', $result->headers, true), 'Frame response is private');
    check($library->getRequiredRoles_($action, $partial) === ($viewer === 1 ? 'cms' : 'no_one'), 'Record permission on a partial record');
    check(cms_Library::render($private->id, 700) === ($viewer === 1 ? $private->data : ''), 'Direct render by record id checks the viewer');
}
FixtureSource::$viewer = 2;
FixtureSource::$shared = array(2);
check(cms_Library::getVisibleEmbedded(Request::$code) === $private, 'A shared viewer can read the native record');
FixtureSource::$shared = array();
check(cms_Library::getVisibleEmbedded(Request::$code) === null, 'Removing sharing denies the same tag');
FixtureSource::$viewer = 1;
$private->accessClassId = null;
check($library->act_Embedded()->media === 'Недостъпен обект', 'Unmigrated source stays private');
$private->accessClassId = 3;
$private->accessObjectId = null;
check($library->act_Embedded()->media === 'Недостъпен обект', 'Incomplete object reference stays private');
$private->accessObjectId = 27;
FixtureSource::$exists = false;
check(cms_Library::render($private, 700) === '', 'Missing source object must deny rendering');
FixtureSource::$enabled = false;
check($library->act_Embedded()->media === 'Недостъпен обект', 'Missing access provider must deny private media');
Request::$code = 'public-Ab23';
check($library->act_Embedded()->media === $public->data, 'Unscoped media stays public');
$public->driverClass = 6;
check(cms_Library::getVisibleEmbedded(Request::$code) === $public, 'Public driver outside the conversation list can be read by code');
check(cms_Library::getVisibleEmbedded('missing-tag') === null, 'Unknown tag has no visible record');
$public->driverClass = 5;
Request::$code = 'public-missing';
check($library->act_Embedded()->media === 'Недостъпен обект', 'Incorrect tag does not open the public frame');

FixtureSource::$enabled = FixtureSource::$exists = true;
FixtureSource::$viewer = 1;
Request::$code = 'private-Qt56';
$action = 'edit';
foreach (array('source' => 'VideoFile', 'images' => '|3|', 'body' => '[file=VideoFile]Example[/file]') as $field => $value) {
    $private->{$field} = $value;
    foreach (array(1, 2) as $viewer) {
        fileman_Files::$viewer = $viewer;
        check(cms_Library::render($partial, 700) === ($viewer === 1 ? $private->data : ''), 'File access for direct render');
        check($library->act_Embedded()->media === ($viewer === 1 ? $private->data : 'Недостъпен обект'), 'File access for frame');
        check($library->getRequiredRoles_($action, $partial) === ($viewer === 1 ? 'cms' : 'no_one'), 'File access for edit');
        check((cms_Library::getVisibleEmbedded(Request::$code) !== null) === ($viewer === 1), 'File access also guards full record reads');
        $public->{$field} = $value;
        check((cms_Library::getVisibleEmbedded(cms_Library::getEmbedTag($public)) !== null) === ($viewer === 1), 'File handles of public objects require file access');
        unset($public->{$field});
        check(cms_Library::render($public, 700) === $public->data, 'Unscoped preview is preserved');
    }
    unset($private->{$field});
}
$private->driverClass = 6;
check($library->act_Embedded()->media === 'Недостъпен обект', 'A retired private driver must not render through its public implementation');
check($library->getRequiredRoles_($action, $partial) === 'no_one', 'A retired private driver is unavailable for direct editing');
$private->driverClass = 7;
check($library->act_Embedded()->media === 'Недостъпен обект', 'Unregistered driver is refused');
$private->denyDriverAccess = true;
check($library->getRequiredRoles_($action, $partial) === 'no_one', 'A partial record still invokes the driver permission hook');
$richtext->params['libraryMediaFrames'] = true;
$html = '[ELM=public-Ab23]';
$plugin->on_AfterCatchRichElements($richtext, $html);
check(strpos($richtext->_htmlBoard['fixture'], '<iframe') === 0, 'Every registered driver can load ET resources in chat');
check((ht::$attributes['data-elm-tag'] ?? null) === '[ELM=public-Ab23]', 'The frame preserves the exact source tag for copying');
echo "OK: library drivers, public media, cached tags, file and source permissions.\n";
