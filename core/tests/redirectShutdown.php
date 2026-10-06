<?php
// A redirect in one late hook cannot replace a closed response or skip the other hooks.
if (PHP_SAPI !== 'cli') exit('CLI only');
function countR($value) { return count((array) $value); }
class core_Debug { public static function log($s) {} public static function startTimer($s) {} public static function stopTimer($s) {} }
class core_BaseClass {
    public $redirect;
    public static $calls = array();
    public function __construct($redirect) { $this->redirect=$redirect; }
    public function invoke($event) { self::$calls[]=$this->redirect; if($this->redirect)throw new core_exception_Redirect('/late'); }
}
require dirname(__DIR__) . '/exception/Redirect.class.php';
require dirname(__DIR__) . '/Cls.class.php';
core_Cls::$singletons=array('redirecting'=>new core_BaseClass(true),'remaining'=>new core_BaseClass(false));
$log=tempnam(sys_get_temp_dir(),'redirect_shutdown_');$old=ini_set('error_log',$log);
try{
    core_Cls::shutdown();
    if(core_BaseClass::$calls!==array(true,false))throw new RuntimeException('Shutdown stopped or repeated a hook');
    if(strpos(file_get_contents($log),'Redirect during shutdown')===false)throw new RuntimeException('Late redirect was not diagnosed');
}finally{ini_set('error_log',$old);unlink($log);}
echo "OK: 2 shutdown redirect checks.\n";
