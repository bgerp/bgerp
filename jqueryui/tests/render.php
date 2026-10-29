<?php

/**
 * Изолирани проверки и браузърна страница с реалните jqueryui/minify/compactor адаптери.
 *
 * @category tests
 * @package jqueryui
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 6) exit("Usage: php render.php OUTPUT_DIR UI_VERSION JQUERY_VERSION raw|legacy|new plain|compact\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });

/** Проверява условие без база данни. */
function expect($condition, $message = 'Assertion failed') { if (!$condition) throw new RuntimeException($message); }
/** Дефинира default за изолирания bootstrap. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Намира ресурс в текущото хранилище. */
function getFullPath($path) { $p = dirname(__DIR__, 2) . '/' . $path; return is_file($p) ? $p : false; }
/** Замества framework брояча. */
function countR($items) { return count((array) $items); }
/** Отбелязва реалния minify fallback. */
function reportException($message, $error) { echo 'Minify fallback: ' . $error->getMessage() . "\n"; }
class core_Plugin {}
class core_exception_Redirect extends Exception {}
class core_ProtoSetup
{
    /** Използва само подадените тестови настройки. */
    public static function get($key)
    {
        if (get_called_class() === 'jqueryui_Setup') return $key === 'VERSION' ? $GLOBALS['uiVersion'] : ($GLOBALS['uiCdn'][$key] ?? '');
        if (get_called_class() === 'jquery_Setup') return $key === 'VERSION' ? $GLOBALS['jqueryVersion'] : '';
        return $GLOBALS['argv'][4] === 'new' ? ($key === 'CSS_VERSION' ? '4.1.1' : 'jshrink-1.8.1') : 'legacy';
    }
}
class core_Packs
{
    /** Включва реалния compactor само в съответния вариант. */
    public static function isInstalled($pack) { return $pack === 'compactor' && $GLOBALS['argv'][5] === 'compact'; }
    /** Запазва формата на legacy конфигурационния обект. */
    public static function getConfig($pack) { return (object) array('JQUERYUI_VERSION' => $GLOBALS['uiVersion']); }
}
class core_ET
{
    public $files = array('JS' => array(), 'CSS' => array());
    /** Събира ресурсите в реда на действителния адаптер. */
    public function push($path, $kind) { $this->files[$kind][] = $path; }
}
class page_Html
{
    /** Запазва относителния път за SBF. */
    public static function getFileForAppend($path) { return $path; }
}
class arr
{
    /** Преобразува списъка на compactor. */
    public static function make($items, $assoc = false)
    {
        $items = is_array($items) ? $items : array_map('trim', explode(',', $items));
        return $assoc ? array_combine($items, $items) : $items;
    }
}
class str
{
    /** Връща разширението за minify. */
    public static function getFileExt($path) { return pathinfo($path, PATHINFO_EXTENSION); }
    /** Премахва префикса при нормализация на CSS URL. */
    public static function crop($value, $prefix) { return substr($value, strlen($prefix)); }
}
class core_Sbf
{
    /** Връща генерирания файл. */
    public static function getSbfFilePath($path) { return EF_SBF_PATH . '/' . $path; }
}
class dt
{
    /** Използва текущото време за кеша на compactor. */
    public static function mysql2timestamp() { return time(); }
}
class log_System
{
    /** Липсващ ресурс трябва да провали теста. */
    public static function add($class, $message, $id, $type) { throw new RuntimeException($message); }
}

/** Копира ресурс, изпълнява minify hook-а и връща URL за локалния HTTP сървър. */
function sbf($path, $quote = '', $absolute = false)
{
    static $copied = array();
    $dest = EF_SBF_PATH . '/' . $path;
    $source = getFullPath($path);
    if (!isset($copied[$path]) && $source) {
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
        $content = file_get_contents($source);
        if ($GLOBALS['argv'][4] !== 'raw') minify_Plugin::on_BeforeSaveFile(null, null, $content, $path);
        file_put_contents($dest, $content);
        $copied[$path] = true;
    }
    expect(is_file($dest), 'Missing resource: ' . $path);
    return $quote . '/' . basename(EF_SBF_PATH) . '/' . $path . $quote;
}

require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Ui.class.php';
foreach (array('jquery/Setup', 'jquery/Jquery', 'minify/Setup', 'minify/Js', 'minify/Css', 'minify/Plugin', 'compactor/Plugin') as $class) {
    require dirname(__DIR__, 2) . '/' . $class . '.class.php';
}
$uiVersion = $argv[2];
$jqueryVersion = $argv[3];
$uiCdn = array();
expect(JQUERYUI_VERSION === '1.12.1' && JQUERY_VERSION === '1.11.2', 'Changed defaults');
expect(in_array($uiVersion, array('1.8.2', '1.11.3', '1.12.1', '1.13.3', '1.14.2'), true), 'Invalid UI version');
expect(in_array($jqueryVersion, array('1.11.2', '3.7.1', '4.0.0'), true), 'Invalid jQuery version');
expect(in_array($argv[4], array('raw', 'legacy', 'new'), true), 'Invalid minify mode');
expect(in_array($argv[5], array('plain', 'compact'), true), 'Invalid compactor mode');
$setup = new jqueryui_Setup();
foreach (array('1.8.2', '1.11.3', '1.12.1', '1.13.3') as $old) {
    $uiVersion = $old;
    expect($setup->getCommonJs() === "jqueryui/{$old}/jquery-ui.js", 'Old compactor path changed');
    $tpl = new core_ET();
    jqueryui_Ui::enableJS($tpl);
    expect($tpl->files['JS'] === array("jqueryui/{$old}/jquery-ui.js"), 'Old loader changed');
}
$uiVersion = '1.14.2';
foreach (array('1.11.2' => false, '3.7.1' => true, '4.0.0' => true) as $version => $compatible) {
    $jqueryVersion = $version;
    expect((!$setup->checkConfig()) === $compatible, 'Compatibility diagnostic');
}
$uiCdn = array('CDN_URL' => 'https://example.invalid/ui.js', 'CDN_INTEGRITY' => 'synthetic-integrity');
$tpl = new core_ET();
jqueryui_Ui::enableJS($tpl);
expect($tpl->files['JS'][0] === 'jqueryui/js/compat.js' && is_object($tpl->files['JS'][1]), 'CDN dependency order');
expect(strpos($tpl->files['JS'][1]->fallbackScript, 'jqueryui/1.14.2/jquery-ui.js') !== false, 'CDN local fallback');
$uiCdn = array();
$uiVersion = $argv[2];
$jqueryVersion = $argv[3];
define('EF_SBF_PATH', rtrim($argv[1], '/'));
foreach (array('', '/js', '/css') as $dir) if (!is_dir(EF_SBF_PATH . $dir)) mkdir(EF_SBF_PATH . $dir, 0775, true);
$tpl = new core_ET();
jqueryui_Ui::enable($tpl);
foreach (glob(dirname(__DIR__) . '/' . $uiVersion . '/images/*') as $image) sbf('jqueryui/' . $uiVersion . '/images/' . basename($image));
if ($argv[5] === 'compact') {
    $compactor = new compactor_Plugin();
    $tpl->files['JS'] = $compactor->compactFiles($tpl->files['JS'], 'jquery/' . $jqueryVersion . '/jquery.min.js, ' . $setup->getCommonJs(), EF_SBF_PATH . '/js');
    $cssCallback = Closure::bind(function ($text, $path) { return $this->changePaths($text, $path); }, $compactor, 'compactor_Plugin');
    $tpl->files['CSS'] = $compactor->compactFiles($tpl->files['CSS'], $setup->getCommonCss(), EF_SBF_PATH . '/css', $cssCallback);
}
$head = '';
foreach ($tpl->files as $kind => $files) foreach ($files as $file) {
    $url = sbf($file);
    $head .= $kind === 'JS' ? '<script src="' . $url . '"></script>' : '<link rel="stylesheet" href="' . $url . '">';
}
copy(__DIR__ . '/browser.js', EF_SBF_PATH . '/browser.js');
$title = 'UI ' . $uiVersion . ' / jQuery ' . $jqueryVersion . ' / PHP ' . PHP_VERSION . ' / ' . $argv[4] . ' / ' . $argv[5];
$html = '<!doctype html><meta charset="utf-8"><title>jQuery UI regression</title>' . $head . '<h1>' . $title . '</h1>';
$html .= '<button id="run">Run regression tests</button><pre id="results">Ready</pre><div id="fixtures"></div>';
$html .= '<script src="browser.js"></script>';
file_put_contents(EF_SBF_PATH . '/index.html', $html);
echo 'PASS defaults, old loaders, compatibility, CDN order; ' . $title . "\n";
