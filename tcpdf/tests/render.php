<?php

/**
 * CLI проверка на TCPDF и CAD адаптера без база данни.
 *
 * @category tests
 * @package tcpdf
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});

class core_BaseClass {}
class core_ProtoSetup
{
    /** Подменя единствено инсталационната настройка в отделния тестов процес. */
    public static function get($name) { global $argv; return $argv[1] ?? TCPDF_VERSION; }
}
class core_Users
{
    /** Синтетичен автор за CAD примера. */
    public static function getCurrent($name) { return 'PDF test'; }
}
/** Дефинира константа със стандартния default. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Прекратява теста при нарушено условие. */
function expect($value, $message = 'Failed assertion') { if (!$value) throw new RuntimeException($message); }
/** Брой на тестовите елементи. */
function countR($value) { return count($value ?? array()); }
/** Деление за преобразуването на CAD цветовете. */
function sDiv($a, $b) { return $b ? $a / $b : 0; }

spl_autoload_register(function ($class) {
    $file = dirname(__DIR__, 2) . '/' . str_replace('_', '/', $class) . '.class.php';
    if (is_file($file)) require_once $file;
});
$dir = $argv[2] ?? '';
expect(is_dir($dir), 'Usage: php tcpdf/tests/render.php VERSION OUTPUT_DIRECTORY');
require dirname(__DIR__) . '/Setup.class.php';
expect(TCPDF_VERSION === '6.2.13', 'Default must stay 6.2.13');
foreach (array('invoice', 'large', 'cad') as $fixture) {
    $bytes = tcpdf_tests_Fixtures::render($fixture);
    expect(strpos($bytes, '%PDF-') === 0 && substr($bytes, -6) === "%%EOF\n", 'Invalid PDF');
    expect(TCPDF_STATIC::getTCPDFVersion() === tcpdf_Setup::get('VERSION'), 'Wrong loaded version');
    file_put_contents($dir . '/' . $fixture . '.pdf', $bytes);
    echo $fixture . ': OK (' . strlen($bytes) . " bytes)\n";
}
// Повторното зареждане не трябва да включва класове от другото копие.
tcpdf_Setup::loadLibrary();
$fontFile = $dir . '/custom.ttf';
file_put_contents($fontFile, gzuncompress(file_get_contents(K_PATH_FONTS . 'dejavusans.z')));
$pdf = new tcpdf_Instance();
$font = $pdf->addTTFfont($fontFile, 'TrueTypeUnicode', '', 32, $dir . '/');
expect($font && is_file($dir . '/' . $font . '.php'), 'TTF conversion failed');
$pdf->AddFont($font, '', $dir . '/' . $font . '.php');
echo "TTF adapter: OK\n";
expect(!class_exists('core_Composer', false), 'Bundled TCPDF must not load Composer');
echo 'Version: ' . TCPDF_STATIC::getTCPDFVersion() . '; default: ' . TCPDF_VERSION . "; OK\n";
