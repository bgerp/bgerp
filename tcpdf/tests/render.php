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
class core_Composer
{
    /** Зарежда единствено изрично подадения тестов Composer autoloader. */
    public static function isInUse() { global $argv; expect(isset($argv[3]), 'Pass Composer autoload.php for TCPDF 7'); require_once $argv[3]; return true; }
    /** Пътят се използва единствено за тестовите зависимости. */
    public static function getAutoloadPath() { global $argv; return $argv[3]; }
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
// Новият двигател допуска изрично само директорията на тестовия шрифт.
if (($argv[1] ?? null) === '7.0.20') define('K_ALLOWED_PATHS', array(realpath($dir)));
require dirname(__DIR__) . '/Setup.class.php';
expect(TCPDF_VERSION === '6.2.13', 'Default must stay 6.2.13');
$orientationPdf = new tcpdf_Instance('', 'mm', array(140, 100));
$orientationPdf->setPrintHeader(false);
$orientationPdf->setPrintFooter(false);
$orientationPdf->AddPage();
expect(abs($orientationPdf->getPageWidth() - 140) < 0.01
    && abs($orientationPdf->getPageHeight() - 100) < 0.01, 'CAD page orientation changed');
unset($orientationPdf);
foreach (array('invoice', 'large', 'cad') as $fixture) {
    $bytes = tcpdf_tests_Fixtures::render($fixture);
    expect(strpos($bytes, '%PDF-') === 0 && substr($bytes, -6) === "%%EOF\n", 'Invalid PDF');
    expect(tcpdf_Setup::getLoadedVersion() === tcpdf_Setup::get('VERSION'), 'Wrong loaded version');
    file_put_contents($dir . '/' . $fixture . '.pdf', $bytes);
    echo $fixture . ': OK (' . strlen($bytes) . " bytes)\n";
}
// Повторното зареждане не трябва да включва класове от другото копие.
tcpdf_Setup::loadLibrary();
$fontFile = $dir . '/custom' . uniqid() . '.ttf';
file_put_contents($fontFile, gzuncompress(file_get_contents(dirname(__DIR__) . '/fonts/dejavusans.z')));
$pdf = new tcpdf_Instance();
$font = $pdf->addTTFfont($fontFile, 'TrueTypeUnicode', '', 32, $dir . '/');
$extension = tcpdf_Setup::get('VERSION') === '7.0.20' ? '.json' : '.php';
expect($font && is_file($dir . '/' . $font . $extension), 'TTF conversion failed');
$pdf->AddFont($font, '', $dir . '/' . $font . $extension);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont($font, '', 12);
$pdf->Text(10, 20, 'Собствен шрифт - България');
file_put_contents($dir . '/font.pdf', $pdf->Output('', 'S'));
echo "TTF adapter: OK\n";
if (tcpdf_Setup::get('VERSION') !== '7.0.20') expect(!class_exists('Composer\\InstalledVersions', false), 'Bundled TCPDF must not load Composer');
echo 'Version: ' . tcpdf_Setup::getLoadedVersion() . '; default: ' . TCPDF_VERSION . "; OK\n";
