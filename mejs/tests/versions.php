<?php
/**
 * Проверява избора на версия и независимите настройки на плейърите.
 *
 * @category  bgerp
 * @package   mejs
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
/** Константа по подразбиране. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Броене за реалния core_App::setIfNot. */
function countR($value) { return is_array($value) ? count($value) : 0; }
/** Използва реалното допълване на параметри от framework. */
function setIfNot(&$value, $default) { return core_App::setIfNot($value, $default); }
/** Условия на адаптера. */
function expect($value) { if (!$value) throw new RuntimeException('Invalid media type'); }
/** Проверява реалния файл без runtime SBF. */
function sbf($path, $quote = '"') { check(is_file(dirname(__DIR__, 2) . '/' . $path), 'Sprite exists'); return '/static/' . $path; }
class core_ProtoSetup
{
    /** Само изборът на версия е изолиран. */
    public static function get($name) { return $GLOBALS['version']; }
}
class ht
{
    private static $count = 0;
    /** Детерминистични уникални идентификатори. */
    public static function getUniqId($prefix) { return $prefix . ++self::$count; }
}
class fileman_Download
{
    /** Запазва URL без файлови записи. */
    public static function getDownloadUrl($source, $life, $type) { return $source; }
}
class ET
{
    public $assets = array(); public $code = ''; public $parts = array(); public $html;
    /** Запазва шаблона за сравнение. */
    public function __construct($html = '') { $this->html = $html; }
    /** Запазва ресурсите. */
    public function push($value, $place) { $this->assets[$place][] = $value; }
    /** Запазва източниците. */
    public function append($value, $place) { $this->parts[$place][] = $value; }
    /** Запазва параметрите на шаблона. */
    public function replace($value, $place) { $this->parts[$place] = $value; }
    /** HTML рендерирането се проверява в браузър. */
    public function removeBlocks() {}
}
class jquery_Jquery
{
    /** Запазва инициализацията. */
    public static function run($tpl, $code) { $tpl->code = $code; }
}
require dirname(__DIR__, 2) . '/core/App.class.php';
require dirname(__DIR__) . '/Setup.class.php';
require $argv[1] ?? dirname(__DIR__) . '/Adapter.class.php';
check(MEJS_VERSION === '2.11.0.0', 'Default preserved');
foreach (array('2.11.0.0','2.20.0','4.2.9','7.1.0') as $version) {
    $params = array('startVolume'=>0,'loop'=>true,'pauseOtherPlayers'=>false,'features'=>array('playpause'));
    $actual = json_decode(mejs_Adapter::prepareParams($params), true);
    check($actual['startVolume'] === 0 && $actual['loop'] === true && $actual['pauseOtherPlayers'] === false, 'Explicit options preserved');
    check(count($actual['features']) === 8 && $actual['features'][1] === 'loop', 'Legacy feature merge preserved');
    $default = json_decode(mejs_Adapter::prepareParams(), true);
    check($default['loop'] === false && $default['pauseOtherPlayers'] === true && $default['startVolume'] === 1, 'Parameter defaults');
    foreach (array('audio','video') as $type) {
        $tpl = mejs_Adapter::create(array('https://example.com/test.mp3','https://example.com/test.webm'),$type,$params,'url');
        check(count($tpl->parts['SOURCE']) === 2 && $tpl->parts['TYPE'] === $type, 'Alternative sources and type');
        preg_match('/id=\'(mejs[0-9]+)\'/', $tpl->html, $match);
        check(strpos($tpl->code,'"#' . $match[1] . '"') !== false, 'Targets its own media element');
        check(strpos($tpl->code,'"loop":true') !== false && strpos($tpl->code,'"startVolume":0') !== false,'Options reach JS');
        check((strpos($tpl->code,'iconSprite') !== false) === ($version === '7.1.0'), 'Modern sprite only');
        foreach (array_merge($tpl->assets['JS'],$tpl->assets['CSS']) as $file) {
            check(is_file(dirname(__DIR__,2) . '/' . $file), 'Resource exists');
            check(strpos($file,'mejs/' . $version . '/') === 0,'No mixed versions');
        }
    }
    $tpl = new ET(); mejs_Adapter::enableMeJs($tpl,'audio',array('iconSprite'=>'custom.svg'));
    check(strpos($tpl->code,'jQuery("audio")') === 0,'Legacy public API selector');
    check(strpos($tpl->code,'custom.svg') !== false,'Explicit sprite preserved');
}
echo json_encode(array('php'=>PHP_VERSION,'checks'=>$checks)) . "\n";
