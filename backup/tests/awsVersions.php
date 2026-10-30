<?php
/** CLI: php awsVersions.php AUTOLOAD VERSION; никога не прави външни S3 заявки. */
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$autoload = $argv[1] ?? '';
$version = $argv[2] ?? '3.337.3';
function expect($condition, $message = '') { if (!$condition) throw new RuntimeException($message); }
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
class core_BaseClass {}
class core_exception_Redirect extends Exception {}
class core_ProtoSetup
{
    public static $storage = 'local';
    public static function get($key, $absolute = false)
    {
        global $version;
        $values = array('AWS_VERSION' => $version, 'STORAGE_TYPE' => self::$storage,
            'AMAZON_KEY' => 'qa-key', 'AMAZON_SECRET' => 'qa-secret', 'AMAZON_BUCKET' => 'qa-bgerp-bucket');
        return $values[$key] ?? null;
    }
    public function install() { return ''; }
}
class core_Composer
{
    public static $available = true;
    public static $installed;
    public static $error;
    public static $calls = array();
    public static function isInUse() { global $autoload; if (!self::$available) return false; require_once $autoload; return true; }
    public static function isInstalled($package, $version, $exact) { return self::$installed === $version; }
    public static function install($package, $version, $exact) { self::$calls[] = func_get_args(); self::$installed = $version; return ''; }
}
class backup_Start { public static function unLock() {} }
class core_Packs { public static function getConfig($pack) { throw new RuntimeException('full-check-boundary'); } }
require dirname(__DIR__) . '/Setup.class.php';
require $argv[3] ?? dirname(__DIR__) . '/Amazon.class.php';
require __DIR__ . '/S3Fixture.class.php';
$checks = 0;
$check = function ($value, $message) use (&$checks) { expect($value, $message); $checks++; };
$setup = new backup_Setup();
$check(BACKUP_AWS_VERSION === '3.272.2' && BACKUP_STORAGE_TYPE === 'local' && $setup->version === '0.1', 'Defaults unchanged');
$error = backup_Setup::getPhpVersionError($version);
if ($error) {
    $check(PHP_VERSION_ID < 80200 && $version === '3.402.3', 'PHP version guard');
    $check($setup->checkConfig() === $error, 'Standard setup error');
    $check(strpos($setup->install(), $error) !== false && !core_Composer::$calls, 'Unsupported SDK does not touch Composer');
    $blocked = false;
    try { new backup_Amazon(); } catch (RuntimeException $e) { $blocked = $e->getMessage() === $error; }
    $check($blocked && !class_exists('Aws\\S3\\S3Client', false), 'Runtime guard precedes autoload');
} else {
    $check(!$setup->checkConfig(), 'Local storage needs no AWS dependency');
    try { $setup->install(); } catch (RuntimeException $e) { expect($e->getMessage() === 'full-check-boundary'); }
    $check(!core_Composer::$calls, 'Local storage does not install AWS');
    core_ProtoSetup::$storage = 'amazon';
    $check((bool) $setup->checkConfig(), 'Missing SDK shown for S3');
    try { $setup->install(); } catch (RuntimeException $e) { expect($e->getMessage() === 'full-check-boundary'); }
    $check(core_Composer::$calls === array(array('aws/aws-sdk-php', $version, true)), 'Selected exact SDK uses common Composer');
    $check(!$setup->checkConfig(), 'Installed version passes setup');
    new backup_Amazon();
    $check(\Aws\Sdk::VERSION === $version, 'Intended SDK loaded');
    $property = new ReflectionProperty('backup_Amazon', 's3Client');
    $property->setAccessible(true);
    $client = $property->getValue();
    $check($client->getRegion() === 'eu-west-1', 'Existing region preserved');
    backup_tests_S3Fixture::run($client, $check);
    core_Composer::$available = false;
    $missing = false;
    try { new backup_Amazon(); } catch (RuntimeException $e) { $missing = strpos($e->getMessage(), 'Липсва AWS') !== false; }
    $check($missing, 'Missing autoloader is controlled');
}
echo json_encode(array('php' => PHP_VERSION, 'version' => $version, 'checks' => $checks, 'blocked' => (bool) $error)) . "\n";
