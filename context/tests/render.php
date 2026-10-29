<?php

/**
 * Изолирана браузърна матрица с реалните context/minify/compactor адаптери.
 *
 * @category tests
 * @package context
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc !== 5 && $argc !== 6) exit("Usage: php render.php OUTPUT_DIR JQUERY_VERSION legacy|new plain|compact [narrow]\n");
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
        if (get_called_class() === 'context_Setup') return CONTEXT_VERSION;
        if (get_called_class() === 'jquery_Setup') return $key === 'VERSION' ? $GLOBALS['argv'][2] : '';
        return $GLOBALS['argv'][3] === 'new' ? ($key === 'CSS_VERSION' ? '4.1.1' : 'jshrink-1.8.1') : 'legacy';
    }
}
class core_Packs
{
    /** Включва compactor само за съответния сценарий. */
    public static function isInstalled($pack) { return $pack === 'compactor' && $GLOBALS['argv'][4] === 'compact'; }
}
class core_ET
{
    public $files = array('JS' => array(), 'CSS' => array());
    public $code = '';
    public $html = '';
    /** Запазва шаблона на реалния context plugin. */
    public function __construct($html = '') { $this->html = $html; }
    /** Добавя бутона на адаптера в неговото място. */
    public function prepend($html, $place) { $this->html = str_replace('[#' . $place . '#]', $html . '[#' . $place . '#]', $this->html); }
    /** Събира ресурсите в реда на адаптера. */
    public function push($path, $kind, $once = false) { $this->files[$kind][$path] = $path; }
    /** Събира инициализацията на адаптера. */
    public function appendOnce($code, $place) { $this->code .= $code; }
}
class page_Html { /** Запазва относителния път. */ public static function getFileForAppend($path) { return $path; } }
class ET extends core_ET {}
class ht { /** Минимален HTML бутон за context hook-а. */ public static function createFnBtn($label, $fn, $warning, $attr) { return '<button class="' . $attr['class'] . '">' . $label . '</button>'; } }
class Mode { /** Поддържа тесния изглед на реалния layout hook. */ public static function is($key, $value) { return $key === 'screenMode' && $value === 'narrow' && ($GLOBALS['argv'][5] ?? '') === 'narrow'; } }
/** Запазва етикетите на синтетичната форма. */
function tr($text) { return $text; }
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

foreach (array('context/Setup', 'context/Plugin', 'jquery/Setup', 'jquery/Jquery', 'minify/Setup', 'minify/Js', 'minify/Css', 'minify/Plugin', 'compactor/Plugin') as $class) require dirname(__DIR__, 2) . '/' . $class . '.class.php';
expect(CONTEXT_VERSION === '1.4.0' && JQUERY_VERSION === '1.11.2', 'Changed defaults');
expect(in_array($argv[2], array('1.11.2', '3.7.1', '4.0.0'), true), 'Invalid jQuery');
expect(in_array($argv[3], array('legacy', 'new'), true) && in_array($argv[4], array('plain', 'compact'), true), 'Invalid mode');
expect(!isset($argv[5]) || $argv[5] === 'narrow', 'Invalid layout');
$setup = new context_Setup();
expect($setup->getCommonJs() === 'context/1.4.0/contextMenu.js' && $setup->getCommonCss() === 'context/1.4.0/contextMenu.css', 'Changed resource paths');
$plugin = new context_Plugin();
$tpl = new ET();
jquery_Jquery::enable($tpl);
$body = '';
foreach (array('first', 'second') as $id) {
    $mvc = (object) array('buttons' => array());
    $places = array('ROW0' => '', 'ROW1' => '', 'ROW2' => '', 'HIDDEN' => '');
    for ($i = 1; $i <= 7; $i++) {
        $row = $i <= 2 ? 1 : ($i <= 5 ? 2 : 3);
        $mvc->buttons[] = (object) array('attr' => array('row' => $row));
        $place = $row === 3 ? 'HIDDEN' : 'ROW' . $row;
        $places[$place] .= '<button class="button" onclick="document.getElementById(&quot;contextAction&quot;).textContent=&quot;Избрано ' . $id . ' ' . $i . '&quot;;">Действие ' . $i . '</button>';
    }
    $layout = null;
    $plugin->on_BeforeGetToolbarLayout($mvc, $layout, $id);
    expect(strpos($layout->html, 'twoColsContext') !== false, 'Missing two-column menu');
    $result = null;
    $plugin->on_BeforeAppendSecondRow($mvc, $result, $layout, $id);
    foreach ($places as $place => $html) $layout->html = str_replace('[#' . $place . '#]', $html, $layout->html);
    $body .= $layout->html;
    foreach (array('JS', 'CSS') as $kind) foreach ($layout->files[$kind] as $file) $tpl->push($file, $kind);
    expect(isset($layout->files['JQUERY_RUN_AFTER_AJAX']['prepareContextMenu']), 'Missing AJAX callback');
}
$common = file_get_contents(getFullPath('js/efCommon.js'));
expect(preg_match('/^function prepareContextMenu\(\) \{.*?^\}/ms', $common, $match) === 1, 'Cannot extract real initializer');
$initializer = $match[0];
minify_Plugin::on_BeforeSaveFile(null, null, $initializer, 'context-qa-init.js');
define('EF_SBF_PATH', rtrim($argv[1], '/'));
foreach (array('', '/js', '/css') as $dir) if (!is_dir(EF_SBF_PATH . $dir)) mkdir(EF_SBF_PATH . $dir, 0775, true);
if ($argv[4] === 'compact') {
    $compactor = new compactor_Plugin();
    $tpl->files['JS'] = $compactor->compactFiles($tpl->files['JS'], 'jquery/' . $argv[2] . '/jquery.min.js, ' . $setup->getCommonJs(), EF_SBF_PATH . '/js');
}
$head = '';
foreach ($tpl->files as $kind => $files) foreach ($files as $file) {
    $url = sbf($file);
    $head .= $kind === 'JS' ? '<script src="' . $url . '"></script>' : '<link rel="stylesheet" href="' . $url . '">';
}
$title = 'context 1.4.0 / jQuery ' . $argv[2] . ' / PHP ' . PHP_VERSION . ' / ' . $argv[3] . ' / ' . $argv[4] . ' / ' . ($argv[5] ?? 'wide');
$html = '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Context QA</title>' . $head;
$html .= '<style>body{font:16px sans-serif}.toolbar{margin:20px 0}.more-btn{width:30px;height:24px}pre{white-space:pre-wrap}.narrow #contextQa{width:320px}</style><body class="' . ($argv[5] ?? 'wide') . '"><h1>' . $title . '</h1>';
$html .= '<div id="contextQa">' . $body . '</div><label>Текст <input id="qaInput"></label><button id="runContextTests">Run context tests</button><pre id="contextResults">Ready</pre><output id="contextAction">Няма избрано действие</output>';
$html .= '<script>var qaEfae = {waitPeriodicAjaxCall:0, preventRequest:0}; function getEfae(){return qaEfae;}' . $initializer . '</script><script src="browser.js"></script>';
file_put_contents(EF_SBF_PATH . '/index.html', $html);
copy(__DIR__ . '/browser.js', EF_SBF_PATH . '/browser.js');
echo 'PASS defaults and adapter: ' . $title . "\n";
