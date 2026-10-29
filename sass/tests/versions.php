<?php

/**
 * Изолирани проверки на Sass адаптера, CSS кеша и старите настройки.
 *
 * @category tests
 * @package sass
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 4 || $argc > 5) exit("Usage: php versions.php phpsass|1.0.4|1.13.0|2.1.0 LIBRARY_DIR_OR_AUTOLOAD OUTPUT_DIR [BASELINE_CONVERTER]\n");
error_reporting(E_ALL);
$mode = $argv[1];
$library = rtrim($argv[2], '/');
$output = rtrim($argv[3], '/');
$reports = $warnings = array();
$checks = 0;

/** Спира теста при неочакван резултат. */
function expect($value, $message = 'Assertion failed') { if (!$value) throw new RuntimeException($message); $GLOBALS['checks']++; }
/** Дефинира реалните defaults. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Запазва очакваните съобщения от fallback компилатора. */
function reportException($error) { $GLOBALS['reports'][] = $error->getMessage(); }
/** Намира само изолираните тестови източници. */
function getFullPath($path) { $file = $GLOBALS['output'] . '/sources/' . $path; return is_file($file) ? $file : false; }
class core_exception_Redirect extends Exception {}
class core_Plugin {}
class core_ProtoSetup
{
    /** Връща настройките за текущия процес, без база данни. */
    public static function get($key) { return $GLOBALS['config'][$key]; }
}
class core_Debug
{
    public static $timers = array();
    /** Отбелязва отворения таймер. */ public static function startTimer($name) { self::$timers[$name] = true; }
    /** Проверява затварянето дори при грешка. */ public static function stopTimer($name) { unset(self::$timers[$name]); }
}
class core_Composer
{
    /** Не инсталира зависимости по време на теста. */ public static function isInUse() { return $GLOBALS['mode'] !== 'phpsass'; }
    /** Проверява вече заредената библиотека за setup диагностиката. */
    public static function isInstalled($pack, $version, $exact) { return class_exists('ScssPhp\\ScssPhp\\Version') && version_compare(ltrim(\ScssPhp\ScssPhp\Version::VERSION, 'v'), $version, '=='); }
}
class core_Os
{
    /** Използва същата промяна на източник за детерминиран тест на кеша. */
    public static function getTimeOfLastModifiedFile($dir, $exclude, $pattern) { return $GLOBALS['sourceTime']; }
}
class core_Sbf
{
    public static $writes = 0;
    /** Форматът е този на core_Sbf::getSbfPathByTime(). */
    public static function getSbfPathByTime($path, $time) { return $GLOBALS['output'] . '/sbf/' . substr($path, 0, -4) . '_' . date('mdHis', $time) . '.css'; }
    /** Записва само в тестовата директория. */
    public static function saveFile($css, $path, $full) { self::$writes++; return file_put_contents($path, $css) !== false; }
}
set_error_handler(function ($level, $message, $file, $line) {
    if (!(error_reporting() & $level)) return false;
    // Стара непроменена зависимост: отчитаме PHP 8.2 deprecations отделно.
    if ($GLOBALS['mode'] === '1.0.4' && $level === E_DEPRECATED) {
        $GLOBALS['warnings'][$message] = ($GLOBALS['warnings'][$message] ?? 0) + 1;
        return true;
    }
    throw new ErrorException($message, 0, $level, $file, $line);
});
expect(in_array($mode, array('phpsass', '1.0.4', '1.13.0', '2.1.0'), true), 'Invalid compiler');
if ($mode !== 'phpsass') require (is_file($library) ? $library : $library . '/scss.inc.php');
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Converter.class.php';
require dirname(__DIR__) . '/Plugin.class.php';
$config = array('USE_COMPOSER' => $mode === 'phpsass' ? 'no' : 'yes', 'VERSION' => $mode === 'phpsass' ? '1.0.4' : $mode);
expect(SASS_USE_COMPOSER === 'no' && SASS_VERSION === '1.0.4', 'Changed defaults');
expect((new sass_Setup())->version === '0.1', 'Changed setup version');
expect((new sass_Setup())->checkConfig() === null, 'Unexpected setup diagnostic');
foreach (array('', '/sources', '/sbf') as $dir) if (!is_dir($output . $dir)) mkdir($output . $dir, 0775, true);
foreach (glob(__DIR__ . '/fixtures/*') as $source) copy($source, $output . '/sources/' . basename($source));
$invoice = $output . '/sources/invoice.scss';
$css = sass_Converter::convert($invoice, true);
expect(strpos($css, 'Сума 123,45 лв.') !== false, 'Cyrillic or amount lost');
expect(strpos($css, '8px') !== false && strpos($css, '75%') !== false, 'Mixin, imports or loop failed');
expect(strpos($css, 'display: none') !== false, 'Hidden element changed');
expect(strpos($css, 'marker.svg') !== false, 'Relative CSS URL changed');
if (in_array($mode, array('1.13.0', '2.1.0'), true)) expect(count($reports) === 0, 'New compiler unexpectedly fell back');
file_put_contents($output . '/invoice.css', $css);
copy(__DIR__ . '/fixtures/marker.svg', $output . '/marker.svg');
$body = '<div class="sass-qa"><div class="logo"></div><h1>Пробна фактура</h1><table><tr><th>Артикул</th><th>Сума</th></tr><tr><td>Кирилица</td><td class="sum">123,45 лв.</td></tr></table><div class="label"></div><div class="hidden">Скрит текст</div></div>';
file_put_contents($output . '/index.html', '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sass QA ' . $mode . '</title><link rel="stylesheet" href="invoice.css">' . $body);

// Старите компилатори се сравняват с адаптера преди промяната.
if (isset($argv[4])) {
    define('SASS_BASELINE_USE_COMPOSER', $config['USE_COMPOSER']);
    $baseline = file_get_contents($argv[4]);
    $baseline = str_replace(array('class sass_Converter', 'SASS_USE_COMPOSER'), array('class sass_BaselineConverter', 'SASS_BASELINE_USE_COMPOSER'), $baseline);
    $baselineFile = $output . '/baseline.php';
    file_put_contents($baselineFile, $baseline);
    require $baselineFile;
    // Старият require е относителен спрямо оригиналния sass пакет.
    set_include_path(dirname(__DIR__) . PATH_SEPARATOR . get_include_path());
    expect(sass_BaselineConverter::convert($invoice, true) === $css, 'Legacy output changed');
}
$simple = $output . '/sources/simple.scss';
file_put_contents($simple, '$color: #048; .simple {color: $color; padding: 4px * 2;}');
$reportCount = count($reports);
$simpleCss = sass_Converter::convert($simple, true);
expect(strpos($simpleCss, '8px') !== false && count($reports) === $reportCount, 'Simple SCSS fell back');
if (isset($argv[4])) expect(sass_BaselineConverter::convert($simple, true) === $simpleCss, 'Legacy compiler output changed');
if ($mode !== '1.13.0') {
    $savedConfig = $config;
    $config = array('USE_COMPOSER' => 'yes', 'VERSION' => '1.13.0');
    expect((new sass_Setup())->checkConfig() !== null, 'Missing version not diagnosed');
    $failed = false;
    try { sass_Converter::convert($simple, true); } catch (RuntimeException $e) { $failed = true; }
    expect($failed, 'Missing selected version silently substituted');
    $config = $savedConfig;
}
$large = $output . '/sources/large.scss';
$source = '';
for ($i = 0; $i < 1000; $i++) $source .= '.row-' . $i . ' {width: ' . ($i + 1) . 'px; color: #048;}' . "\n";
file_put_contents($large, $source);
$largeCss = sass_Converter::convert($large, true);
expect(strpos($largeCss, '.row-999') !== false && strpos($largeCss, '1000px') !== false, 'Large stylesheet incomplete');
file_put_contents($output . '/large.css', $largeCss);
$invalid = $output . '/sources/invalid.scss';
file_put_contents($invalid, '.broken { @include no_such_mixin; }');
$failed = false;
try { sass_Converter::convert($invalid, true); } catch (Throwable $e) { $failed = true; }
expect($failed, 'Invalid SCSS accepted');
// PHPSass таймерът е извън променения Composer път; не го проверяваме тук.
foreach (core_Debug::$timers as $timer => $unused) expect(strpos($timer, 'sassConvert:') !== 0, 'Composer timer leaked');

$sourceTime = filemtime($invoice);
$oldCss = $output . '/sources/invoice.css';
file_put_contents($oldCss, '/* retained legacy CSS */');
touch($oldCss, $sourceTime + 10);
$plugin = new sass_Plugin();
$path = 'invoice.css'; $result = null;
$plugin->on_BeforeGetSbfFilePath(null, $result, $path);
expect(file_get_contents($oldCss) === '/* retained legacy CSS */', 'Existing CSS overwritten');
if (in_array($mode, array('1.13.0', '2.1.0'), true)) {
    expect(strpos($result, '_scssphp' . str_replace('.', '_', $mode) . '.css') !== false, 'Missing version-specific SBF path');
    expect(file_get_contents($result) === $css, 'SBF did not use new compiler');
    $firstPath = $result; $writes = core_Sbf::$writes;
    $plugin->on_BeforeGetSbfFilePath(null, $result, $path);
    expect($result === $firstPath && core_Sbf::$writes === $writes, 'Cache not reused');
    $config['USE_COMPOSER'] = 'no'; $result = null;
    $plugin->on_BeforeGetSbfFilePath(null, $result, $path);
    expect($result === null, 'Legacy compiler reused new SBF');
    $config['USE_COMPOSER'] = 'yes';
    $plugin->on_BeforeGetSbfFilePath(null, $result, $path);
    expect($result === $firstPath, 'Switching back lost the selected cache');
    $sourceTime += 20;
    $plugin->on_BeforeGetSbfFilePath(null, $result, $path);
    expect($result !== $firstPath && core_Sbf::$writes === $writes + 1, 'Changed SCSS did not invalidate cache');
}
$path = 'missing.css'; $result = null;
$plugin->on_BeforeGetSbfFilePath(null, $result, $path);
expect($result === null, 'Missing SCSS intercepted ordinary CSS');
echo json_encode(array('mode' => $mode, 'php' => PHP_VERSION, 'checks' => $checks, 'cssSha256' => hash('sha256', $css), 'legacyDeprecations' => array_sum($warnings), 'fallbackReports' => count($reports)), JSON_UNESCAPED_UNICODE) . "\n";

// Проверка на входния hook без Composer инсталация или SQL.
$form = new class {
    public $rec;
    public $errors = array();
    /** Формата е подадена за запис. */ public function isSubmitted() { return true; }
    /** Запазва стандартните грешки по полета. */ public function setError($field, $message) { $this->errors[$field] = $message; }
};
$form->rec = (object) array('SASS_USE_COMPOSER' => 'yes', 'SASS_VERSION' => '2.1.0');
(new sass_Setup())->inputConfigDescriptionForm($form);
expect(isset($form->errors['SASS_VERSION']) === (PHP_VERSION_ID < 80100), 'PHP form guard failed');
if (PHP_VERSION_ID < 80100) {
    $config = array('USE_COMPOSER' => 'yes', 'VERSION' => '2.1.0');
    expect(strpos((new sass_Setup())->checkConfig(), 'PHP 8.1') !== false, 'Setup PHP error missing');
    expect(strpos((new sass_Setup())->install(), "class='red'") !== false, 'Installer must reject before Composer');
    $failed = false;
    try { sass_Converter::convert($simple, true); } catch (RuntimeException $e) { $failed = strpos($e->getMessage(), 'PHP 8.1') !== false; }
    expect($failed, 'Runtime must reject before loading PHP 8 code');
    $form->errors = array();
    $form->rec->SASS_USE_COMPOSER = 'no';
    (new sass_Setup())->inputConfigDescriptionForm($form);
    expect(!$form->errors, 'Unused Composer selection must not block PHPSass');
}
echo "PHP requirement guards: OK\n";
