<?php
/**
 * Изолирана проверка на достъпа и подаването на личните настройки.
 *
 * @category  bgerp
 * @package   funny
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
define('EF_APP_NAME', 'fixture');
class core_Plugin {}
class core_Users
{
    public static $id = 1;
    public static $power = true;
    public static function getCurrent() { return self::$id; }
}
class Mode
{
    public static $values = array();
    public static function is($key) { return !empty(self::$values[$key]); }
}
class Request
{
    public static $ajax = false;
    public static $route = array('Ctr' => 'Portal', 'Act' => 'Show');
    public static function get($key) { return $key === 'ajax_mode' ? self::$ajax : (self::$route[$key] ?? null); }
}
class core_ProtoSetup
{
    public static $values = array('MODE' => 'auto', 'CHARACTERS' => 'ghost,casper', 'TRIGGER' => 'both', 'FREQUENCY' => 'normal', 'SOUND' => 'yes', 'WEATHER' => 'off', 'STRENGTH' => 'normal');
    public static function get($key) { return self::$values[$key]; }
}
class jquery_Jquery
{
    public static $script = '';
    public static function run($page, $script) { self::$script .= $script; }
}
class core_Packs
{
    public static $installed = array('visualcrossing' => true);
    public static function isInstalled($name) { return !empty(self::$installed[$name]); }
}
class visualcrossing_Forecasts
{
    public static $icon = 'rain';
    public static $calls = 0;
    public static $hourlyIcon = null;
    public static $lastHour = null;
    public static function getForecast($date, $hour = null) {
        self::$calls++;
        if ($hour !== null) self::$lastHour = $hour;
        $icon = $hour === null ? self::$icon : self::$hourlyIcon;
        return $icon ? (object) array('icon' => $icon) : false;
    }
}
class darksky_Forecasts extends visualcrossing_Forecasts {}
function haveRole($role) { return $role === 'powerUser' && core_Users::$power; }
function sbf($path, $unused) { return '/assets/' . $path; }
function tr($text) { return $text; }
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__, 2) . '/core/DateTime.class.php';
class_alias('core_DateTime', 'dt');
require dirname(__DIR__) . '/Plugin.class.php';
function verify($expected, $label)
{
    $files = (object) array('js' => array('existing.js'), 'css' => array('existing.css'));
    $result = null;
    jquery_Jquery::$script = '';
    $plugin = new funny_Plugin();
    $plugin->on_BeforeAppendFiles(new stdClass(), $result, $files);
    if ((count($files->js) === 2) !== $expected || (jquery_Jquery::$script !== '') !== $expected) {
        throw new RuntimeException($label);
    }
    if ($files->js[0] !== 'existing.js') throw new RuntimeException('Existing scripts changed');
    echo "PASS {$label}\n";
}
verify(true, 'authenticated powerUser');
core_Users::$id = 0;
verify(false, 'anonymous');
core_Users::$id = 1;
core_Users::$power = false;
verify(false, 'user without powerUser');
core_Users::$power = true;
foreach (array('printing', 'pdf', 'text', 'readOnly', 'noWrapper') as $mode) {
    Mode::$values = array($mode => true);
    verify(false, $mode);
}
Mode::$values = array();
Request::$ajax = true;
verify(false, 'AJAX');
Request::$ajax = false;
funny_Setup::$values['MODE'] = 'off';
verify(false, 'personal off');
funny_Setup::$values['MODE'] = 'selected';
funny_Setup::$values['FREQUENCY'] = 'off';
verify(false, 'personal frequency off including double click');
funny_Setup::$values['FREQUENCY'] = 'normal';
funny_Setup::$values['SOUND'] = 'no';
verify(true, 'personal selected / silent');
if (strpos(jquery_Jquery::$script, '"sound":"no"') === false || strpos(jquery_Jquery::$script, '"mode":"selected"') === false) {
    throw new RuntimeException('Personal options lost');
}
funny_Setup::$values['SOUND'] = 'scary';
funny_Setup::$values['TRIGGER'] = 'manual';
funny_Setup::$values['FREQUENCY'] = 'frequent';
verify(true, 'personal scary sound');
if (strpos(jquery_Jquery::$script, '"sound":"scary"') === false || strpos(jquery_Jquery::$script, '"easterDates":') === false ||
    strpos(jquery_Jquery::$script, '"frequency":"frequent"') === false || strpos(jquery_Jquery::$script, '"trigger":"manual"') === false) {
    throw new RuntimeException('Sound mode or Easter dates lost');
}
if (FUNNY_MODE !== 'auto' || FUNNY_TRIGGER !== 'both' || FUNNY_FREQUENCY !== 'normal' || FUNNY_SOUND !== 'yes' || FUNNY_WEATHER !== 'on' || FUNNY_STRENGTH !== 'normal' || count(explode(',', FUNNY_CHARACTERS)) !== 17) {
    throw new RuntimeException('Unexpected package defaults');
}
if (date('Y-m-d', dt::getOrthodoxEasterTms(2026)) !== '2026-04-12' || date('Y-m-d', dt::getOrthodoxEasterTms(2027)) !== '2027-05-02') {
    throw new RuntimeException('Unexpected Orthodox Easter dates');
}
echo "13 plugin checks, package defaults and Easter dates passed\n";

/** Weather is available on normal pages; disabled characters retain the shared manual input. */
function verifyWeather($expected, $label, $characters = false)
{
    $files = new stdClass(); $result = null; jquery_Jquery::$script = '';
    (new funny_Plugin())->on_BeforeAppendFiles(new stdClass(), $result, $files);
    if (in_array('funny/js/weather.js', $files->js ?? array(), true) !== $expected ||
        (strpos(jquery_Jquery::$script, 'window.funnyWeather.init(') !== false) !== $expected ||
        in_array('funny/js/funny.js', $files->js ?? array(), true) !== ($characters || $expected)) {
        throw new RuntimeException($label);
    }
    echo "PASS {$label}\n";
}
funny_Setup::$values['MODE'] = 'off';
funny_Setup::$values['FREQUENCY'] = 'off';
verifyWeather(false, 'weather explicitly off');
funny_Setup::$values['WEATHER'] = 'on';
verifyWeather(true, 'personal weather independent of disabled characters');
if (strpos(jquery_Jquery::$script, '"icon":"rain"') === false || strpos(jquery_Jquery::$script, '"mode":"on"') === false) throw new RuntimeException('Forecast/options lost');
funny_Setup::$values['WEATHER'] = 'sound';
Request::$route['Ctr'] = 'bgerp_Portal';
verifyWeather(true, 'full portal class and sound option');
Request::$route['Act'] = 'list';
$calls = visualcrossing_Forecasts::$calls;
verifyWeather(true, 'portal management supports weather');
Request::$route = array('Ctr' => 'crm_Profiles', 'Act' => 'single');
verifyWeather(true, 'other normal pages support weather');
if ($calls >= visualcrossing_Forecasts::$calls || strpos(jquery_Jquery::$script, '"standalone":true') === false) throw new RuntimeException('Standalone weather outside portal missing');
visualcrossing_Forecasts::$hourlyIcon = 'clear-night';
verifyWeather(true, 'hourly forecast wins over the daily forecast');
if (strpos(jquery_Jquery::$script, '"icon":"clear-night"') === false || visualcrossing_Forecasts::$lastHour !== date('G')) throw new RuntimeException('Current hourly forecast not used');
visualcrossing_Forecasts::$hourlyIcon = '../invalid';
verifyWeather(true, 'invalid hourly forecast falls back to daily');
if (strpos(jquery_Jquery::$script, '"icon":"rain"') === false) throw new RuntimeException('Daily fallback missing');
visualcrossing_Forecasts::$hourlyIcon = null;
Request::$route = array('Ctr' => 'Portal', 'Act' => 'Show');
core_Users::$power = false; verifyWeather(false, 'weather requires powerUser'); core_Users::$power = true;
core_Users::$id = 0; verifyWeather(false, 'weather requires authentication'); core_Users::$id = 1;
foreach (array('printing', 'pdf', 'text', 'readOnly', 'noWrapper') as $mode) {
    Mode::$values = array($mode => true); verifyWeather(false, 'weather excludes ' . $mode);
}
Mode::$values = array();
Request::$ajax = true; verifyWeather(false, 'weather excludes AJAX'); Request::$ajax = false;
visualcrossing_Forecasts::$icon = ''; verifyWeather(false, 'missing forecast');
visualcrossing_Forecasts::$icon = '../invalid'; verifyWeather(false, 'invalid icon');
visualcrossing_Forecasts::$icon = 'snow';
core_Packs::$installed = array(); verifyWeather(false, 'no weather package');
core_Packs::$installed = array('darksky' => true); verifyWeather(true, 'Dark Sky forecast supported');
if (strpos(jquery_Jquery::$script, '"icon":"snow"') === false) throw new RuntimeException('Dark Sky daily fallback lost');
funny_Setup::$values['MODE'] = 'auto'; funny_Setup::$values['FREQUENCY'] = 'normal';
verifyWeather(true, 'weather and characters coexist', true);
if (strpos(jquery_Jquery::$script, '"standalone":false') === false) throw new RuntimeException('Two automatic schedulers enabled');
$setup = new funny_Setup();
if (strpos($setup->configDescription['FUNNY_WEATHER'][1], 'customizeBy=powerUser') === false) throw new RuntimeException('Weather personalization roles lost');
echo "20 weather plugin checks passed\n";
funny_Setup::$values['STRENGTH'] = 'gentle';
core_Users::$id = 23;
verifyWeather(true, 'personal intensity reaches weather and heroes', true);
if (substr_count(jquery_Jquery::$script, '"strength":"gentle"') !== 2 || strpos(jquery_Jquery::$script, '"scoreKey":"funny.best.fixture.23"') === false ||
    strpos($setup->configDescription['FUNNY_STRENGTH'][1], 'customizeBy=powerUser') === false) throw new RuntimeException('Personal intensity or isolated record key lost');
