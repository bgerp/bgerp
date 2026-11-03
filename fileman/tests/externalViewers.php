<?php

/**
 * Isolated checks for external file viewers, without a database or network requests.
 *
 * @category  bgerp
 * @package   fileman
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// Run: php fileman/tests/externalViewers.php [source-root]
if (PHP_SAPI !== 'cli') exit("Run from CLI.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); }
function expect($ok) { if (!$ok) throw new RuntimeException('Expectation failed'); }
function tr($text) { return $text; }
function setIfNot(&$value, $fallback) { if (!isset($value)) $value = $fallback; }
class core_Plugin {}
class core_Exception_Expect extends Exception {}
class arr { public static function make($text) { return explode(',', $text); } }
class core_Type { public static function escape($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); } }
class core_ET
{
    public $content;
    public $values = array();
    public function __construct($template = '', $value = '') { $this->content = str_replace('[#1#]', $value, $template); }
    public function getContent() { return $this->content; }
    public function replace($value, $key) { $this->values[$key] = $value; }
    public function push($file, $place) {}
}
function getTplFromFile($path) { return new core_ET(); }
class jquery_Jquery { public static function run(&$tpl, $code) {} }
class Request { public static $values = array(); public static function get($key) { return self::$values[$key] ?? null; } }
class Mode { public static function set($key, $value) {} public static function is($key, $value) { return false; } }
class core_App { public static $private = false; public static function checkCurrentHostIsPrivate() { return self::$private; } }
class fileman_Setup { public static function get($key) { return 600; } }
class fileman_Files
{
    public static $records = array();
    public static function getExt($name) { return strtolower(pathinfo($name, PATHINFO_EXTENSION)); }
    public static function fetchByFh($fh) { return self::$records[$fh] ?? false; }
}
class fileman_Download
{
    public static $calls = array();
    public static $url = 'https://files.example.test/A%26B%23C.pdf?token=a%2Bb&v=1';
    public static function getDownloadUrl($fh, $lifeTime = 1, $type = 'handler', $absolute = true)
    {
        self::$calls[] = $fh;
        return self::$url;
    }
}
class bgerp_F { public static function getShortLink($fh, $name) { return fileman_Download::$url; } }
class ViewerToolbar
{
    public $buttons = array();
    public function addBtn($title, $url, $params, $more) { $this->buttons[] = array('title' => $title, 'url' => $url); }
}
class ViewerMvc
{
    public $allowed = true;
    public $checked = array();
    public function haveRightFor($action, $rec = null) { return $this->allowed; }
    public function requireRightFor($action, $rec = null)
    {
        $this->checked[] = $rec->fileHnd ?? null;
        if (!$this->allowed) throw new RuntimeException('Denied');
    }
    public function logRead($message, $id) {}
}

$root = $argv[1] ?? dirname(__DIR__, 2);
require dirname(__DIR__, 2) . '/core/Html.class.php';
class ht extends core_Html
{
    public static function createLink($title, $url = array(), $warning = null, $attr = array()) { return $title; }
}
class_alias('fileman_Files', 'fileman');
foreach (array('gdocs/Plugin', 'pixlr/Plugin', 'sharecad/View', 'sharecad/plugins/DWG', 'photopea/Plugin') as $file) {
    require $root . '/' . $file . '.class.php';
}
$mvc = new ViewerMvc();
$res = null;
$rec = (object) array('id' => 1, 'name' => 'A & B.pdf', 'fileHnd' => 'testPdf');
$data = (object) array('rec' => $rec, 'toolbar' => new ViewerToolbar());
(new gdocs_Plugin())->on_AfterPrepareSingleToolbar($mvc, $res, $data);
parse_str(parse_url($data->toolbar->buttons[0]['url'], PHP_URL_QUERY), $query);
check(($query['url'] ?? null) === fileman_Download::$url, 'Google must receive the complete original URL');
check(count($query) === 1, 'File query parameters must not become Google parameters');

$pixlrName = 'A & B + #.png';
$rec->name = $pixlrName;
$toolbar = $data->toolbar = new ViewerToolbar();
fileman_Download::$calls = array();
(new pixlr_Plugin())->on_AfterPrepareSingleToolbar($mvc, $res, $data);
$pixlrUrl = $toolbar->buttons[0]['url'] ?? '';
check(strpos($pixlrUrl, 'https://pixlr.com/editor/?') === 0, 'Use the HTTPS Pixlr editor');
parse_str(parse_url($pixlrUrl, PHP_URL_QUERY), $query);
check(($query['file'] ?? null) === fileman_Download::$url, 'Pixlr must receive the complete URL in file');
check(($query['name'] ?? null) === $pixlrName, 'Pixlr must receive the original name');
check(count($query) === 2, 'File and name characters must not create extra Pixlr parameters');
check(fileman_Download::$calls === array('testPdf'), 'Pixlr must use the selected file');

$withoutToolbar = (object) array('rec' => $rec);
fileman_Download::$calls = array();
(new pixlr_Plugin())->on_AfterPrepareSingleToolbar($mvc, $res, $withoutToolbar);
check(!fileman_Download::$calls, 'Do not publish a Pixlr file link without a toolbar');
$downloadUrl = fileman_Download::$url;
fileman_Download::$url = false;
$toolbar = $data->toolbar = new ViewerToolbar();
(new pixlr_Plugin())->on_AfterPrepareSingleToolbar($mvc, $res, $data);
check(!$toolbar->buttons, 'Do not offer Pixlr when no download URL is available');
fileman_Download::$url = $downloadUrl;

foreach (array('gdocs_Plugin', 'pixlr_Plugin', 'photopea_Plugin') as $plugin) {
    $data->toolbar = new ViewerToolbar();
    $mvc->allowed = false;
    (new $plugin())->on_AfterPrepareSingleToolbar($mvc, $res, $data);
    check(!$data->toolbar->buttons, 'No button without file access: ' . $plugin);
    $missing = new stdClass();
    (new $plugin())->on_AfterPrepareSingleToolbar($mvc, $res, $missing);
}
$mvc->allowed = true;
$rec->name = 'test.dwg';
fileman_Files::$records['testDwg'] = (object) array('fileHnd' => 'testDwg', 'name' => 'A & B.dwg');
Request::$values = array('id' => 'testDwg');
fileman_Download::$calls = array();
sharecad_plugins_DWG::on_BeforeAction($mvc, $res, 'preview');
check(fileman_Download::$calls === array('testDwg'), 'ShareCAD must use the requested file');
check(in_array('testDwg', $mvc->checked), 'ShareCAD must check rights to that file');
preg_match('/src="([^"]+)"/', $res, $match);
$url = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
check(parse_url($url, PHP_URL_HOST) === 'iframe.sharecad.org', 'Use the current ShareCAD iframe host');
parse_str(parse_url($url, PHP_URL_QUERY), $query);
check($query['url'] === fileman_Download::$url, 'ShareCAD must receive the complete download URL');
core_App::$private = true;
fileman_Download::$calls = array();
sharecad_plugins_DWG::on_BeforeAction($mvc, $res, 'preview');
check(!fileman_Download::$calls && strpos($res, 'публичен') !== false, 'Explain private hosts without publishing a file');
core_App::$private = false;

$mvc->allowed = false;
try {
    sharecad_plugins_DWG::on_BeforeAction($mvc, $res, 'preview');
    throw new LogicException('ShareCAD accepted a denied request');
} catch (RuntimeException $e) {
    check($e->getMessage() === 'Denied', 'Unexpected ShareCAD failure');
}
$mvc->allowed = true;
$rec->name = 'test.PNG';
$data->toolbar = new ViewerToolbar();
photopea_Plugin::on_AfterPrepareSingleToolbar($mvc, $res, $data);
check($data->toolbar->buttons[0]['title'] === 'Photopea', 'Offer Photopea for supported images');
check($data->toolbar->buttons[0]['url']['fileHnd'] === 'testPdf', 'Pass the selected file to Photopea');
check(!fileman_Download::$calls, 'Rendering the Photopea button must not publish a file');
$rec->name = 'test.exe';
$data->toolbar = new ViewerToolbar();
photopea_Plugin::on_AfterPrepareSingleToolbar($mvc, $res, $data);
check(!$data->toolbar->buttons, 'Do not offer Photopea for unsupported files');
Request::$values = array('fileHnd' => 'testPdf');
fileman_Files::$records['testPdf'] = $rec;
foreach (array(false, true) as $allowed) {
    $mvc->allowed = $allowed;
    try {
        photopea_Plugin::on_BeforeAction($mvc, $res, 'photopea');
        throw new LogicException('Photopea accepted a denied or unsupported request');
    } catch (RuntimeException $e) {
        check(!fileman_Download::$calls, 'Reject invalid Photopea requests before publishing a file');
    }
}
$rec->name = 'A < B & C.png';
photopea_Plugin::on_BeforeAction($mvc, $res, 'photopea');
check(fileman_Download::$calls === array('testPdf'), 'Photopea opens the selected file after checking rights');
check($res->values['FILE_NAME'] === 'A &lt; B &amp; C.png', 'Escape file names in the editor page');
echo "External viewer checks passed.\n";
