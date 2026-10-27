<?php

/**
 * Проверки на избора, PHP съвместимостта и запазването на CSS/JS семантиката.
 *
 * @category tests
 * @package minify
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});
class core_ProtoSetup
{
    public static $config = array();

    /** Връща тестовата конфигурация или реалния default. */
    public static function get($key)
    {
        return self::$config[$key] ?? constant('MINIFY_' . $key);
    }
}
class core_Plugin {}
class core_exception_Redirect extends Exception {}
class str
{
    /** Извлича разширението за SBF hook-а. */
    public static function getFileExt($path) { return pathinfo($path, PATHINFO_EXTENSION); }
}
/** Дефинира default само веднъж, както framework helper-ът. */
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
/** Резолвира тестовите библиотеки в текущото хранилище. */
function getFullPath($path) { return dirname(__DIR__, 2) . '/' . $path; }
/** Замества null-safe брояча на framework-а. */
function countR($items) { return count((array) $items); }
/** Отбелязва очакваната грешка при невалиден JS. */
function reportException($message, $error) { $GLOBALS['minifyReported'][] = $error; }
/** Прекъсва при нарушено очакване. */
function expect($condition, $message = '') { if (!$condition) throw new RuntimeException($message); }
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Css.class.php';
require dirname(__DIR__) . '/Js.class.php';
require dirname(__DIR__) . '/Plugin.class.php';
$checks = 0;
/** Брои успешно проверените очаквания. */
function verifyMinify($condition, $message)
{
    expect($condition, $message);
    $GLOBALS['checks']++;
}
verifyMinify(MINIFY_CSS_VERSION === 'legacy' && MINIFY_JS_VERSION === 'legacy', 'legacy defaults');
$css = '/*! лиценз */ .пример { color: rgb(255, 0, 0); padding: 0px 0px; content: "Щъркел  & €"; width: calc(100% - 10px); }';
$js = '/*! лиценз */ var име = "Щъркел  & €"; var pattern = /a[\/]/; var n = 1 + ++x;';
$legacyCss = (new minify_Css(false))->make($css);
$legacyJs = (new minify_Js($js))->min();
foreach (array('legacy', 'new', 'legacy', 'new') as $version) {
    core_ProtoSetup::$config = array('CSS_VERSION' => $version === 'new' ? '4.1.1' : 'legacy', 'JS_VERSION' => $version === 'new' ? 'jshrink-1.8.1' : 'legacy');
    $resultCss = minify_Css::process($css);
    $resultJs = minify_Js::process($js);
    verifyMinify(strpos($resultCss, 'Щъркел  & €') !== false, 'CSS Unicode/string whitespace');
    verifyMinify(strpos($resultCss, 'calc(100% - 10px)') !== false, 'calc spaces');
    verifyMinify(strpos($resultJs, 'Щъркел  & €') !== false, 'JS Unicode/string whitespace');
    verifyMinify(strpos($resultJs, '/*! лиценз */') !== false, 'license comment');
    verifyMinify(minify_Js::process('var x = 1; // EOF') === 'var x=1;', 'EOF comment');
    verifyMinify(minify_Css::process('') === '' && minify_Js::process('') === '', 'empty sources');
    $invalid = 'var x = "unterminated';
    $original = $invalid;
    $res = null;
    $GLOBALS['minifyReported'] = array();
    minify_Plugin::on_BeforeSaveFile(null, $res, $invalid, 'invalid.js');
    verifyMinify($invalid === $original && count($GLOBALS['minifyReported']) === 1, 'failure keeps source');
    if ($version === 'legacy') {
        verifyMinify($resultCss === $legacyCss && $resultJs === $legacyJs, 'legacy output unchanged');
    } else {
        verifyMinify(minify_Js::process('const x = `a  b`;') === 'const x=`a  b`;', 'template literal spaces');
        $units = minify_Css::process('a{--gap:0px;width:calc(100% - var(--gap));flex:0 0 0%;animation:foo 0s}');
        verifyMinify(strpos($units, '--gap:0px') !== false && strpos($units, 'flex:0 0 0%') !== false && strpos($units, 'foo 0s') !== false, 'dimensioned zeroes');
    }
}
echo "PASS: {$checks} minify checks\n";
