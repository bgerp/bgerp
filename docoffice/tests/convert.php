<?php

/**
 * Реални Office конвертирания през PHP адаптера, с изолиран framework слой.
 *
 * @category tests
 * @package docoffice
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
/** Проверява условие без база данни. */
function expect($value, $message = 'Assertion failed', ...$context) { if (!$value) throw new RuntimeException($message); }
/** Запазва пакетните defaults. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Намира файл от текущото хранилище. */
function getFullPath($path) { $file = dirname(__DIR__, 2) . '/' . $path; return is_file($file) ? $file : false; }
class core_Mvc {}
class core_Manager extends core_Mvc {}
class core_ProtoSetup
{
    /** Избира само тестовата версия, без промяна на реална настройка. */
    public static function get($name, $absolute = false) { global $argv; return $name === 'OFFICE_CONVERTER_CLASS' ? 'docoffice_Jodconverter' : $argv[1]; }
    /** Пропуска регистрациите в базата данни. */
    public function install() { return ''; }
}
class core_Packs
{
    /** Позволява сравнение и с предишния адаптер. */
    public static function getConfig($pack) { global $argv; return (object) array('OFFICE_JODCONVERTER_VERSION' => $argv[1]); }
}
class cls
{
    /** Създава тестовата fconv услуга. */
    public static function get($class) { return new $class(); }
    /** Зарежда реалните helper-и, когато нямат тестова замяна. */
    public static function load($class) { if (!class_exists($class, false)) require_once getFullPath(str_replace('_', '/', $class) . '.class.php'); }
    /** Тестовият конфиг използва имена вместо идентификатори от базата. */
    public static function getClassName($class) { return $class; }
}
class core_Cache
{
    /** Подаденият PHAR пропуска Composer bootstrap и self-update. */
    public static function get($type, $key) { return getenv('BGERP_TEST_COMPOSER'); }
}
class core_Os
{
    /** Използва текущия PHP runtime. */
    public static function getPhpCmd() { return PHP_BINARY; }
    /** Създава тестова директория с нормални права за четене. */
    public static function requireDir($dir, $mode = 0755) { if (!is_dir($dir)) expect(mkdir($dir, $mode, true)); }
}
class core_App
{
    /** CLI тестът няма web time limit. */
    public static function setTimeLimit($seconds) {}
}
class core_Type
{
    /** Екранира инсталационна грешка. */
    public static function escape($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}
class core_Locks
{
    public static $held = array();
    /** Записва заключването за проверка на callback-а. */
    public static function obtain($name, ...$params) { self::$held[$name] = true; return true; }
    /** Отбелязва освобождаването на ключалка. */
    public static function release($name) { unset(self::$held[$name]); }
}
class docoffice_Office
{
    public static $prepared = 0, $count = 0;
    /** Старият CLI сам стартира Office; тестът пропуска допълнителния framework процес. */
    public static function prepareOffice() { self::$prepared++; }
    /** Запазва броенето само за стария адаптер. */
    public static function increaseConvertCount() { self::$count++; }
    /** Използва отделен тестов порт за новия CLI. */
    public static function findEmptyPort() { return 22004; }
    /** Старият адаптер не подава този порт към своя CLI. */
    public static function getOfficePort() { return 22004; }
    /** Проверява общата Office ключалка. */
    public static function lockOffice(...$params) { core_Locks::obtain('OfficePacket'); }
    /** Освобождава тестовата Office ключалка. */
    public static function unlockOffice() { core_Locks::release('OfficePacket'); }
}
class fileman_Files
{
    /** Извлича име от входен тестов път. */
    public static function getFileNameWithoutExt($path) { return pathinfo($path, PATHINFO_FILENAME); }
}
class fileman_webdrv_Generic
{
    /** Отделя stderr за диагностика. */
    public static function getErrLogFilePath($path) { return $path . '.err'; }
}
class fileman_Indexes
{
    public static $errors = 0;
    /** Отбелязва грешка, без запис в реален индекс. */
    public static function createError($params) { self::$errors++; }
}
class fconv_Script
{
    public $tempDir, $tempPath, $outFilePath, $params, $fh, $line, $options, $callback;
    public $values = array();
    public static $last;
    /** Създава отделно работно място за всяко конвертиране. */
    public function __construct() { global $argv; $this->tempPath = $argv[2]; $this->tempDir = $argv[2] . '/run-' . uniqid() . '/'; mkdir($this->tempDir); self::$last = $this; }
    /** Приема входен/изходен файл с реалното shell quoting. */
    public function setFile($name, $value) { $this->setParam($name, $value); }
    /** Екранира аргумент на командата. */
    public function setParam($name, $value, $escape = true) { $this->values[$name] = $escape ? escapeshellarg((string) $value) : $value; }
    /** Запомня точната команда на адаптера. */
    public function lineExec($line, $options) { $this->line = $line; $this->options = $options; }
    /** Запомня callback-а на адаптера. */
    public function callBack($callback) { $this->callback = $callback; }
    /** Проверява заявената външна програма. */
    public function setCheckProgramsArr($program) { expect($program === 'java'); }
    /** Изпълнява истинските Java и LibreOffice, после callback-а на адаптера. */
    public function run($async)
    {
        expect(!$async, 'CLI test must be synchronous');
        $cmd = $this->line;
        foreach ($this->values as $name => $value) $cmd = str_replace('[#' . $name . '#]', $value, $cmd);
        $cmd = 'LANG=' . escapeshellarg($this->options['LANG']) . ' ' . $cmd . ' > ' . escapeshellarg($this->outFilePath . '.log') . ' 2> ' . escapeshellarg($this->options['errFilePath']);
        exec($cmd, $output, $code);
        expect($code === 0, 'Conversion failed: ' . file_get_contents($this->options['errFilePath']));
        call_user_func($this->callback, $this);
        return true;
    }
}
expect(isset($argv[1], $argv[2]) && is_dir($argv[2]), 'Usage: php docoffice/tests/convert.php VERSION OUTPUT_DIR [BASELINE_ADAPTER]');
define('EF_ROOT_PATH', dirname(__DIR__, 3));
define('EF_VENDOR_PATH', getenv('BGERP_TEST_VENDOR_PATH') ?: $argv[2] . '/dependencies');
define('OFFICE_PACKET_PATH', 'soffice');
spl_autoload_register(function ($class) { $path = getFullPath(str_replace('_', '/', $class) . '.class.php'); if ($path) require_once $path; });
require dirname(__DIR__) . '/Setup.class.php';
expect(OFFICE_JODCONVERTER_VERSION === '3.0b4' && OFFICE_CONVERTER_CLASS === 'docoffice_Jodconverter', 'Default changed');
if ($argv[1] === '5.1.1') {
    expect(is_file(getenv('BGERP_TEST_COMPOSER') ?: ''), 'Set BGERP_TEST_COMPOSER to an existing Composer PHAR');
    core_Os::requireDir(EF_VENDOR_PATH);
    $setup = new docoffice_Setup();
    expect($setup->checkConfig() === null, 'Java/Office requirements failed');
    $result = $setup->install();
    expect(strpos($result, 'debug-error') === false, strip_tags($result));
}
if (!empty($argv[3])) require $argv[3];
$files = docoffice_tests_Fixtures::create($argv[2]);
$results = array();
foreach ($files as $file) {
    $pdf = docoffice_Jodconverter::convertDoc($argv[2] . '/' . $file, 'pdf', array('asynch' => false, 'callBack' => ''));
    expect(is_file($pdf) && filesize($pdf) > 1000, 'Missing PDF: ' . $file);
    exec('pdftotext -layout ' . escapeshellarg($pdf) . ' -', $lines, $code);
    $text = implode("\n", $lines);
    $lines = array();
    expect($code === 0, 'PDF text extraction failed');
    foreach (array('България', 'София', '00123', '37,50', 'EUR') as $needle) expect(strpos($text, $needle) !== false, 'Missing ' . $needle . ' in ' . $file);
    if ($file === 'large.html') expect(strpos($text, 'КРАЙ 500') !== false && substr_count($text, 'Ред ') === 500, 'Large document truncated');
    expect(!core_Locks::$held, 'Callback left a lock');
    $results[$file] = array('pdf' => $pdf, 'text' => $text);
    echo $file . ": OK\n";
}
// XLS е реален изходен формат на export_OfficeXls, не само междинен вход за PDF.
$csv = $argv[2] . '/export.csv';
file_put_contents($csv, "\"Място\",\"Сума\"\n\"София\",12.5\n\"България\",25\n\"Общо\",37.5\n");
$xls = docoffice_Jodconverter::convertDoc($csv, 'xls', array('asynch' => false, 'callBack' => ''));
expect(is_file($xls) && bin2hex(file_get_contents($xls, false, null, 0, 8)) === 'd0cf11e0a1b11ae1', 'Missing BIFF XLS export');
$pdf = docoffice_Jodconverter::convertDoc($xls, 'pdf', array('asynch' => false, 'callBack' => ''));
exec('pdftotext -layout ' . escapeshellarg($pdf) . ' -', $lines, $code);
$text = implode("\n", $lines);
foreach (array('София', 'България', 'Общо', '12.5', '25', '37.5') as $needle) expect(strpos($text, $needle) !== false, 'XLS export lost ' . $needle);
expect($code === 0 && !core_Locks::$held, 'XLS export failed or left locks');
$results['export.csv'] = array('xls' => $xls, 'pdf' => $pdf, 'text' => $text);
echo "CSV -> XLS -> PDF: OK\n";
if ($argv[1] === '5.1.1') expect(docoffice_Office::$prepared === 0 && docoffice_Office::$count === 0, 'New CLI changed the legacy Office lifecycle');
file_put_contents($argv[2] . '/results.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'PHP ' . PHP_VERSION . '; JODConverter ' . $argv[1] . "; OK\n";
