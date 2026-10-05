<?php
// Isolated dependencies; no configuration, database, user session or external services.
error_reporting(E_ALL & ~E_DEPRECATED);
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function countR($value) { return is_array($value) ? count($value) : 0; }
function expect($ok, ...$args) { if (!$ok) throw new RuntimeException('Expectation failed'); }
function tr($value) { return isset($GLOBALS['redirectTestTranslator']) ? call_user_func($GLOBALS['redirectTestTranslator'], $value) : (string) $value; }
function toUrl($value, $type = null) {
    if (is_string($value)) return $value;
    $ctr = cls::getClassName($value['Ctr'] ?? $value[0] ?? Request::get('Ctr'));
    $act = $value['Act'] ?? $value[1] ?? 'default';
    $id = $value['id'] ?? $value[2] ?? null;
    unset($value['Ctr'], $value[0], $value['Act'], $value[1], $value['id'], $value[2]);
    if (($value['ret_url'] ?? null) === true) $value['ret_url'] = Mode::get('ret_url') ?? Request::get('ret_url');
    $query = $value ? '?' . http_build_query($value) : '';
    return ($type === 'absolute' ? 'https://example.test' : '') . '/' . $ctr . '/' . $act . ($id === null ? '' : '/' . $id) . $query;
}
function redirect(...$args) { return core_App::redirect(...$args); }
class core_BaseClass { public function invoke($event) { return -1; } }
class core_Plugin extends core_BaseClass {}
class core_Master extends core_BaseClass {
    public static function fetch($query) { return (object) array('state'=>'active', 'type'=>'custom', 'driverClass'=>'TestToolDriver'); }
}
class cls {
    public static $objects = array();
    public static function &get($name) { if (!isset(self::$objects[$name])) self::$objects[$name] = new $name(); return self::$objects[$name]; }
    public static function getClassName($value) { return is_object($value) ? ($value->className ?? get_class($value)) : $value; }
    public static function load($name, $silent = false) { return class_exists($name); }
}
class core_Cls extends cls { public static function shutdown() { if (PHP_SAPI !== 'cli') header('X-Test-Shutdown: complete'); } }
class core_HackDetector { public static function check($value,$level) {} }
class core_Forwards { const CORE_FORWARD_SYSID_LEN = 16; }
class core_Debug { public static function log($msg) {} }
class Debug extends core_Debug {}
class dt { public static function mysql2timestamp() { return time(); } }
require_once dirname(__DIR__) . '/Mode.class.php';
class_alias('core_Mode', 'Mode');
class core_Users {
    public static $id=7;
    public static function getCurrent($part='id',$force=true) { $rec=Mode::get('currentUserRec'); return $rec ? $rec->id : self::$id; }
    public static function sudo($id) { Mode::push('currentUserRec', (object) array('id'=>$id)); return $id; }
    public static function exitSudo() { Mode::pop('currentUserRec'); }
}
class core_Statuses { public static $messages=array(); public static function newStatus(...$args) { self::$messages[]=$args; if(PHP_SAPI!=='cli')header('X-Test-Status: '.$args[1].':'.$args[2].':'.$args[4]); } }
class core_Session {
    public static function pause() {}
    public static function getDecoratePrefix() { return 'redirectTest'; }
    public static function get($key) { return array(); }
}
class str { public static function getRand() { return 'test-hit'; } public static function addHash($s,$len=0,$name='') {return $s;} public static function checkHash($s,$len=0,$name='') {return $s;} }
class arr { public static function make($v,$keys=false) { return (array)$v; } }
class core_Url { public static function change($url,$params) { return $url.(strpos($url,'?')===false?'?':'&').http_build_query($params); } }
require_once dirname(__DIR__) . '/exception/Redirect.class.php';
require_once dirname(__DIR__) . '/App.class.php';
require_once dirname(__DIR__) . '/Request.class.php';
class_alias('core_Request','Request');
require_once dirname(__DIR__) . '/ET.class.php';
require_once dirname(__DIR__) . '/Redirect.class.php';
class_alias('core_Redirect','Redirect');
require_once dirname(__DIR__,2) . '/plg/Current.class.php';
