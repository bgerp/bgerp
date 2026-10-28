<?php

/**
 * Изолирани проверки на домейните в richtext без база данни.
 *
 * @category bgerp
 * @package type
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
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
define('EF_APP_NAME_FIXED', true);
function getBoot($absolute = false, $forceHost = false, $addApp = false) { return ($absolute ? 'https://app.example.com' : '') . Fixture::$base; }
function decodeUrl($url) { return urldecode($url); }
function countR($value) { return is_array($value) ? count($value) : 0; }
function expect($condition) { if (!$condition) throw new RuntimeException('Framework expectation failed'); }
class Fixture { public static $base = ''; public static $verbalCalls = 0; }
class Mode
{
    public static $text;
    public static function get($name) { return $name === 'text' ? self::$text : null; }
    public static function is($name, $value = null) { return $name === 'text' && self::$text === $value; }
    public static function isReadOnly() { return self::$text === 'xhtml'; }
}
class cms_Domains { public static function getDomainOptions($unique) { return array('example.com', 'localhost'); } }
class core_Manager { public function act_List() {} public function act_Default() {} }
class core_Master extends core_Manager { public function act_Single() {} }
class fixture_Items extends core_Master {}
class fixture_Public extends core_Master { public $loadList = 'cms_ExternalWrapper'; }
class fixture_PublicArray extends core_Master { public $loadList = array('cms_ExternalWrapper'); }
class fixture_Index extends core_Manager { public function act_Default() {} }
class borsa_Lots extends core_Master {}
class fileman_Files extends core_Master { public function act_Previewfile() {} }
class bgerp_Portal extends core_Manager { public function act_Show() {} }
class doc_Threads extends core_Manager
{
    public static function getVerbalLink($params) { Fixture::$verbalCalls++; return '<a href="/thread/17">Thread 17</a>'; }
}
class doc_Folders
{
    public static function getVerbalLink($params) { Fixture::$verbalCalls++; return '<a href="/folder/17">Folder 17</a>'; }
}
class doc_Containers extends core_Manager
{
    public static function getVerbalLink($params) { Fixture::$verbalCalls++; return '<a href="/document/17">Document 17</a>'; }
}
class core_Request { public static function unprotectId($id, $ctr) { return $id; } }
class cls
{
    public static function load($class, $silent = false) { return class_exists(self::getClassName($class), false); }
    public static function getClassName($class) { return strtolower($class) === 'portal' ? 'bgerp_Portal' : $class; }
}
class type_Varchar { public static function escape($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); } }
class ht { public static function balanceTags($value) { return $value; } }
require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
require dirname(__DIR__, 2) . '/core/Plugin.class.php';
require dirname(__DIR__, 2) . '/bgerp/plg/InternalLinkReplacement.class.php';
class fixture_Observer extends core_Plugin
{
    public function on_BeforeInternalUrl($rt, &$res, $url, $title, $rest) { $rt->internalCalls++; }
}
class type_Blob extends core_BaseClass
{
    public $_htmlBoard = array();
    public $randMark = 1;
    public $internalCalls = 0;
    public function __construct()
    {
        $this->_plugins = array(new bgerp_plg_InternalLinkReplacement(), new fixture_Observer());
    }
}
require dirname(__DIR__, 2) . '/core/String.class.php';
class_alias('core_String', 'str');
require dirname(__DIR__, 2) . '/core/Url.class.php';
require dirname(__DIR__) . '/Richtext.class.php';

$checks = 0;
function check($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function renderLink($url, $bbcode, $title = ' Link')
{
    Fixture::$verbalCalls = 0;
    $rt = new type_Richtext();
    $html = $bbcode ? $rt->_catchLink(array('', '', $url, $title)) : $rt->_catchUrls(array($url));
    foreach ($rt->_htmlBoard as $key => $value) $html = str_replace('[#' . $key . '#]', $value, $html);
    preg_match('/href="([^"]+)"/', $html, $match);
    return array($match[1] ?? $html, $rt->internalCalls, $html);
}
$cases = array(
    'https://example.com/fixture_Items/list/' => '/fixture_Items/list/',
    'https://example.com/fixture_Items/single/17' => '/fixture_Items/single/17',
    'https://example.com/fixture_Items/default/' => '/fixture_Items/default/',
    'https://example.com/fixture_Items/' => '/fixture_Items/',
    'http://WWW.EXAMPLE.COM/fixture_Items/LIST/?q=a%2Fb&n=2' => '/fixture_Items/LIST/?q=a%2Fb&n=2',
    'https://example.com/fileman_Files/previewfile/17' => '/fileman_Files/previewfile/17',
    'https://example.com/Portal/Show' => '/Portal/Show',
    'https://example.com/?Ctr=fixture_Items&Act=list' => '/?Ctr=fixture_Items&Act=list',
    'https://example.com/index.php?Ctr=fixture_Items&Act=list' => '/index.php?Ctr=fixture_Items&Act=list',
);
foreach ($cases as $url => $expected) {
    foreach (array(false, true) as $bbcode) {
        list($href, $calls) = renderLink($url, $bbcode);
        $formatted = !$bbcode && (strpos($url, '/single/') !== false || strpos($url, '/previewfile/') !== false);
        check($href === ($formatted ? '/document/17' : $expected), "Internal URL: {$url}: {$href}");
        if (!$bbcode) check($calls === 1, 'Internal document formatting must remain enabled');
    }
}
list($href, , $html) = renderLink('https://example.com/fixture_Items/list/', true, '');
check($href === '/fixture_Items/list/' && strpos($html, '>example.com</a>') !== false, 'An empty BBCode title keeps the original hostname');
foreach (array(
    'https://example.com/',
    'https://example.com/fixture_Index/default/',
    'https://example.com/fixture_Items/show/17',
    'https://example.com/fixture_Public/single/17',
    'https://example.com/fixture_Public/list/',
    'https://example.com/fixture_PublicArray/list/',
    'https://example.com/borsa_Lots/list/',
    'https://example.com/borsa_Lots/default/',
    'https://example.com/unknown_Controller/list/',
    'https://example.com/Bg/Products',
    'https://example.com/?Ctr[]=fixture_Items&Act=list',
    'https://example.com/?Ctr=fixture_Items&Act[]=list',
    'https://example.com.other.test/fixture_Items/list/',
    'https://example.com@other.test/fixture_Items/list/',
    'https://user@example.com/fixture_Items/list/',
    'https://other.test/fixture_Items/list/',
    'http://localhost/fixture_Items/list/',
) as $url) {
    foreach (array(false, true) as $bbcode) {
        list($href, $calls) = renderLink($url, $bbcode);
        check($href === $url, "Preserved URL: {$url}: {$href}");
        check(Fixture::$verbalCalls === 0, 'Public/unknown links must not enter document formatting');
    }
}
foreach (array('doc_Threads/list/?folderId=17' => '/folder/17', 'doc_Containers/list/?threadId=17' => '/thread/17') as $path => $expected) {
    list($href) = renderLink('https://example.com/' . $path, false);
    check($href === $expected && Fixture::$verbalCalls === 1, 'Existing folder/thread formatting');
    list($href, , $html) = renderLink('https://example.com/' . $path, true);
    check($href === '/' . $path && strpos($html, '> Link</a>') !== false, 'BBCode keeps its explicit title');
}
list(, $calls) = renderLink('https://example.com/fixture_Public/single/17', false);
check($calls === 1, 'Other plugins must still receive public URLs on supported domains');
Fixture::$base = '/erp';
foreach (array(false, true) as $bbcode) {
    list($href) = renderLink('https://example.com/erp/fixture_Items/list/?q=a%2Fb#section', $bbcode);
    check($href === '/erp/fixture_Items/list/?q=a%2Fb#section', 'Installation path, query and fragment');
    list($href) = renderLink('https://example.com/other/fixture_Items/list/', $bbcode);
    check($href === 'https://example.com/other/fixture_Items/list/', 'Another application path');
    Mode::$text = 'xhtml';
    list($href) = renderLink('https://example.com/erp/fixture_Items/list/', $bbcode);
    check($href === 'https://app.example.com/erp/fixture_Items/list/', 'Export needs an absolute URL');
    Mode::$text = null;
}
echo "PASS: {$checks} richtext domain checks\n";
