<?php
/**
 * Проверки на ресурсите и избора на версия без база данни.
 *
 * @category  bgerp
 * @package   hljs
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
$checks = 0;
/** Проверка с описание. */
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); $GLOBALS['checks']++; }
/** Запазва зададения default. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Търси реален ресурс в хранилището. */
function getFullPath($path) { $file = dirname(__DIR__, 2) . '/' . $path; return is_file($file) ? $file : false; }
class core_ProtoSetup {}
class core_Packs
{
    /** Подменя само конфигурационния източник. */
    public static function getConfig($pack) { return (object) array('HLJS_VERSION' => $GLOBALS['version']); }
}
class ET
{
    public $assets = array();
    public $initial = '';
    public $ajax = '';
    /** Запомня заявените ресурси. */
    public function push($value, $place) { $this->assets[$place][] = $value; }
}
class jquery_Jquery
{
    /** Запомня началното извикване. */
    public static function run($tpl, $code, $once) { $tpl->initial = $code; }
    /** Запомня AJAX обработчика. */
    public static function runAfterAjax($tpl, $code) { $tpl->ajax = $code; }
}
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Adapter.class.php';
check(HLJS_VERSION === '7.3', 'Unchanged default');
foreach (array('7.3', '9.15.9', '11.12.0', null) as $version) {
    $expected = $version ?? HLJS_VERSION;
    foreach (array('default', 'googlecode', 'github', 'missing-test-theme') as $style) {
        $tpl = hljs_Adapter::enable($style);
        check($tpl->assets['JS'] === array('hljs/' . $expected . '/highlight.pack.js'), 'Selected version only');
        foreach (array_merge($tpl->assets['CSS'], $tpl->assets['JS']) as $file) check((bool) getFullPath($file), 'Existing resource: ' . $file);
        if ($style === 'missing-test-theme') check($tpl->assets['CSS'][0] === 'hljs/' . $expected . '/styles/default.css', 'Theme fallback');
        check(in_array('hljs/lib/compat.css', $tpl->assets['CSS'], true) === ($expected === '11.12.0'), 'Tab width CSS only for modern API');
        check($tpl->initial === 'runHljs();' && $tpl->ajax === 'runHljs', 'Initial and AJAX registration');
    }
}
echo json_encode(array('php' => PHP_VERSION, 'checks' => $checks)) . "\n";
