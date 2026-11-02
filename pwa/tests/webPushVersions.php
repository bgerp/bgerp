<?php
/** CLI: php webPushVersions.php AUTOLOAD VERSION; без външни push заявки. */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$autoload = $argv[1] ?? '';
$version = $argv[2] ?? '7.0.0';
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
function expect($condition, $message = '') { if (!$condition) throw new RuntimeException($message); }
function setIfNot(&$value, $default) { if (!isset($value)) $value = $default; }
class core_Manager { public static function logNotice($message) {} }
class core_exception_Redirect extends Exception {}
class core_Webroot { public static function isExists($file) { return false; } }
class log_System
{
    public static $messages = array();
    public static function add($class, $message, $id, $type) { self::$messages[] = array($type, $message); }
}
class cls
{
    public static function get($class) { require_once dirname(__DIR__) . '/WebPushLogger.class.php'; return new $class(); }
}
class core_ProtoSetup
{
    public static function get($key) { global $version; return $version; }
}
class core_Composer
{
    public static $installed;
    public static function isInUse() { global $autoload; require_once $autoload; return true; }
    public static function isInstalled($name, $version, $exact) { return $exact ? self::$installed === $version : version_compare(self::$installed ?: '0', $version, '>='); }
}
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/PushSubscriptions.class.php';
require __DIR__ . '/WebPushFixture.class.php';
$checks = 0;
$check = function ($value, $message) use (&$checks) { expect($value, $message); $checks++; };
$setup = new pwa_Setup();
$check(PWA_WEB_PUSH_VERSION === 'auto' && $setup->version === '0.6', 'Defaults preserved');
$selected = $version;
$version = 'auto';
$check(pwa_Setup::getLibraryVersion() === (PHP_VERSION_ID >= 80000 ? '8' : '7'), 'Legacy automatic choice');
$version = $selected;
$check(pwa_Setup::getLibraryVersion() === $version, 'Explicit version');
$error = pwa_Setup::getPhpVersionError($version);
if ($error) {
    $check($setup->checkConfig() === $error, 'Standard PHP setup error');
    $check(strpos($setup->install(), $error) !== false, 'Incompatible setup stops before Composer');
    $check(pwa_PushSubscriptions::sendAlert(1, 'QA', 'QA', null, null, false) === array(), 'Unsupported runtime stops before querying subscriptions');
    $check(!class_exists('Minishlink\\WebPush\\WebPush', false), 'Unsupported SDK is not loaded');
} else {
    $check((bool) $setup->checkConfig(), 'Missing dependency shown');
    core_Composer::$installed = $version;
    $check(!$setup->checkConfig(), 'Selected dependency accepted');
    require $autoload;
    $actual = ltrim(\Composer\InstalledVersions::getPrettyVersion('minishlink/web-push'), 'v');
    $check($actual === $version, 'Intended library loaded');
    pwa_tests_WebPushFixture::run($check);
}
echo json_encode(array('php' => PHP_VERSION, 'version' => $version, 'checks' => $checks, 'blocked' => (bool) $error, 'diagnostics' => log_System::$messages)) . "\n";
