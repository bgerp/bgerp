<?php

/**
 * Генерира изолиран браузърен тест с реалния адаптер и избран minify.
 *
 * @category tests
 * @package ckeditor
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 4) exit("Usage: php render.php OUTPUT_DIR VERSION raw|legacy|new [bg|en]\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/** Проверява условие без framework bootstrap. */
function expect($condition, $message = 'Assertion failed') { if (!$condition) throw new RuntimeException($message); }
/** Зарежда файловете от текущото хранилище. */
function getFullPath($path) { $file = dirname(__DIR__, 2) . '/' . $path; return is_file($file) ? $file : false; }
/** Дефинира default само веднъж. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Изключва единствено диагностичните HTML коментари. */
function isDebug() { return false; }
/** Замества framework брояча за minify. */
function countR($items) { return count((array) $items); }
/** Запазва отчет за fallback поведението на реалния SBF hook. */
function reportException($message, $error) { echo 'Minify fallback: ' . get_class($error) . "\n"; }
class core_Plugin {}
class core_exception_Redirect extends Exception {}
class str
{
    /** Извлича разширението за SBF hook-а. */
    public static function getFileExt($path) { return pathinfo($path, PATHINFO_EXTENSION); }
}
class core_BaseClass {}
class core_ProtoSetup
{
    /** Връща тестовия избор, без промени в инсталацията. */
    public static function get($key)
    {
        if (get_called_class() === 'ckeditor_Setup') return $GLOBALS['argv'][2];
        return $GLOBALS['argv'][3] === 'new' ? ($key === 'CSS_VERSION' ? '4.1.1' : 'jshrink-1.8.1') : 'legacy';
    }
}
class core_Lg
{
    /** Връща езика на генерирания пример. */
    public static function getCurrent() { return $GLOBALS['argv'][4] ?? 'bg'; }
}
class core_ET
{
    public $body = '';
    public $head = '';
    public $run = '';
    public $files = array('CSS' => array(), 'JS' => array());
    /** Събира ресурсите без дублиране при второто поле. */
    public function push($file, $place) { $this->files[$place][$file] = $file; }
    /** Събира стария inline код. */
    public function append($text, $place = '') { $this->body .= $text; }
    /** Събира ресурса на стария build. */
    public function appendOnce($text, $place = '') { $this->head .= $text; }
}
class jquery_Jquery
{
    /** Запазва реалните init извиквания за браузърния тест. */
    public static function run($tpl, $code) { $tpl->run .= $code; }
}

require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/CKeditor.class.php';
require dirname(__DIR__, 2) . '/minify/Setup.class.php';
require dirname(__DIR__, 2) . '/minify/Js.class.php';
require dirname(__DIR__, 2) . '/minify/Css.class.php';
require dirname(__DIR__, 2) . '/minify/Plugin.class.php';
expect(CKEDITOR_VERSION === '5.0.1', 'Changed default');
expect(in_array($argv[3], array('raw', 'legacy', 'new'), true), 'Invalid minify mode');
$output = $argv[1];
if (!is_dir($output)) mkdir($output, 0775, true);
$tpl = new core_ET();
$adapter = new ckeditor_CKeditor();
$adapter->renderHtml($tpl, array('id' => 'first'));
$adapter->renderHtml($tpl, array('id' => 'second'));
$assetTags = '';
$assets = $tpl->files;
if ($argv[2] === '5.0.1') {
    $assets['JS']['legacy'] = 'ckeditor/5.0.1/build/ckeditor.js';
}
foreach ($assets as $kind => $files) {
    foreach ($files as $file) {
        $content = file_get_contents(getFullPath($file));
        if ($argv[3] !== 'raw') {
            $res = null;
            minify_Plugin::on_BeforeSaveFile(null, $res, $content, $file);
        }
        $name = str_replace('/', '-', $file);
        file_put_contents($output . '/' . $name, $content);
        $assetTags .= $kind === 'CSS' ? '<link rel="stylesheet" href="' . $name . '">' : '<script src="' . $name . '"></script>';
    }
}
copy(__DIR__ . '/browser.js', $output . '/browser.js');
$version = htmlspecialchars($argv[2], ENT_QUOTES, 'UTF-8');
$html = '<!doctype html><html lang="bg"><meta charset="utf-8"><title>CKEditor regression</title>' . $assetTags;
$html .= '<style>body{max-width:1000px;margin:20px auto;font:16px sans-serif}.ck-editor{max-width:1000px}#results{white-space:pre-wrap}</style>';
$html .= '<h1>CKEditor ' . $version . ' / PHP ' . PHP_VERSION . ' / ' . $argv[3] . '</h1>';
$html .= '<button id="test">Run regression tests</button><pre id="results">Ready</pre><form><textarea id="first"></textarea><textarea id="second"></textarea></form>';
$html .= $tpl->body . '<script>' . $tpl->run . '</script><script src="browser.js"></script></html>';
file_put_contents($output . '/index.html', $html);
echo 'PASS PHP ' . PHP_VERSION . ': default, adapter, two fields, ' . $version . ', ' . $argv[3] . ' -> ' . $output . "/index.html\n";
