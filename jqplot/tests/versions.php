<?php
/**
 * Изолирани проверки на PHP адаптера и ресурсите, без база данни.
 *
 * @category  bgerp
 * @package   jqplot
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
/** Framework default. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Framework debug режим. */
function isDebug() { return $GLOBALS['debug']; }
/** Броене на колекция. */
function countR($data) { return is_array($data) ? count($data) : 0; }
/** Без външна SBF директория. */
function sbf($path) { return $path; }
class core_ProtoSetup {}
class core_Plugin {}
class core_Packs
{
    /** Замества само избора на версия. */
    public static function getConfig($pack) { return (object) array('JQPLOT_VERSION' => $GLOBALS['version']); }
}
class core_ET
{
    public $code = ''; public $assets = array(); public $parts = array();
    /** Запазва заявените ресурси. */
    public function push($value, $place) { $this->assets[$place][] = $value; }
    /** Запазва IE условието. */
    public function appendOnce($value, $place) { $this->assets[$place][] = $value; }
    /** Запазва заменената таблица. */
    public function append($value) { $this->parts[] = $value; }
}
class core_Html
{
    /** Детерминистичен DOM id. */
    public static function setUniqId(&$attrs) { $attrs['id'] = 'test-chart'; }
    /** Изолиран шаблон, без браузърен HTML. */
    public static function createElement($tag, $attrs, $body, $asTemplate) { return new core_ET(); }
}
class jquery_Jquery
{
    /** Запазва реално генерирания Javascript. */
    public static function run($tpl, $code) { $tpl->code = $code; }
}
class jqplot_tests_Host
{
    /** Синтетично заявена графика. */
    public static function getRequestedChartName() { return 'test'; }
    /** Минимална конфигурация на плъгина. */
    public static function getChartConfig($name) { return array('ax' => 'month', 'ay' => 'amount'); }
}
require dirname(__DIR__) . '/Setup.class.php';
require $argv[1] ?? dirname(__DIR__) . '/Chart.class.php';
require $argv[2] ?? dirname(__DIR__) . '/Plugin.class.php';
check(JQPLOT_VERSION === '1.0.8r1250', 'Default unchanged');
foreach (array('1.0.0r1012', '1.0.8r1250', '1.0.9') as $version) {
    foreach (array(false, true) as $debug) {
        $chart = new jqplot_Chart(array('type' => 'bars', 'dir' => 'horizontal', 'log' => false));
        $chart->setTitle('Кирилица');
        $chart->setTickAxisLabel('Категория'); $chart->setValueAxisLabel('Сума');
        $chart->setTickAxisFormat('%s'); $chart->setValueAxisFormat('%.2f');
        $chart->addPoint('2026', 'Януари', 0, '0.00');
        $chart->addPoint('2026', 'Февруари', -12.5, '-12.50');
        $chart->addPoint('2025', 'Януари', 42.25, '42.25');
        $tpl = $chart->getElement();
        check(strpos($tpl->code, '[[[0,') !== false && strpos($tpl->code, '-12.5') !== false, 'Horizontal values/series');
        check(strpos($tpl->code, '"renderer":$.jqplot.BarRenderer') !== false, 'Renderer expression');
        check(strpos($tpl->code, '"barDirection":"horizontal"') !== false, 'Horizontal bars');
        foreach (array_merge($tpl->assets['JS'], $tpl->assets['CSS']) as $file) {
            check(is_file(dirname(__DIR__, 2) . '/' . $file), 'Asset: ' . $file);
            check(strpos($file, 'jqplot/' . $version . '/') === 0, 'Single-version assets');
        }
        $empty = new jqplot_Chart(array('type' => 'bars', 'dir' => 'horizontal', 'log' => false));
        check($empty->getElement() instanceof core_ET, 'Empty horizontal chart does not warn');
        $default = new jqplot_Chart(array());
        check($default->getElement() instanceof core_ET, 'Optional config values');
        $log = new jqplot_Chart(array('type' => 'lines', 'log' => true));
        $log->addPoint(0, 'А', 10);
        check(strpos($log->getElement()->code, '$.jqplot.LogAxisRenderer') !== false, 'Log renderer');
    }
}
$tpl = new core_ET(); $original = $tpl;
$data = (object) array('recs' => array((object) array('month' => 'А', 'amount' => 10)), 'rows' => array((object) array('month' => 'А', 'amount' => '10.00')));
jqplot_Plugin::on_AfterRenderListTable(new jqplot_tests_Host(), $tpl, $data);
check($tpl !== $original && count($tpl->parts) === 2, 'Plugin replaces table template');
echo json_encode(array('php' => PHP_VERSION, 'checks' => $checks)) . "\n";
