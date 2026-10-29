<?php

/**
 * Генерира изолиран браузърен тест с реалния адаптер и избран minify.
 *
 * @category tests
 * @package tinymce
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
        if (get_called_class() === 'tinymce_Setup') return $GLOBALS['argv'][2];
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


/** Връща локален URL в генерирания тест. */
function sbf($path, $quote = '"') { return $quote . $path . $quote; }
/** Съвместимост със стария адаптер при сравнението с HEAD. */
function setIfNot(&$value, $fallback) { if (!isset($value)) $value = $fallback; }
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Plugin.class.php';
require dirname(__DIR__, 2) . '/minify/Setup.class.php';
require dirname(__DIR__, 2) . '/minify/Js.class.php';
require dirname(__DIR__, 2) . '/minify/Css.class.php';
require dirname(__DIR__, 2) . '/minify/Plugin.class.php';
expect(TINYMCE_VERSION === '4.7.13', 'Changed default');
expect(in_array($argv[2], array('4.7.13', '8.10.1'), true), 'Invalid version');
expect(in_array($argv[3], array('raw', 'legacy', 'new'), true), 'Invalid minify mode');
if ($argv[3] !== 'raw') {
    $probe = "function example() { /* comment */ return 1 + 2; }";
    $normal = $probe;
    minify_Plugin::on_BeforeSaveFile(null, null, $normal, 'example/script.js');
    expect($normal === minify_Js::process($probe) && $normal !== $probe, 'Other assets bypass minify');
    $absolute = $probe;
    minify_Plugin::on_BeforeSaveFile(null, null, $absolute, dirname(__DIR__) . '/8.10.1/tinymce.min.js', true);
    expect($absolute === $probe, 'Absolute TinyMCE path is modified');
}
$output = $argv[1];
if (!is_dir($output)) mkdir($output, 0775, true);
$adapter = new tinymce_Plugin();
$tpl = new core_ET();
$invoker = (object) array('params' => array('tinyToolbars' => 'fullscreen print save', 'tinySaveCallback' => 'synthetic-save'));
foreach (array('first', 'second', 'readonly') as $id) {
    $attr = array('id' => $id);
    $adapter->on_AfterRenderInput($invoker, $tpl, $id, '', $attr);
}
$disabled = (object) array('params' => array('tinyEditor' => 'no'));
$empty = new core_ET();
$adapter->on_AfterRenderInput($disabled, $empty, 'disabled', '', $attr);
expect(!$empty->files['JS'] && !$empty->run, 'tinyEditor=no loads editor');

// Сравнява стария init код с оригиналния адаптер, подаден от git show HEAD.
$baseline = getenv('TINYMCE_BASELINE');
if ($argv[2] === '4.7.13' && $baseline) {
    $source = file_get_contents($baseline);
    eval(substr(str_replace('class tinymce_Plugin ', 'class tinymce_Baseline ', $source), 5));
    $old = new tinymce_Baseline();
    $oldTpl = new core_ET();
    foreach (array('first', 'second', 'readonly') as $id) {
        $attr = array('id' => $id);
        $old->on_AfterRenderInput($invoker, $oldTpl, $id, '', $attr);
    }
    expect($oldTpl == $tpl, 'Legacy renderer changed');
    echo "PASS exact legacy init comparison\n";
}
$paths = array('tinymce/' . $argv[2]);
if ($argv[2] === '8.10.1') $paths = array_merge($paths, array('tinymce/codemirror-5.65.21', 'tinymce/js'));
foreach ($paths as $path) {
    $base = dirname(__DIR__, 2) . '/' . $path;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $relative = $path . '/' . substr($file->getPathname(), strlen($base) + 1);
        $dest = $output . '/' . $relative;
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
        $content = file_get_contents($file->getPathname());
        if ($argv[3] !== 'raw' && in_array(pathinfo($relative, PATHINFO_EXTENSION), array('js', 'css'), true)) {
            $original = $content;
            minify_Plugin::on_BeforeSaveFile(null, null, $content, $relative);
            if ($argv[2] === '8.10.1' && $path === 'tinymce/8.10.1') {
                expect($content === $original, 'Official TinyMCE build is modified: ' . $relative);
            }
        }
        file_put_contents($dest, $content);
    }
}
$tags = '';
foreach ($tpl->files as $kind => $files) {
    foreach ($files as $file) $tags .= $kind === 'CSS' ? '<link rel="stylesheet" href="' . $file . '">' : '<script src="' . $file . '"></script>';
}
copy(__DIR__ . '/browser.js', $output . '/browser.js');
$html = '<!doctype html><meta charset="utf-8"><title>TinyMCE regression</title>' . $tags;
$html .= '<h1>TinyMCE ' . $argv[2] . ' / PHP ' . PHP_VERSION . ' / ' . $argv[3] . '</h1><button id="test">Run regression tests</button><pre id="results">Ready</pre>';
$html .= '<form><textarea id="first"></textarea><textarea id="second">Второ независимо поле</textarea><textarea id="readonly" readonly>Само за четене</textarea></form>';
$html .= '<script>' . $tpl->run . '</script><script src="browser.js"></script>';
file_put_contents($output . '/index.html', $html);
echo 'PASS PHP ' . PHP_VERSION . ': default, adapter, three fields, opt-out, ' . $argv[2] . ', ' . $argv[3] . "\n";
