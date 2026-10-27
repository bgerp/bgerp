<?php

/**
 * CLI проверка на изолираното инсталиране с реалния core_Composer и Composer executable.
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

/** Проверява условие без зареждане на база данни. */
function expect($value, $message = 'Assertion failed', ...$context) { if (!$value) throw new RuntimeException($message); }
/** Дефинира константа по framework конвенцията. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Намира пакетен файл в тестовото хранилище. */
function getFullPath($path) { $file = dirname(__DIR__, 2) . '/' . $path; return is_file($file) ? $file : false; }
class core_Mvc {}
class core_ProtoSetup
{
    public static $selected = '1.24.1';
    /** Подменя единствено избраната за теста версия. */
    public static function get($name, $absolute = false) { return self::$selected; }
    /** Пропуска регистрациите в базата данни. */
    public function install() { return ''; }
}
class core_Cache
{
    /** Използва изрично подадения Composer, без неговото bootstrap/self-update. */
    public static function get($type, $key) { global $argv; expect($type === 'COMPOSER' && $key === 'PATH'); return realpath($argv[1]); }
}
class core_Os
{
    public static $phpFailure;
    /** Използва PHP runtime-а, с който е стартиран тестът. */
    public static function getPhpCmd()
    {
        if (self::$phpFailure === 'throw') throw new RuntimeException('Synthetic PHP command failure');
        return self::$phpFailure === 'return' ? false : PHP_BINARY;
    }
    /** Създава само тестовата директория. */
    public static function requireDir($dir) { if (!is_dir($dir)) expect(mkdir($dir, 0755, true)); }
}
class core_App
{
    /** CLI тестът няма ограничението на уеб заявката. */
    public static function setTimeLimit($seconds) {}
}
class core_Type
{
    /** Екранира текста на съобщението при инсталационна грешка. */
    public static function escape($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
}
class cls
{
    /** Зарежда реалния Composer helper на bgERP. */
    public static function load($class) { require_once getFullPath(str_replace('_', '/', $class) . '.class.php'); }
}

expect(isset($argv[1], $argv[2]) && is_file($argv[1]) && is_dir($argv[2]), 'Usage: php apachetika/tests/install.php COMPOSER_PHAR EMPTY_TEST_DIR');
define('EF_VENDOR_PATH', realpath($argv[2]));
define('EF_ROOT_PATH', dirname(__DIR__, 3));
expect(!file_exists(EF_VENDOR_PATH . '/apachetika'), 'Use a fresh test directory');
expect(!file_exists(EF_VENDOR_PATH . '/composer.json'), 'Use an empty test directory');
file_put_contents(EF_VENDOR_PATH . '/composer.json', '{"name":"bgerp/composer-test","config":{"vendor-dir":"shared-php-vendor"}}');
$sharedHash = hash_file('sha256', EF_VENDOR_PATH . '/composer.json');
cls::load('core_Composer');
$defaultDir = core_Composer::run('config vendor-dir --absolute --no-plugins --no-scripts');
expect($defaultDir !== false && end($defaultDir) === EF_VENDOR_PATH . '/shared-php-vendor', 'Default Composer directory changed');
require dirname(__DIR__) . '/Setup.class.php';
$setup = new apachetika_Setup();
expect(APACHE_TIKA_VERSION === '1.24.1');
foreach (array('1.7', '1.24.1', '2.6.0', '2.9.2') as $version) {
    core_ProtoSetup::$selected = $version;
    expect($setup->install() === '', 'Old versions must not invoke Composer');
    expect(apachetika_Setup::getJarPath() === getFullPath("apachetika/{$version}/tika-app.jar"));
}
expect(!file_exists(EF_VENDOR_PATH . '/apachetika'));
// Подменяме Java само в PATH на тестовия процес, без промяна на системната инсталация.
$javaDir = EF_VENDOR_PATH . '/test-java';
core_Os::requireDir($javaDir);
$previousPath = getenv('PATH');
try {
    putenv('PATH=' . $javaDir);
    core_ProtoSetup::$selected = '4.1.0';
    expect(strpos($setup->checkConfig() ?? '', 'липсва') !== false, 'Missing Java was accepted');
    expect(strpos($setup->install(), 'debug-error') !== false && !file_exists(EF_VENDOR_PATH . '/apachetika'), 'Missing Java must stop installation before Composer');
    foreach (array(
        array('4.1.0', 'openjdk version "11.0.29"', 'Java 17'),
        array('3.3.3', 'openjdk version "11.0.29"', null),
        array('3.3.3', 'java version "1.8.0_472"', 'Java 11'),
        array('4.1.0', 'java version "17.0.21"', null),
        array('4.1.0', 'openjdk version "21.0.12"', null),
        array('4.1.0', 'Unknown Java banner', 'Не може да се определи'),
    ) as $case) {
        list($version, $banner, $error) = $case;
        file_put_contents($javaDir . '/java', "#!/bin/sh\nprintf '%s\\n' " . escapeshellarg($banner) . " >&2\n");
        chmod($javaDir . '/java', 0755);
        core_ProtoSetup::$selected = $version;
        $result = $setup->checkConfig();
        expect($error === null ? $result === null : strpos($result ?? '', $error) !== false, 'Wrong Java compatibility result: ' . $banner);
    }
    core_ProtoSetup::$selected = '1.24.1';
    expect($setup->checkConfig() === null && $setup->install() === '', 'Legacy setup behavior changed');
} finally {
    putenv($previousPath === false ? 'PATH' : 'PATH=' . $previousPath);
}
foreach (array('3.3.3', '4.1.0') as $version) {
    $previousEnvironment = $version === '3.3.3'
        ? array('COMPOSER' => false, 'COMPOSER_VENDOR_DIR' => false)
        : array('COMPOSER' => EF_VENDOR_PATH . '/composer.json', 'COMPOSER_VENDOR_DIR' => EF_VENDOR_PATH . '/shared-php-vendor');
    foreach ($previousEnvironment as $name => $value) putenv($value === false ? $name : $name . '=' . $value);
    core_ProtoSetup::$selected = $version;
    $result = $setup->install();
    expect(strpos($result, 'debug-error') === false, strip_tags($result));
    foreach ($previousEnvironment as $name => $value) expect(getenv($name) === $value, 'Composer environment leaked');
    $jar = apachetika_Setup::getJarPath();
    expect(strpos($jar, EF_VENDOR_PATH . '/apachetika/vendor/') === 0);
    exec('java -jar ' . escapeshellarg($jar) . ' --version 2>&1', $output, $code);
    expect($code === 0 && strpos(implode("\n", $output), $version) !== false, 'Wrong Java distribution');
    $output = array();
    echo $version . ": installed and runnable\n";
}
// Възстановяването е задължително и при нормална грешка, и при изключение.
foreach (array('return', 'throw') as $failure) {
    core_Os::$phpFailure = $failure;
    $thrown = false;
    try {
        $result = $setup->install();
        expect($failure === 'return' && strpos($result, 'debug-error') !== false, 'Installation failure was hidden');
    } catch (RuntimeException $exception) {
        expect($failure === 'throw' && $exception->getMessage() === 'Synthetic PHP command failure', 'Unexpected exception');
        $thrown = true;
    } finally {
        core_Os::$phpFailure = null;
    }
    expect($thrown === ($failure === 'throw'), 'Installation exception was swallowed');
    foreach ($previousEnvironment as $name => $value) expect(getenv($name) === $value, 'Composer environment leaked on failure');
}
putenv('COMPOSER');
putenv('COMPOSER_VENDOR_DIR');
$defaultDir = core_Composer::run('config vendor-dir --absolute --no-plugins --no-scripts');
expect($defaultDir !== false && end($defaultDir) === EF_VENDOR_PATH . '/shared-php-vendor', 'Subsequent Composer call changed project');
foreach (array('composer.json', 'composer.lock') as $file) {
    expect(hash_file('sha256', EF_VENDOR_PATH . '/apachetika/' . $file) === hash_file('sha256', dirname(__DIR__) . '/composer/' . $file), 'Manifest/lock changed');
}
expect(hash_file('sha256', EF_VENDOR_PATH . '/composer.json') === $sharedHash, 'Shared manifest was touched');
expect(!file_exists(EF_VENDOR_PATH . '/composer.lock') && !file_exists(EF_VENDOR_PATH . '/shared-php-vendor'), 'Shared lock/vendor was touched');

// Composer трябва да отхвърли изтеглен файл, който не съвпада с фиксирания checksum.
$badDir = EF_VENDOR_PATH . '/bad-checksum';
core_Os::requireDir($badDir);
file_put_contents($badDir . '/bad.jar', 'Synthetic invalid distribution');
$manifest = json_decode(file_get_contents(dirname(__DIR__) . '/composer/composer.json'), true);
unset($manifest['require']['bgerp/tika-app-4']);
$manifest['repositories'][1]['package']['dist']['url'] = 'file://' . $badDir . '/bad.jar';
file_put_contents($badDir . '/composer.json', json_encode($manifest));
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(realpath($argv[1])) . ' --working-dir=' . escapeshellarg($badDir) . ' update --no-interaction --no-plugins --no-scripts --prefer-dist 2>&1', $output, $code);
expect($code !== 0 && stripos(implode("\n", $output), 'checksum') !== false, 'Bad checksum was accepted');
echo 'PHP ' . PHP_VERSION . "; Java requirements, default directory, old versions/default, isolated/repeat install, environment restoration after success/failure/exception, bad checksum rejection: OK\n";
