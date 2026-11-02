<?php
/**
 * Регресия на PHP плъгина и избора на keyboard ресурси.
 *
 * @category  bgerp
 * @package   keyboard
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
$checks = 0;
/** Прекъсва при непокрито очакване. */
function check($ok, $message) { if (!$ok) throw new RuntimeException($message); $GLOBALS['checks']++; }
/** Дефинира непроменения default. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Без превод в изолирания тест. */
function tr($value) { return $value; }
/** Локален път без runtime SBF. */
function sbf($path, $quote = '"') { return '/static/' . $path; }
class core_ProtoSetup {}
class core_Plugin {}
class type_Varchar {}
class type_Richtext extends type_Varchar {}
class type_Enum {}
class core_Packs
{
    public static $conf;
    /** Само конфигурацията е заместена. */
    public static function getConfig($pack) { return self::$conf; }
}
class Mode
{
    public static $narrow = false;
    /** Двата режима на плъгина. */
    public static function is($name, $value) { return self::$narrow; }
}
class cls
{
    /** Наследяването е реално. */
    public static function isSubclass($object, $class) { return $object instanceof $class; }
}
class ET
{
    public $assets = array(); public $parts = array();
    /** Запазва поръчаните ресурси. */
    public function push($file, $place) { $this->assets[$place][] = $file; }
    /** Запазва лентата на редактора. */
    public function append($value, $place) { $this->parts[$place] = $value; }
}
class ht
{
    /** Минимално HTML рендериране за проверка на атрибутите. */
    public static function createElement($tag, $attrs, $content = '') {
        $html = '<' . $tag;
        foreach ($attrs as $key => $value) $html .= ' ' . $key . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
        return $html . '>' . $content . '</' . $tag . '>';
    }
}
require dirname(__DIR__) . '/Setup.class.php';
require $argv[1] ?? dirname(__DIR__) . '/Plugin.class.php';
$setup = new keyboard_Setup(); $plugin = new keyboard_Plugin();
check(VKI_version === '1.28' && $setup->version === '0.1', 'Defaults preserved');
foreach (array('1.28','1.54') as $version) {
    core_Packs::$conf = (object)array('VKI_version'=>$version);
    check(strpos($setup->configDescription['VKI_version'][0],$version) !== false,'Selectable version');
    foreach (array(new type_Varchar(), new type_Richtext()) as $invoker) {
        $ret = null; $attr = array('id'=>'synthetic-field','ondblclick'=>'original()'); $tpl = new ET();
        $plugin->on_BeforeRenderInput($invoker,$ret,'field','',$attr);
        $plugin->on_BeforeRenderInput($invoker,$ret,'field','',$attr);
        check(substr_count($attr['ondblclick'],'showKeyboard(this,') === 1, 'No duplicate handler');
        check(strpos($attr['ondblclick'],'original()') === 0, 'Existing handler preserved');
        $plugin->on_AfterRenderInput($invoker,$tpl,'field','',$attr);
        check($tpl->assets['JS'][0] === $setup->getCommonJs(),'JS matches compactor');
        check($tpl->assets['CSS'][0] === $setup->getCommonCss(),'CSS matches compactor');
        foreach(array_merge($tpl->assets['JS'],$tpl->assets['CSS']) as $file) check(is_file(dirname(__DIR__,2).'/'.$file),'Resource exists');
        check(isset($tpl->parts['TBL_GROUP3']) === ($invoker instanceof type_Richtext),'Actual richtext toolbar');
        if ($invoker instanceof type_Richtext) check(strpos($tpl->parts['TBL_GROUP3'],'event.stopPropagation()') !== false,'Toolbar remains open');
    }
    $invoker = new type_Richtext(); $attr = array(); $tpl = new ET();
    $plugin->on_AfterRenderInput($invoker,$tpl,'field','',$attr);
    check(!$tpl->parts,'Missing id does not generate broken button');
    foreach(array(new type_Enum(),new type_Varchar()) as $index=>$invoker) {
        Mode::$narrow = (bool)$index; $attr = array(); $tpl = new ET();
        $plugin->on_BeforeRenderInput($invoker,$ret,'field','',$attr);
        $plugin->on_AfterRenderInput($invoker,$tpl,'field','',$attr);
        check(!$attr && !$tpl->assets,'Enum/narrow unchanged');
    }
    Mode::$narrow = false;
}
echo json_encode(array('php'=>PHP_VERSION,'checks'=>$checks)) . "\n";
