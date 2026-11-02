<?php
/**
 * Проверява конфигурацията и сериализацията без мрежа или реални XMPP данни.
 *
 * @category bgerp
 * @package conversejs
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function($level,$message,$file,$line){throw new ErrorException($message,0,$level,$file,$line);});
$checks=0;
/** Проверява договореното поведение. */
function check($ok,$message){if(!$ok)throw new RuntimeException($message);$GLOBALS['checks']++;}
/** Запазва дефолтните константи. */
function defIfNot($name,$value){if(!defined($name))define($name,$value);}
/** Детерминистични пътища без конкретна инсталация. */
function sbf($path,$suffix){return '/assets/'.$path;}
class core_Mvc {}
class core_ProtoSetup {
    public static $selected='legacy';
    /** Изолира избраната версия. */
    public static function get($name){return self::$selected;}
}
class page_Html {
    public $assets=array(),$parts=array();
    /** Записва ресурсите за проверка. */
    public function push($file,$place){$this->assets[$place][]=$file;}
    /** Записва местата в шаблона. */
    public function appendOnce($text,$place){$this->parts[$place]=($this->parts[$place]??'').$text;}
}
require dirname(__DIR__).'/Setup.class.php';
require dirname(__DIR__).'/Adapter.class.php';
check(CONVERSEJS_VERSION==='legacy','Global default unchanged');
check(CONVERSEJS_BOSH_SERVICE_URL==='https://conversejs.org/http-bind/','BOSH default unchanged');
check((new conversejs_Setup())->version==='0.1','Setup version unchanged');
foreach(array('legacy','14.0.1') as $version){
    core_ProtoSetup::$selected=$version;
    foreach(array(null,(object)array('xmppUser'=>'test@example.invalid','xmppPass'=>'synthetic-pass'),(object)array('xmppUser'=>"тест'\"</script>@example.invalid",'xmppPass'=>"парола\n'\"\\</script>")) as $rec){
        $url="https://chat.example.invalid/http-bind/?name='\"&lang=bg";
        $tpl=conversejs_Adapter::getChatPage($rec,$url,'Test');
        $module=$version==='14.0.1';$base='https://cdn.conversejs.org/'.($module?'14.0.1/':'').'dist/';
        check($tpl->assets['CSS']===array($base.'converse.min.css'),'Selected CSS');
        check(($tpl->assets['JS']??array())===($module?array():array($base.'converse.min.js')),'Module not passed to classic JS loader');
        $js=$tpl->parts[$module?'HEAD':'SCRIPTS'];
        check((strpos($js,'type="module"')!==false)===$module,'Module script type');
        preg_match('/converse\.initialize\((\{.*?\})\);/s',$js,$match);
        $options=json_decode($match[1]??'',true);
        check(is_array($options),'Valid JSON settings');check($options['bosh_service_url']===$url,'Exact BOSH URL');
        check($options['show_controlbox_by_default']===true,'Controlbox');
        check(($options['assets_path']??null)===($module?$base:null),'Versioned dynamic assets');
        check(($options['discover_connection_methods']??null)===($module?false:null),'Explicit configured BOSH transport');
        if($rec){
            check($options['jid']===$rec->xmppUser,'Exact JID');check($options['password']===$rec->xmppPass,'Exact Unicode password');
            check($options['auto_login']===true && $options['keepalive']===true,'Existing login behavior');
            check($options['view_mode']==='fullscreen' && $options['message_carbons']===true,'Existing chat behavior');
        }else{check(!isset($options['password'])&&!isset($options['auto_login']),'No invented credentials');}
        check(substr_count($js,'</script>')===($module?1:0),'Data cannot close the initialization tag');
    }
}
echo "PASS {$checks} checks on PHP ".PHP_VERSION."\n";
