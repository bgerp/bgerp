<?php

/**
 * Изолирани проверки на финализирането на JSON-LD: php jsonld/tests/external.php
 *
 * @category  bgerp
 * @package   jsonld
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });
class core_Plugin {}
class core_exception_Redirect extends Exception {}
function reportException($e) { $GLOBALS['reported'][] = $e; }
class jsonld_Helper
{
    public static $stage, $exception;
    public static function getOrganization() { self::fail('organization'); return array('name' => 'Synthetic company'); }
    public static function getWebSite() { self::fail('website'); return array('name' => 'Synthetic site'); }
    public static function fail($stage) { if (self::$stage === $stage) throw self::$exception; }
}
class jsonld_Aggregator
{
    public static function add($node) {}
    public static function getAll() { jsonld_Helper::fail('graph'); return array(); }
}
class jsonld_Renderer { public static function render($nodes) { return '<script>synthetic</script>'; } }
class OutputTemplate
{
    public $scripts = array();
    public function appendOnce($value, $place) { $this->scripts[] = $value; }
}
require dirname(__DIR__) . '/plg/External.class.php';
$checks = 0;
foreach (array('organization', 'website', 'graph') as $stage) {
    foreach (array(new RuntimeException('Synthetic failure'), new core_exception_Redirect('Redirect')) as $exception) {
        jsonld_Helper::$stage = $stage;
        jsonld_Helper::$exception = $exception;
        $GLOBALS['reported'] = array();
        $tpl = new OutputTemplate();
        $caught = null;
        try { jsonld_plg_External::on_Output($tpl); } catch (core_exception_Redirect $e) { $caught = $e; }
        $redirect = $exception instanceof core_exception_Redirect;
        if ($redirect ? ($caught !== $exception || $GLOBALS['reported'] || $tpl->scripts)
            : ($caught || $GLOBALS['reported'] !== array($exception) || !$tpl->scripts)) {
            throw new RuntimeException('Failed output boundary: ' . $stage);
        }
        $checks++;
    }
}
echo "PASS: {$checks} JSON-LD exception checks\n";
