<?php

/**
 * Реални PDF проверки с изолирани framework услуги и външен Composer проект.
 *
 * @category tests
 * @package chromephp
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 5) exit("Usage: php convert.php DEPENDENCIES OUTPUT CHROME_BIN shared|1.16.1 [BASELINE_ADAPTER]\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});
define('EF_VENDOR_PATH', $argv[1]);
define('EF_TEMP_PATH', $argv[2]);
if (!is_dir(EF_TEMP_PATH)) mkdir(EF_TEMP_PATH, 0755, true);
/** Проверява условие без framework bootstrap. */
function expect($value, $message = 'Assertion failed') { if (!$value) throw new RuntimeException($message); }
/** Запазва оригиналните defaults. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Съвместимост със стандартния framework helper. */
function countR($value) { return is_countable($value) ? count($value) : 0; }
class core_ProtoSetup
{
    public static $settings;
    /** Чете само тестовата конфигурация. */
    public static function get($name) { return self::$settings[$name] ?? constant('CHROMEPHP_' . $name); }
}
core_ProtoSetup::$settings = array('VERSION' => $argv[4], 'BIN_PATH' => $argv[3]);
class core_Composer
{
    /** Намира общия Composer autoload в тестовата среда. */
    public static function getAutoloadPath() { return EF_VENDOR_PATH . '/vendor/autoload.php'; }
    /** Зарежда тестовия общ vendor. */
    public static function isInUse() { if (!is_file(self::getAutoloadPath())) return false; require_once self::getAutoloadPath(); return true; }
    /** Проверява наличието без Composer команда или база данни. */
    public static function isInstalled($name, $version = null, $exact = false)
    {
        $data = json_decode(file_get_contents(EF_VENDOR_PATH . '/vendor/composer/installed.json'), true);
        foreach (($data['packages'] ?? $data) as $package) {
            if (($package['name'] ?? '') === $name) return $version === null || ltrim($package['version'] ?? '', 'v') === $version;
        }
        return false;
    }
}
class cls
{
    /** Основните класове са предоставени от теста. */
    public static function load($name) {}
    /** Изолира print wrapper-а от инсталационните CSS и база данни. */
    public static function get($name) { expect($name === 'page_Print'); return new chromephp_tests_Wrapper(); }
}
class chromephp_tests_Wrapper
{
    private $html;
    /** Няма външни ресурси във fixture-ите. */
    public function push($value, $place) {}
    /** Поставя HTML документа. */
    public function replace($html, $place) { $this->html = $html; }
    /** Връща самостоятелен UTF-8 документ. */
    public function getContent($a, $b, $c) { return '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $this->html . '</body></html>'; }
}
class core_Debug
{
    public static $timers = array();
    /** Запомня отворените таймери. */
    public static function startTimer($name) { self::$timers[$name] = true; }
    /** Отбелязва приключилия таймер. */
    public static function stopTimer($name) { unset(self::$timers[$name]); }
}
class fileman
{
    /** Записва само в изходната директория на теста. */
    public static function absorbStr($bytes, $bucket, $name) { $path = EF_TEMP_PATH . '/' . $name; file_put_contents($path, $bytes); return $path; }
}
require dirname(__DIR__) . '/Setup.class.php';
require $argv[5] ?? dirname(__DIR__) . '/Browser.class.php';
require __DIR__ . '/Fixtures.class.php';
expect(CHROMEPHP_VERSION === 'shared' && CHROMEPHP_BIN_PATH === '' && CHROMEPHP_SHOW_PAGE_NUMBERS === 'no', 'Defaults changed');
core_Composer::isInUse();
if (interface_exists('Psr\\Log\\LoggerInterface')) new Psr\Log\NullLogger();
$converter = new chromephp_Browser();
$results = array();
foreach (array('invoice', 'wide', 'large', 'javascript', 'image', 'numbered') as $fixture) {
    core_ProtoSetup::$settings['SHOW_PAGE_NUMBERS'] = $fixture === 'numbered' ? 'yes' : 'no';
    $start = microtime(true);
    $file = $converter->convert(chromephp_tests_Fixtures::html($fixture), $fixture . '.pdf', 'test', array('JS' => array()));
    expect(substr(file_get_contents($file), 0, 5) === '%PDF-', 'Invalid PDF');
    exec('pdftotext -layout ' . escapeshellarg($file) . ' ' . escapeshellarg($file . '.txt'), $output, $code);
    expect($code === 0, 'pdftotext failed');
    $text = file_get_contents($file . '.txt');
    foreach (array('България', 'София', '00123', 'EUR', $fixture === 'wide' ? 'Стойност 12' : '37,50') as $word) expect(strpos($text, $word) !== false, 'Missing ' . $word);
    expect(strpos($text, 'HIDDEN_SENTINEL') === false, 'Hidden element printed');
    if ($fixture === 'javascript') expect(strpos($text, 'JavaScript готов') !== false && strpos($text, 'WAITING_SENTINEL') === false, 'JavaScript not ready');
    if ($fixture === 'large') expect(preg_match_all('/Артикул [0-9]+/', $text) === 600 && strpos($text, 'Артикул 600') !== false, 'Missing large-document rows');
    if ($fixture === 'numbered') expect(preg_match('/1\s*\/\s*1/', $text), 'Missing page number');
    expect(!core_Debug::$timers, 'Leaked timer');
    $results[$fixture] = array('seconds' => round(microtime(true) - $start, 2), 'bytes' => filesize($file));
    echo $fixture . ": OK\n";
}
file_put_contents(EF_TEMP_PATH . '/results.json', json_encode(array('php' => PHP_VERSION, 'version' => $argv[4], 'results' => $results), JSON_PRETTY_PRINT));
echo 'PHP ' . PHP_VERSION . '; ' . $argv[4] . ": all conversions OK\n";

if (!isset($argv[5])) {
    class chromephp_tests_FailingBrowser extends chromephp_Browser
    {
        public $browser;
        /** Проверява cleanup при грешка след стартиране на реалния браузър. */
        protected function renderPdf($browser, $html) { $this->browser = $browser; throw new LogicException('Expected rendering failure'); }
    }
    $failing = new chromephp_tests_FailingBrowser();
    try {
        $failing->convert('<p>Test</p>', 'failure.pdf', 'test');
        throw new RuntimeException('Expected rendering error missing');
    } catch (LogicException $error) {
        expect($error->getMessage() === 'Expected rendering failure');
    }
    expect(!$failing->browser->getConnection()->isConnected(), 'Browser remained connected after error');
    expect(!is_file(EF_TEMP_PATH . '/failure.pdf'), 'Partial PDF saved');
    echo "Error cleanup and omitted JS: OK\n";
}
