<?php

/**
 * Изолиран CLI тест с реалните библиотеки, адаптер и type_Time.
 * UI помощниците са заместени; реалният HTML се проверява в браузър.
 *
 * @category  tests
 * @package   ical
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 2 || $argc > 3) exit("Usage: php versions.php VERSION [BASELINE_PARSER_FILE]\n");
$version = $argv[1];
$baselineWarnings = array();
$baselineRunning = false;
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) use (&$baselineWarnings, &$baselineRunning) {
    // Само сравнението с предишния адаптер отчита неговите известни предупреждения.
    if ($baselineRunning) {
        $key = $level . ': ' . $message;
        $baselineWarnings[$key] = ($baselineWarnings[$key] ?? 0) + 1;
        return true;
    }
    throw new ErrorException($message, 0, $level, $file, $line);
});
/** Проверява очакване без база данни. */
function expect($condition, $message = 'Assertion failed') { if (!$condition) throw new RuntimeException($message); }
/** Дефинира пакетните defaults. */
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
/** Изолиран превод без езикова база. */
function tr($text) { return explode('||', $text)[0]; }
/** Брой елементи за тестовия Richtext заместител. */
function countR($value) { return is_array($value) ? count($value) : 0; }
class core_Mvc {}
class core_ProtoSetup
{
    /** Избор само за текущия тестов процес. */
    public static function get($key) { return $GLOBALS['version']; }
}
class cls
{
    /** UI типовете нямат нужда от framework инициализация в изолирания тест. */
    public static function get($class) { return new $class(); }
}
class type_Varchar
{
    public $params = array();
    /** Същото escaping като core_Type::escape(). */
    public static function escape($value) { return str_replace(array('&', '<', '&amp;lt;', '&amp;amp;'), array('&amp;', '&lt;', '&lt;', '&amp;'), $value ?? ''); }
}
class type_Richtext
{
    const URL_PATTERN = '~https?://[^\s<]+~';
    public $_htmlBoard = array();
    /** HTML линковете се проверяват отделно с реалния framework. */
    public function _catchUrls($match) { return $match[0]; }
}
class type_Email
{
    /** Извлича синтетичните адреси от теста. */
    public static function extractEmails($value) { preg_match_all('/[a-z]+@example\.(?:invalid|com)/', $value, $matches); return $matches[0]; }
    /** Оставя адреса четим за проверката. */
    public function toVerbal($value) { return $value; }
}
require dirname(__DIR__, 2) . '/core/DateTime.class.php';
require dirname(__DIR__, 2) . '/core/String.class.php';
require dirname(__DIR__, 2) . '/type/Time.class.php';
class_alias('core_String', 'str');
class dt extends core_DateTime
{
    /** Изолиран формат без езикови и потребителски настройки. */
    public static function mysql2verbal($value, $mask = 'Y-m-d H:i:s', $lg = null, $autoTimeZone = null, $callRecursive = true) { return date($mask, strtotime($value)); }
}
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Parser.class.php';
require __DIR__ . '/Cases.class.php';
expect(in_array($version, array('2.0.1', '3.6.0'), true), 'Unknown version');
expect(ICAL_VERSION === '2.0.1', 'Default changed');
chdir(sys_get_temp_dir());
$result = ical_tests_Cases::run();
if (isset($argv[2])) {
    expect($version === '2.0.1', 'Baseline requires legacy library');
    $baseline = str_replace(array('class ical_Parser', '__DIR__'), array('class ical_BaselineParser', var_export(dirname(__DIR__), true)), file_get_contents($argv[2]));
    $baselineFile = tempnam(sys_get_temp_dir(), 'ical-baseline-');
    file_put_contents($baselineFile, $baseline);
    require $baselineFile;
    unlink($baselineFile);
    $baselineRunning = true;
    $old = ical_tests_Cases::run('ical_BaselineParser', true);
    $baselineRunning = false;
    expect($result['snapshot'] === $old['snapshot'], 'Legacy snapshot changed');
    $result['baselineIdentical'] = true;
}
expect(count(array_filter(get_included_files(), function ($path) { return basename($path) === 'ICal.php'; })) === 1, 'Multiple parser versions loaded');
unset($result['snapshot']);
echo json_encode(array('php' => PHP_VERSION, 'version' => $version) + $result + array('baselineWarnings' => $baselineWarnings), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
