<?php

/**
 * Изолиран CLI тест: една библиотечна версия на PHP процес.
 *
 * @category tests
 * @package iban
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 2 || $argc > 3) exit("Usage: php versions.php VERSION [BASELINE_TYPE_FILE]\n");
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
$version = $argv[1];

/** Проверява очакване без зависимост от база данни. */
function expect($condition, $message = 'Assertion failed') { if (!$condition) throw new RuntimeException($message); }
/** Дефинира пакетните defaults. */
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
class core_ProtoSetup
{
    /** Избор само за текущия тестов процес. */
    public static function get($key) { return $GLOBALS['version']; }
}
class cls
{
    /** Адаптерът няма нужда от framework инициализация в изолирания тест. */
    public static function get($class) { return new $class(); }
}
class type_Varchar
{
    /** Същото HTML escaping като core_Type::escape(). */
    public static function escape($value) { return str_replace(array('&', '<', '&amp;lt;', '&amp;amp;'), array('&amp;', '&lt;', '&lt;', '&amp;'), $value ?? ''); }
}
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Type.class.php';
require __DIR__ . '/Cases.class.php';
expect(in_array($version, array('1.1.2', '1.4.7', '2.5.6', '2.6.5', '2.7.1', '4.0.0', '4.2.3', '5.0.0'), true), 'Unknown version');
expect(IBAN_CODE_VERSION === '2.6.5', 'Default changed');
expect((new iban_Setup())->version === '0.1', 'Setup version changed');
// Умишлено различна директория за проверка на абсолютното зареждане.
chdir(sys_get_temp_dir());
$result = iban_tests_Cases::run();
$type = new iban_Type();
expect($type->canonize(null) === '' && $type->canonize('') === '', 'Empty normalization');
expect(iban_Type::removeDs(null) === null && iban_Type::removeDs('') === '', 'Empty display helper');
iban_Type::loadCode();
expect(count(array_filter(get_included_files(), function ($path) { return basename($path) === 'php-iban.php'; })) === 1, 'Library loaded twice');
if (isset($argv[2])) {
    $baseline = str_replace('class iban_Type', 'class iban_BaselineType', file_get_contents($argv[2]));
    // Запазваме стария относителен require и му даваме оригиналния include path.
    set_include_path(dirname(__DIR__) . PATH_SEPARATOR . get_include_path());
    $baselineFile = tempnam(sys_get_temp_dir(), 'iban-baseline-');
    file_put_contents($baselineFile, $baseline);
    require $baselineFile;
    unlink($baselineFile);
    $old = iban_tests_Cases::run('iban_BaselineType');
    expect($result['snapshot'] === $old['snapshot'], 'Legacy behavior changed');
    $result['baselineIdentical'] = true;
}
unset($result['snapshot']);
echo json_encode(array('php' => PHP_VERSION, 'version' => $version) + $result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
