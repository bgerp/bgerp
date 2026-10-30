<?php
/**
 * Проверява Bootstrap ресурсите и запазения CMS default.
 *
 * @category bgerp
 * @package cms
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($level,$message,$file,$line) { throw new ErrorException($message,0,$level,$file,$line); });
$checks=0;
/** Проверява условие с описание. */
function check($ok,$message) { if(!$ok)throw new RuntimeException($message);$GLOBALS['checks']++; }
/** Не променя вече зададени константи. */
function defIfNot($name,$value) { if(!defined($name))define($name,$value); }
class core_ProtoSetup {
    public static $selected;
    /** Симулира избрана настройка, без достъп до база. */
    public static function get($name) { return self::$selected; }
}
class ET {
    public $assets=array();
    /** Записва ресурсите в реда на добавянето. */
    public function push($file,$type) { $this->assets[$type][]=$file; }
}
require dirname(__DIR__).'/Setup.class.php';
require dirname(__DIR__).'/Bootstrap.class.php';
$setup=new cms_Setup();
check(CMS_BOOTSTRAP_VERSION==='legacy','Legacy remains default');
check($setup->version==='0.1','Package version unchanged');
check(CMS_PAGE_WRAPPER==='cms_page_External','Page default unchanged');
check(CMS_BROWSER_CACHE_EXPIRES===3600,'Cache default unchanged');
foreach(array('legacy','5.3.8','legacy','5.3.8',null,'unsupported') as $version) {
 core_ProtoSetup::$selected=$version;$tpl=new ET();cms_Bootstrap::enable($tpl);
 $modern=$version==='5.3.8';
 check(count($tpl->assets['CSS'])===1 && count($tpl->assets['JS'])===1,'One CSS and JS');
 check($tpl->assets['CSS'][0]===($modern?'cms/bootstrap/5.3.8/css/bootstrap.min.css':'cms/css/bootstrap.css'),'CSS selection');
 check($tpl->assets['JS'][0]===($modern?'cms/bootstrap/5.3.8/js/bootstrap.bundle.min.js':'cms/js/bootstrap.min.js'),'JS selection');
 foreach($tpl->assets as $files)foreach($files as $file)check(is_file(dirname(__DIR__,2).'/'.$file),'File exists');
}
foreach(array('legacy','5.3.8') as $version)check(strpos($setup->configDescription['CMS_BOOTSTRAP_VERSION'][0],$version.'=')!==false,'UI choice');
foreach(array('css/bootstrap.min.css','js/bootstrap.bundle.min.js') as $file) {
 $path=dirname(__DIR__).'/bootstrap/5.3.8/'.$file;
 check(preg_match('/Bootstrap\s+v5\.3\.8/',file_get_contents($path))===1,'Official version header');
 check(is_file($path.'.map'),'Source map available');
 $map=json_decode(file_get_contents($path.'.map'),true);
 check(!empty($map['sourcesContent']),'Self-contained source map');
}
echo json_encode(array('php'=>PHP_VERSION,'checks'=>$checks))."\n";
