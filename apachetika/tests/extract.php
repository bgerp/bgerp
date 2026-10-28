<?php

/**
 * CLI проверка на реалната Java команда през PHP адаптера, без база данни.
 *
 * @category tests
 * @package apachetika
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

/** Проверява тестово условие. */
function expect($value, $message = 'Assertion failed', ...$context) { if (!$value) throw new RuntimeException($message); }
/** Дефинира непроменените пакетни defaults. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Намира файл спрямо хранилището. */
function getFullPath($path) { $file = dirname(__DIR__, 2) . '/' . $path; return is_file($file) ? $file : false; }
class core_ProtoSetup
{
    /** Подменя само избора на версия в тестовия процес. */
    public static function get($name, $absolute = false) { global $argv; return $name === 'VERSION' ? TCPDF_VERSION : $argv[1]; }
}
class core_Packs
{
    /** Поддържа и стария адаптер за сравнение със запазена контролна проба. */
    public static function getConfig($pack) { global $argv; return (object) array('APACHE_TIKA_VERSION' => $argv[1]); }
}
class cls
{
    /** Подменя framework услугите с локални тестови реализации. */
    public static function get($class) { return new $class(); }
    /** Дефинира само директорията на зависимостите, без зареждане на общия autoloader. */
    public static function load($class) {
        expect($class === 'core_Composer');
        defIfNot('EF_VENDOR_PATH', getenv('BGERP_TEST_VENDOR_PATH') ?: dirname(__DIR__, 3) . '/composer');
    }
}
class core_Locks
{
    public static $released = array();
    /** Записва освобождаването на тестова ключалка. */
    public static function release($key) { self::$released[] = $key; }
}
class i18n_Charset
{
    /** Проверява, че Java вече връща UTF-8. */
    public static function convertToUtf8($text, $charset) { expect(mb_check_encoding($text, 'UTF-8'), 'Invalid UTF-8'); return $text; }
}
class fileman_webdrv_Generic
{
    /** Отделя stderr от извлеченото съдържание. */
    public static function getErrLogFilePath($path) { return $path . '.err'; }
}
class fileman_Indexes
{
    public static $errors = 0;
    /** Отчита липсваща изпълнима програма. */
    public static function createError($params) { self::$errors++; }
    /** Отчита празни метаданни при липсваща програма. */
    public static function saveContent($params) { expect($params['content'] === ''); self::$errors++; }
}
class fconv_Script
{
    public $tempDir, $tempPath, $outFilePath, $params, $fh;
    public $files = array(), $values = array(), $line, $options, $callback;
    public static $last, $missing = false;
    /** Създава отделно работно място за всяко извличане. */
    public function __construct() {
        global $argv;
        $this->tempPath = $argv[2];
        $this->tempDir = $this->tempPath . '/run-' . uniqid() . '/';
        mkdir($this->tempDir);
        self::$last = $this;
    }
    /** Приема входен/изходен файл както fconv_Script. */
    public function setFile($name, $path) { $this->files[$name] = $path; }
    /** Използва същото shell quoting като framework-а. */
    public function setParam($name, $value, $escape = true) { $this->values[$name] = $escape ? escapeshellarg($value) : $value; }
    /** Запомня точната команда на адаптера. */
    public function lineExec($line, $options) { $this->line = $line; $this->options = $options; }
    /** Запомня callback-а за отделна проверка. */
    public function callBack($callback) { $this->callback = $callback; }
    /** Проверява зависимостта, поискана от адаптера. */
    public function setCheckProgramsArr($program) { expect($program === 'java'); }
    /** Изпълнява реален Tika процес, като подменя само fconv/DB слоя. */
    public function run($async) {
        if (self::$missing) return false;
        expect(!$async, 'CLI test must be synchronous');
        $values = $this->values;
        foreach ($this->files as $name => $path) $values[$name] = escapeshellarg($path);
        $cmd = $this->line;
        foreach ($values as $name => $value) $cmd = str_replace('[#' . $name . '#]', $value, $cmd);
        $cmd = 'LANG=' . escapeshellarg($this->options['LANG']) . ' HOME=' . escapeshellarg($this->options['HOME'])
            . ' ' . $cmd . ' 2> ' . escapeshellarg($this->options['errFilePath']);
        exec($cmd, $output, $code);
        expect($code === 0, 'Java failed: ' . file_get_contents($this->options['errFilePath']));
    }
}
spl_autoload_register(function ($class) {
    $path = getFullPath(str_replace('_', '/', $class) . '.class.php');
    if ($path) require_once $path;
});
expect(isset($argv[1], $argv[2]) && is_dir($argv[2]), 'Usage: php apachetika/tests/extract.php VERSION OUTPUT_DIR [BASELINE_ADAPTER]');
require dirname(__DIR__) . '/Setup.class.php';
expect(APACHE_TIKA_VERSION === '1.24.1', 'Default changed');
if (!empty($argv[3])) require $argv[3];
$files = apachetika_tests_Fixtures::create($argv[2]);
$results = array();
foreach ($files as $file) {
    foreach (array('text', 'metadata') as $type) {
        $params = array('type' => $type, 'asynch' => false, 'isPath' => true, 'lockId' => 'test', 'callBack' => '');
        $content = apachetika_Detect::extract($argv[2] . '/' . $file, $params);
        expect(strlen($content) > 0, 'Empty result: ' . $file . '/' . $type);
        if ($type === 'text') {
            foreach (array('България', 'София', '00123', '37,50', 'EUR') as $needle) {
                expect(strpos($content, $needle) !== false, 'Missing ' . $needle . ' in ' . $file);
            }
            if ($file === 'large.html') expect(strpos($content, 'КРАЙ 5000') !== false && substr_count($content, 'Ред ') === 5000, 'Truncated large file');
        } else {
            expect(stripos($content, 'Content-Type') !== false, 'Missing MIME metadata');
            if (in_array($file, array('sample.html', 'sample.docx', 'sample.xlsx', 'sample.pdf'), true)) {
                expect(strpos($content, 'Тестов документ') !== false, 'Missing title');
                expect(strpos($content, 'Tika Test') !== false, 'Missing author');
            }
        }
        expect(apachetika_Detect::afterExtract(fconv_Script::$last) === true, 'Empty callback failed');
        $results[$file][$type] = $content;
        echo $file . '/' . $type . ": OK\n";
    }
}
foreach (array('html', 'xml', 'xhtml') as $type) {
    $content = apachetika_Detect::extract($argv[2] . '/sample.docx', array('type' => $type, 'asynch' => false, 'isPath' => true, 'lockId' => 'test', 'callBack' => ''));
    expect(strpos($content, 'България') !== false && strpos($content, '<table') !== false, 'HTML/XHTML content missing');
    $results['sample.docx'][$type] = $content;
    echo $type . ": OK\n";
}
file_put_contents($argv[2] . '/results.json', json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
if (empty($argv[3])) {
    fconv_Script::$missing = true;
    foreach (array('text', 'metadata') as $type) {
        expect(apachetika_Detect::extract('missing', array('type' => $type, 'asynch' => false)) === '', 'Missing Java handling');
    }
    expect(fileman_Indexes::$errors === 2, 'Missing error registration');
    expect(apachetika_Detect::afterExtract((object) array('params' => serialize(array()))) === true, 'Optional callback');
}
echo 'Version ' . $argv[1] . '; default ' . APACHE_TIKA_VERSION . "; OK\n";
