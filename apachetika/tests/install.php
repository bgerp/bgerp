<?php

/**
 * CLI проверки на общия Composer, превключването и запазването на чуждите зависимости.
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
/** Framework съвместимост за процесния кеш. */
function countR($value) { return is_countable($value) ? count($value) : 0; }
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
    public static function get($type, $key) { global $argv; return $key === 'PATH' ? realpath($argv[1]) : false; }
    /** Тестът не използва постоянен кеш. */
    public static function set(...$args) {}
    /** core_Composer чисти своя process cache след промяна. */
    public static function remove(...$args) {}
}
class core_Os
{
    /** Използва PHP runtime-а, с който е стартиран тестът. */
    public static function getPhpCmd() { return PHP_BINARY; }
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
expect(!file_exists(EF_VENDOR_PATH . '/composer.json'), 'Use an empty test directory');
file_put_contents(EF_VENDOR_PATH . '/composer.json', '{"name":"bgerp/composer-test","config":{"platform":{"php":"7.4.33"}},"extra":{"preserve":"unchanged"}}');
cls::load('core_Composer');
$result = core_Composer::install('psr/log', '1.1.4', true);
expect(strpos($result, 'debug-error') === false, strip_tags($result));
require dirname(__DIR__) . '/Setup.class.php';
$setup = new apachetika_Setup();
expect(APACHE_TIKA_VERSION === '1.24.1');
foreach (array('1.7', '1.24.1', '2.6.0', '2.9.2') as $version) {
    core_ProtoSetup::$selected = $version;
    expect($setup->install() === '', 'Old versions must not invoke Composer');
    expect(apachetika_Setup::getJarPath() === getFullPath("apachetika/{$version}/tika-app.jar"));
}
expect(!file_exists(EF_VENDOR_PATH . '/vendor/bgerp/tika-app'));
// Подменяме Java само в PATH на тестовия процес, без промяна на системната инсталация.
$javaDir = EF_VENDOR_PATH . '/test-java';
core_Os::requireDir($javaDir);
$previousPath = getenv('PATH');
try {
    putenv('PATH=' . $javaDir);
    core_ProtoSetup::$selected = '4.1.0';
    expect(strpos($setup->checkConfig() ?? '', 'липсва') !== false, 'Missing Java was accepted');
    expect(strpos($setup->install(), 'debug-error') !== false && !file_exists(EF_VENDOR_PATH . '/vendor/bgerp/tika-app'), 'Missing Java must stop installation before Composer');
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
foreach (array('3.3.3', '4.1.0', '3.3.3', '1.24.1', '4.1.0') as $version) {
    core_ProtoSetup::$selected = $version;
    $result = $setup->install();
    expect(strpos($result, 'debug-error') === false, strip_tags($result));
    $jar = apachetika_Setup::getJarPath();
    $other = $version === '3.3.3' ? '4.1.0' : '3.3.3';
    expect(!is_file(EF_VENDOR_PATH . '/vendor/bgerp/tika-app/tika-app-' . $other . '.jar'), 'Unselected version remained in vendor');
    if ($version === '1.24.1') expect(!is_dir(EF_VENDOR_PATH . '/vendor/bgerp/tika-app'), 'Vendor retained Tika after selecting bundled version');
    exec('java -jar ' . escapeshellarg($jar) . ' --version 2>&1', $output, $code);
    expect($code === 0 && strpos(implode("\n", $output), $version) !== false, 'Wrong Java distribution');
    $output = array();
    $hash = hash_file('sha256', EF_VENDOR_PATH . '/composer.lock');
    expect(strpos($setup->install(), 'debug-error') === false, 'Repeat setup failed');
    expect(hash_file('sha256', EF_VENDOR_PATH . '/composer.lock') === $hash, 'Repeat setup changed lock');
    expect(core_Composer::isInstalled('psr/log', '1.1.4', true), 'Unrelated package changed');
    $manifest = json_decode(file_get_contents(EF_VENDOR_PATH . '/composer.json'), true);
    expect(($manifest['extra']['preserve'] ?? null) === 'unchanged', 'Unrelated config changed');
    expect(($manifest['require']['psr/log'] ?? null) === '1.1.4', 'Unrelated requirement changed');
    echo $version . ": installed, runnable, sole selected distribution; unrelated package unchanged\n";
}
// Неуспешна замяна не трябва да премахва работещата версия.
$repository = json_decode(file_get_contents(dirname(__DIR__) . '/composer-packages.json'), true);
$repository['package'][0]['dist']['url'] = 'file://' . EF_VENDOR_PATH . '/bad.jar';
file_put_contents(EF_VENDOR_PATH . '/bad.jar', 'Synthetic invalid archive');
$result = core_Composer::install('bgerp/tika-app', '3.3.3', true, $repository);
expect(strpos($result, 'debug-error') !== false && stripos($result, 'checksum') !== false, 'Bad checksum accepted');
expect(is_file(apachetika_Setup::getJarPath('4.1.0')), 'Working distribution lost on failed download');
expect(core_Composer::isInstalled('psr/log', '1.1.4', true), 'Unrelated package changed after failure');
echo 'PHP ' . PHP_VERSION . "; requirements, defaults, shared install, switch/remove/reinstall, checksums: OK\n";
