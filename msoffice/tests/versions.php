<?php

/**
 * CLI регресии с реалните библиотеки, без база данни или мрежа.
 *
 * @category  bgerp
 * @package   msoffice
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
$version = $argv[1] ?? '0.8.4';
$baseline = isset($argv[2]) ? realpath($argv[2]) : null;
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    if (!(error_reporting() & $level)) return false;
    throw new ErrorException($message, 0, $level, $file, $line);
});
/** Минимално очакване за изолирания тест. */
function expect($ok, $message = 'Assertion failed') { if (!$ok) throw new RuntimeException($message); }
/** Дефинира defaults само веднъж. */
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
/** Локално разрешаване на стария loader при baseline сравнението. */
function getFullPath($path) { return dirname(__DIR__, 2) . '/' . $path; }
class core_BaseClass {}
class cls
{
    /** Изолира фабриката на framework-а. */
    public static function get($class) { return new $class(); }
}
class core_ProtoSetup
{
    /** Заместител само на избраната версия. */
    public static function get($name) { return $GLOBALS['version']; }
}
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Excel.class.php';
require __DIR__ . '/Fixture.class.php';
expect(in_array($version, array('0.8.4', '1.1.19'), true), 'Unsupported version');
expect(MSOFFICE_SIMPLEXLSX_VERSION === '0.8.4', 'Default changed');
$reader = msoffice_Excel::getReaderClass();
$file = tempnam(sys_get_temp_dir(), 'xlsx-');
$checks = 0;
$assert = function ($ok, $message) use (&$checks) { expect($ok, $message); $checks++; };
try {
    msoffice_tests_Fixture::create($file);
    $xlsx = $reader::parse($file);
    $assert($xlsx !== false, 'Parse fixture');
    $rows = $xlsx->rows();
    $assert($xlsx->sheetNames() === array('Оферта', 'Скрит'), 'Cyrillic sheet names');
    $assert($rows[1][1] === 'Артикул', 'Shared string');
    $assert($rows[2][1] === '00123', 'Leading zeros in text');
    $assert($rows[2][2] === 12.5 && $rows[2][3] === 2 && $rows[2][4] === 25, 'Numbers and cached formula result');
    $assert($rows[2][5] === '2024-01-01 00:00:00', 'Excel 1900 date');
    $assert($rows[2][6] === false && $rows[2][7] === 7, 'False and raw custom-format number');
    $space = $version === '0.8.4' ? '  ' : ' ';
    $assert($rows[4][1] === "Кирилица{$space}& апостроф ' C:\\temp" && $rows[4][2] === '#DIV/0!', 'Rich inline text and cell error');
    $assert($xlsx->rows(1)[0] === array('Втори лист', 0), 'Second sheet and zero');
    $trimmed = msoffice_Excel::getRows($file, 'path');
    $assert(!isset($trimmed[0]) && !isset($trimmed[1][0]) && $trimmed[1][1] === 'Артикул', 'Preserved trim indexes');
    $assert(isset($trimmed[3]) && $trimmed[3][1] === '', 'Interior empty row retained');
    $assert((new msoffice_Excel())->getRows($file, 'path', true) === $trimmed, 'Object and static callers');
    if ($baseline) {
        $source = str_replace('msoffice_Excel', 'msoffice_BaselineExcel', file_get_contents($baseline));
        $temp = tempnam(sys_get_temp_dir(), 'xlsx-base-');
        file_put_contents($temp, $source);
        require $temp;
        unlink($temp);
        $assert((new msoffice_BaselineExcel())->getRows($file, 'path') === $trimmed, 'Exact old adapter output');
    }
    if ($version === '1.1.19') {
        $assert($xlsx->isHiddenSheet(1), 'Hidden sheet metadata');
        $assert(iterator_to_array($xlsx->readRows()) === $rows, 'Generator matches rows');
        $extended = $xlsx->rowsEx();
        $assert($extended[2][4]['f'] === 'C3*D3', 'Formula metadata');
        $assert(strpos($xlsx->toHTMLEx(), '<table') !== false, 'Extended companion loader');
        msoffice_tests_Fixture::create($file, 0, true);
        $assert($reader::parse($file)->rows()[2][5] === '2024-01-01 00:00:00', 'Excel 1904 date');
    }
    msoffice_tests_Fixture::create($file);
    $zip = new ZipArchive();
    $zip->open($file);
    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $xml = str_replace('r="B5"', 'r="A5"', $xml);
    $xml = preg_replace('/<c r="C5".*?<\/c>/', '', $xml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
    $zip->close();
    // 0.8.4 измества самотна клетка в колона A след пропуснат ред; новият reader пази адреса.
    $sparseRow = $version === '0.8.4' ? 3 : 4;
    $assert(strpos($reader::parse($file)->rows()[$sparseRow][0], 'Кирилица') === 0, 'Sparse column A row addressing');
    msoffice_tests_Fixture::create($file, 5000);
    $started = microtime(true);
    $large = msoffice_Excel::getRows($file, 'path');
    $seconds = microtime(true) - $started;
    $assert(count($large) === 5000 && $large[4999][0] === 'Продукт 5000', 'Large workbook complete');
    $assert(array_sum(array_column($large, 1)) === 62500.0, 'Large workbook sum');
    $selectedVersion = $version;
    foreach (array('0.8.4', '1.1.19', '0.8.4') as $version) {
        $assert(msoffice_Excel::getRows($file, 'path') === $large, 'Same-process version switch ' . $version);
    }
    $version = $selectedVersion;
    $zip = new ZipArchive();
    $zip->open($file);
    $zip->deleteName('xl/worksheets/sheet1.xml');
    $zip->close();
    $failed = false;
    try { msoffice_Excel::getRows($file, 'path'); } catch (RuntimeException $e) { $failed = strpos($e->getMessage(), 'лист') !== false; }
    $assert($failed, 'Missing worksheet rejected');
    file_put_contents($file, 'invalid archive');
    ob_start();
    $failed = false;
    try { msoffice_Excel::getRows($file, 'path'); } catch (RuntimeException $e) { $failed = true; }
    $output = ob_get_clean();
    $assert($failed && $output === '', 'Invalid archive fails without printed output');
    echo json_encode(array('php' => PHP_VERSION, 'version' => $version, 'checks' => $checks, 'largeSeconds' => round($seconds, 4)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} finally {
    unlink($file);
}
