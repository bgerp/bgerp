<?php
/**
 * Проверява PHP адаптерите, настройките и ресурсите на jsgauge.
 *
 * @category  bgerp
 * @package   jsgauge
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($level,$message,$file,$line) { throw new ErrorException($message,0,$level,$file,$line); });
$checks=0;
/** Описва неуспешното очакване. */
function check($ok,$message) { if(!$ok) throw new RuntimeException($message); $GLOBALS['checks']++; }
/** Константи без промяна на зададените стойности. */
function defIfNot($name,$value) { if(!defined($name)) define($name,$value); }
/** Реалният framework default helper. */
function setIfNot(&$value,$default) { return core_App::setIfNot($value,$default); }
/** Броене за default helper. */
function countR($value) { return is_array($value) ? count($value) : 0; }
/** Ресурсите съществуват в хранилището. */
function sbf($path,$quote='"') { check(is_file(dirname(__DIR__,2).'/'.$path),'SBF file exists'); return '/static/'.$path; }
class core_ProtoSetup {}
class core_Packs {
    public static $path;
    /** Изолиран избор на версия. */
    public static function getConfig($name) { return (object)array('GAUGE_PATH'=>self::$path); }
}
class str {
    /** Детерминистично id за сравнение на стария и новия адаптер. */
    public static function getRand() { return 'testGauge'; }
}
class ET {
    public $html; public $assets=array(); public $parts=array();
    /** Запазва шаблона. */
    public function __construct($html='') { $this->html=$html; }
    /** Замества всички параметри както основният адаптер. */
    public function placeObject($data) { foreach($data as $key=>$value) $this->html=str_replace('[#'.$key.'#]',(string)$value,$this->html); }
    /** Запазва скриптовете. */
    public function append($value,$place) { $this->parts[$place][]= (string)$value; }
    /** Един екземпляр на споделения helper. */
    public function appendOnce($value,$place) { if(!in_array($value,$this->parts[$place]??array(),true))$this->parts[$place][]=$value; }
    /** Запазва библиотеките. */
    public function push($file,$place) { $this->assets[$place][]=$file; }
    /** Рендира вложен ET. */
    public function __toString() { return $this->html; }
}
require dirname(__DIR__,2).'/core/App.class.php';
require dirname(__DIR__).'/Setup.class.php';
require $argv[1] ?? dirname(__DIR__).'/Gauge.class.php';
$setup=new jsgauge_Setup();
check(GAUGE_PATH==='jsgauge/0.4.1' && $setup->version==='0.1','Defaults');
$snapshots=array();
foreach(array('0.4.1','0.4.6') as $version) {
 core_Packs::$path='jsgauge/'.$version;
 check(strpos($setup->configDescription['GAUGE_PATH'][0],core_Packs::$path)!==false,'Choice exists');
 foreach(array('Temperature'=>50,'Pressure'=>10,'Humidity'=>100) as $kind=>$max) {
  $method='render'.$kind;
  $tpl=jsgauge_Gauge::$method();
  check(strpos($tpl->html,'id="testGauge"')!==false,'Default id');
  check(strpos($tpl->html,'height="175" width="175"')!==false,'Default dimensions');
  $js=implode("\n",$tpl->parts['SCRIPTS']);
  check(strpos($js,'max: '.$max.',')!==false,'Default range');
  check(strpos($js,'testGauge.setValue( 0 )')!==false,'Zero value');
  check($tpl->assets['JS']===array(core_Packs::$path.'/gauge.js'),'Selected JS');
  check(strpos($js,'[#')===false && strpos($tpl->html,'[#')===false,'No placeholders');
  $custom=jsgauge_Gauge::$method(-12.5,'customGauge',array('min'=>-50,'max'=>60,'label'=>'Температура','width'=>320,'height'=>200,'redColor'=>'#123456'));
  $customJs=implode("\n",$custom->parts['SCRIPTS']);
  check(strpos($customJs,'setValue( -12.5 )')!==false,'Negative fractional value');
  check(strpos($customJs,'min: -50,')!==false && strpos($customJs,'max: 60,')!==false,'Custom range');
  check(strpos($customJs,'label: "Температура"')!==false,'Cyrillic label');
  check(strpos($custom->html,'height="200" width="320"')!==false,'Custom dimensions');
  check(strpos($customJs,'"#123456"')!==false,'Custom band');
  $snapshots[$kind]=array($tpl->html,$js,$custom->html,$customJs);
 }
}
echo json_encode(array('php'=>PHP_VERSION,'checks'=>$checks,'renderHash'=>hash('sha256',serialize($snapshots)))) . "\n";
