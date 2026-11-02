<?php
/**
 * Регресии за CMS слайдерите без файлово хранилище и база данни.
 *
 * @category  bgerp
 * @package   slick
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
$checks=0; $plain=false;
/** Проверка с описание. */
function check($ok,$message) { if (!$ok) throw new RuntimeException($message); $GLOBALS['checks']++; }
/** Запазва константа по подразбиране. */
function defIfNot($name,$value) { if (!defined($name)) define($name,$value); }
/** Броене на синтетичните картинки. */
function countR($value) { return is_array($value)?count($value):0; }
class core_BaseClass {}
class core_ProtoSetup
{
    /** Подменя само избраната версия. */
    public static function get($name) { return $GLOBALS['version']; }
}
class Mode
{
    /** Режим plain text. */
    public static function is($key,$value) { return $GLOBALS['plain']; }
}
class keylist
{
    /** Синтетична колекция от локално дефинирани id. */
    public static function toArray($value) { return array_filter(explode('|',$value)); }
}
class ht
{
    private static $count=0;
    /** Уникалност в рамките на заявката. */
    public static function getUniqId($prefix) { return $prefix.'-'.++self::$count; }
}
class fileman
{
    /** Без реални файлови записи. */
    public static function idToFh($id) { return 'test'.$id; }
}
class thumb_Img
{
    public $params;
    /** Запазва исканите размери. */
    public function __construct($params) { $this->params=$params; }
    /** Детерминистично синтетично име. */
    public function getThumbName() { return $this->params[0].'.png'; }
    /** Детерминистичен URL без заявка към мрежата. */
    public function getUrl($mode) { return '/test/'.$this->params[1].'/'.$this->getThumbName(); }
}
class ET
{
    public $html; public $assets=array(); public $code='';
    /** Запазва шаблона. */
    public function __construct($html) { $this->html=$html; }
    /** Добавя синтетичните слайдове. */
    public function append($text,$place) { $this->html=str_replace('[#'.$place.'#]',$text.'[#'.$place.'#]',$this->html); }
    /** Заменя CSS класове. */
    public function replace($text,$place) { $this->html=str_replace('[#'.$place.'#]',$text,$this->html); }
    /** Запазва ресурсите. */
    public function push($value,$place) { $this->assets[$place][]=$value; }
    /** Реалното HTML рендериране се проверява в браузъра. */
    public function removeBlocks() {}
}
class jquery_Jquery
{
    /** Запазва реалната инициализация. */
    public static function run($tpl,$code,$once) { $tpl->code=$code; }
}
require dirname(__DIR__).'/Setup.class.php';
require $argv[1] ?? dirname(__DIR__).'/Driver.class.php';
check(SLICK_VERSION==='1.9','Default preserved');
foreach (array('1.8','1.9','2.0.1') as $version) {
    check(slick_Driver::render((object)array())===null,'No images, no warnings');
    $rec=(object)array('id'=>123,'images'=>'|1|2|3|','arrows'=>'out','dots'=>'out','maxSize'=>400,'autoplay'=>2);
    $tpl=slick_Driver::render($rec); $second=slick_Driver::render($rec);
    preg_match("/id='([^']+)'/",$tpl->html,$firstId);preg_match("/id='([^']+)'/",$second->html,$secondId);
    check($firstId[1]!==$secondId[1],'Repeated CMS record has unique DOM ids');
    check(strpos($tpl->code,".not('.slick-initialized')")!==false,'Repeat init guard');
    check(strpos($tpl->code,"'#".$firstId[1]."'")!==false,'Correct target');
    check(substr_count($tpl->html,'<img ')===3,'All images retained');
    check(strpos($tpl->html,'/test/400/')!==false,'Max size');
    check(strpos($tpl->html,'outerArrows outerDots')!==false,'Outer controls');
    check(strpos($tpl->code,'"autoplaySpeed":2000')!==false && strpos($tpl->code,'"autoplay":true')!==false,'Autoplay units');
    foreach (array_merge($tpl->assets['JS'],$tpl->assets['CSS']) as $file) check(is_file(dirname(__DIR__,2).'/'.$file),'Resource: '.$file);
    check(in_array('slick/lib/bgerp.css',$tpl->assets['CSS'],true)===($version==='2.0.1'),'Custom CSS only for new version');
    $rec->arrows=$rec->dots='no';$rec->autoplay=0;
    $tpl=slick_Driver::render($rec);
    check(strpos($tpl->code,'"dots":false')!==false && strpos($tpl->code,'"arrows":false')!==false,'Disabled controls');
    check(strpos($tpl->code,'"autoplay":false')!==false,'No autoplay');
    $missing=slick_Driver::render((object)array('images'=>'|1|'));
    check($missing instanceof ET && strpos($missing->html,'/test/1200/')!==false,'Optional fields');
    $plain=true;check(slick_Driver::render($rec)==='','No resources in plain text');$plain=false;
}
echo json_encode(array('php'=>PHP_VERSION,'checks'=>$checks))."\n";
