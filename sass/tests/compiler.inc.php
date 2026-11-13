<?php

/**
 * Обща изолирана среда за проверките на реалните Sass компилатори.
 *
 * @category tests
 * @package sass
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
$mode = $argv[1];
$library = rtrim($argv[2], '/');
$output = rtrim($argv[3], '/');
$checks = $deprecations = 0;

/** Спира при неочакван резултат. */
function expect($value, $message = 'Assertion failed') { if (!$value) throw new RuntimeException($message); $GLOBALS['checks']++; }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function countR($value) { return count((array) $value); }
/** Fallback би скрил несъвместимостта на реалния източник. */
function reportException($error) { throw $error; }
class core_exception_Redirect extends Exception {}
class core_ProtoSetup
{
    public static function get($key) { return $key === 'USE_COMPOSER' ? ($GLOBALS['mode'] === 'phpsass' ? 'no' : 'yes') : $GLOBALS['mode']; }
}
class core_Composer { public static function isInUse() { return $GLOBALS['mode'] !== 'phpsass'; } }
class minify_Setup { public static function get($key) { return 'legacy'; } }
class core_Debug
{
    public static $timers = array();
    public static function startTimer($name) { self::$timers[$name] = true; }
    public static function stopTimer($name) { unset(self::$timers[$name]); }
}
set_error_handler(function ($level, $message, $file, $line) {
    if (!(error_reporting() & $level)) return false;
    if ($GLOBALS['mode'] === '1.0.4' && $level === E_DEPRECATED) {
        $GLOBALS['deprecations']++;
        return true;
    }
    throw new ErrorException($message, 0, $level, $file, $line);
});
expect(in_array($mode, array('phpsass', '1.0.4', '1.13.0', '2.1.0'), true), 'Unknown compiler');
if ($mode !== 'phpsass') {
    require (is_file($library) ? $library : $library . '/scss.inc.php');
    expect(version_compare(ltrim(\ScssPhp\ScssPhp\Version::VERSION, 'v'), $mode, '=='), 'Wrong compiler library');
}
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Converter.class.php';
require dirname(__DIR__, 2) . '/minify/Css.class.php';
set_include_path(dirname(__DIR__) . PATH_SEPARATOR . get_include_path());
