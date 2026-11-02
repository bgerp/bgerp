<?php
/**
 * Проверява ZTM таблото без база, мрежа или хардуер.
 *
 * @category bgerp
 * @package ztm
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 */
if(PHP_SAPI!=='cli')exit;
error_reporting(E_ALL);
set_error_handler(function($level,$message,$file,$line){throw new ErrorException($message,0,$level,$file,$line);});
$checks=0;
/** Проверява условие с описание. */
function check($ok,$message){if(!$ok)throw new RuntimeException($message);$GLOBALS['checks']++;}
/** Запазва съществуващите константи. */
function defIfNot($name,$value){if(!defined($name))define($name,$value);}
/** Зарежда реалния шаблон или изрично подаден HEAD snapshot. */
function getTplFromFile($path){return file_get_contents($GLOBALS['argv'][2]??dirname(__DIR__,2).'/'.$path);}
class core_Mvc {}
class core_ProtoSetup {
 public static $selected;
 /** Изолира избраната настройка. */
 public static function get($name){return self::$selected;}
}
class Mode {
 public static $values=array();
 /** Записва standalone wrapper-а. */
 public static function set($name,$value){self::$values[$name]=$value;}
}
class core_Packs {
 /** Не ползва външна прогноза или API. */
 public static function isInstalled($name){return false;}
}
class dt {
 /** Детерминистична тестова дата. */
 public static function now($full){return '2026-01-01';}
}
class ht {
 /** Не генерира локални SBF адреси. */
 public static function createImg($params){return '<img src="'.$params['path'].'">';}
}
class jquery_Jquery {
 /** Записва инициализацията, без изпълнение. */
 public static function run($tpl,$code){$tpl->scripts[]=$code;}
}
class ET {
 public $html;public $assets=array();public $scripts=array();
 /** Запазва реалния шаблон. */
 public function __construct($html){$this->html=(string)$html;}
 /** Замества плейсхолдерите. */
 public function replace($value,$place){$this->html=str_replace('[#'.$place.'#]',$value,$this->html);}
 /** Запазва ресурсите. */
 public function push($path,$type){$this->assets[$type][]=$path;}
}
require dirname(__DIR__).'/Setup.class.php';
require $argv[1]??dirname(__DIR__).'/Adapter.class.php';
$setup=new ztm_Setup();
check($setup->version==='0.1','Setup version unchanged');
check(ZTM_BOOTSTRAP_VERSION==='4.0.0-beta.2','Original Bootstrap default');
check(ZTM_FORCE_REGISTRY_SYNC===false,'Registry default unchanged');
$baseline=isset($argv[1]);
$hash=null;
foreach($baseline?array('4.0.0-beta.2'):array('4.0.0-beta.2','5.3.8','4.0.0-beta.2','5.3.8') as $version){
 core_ProtoSetup::$selected=$version;$tpl=(new ztm_Adapter())->act_Default();$modern=$version==='5.3.8';
 check(Mode::$values['wrapper']==='page_PureHtml','Standalone wrapper');
 check($tpl->assets['CSS']===array($modern?'cms/bootstrap/5.3.8/css/bootstrap.min.css':'ztm/css/bootstrap.min.css','ztm/css/rangeslider.css','ztm/css/layout.css'),'Complete CSS selection');
 $js=array('ztm/js/jquery-3.1.1.min.js');
 $js=array_merge($js,$modern?array('cms/bootstrap/5.3.8/js/bootstrap.bundle.min.js'):array('ztm/js/popper.min.js','ztm/js/bootstrap.min.js'),array('ztm/js/skycons.js','ztm/js/rangeslider.js','ztm/js/custom.js'));
 check($tpl->assets['JS']===$js,'One jQuery and one Bootstrap, matching Popper');
 foreach($tpl->assets as $files)foreach($files as $file)check(is_file(dirname(__DIR__,2).'/'.$file),'Resource exists');
 check(strpos($tpl->html,'[#')===false,'All placeholders replaced');
 check(substr_count($tpl->html,'data-rangeslider')===4,'Four unchanged sliders');
 check(strpos($tpl->html,'value="24.5" step="0.5"')!==false,'Fractional temperature UI preserved');
 check(strpos($tpl->html,'data-'.($modern?'bs-':'').'parent="#accordion"')!==false,'Accordion parent');
 check(substr_count($tpl->html,'data-'.($modern?'bs-':'').'toggle="collapse"')===3,'Three collapsible settings');
 check(strpos($tpl->html,'data-'.($modern?'bs-':'').'dismiss="modal"')!==false,'Matching dismiss API');
 check(count($tpl->scripts)===3,'Initialization unchanged');
 check($tpl->scripts[0]==='prepareDashboard("")','Missing forecast preserved');
 if(!$modern){
  $html=preg_replace('~<button type="button" class="optionsBtn".*?</button>~','<i class="fa fa-gear optionsBtn" data-toggle="modal" data-target="#toolsModal"></i>',$tpl->html);
  $hash=hash('sha256',serialize(array($html,$tpl->assets,$tpl->scripts)));
 }
}
echo json_encode(array('php'=>PHP_VERSION,'checks'=>$checks,'legacyRenderHash'=>$hash))."\n";
