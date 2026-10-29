<?php

/**
 * Изолирана браузърна матрица с реалните autosize/minify/compactor адаптери.
 *
 * @category tests
 * @package autosize
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc !== 6) exit("Usage: php render.php OUTPUT_DIR AUTOSIZE_VERSION JQUERY_VERSION legacy|new plain|compact\n");
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });

/** Проверява изолирано условие. */
function expect($value, $message = 'Assertion failed') { if (!$value) throw new RuntimeException($message); }
/** Дефинира реалните defaults без framework bootstrap. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Намира файл в текущото хранилище. */
function getFullPath($path) { $p = dirname(__DIR__, 2) . '/' . $path; return is_file($p) ? $p : false; }
/** Замества framework брояча. */
function countR($items) { return count((array) $items); }
/** Не допуска неусетно прескачане на минификацията. */
function reportException($message, $error) { throw new RuntimeException($message, 0, $error); }
class core_Plugin {}
class core_exception_Redirect extends Exception {}
class core_ProtoSetup
{
    /** Връща подадената тестова конфигурация. */
    public static function get($key)
    {
        if (get_called_class() === 'jquery_Setup') return $key === 'VERSION' ? $GLOBALS['argv'][3] : '';
        return $GLOBALS['argv'][4] === 'new' ? ($key === 'CSS_VERSION' ? '4.1.1' : 'jshrink-1.8.1') : 'legacy';
    }
}
class core_Packs
{
    /** Включва compactor само за съответния сценарий. */
    public static function isInstalled($pack) { return $pack === 'compactor' && $GLOBALS['argv'][5] === 'compact'; }
    /** Поддържа формата на конфигурационния обект. */
    public static function getConfig($pack) { return (object) array('AUTOSIZE_VERSION' => $GLOBALS['autosizeVersion']); }
}
class core_ET
{
    public $files = array('JS' => array(), 'CSS' => array());
    public $code = '';
    /** Събира ресурсите в реда на адаптера. */
    public function push($path, $kind, $once = false) { $this->files[$kind][$path] = $path; }
    /** Събира инициализацията на адаптера. */
    public function appendOnce($code, $place) { $this->code .= $code; }
}
class page_Html { /** Запазва относителния път. */ public static function getFileForAppend($path) { return $path; } }
class ht { /** Тестов стабилен идентификатор. */ public static function setUniqId(&$attr) { $attr['id'] = $attr['id'] ?? 'text'; } }
class arr
{
    /** Преобразува списъка на compactor. */
    public static function make($items, $assoc = false) { $items = is_array($items) ? $items : array_map('trim', explode(',', $items)); return $assoc ? array_combine($items, $items) : $items; }
}
class str
{
    /** Разширение за minify. */ public static function getFileExt($path) { return pathinfo($path, PATHINFO_EXTENSION); }
    /** Премахва SBF префикса. */ public static function crop($text, $prefix) { return substr($text, strlen($prefix)); }
}
class core_Sbf { /** Път към генерирания ресурс. */ public static function getSbfFilePath($path) { return EF_SBF_PATH . '/' . $path; } }
class dt { /** Време за кеша на compactor. */ public static function mysql2timestamp() { return time(); } }
class log_System { /** Липсващ ресурс проваля теста. */ public static function add($class, $message, $id, $type) { throw new RuntimeException($message); } }

/** Изолира SBF файловете и изпълнява реалния minify hook. */
function sbf($path, $quote = '', $absolute = false)
{
    static $copied = array();
    $dest = EF_SBF_PATH . '/' . $path;
    if (!isset($copied[$path]) && ($source = getFullPath($path))) {
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
        $content = file_get_contents($source);
        minify_Plugin::on_BeforeSaveFile(null, null, $content, $path);
        file_put_contents($dest, $content);
        $copied[$path] = true;
    }
    expect(is_file($dest), 'Missing resource: ' . $path);
    return $quote . '/' . basename(EF_SBF_PATH) . '/' . $path . $quote;
}

foreach (array('autosize/Setup', 'autosize/Plugin', 'jquery/Setup', 'jquery/Jquery', 'minify/Setup', 'minify/Js', 'minify/Css', 'minify/Plugin', 'compactor/Plugin') as $class) require dirname(__DIR__, 2) . '/' . $class . '.class.php';
expect(AUTOSIZE_VERSION === 'v1.18.4' && JQUERY_VERSION === '1.11.2', 'Changed defaults');
expect(in_array($argv[2], array('v1.18.4', 'v1.18.9', 'v6.0.1'), true), 'Invalid Autosize');
expect(in_array($argv[3], array('1.11.2', '3.7.1', '4.0.0'), true), 'Invalid jQuery');
expect(in_array($argv[4], array('legacy', 'new'), true) && in_array($argv[5], array('plain', 'compact'), true), 'Invalid mode');
$setup = new autosize_Setup();
foreach (array('v1.18.4', 'v1.18.9') as $old) {
    $autosizeVersion = $old;
    expect($setup->getCommonJs() === 'autosize/' . $old . '/jquery.autosize.min.js', 'Changed old compactor list');
}
$autosizeVersion = $argv[2];
$plugin = new autosize_Plugin();
$invoker = new stdClass();
$tpl = new core_ET();
jquery_Jquery::enable($tpl);
$attr = array('id' => 'text', 'class' => 'existing');
$plugin->on_BeforeRenderInput($invoker, $tpl, 'text', '', $attr);
expect($attr['class'] === 'existing autosize', 'Existing class lost');
$emptyAttr = array();
$plugin->on_BeforeRenderInput($invoker, $tpl, 'text', '', $emptyAttr);
expect(trim($emptyAttr['class']) === 'autosize', 'Missing class attribute');
$plugin->on_AfterRenderInput($invoker, $tpl, 'text', '', $attr);
expect(strpos($tpl->code, "$('.autosize:visible').autosize({maxHeight:$(window).height() - 150});") !== false, 'Initialization changed');
define('EF_SBF_PATH', rtrim($argv[1], '/'));
foreach (array('', '/js', '/css') as $dir) if (!is_dir(EF_SBF_PATH . $dir)) mkdir(EF_SBF_PATH . $dir, 0775, true);
if ($argv[5] === 'compact') {
    $compactor = new compactor_Plugin();
    $tpl->files['JS'] = $compactor->compactFiles($tpl->files['JS'], 'jquery/' . $argv[3] . '/jquery.min.js, ' . $setup->getCommonJs(), EF_SBF_PATH . '/js');
}
$head = '';
foreach ($tpl->files as $kind => $files) foreach ($files as $file) {
    $url = sbf($file);
    $head .= $kind === 'JS' ? '<script src="' . $url . '"></script>' : '<link rel="stylesheet" href="' . $url . '">';
}
$title = $autosizeVersion . ' / jQuery ' . $argv[3] . ' / PHP ' . PHP_VERSION . ' / ' . $argv[4] . ' / ' . $argv[5];
$html = '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Autosize QA</title>' . $head;
$html .= '<style>textarea{box-sizing:border-box;width:340px;line-height:20px;font:16px/20px sans-serif;padding:5px;border:1px solid #777;min-height:52px}body{font:16px sans-serif}</style>';
$html .= '<h1>' . $title . '</h1><label>Текст <textarea id="text" class="autosize" rows="2"></textarea></label><button id="run">Run regression tests</button><pre id="results">Ready</pre><div id="fixtures"></div><script>' . $tpl->code . '</script><script src="browser.js"></script>';
file_put_contents(EF_SBF_PATH . '/index.html', $html);
copy(__DIR__ . '/browser.js', EF_SBF_PATH . '/browser.js');
echo 'PASS defaults and adapter: ' . $title . "\n";
