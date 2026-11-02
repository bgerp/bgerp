<?php
/** Изолиран CLI тест: php versions.php AUTOLOAD VERSION [SPF_SOURCE] [BASELINE_ADAPTER]. */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$autoload = $argv[1] ?? '';
$version = $argv[2] ?? '3.3.0';
$source = $argv[3] ?? '';
$baseline = $argv[4] ?? '';
require_once $autoload;
if ($source !== '') {
    spl_autoload_register(function ($class) use ($source) {
        if (strpos($class, 'SPFLib\\') === 0) {
            $file = $source . '/' . str_replace('\\', '/', substr($class, 7)) . '.php';
            if (is_file($file)) require $file;
        }
    }, true, true);
}
/** Минимална рамка за изпълнение на реалния адаптер и ServMon без база данни. */
function expect($condition, $message = '') { if (!$condition) throw new RuntimeException($message); }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
class core_ProtoSetup
{
    public static function get($key) { global $version; return $version; }
    public function install() { return ''; }
}
class core_Composer
{
    public static $available = true;
    public static $installed;
    public static $installArgs;
    public static function isInUse() { global $autoload; if (!self::$available) return false; require_once $autoload; return true; }
    public static function isInstalled($package, $version = null, $exact = false) { return self::$installed === $version; }
    public static function install($package, $version, $exact) { self::$installArgs = func_get_args(); return ''; }
}
class core_Packs { public static function isInstalled($pack) { return true; } }
class sens2_ProtoDriver {}
require dirname(__DIR__) . '/Setup.class.php';
require $baseline !== '' ? $baseline : dirname(__DIR__) . '/Checker.class.php';
require dirname(__DIR__, 2) . '/sens2/ServMon.class.php';
require __DIR__ . '/Fixture.class.php';
$checks = 0;
$check = function ($value, $message) use (&$checks) { expect($value, $message); $checks++; };
$check(SPFLIB_VERSION === '3.1.1', 'Global default unchanged');
$setup = new spflib_Setup();
$check($setup->version === '0.1', 'Setup version unchanged');
$actualSource = (new ReflectionClass('SPFLib\\Checker'))->getFileName();
$expectedSource = $source !== '' ? realpath($source) : realpath(dirname($autoload) . '/mlocati/spf-lib/src');
$check(dirname($actualSource) === $expectedSource, 'The intended library source is loaded');
$check((bool) $setup->checkConfig(), 'Missing dependency is visible in setup');
core_Composer::$installed = $version === '3.3.0' ? '3.1.1' : '3.3.0';
$check((bool) $setup->checkConfig(), 'Different version is visible in setup');
core_Composer::$installed = $version;
$check(!$setup->checkConfig(), 'Selected version passes setup');
$setup->install();
$check(core_Composer::$installArgs === array('mlocati/spf-lib', $version, true), 'Selected exact version uses existing Composer');
spflib_tests_Fixture::run($check);
if ($baseline === '') {
    core_Composer::$available = false;
    $missing = false;
    try {
        spflib_Checker::check();
    } catch (RuntimeException $e) {
        $missing = strpos($e->getMessage(), 'Инициализирайте') !== false;
    }
    $check($missing, 'Missing Composer gives a controlled error');
}
echo json_encode(array('php' => PHP_VERSION, 'version' => $version, 'checks' => $checks, 'baseline' => (bool) $baseline), JSON_UNESCAPED_UNICODE) . "\n";
