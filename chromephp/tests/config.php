<?php

/**
 * Проверки на конфигурацията и точната версия в общия Composer.
 *
 * @category tests
 * @package chromephp
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 3) exit("Usage: php config.php NEW_PROJECT OLD_PROJECT\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
/** Проверява условие без framework bootstrap. */
function expect($value, $message = 'Assertion failed') { if (!$value) throw new RuntimeException($message); }
/** Запазва defaults от оригиналния setup. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
class core_ProtoSetup
{
    public static $version = '1.16.1';
    /** Изолира избора от базата данни. */
    public static function get($name) { return self::$version; }
}
class cls
{
    /** Composer е предоставен от теста. */
    public static function load($name) {}
}
class core_Composer
{
    public static $path;
    /** Връща тестовото старо копие. */
    public static function getAutoloadPath() { return self::$path . '/vendor/autoload.php'; }
    /** Досегашното зареждане не е променено. */
    public static function isInUse() { require_once self::getAutoloadPath(); return true; }
    /** Тестовият стар пакет е предварително инсталиран. */
    public static function isInstalled($name, $version = null, $exact = false)
    {
        $data = json_decode(file_get_contents(self::$path . '/vendor/composer/installed.json'), true);
        foreach (($data['packages'] ?? $data) as $package) {
            if (($package['name'] ?? '') === $name) return $version === null || ltrim($package['version'] ?? '', 'v') === $version;
        }
        return false;
    }
}
class chromephp_Browser
{
    public static $binary = false;
    /** Симулира наличен или липсващ браузър без стартиране. */
    public static function getBinPath() { return self::$binary; }
}
core_Composer::$path = sys_get_temp_dir() . '/chromephp-config-missing-' . uniqid();
require dirname(__DIR__) . '/Setup.class.php';
$setup = new chromephp_Setup();
expect(strpos($setup->checkConfig(), 'Инициализирайте') !== false, 'Missing package accepted');
expect(!chromephp_Setup::loadLibrary(), 'Missing package loaded');
core_Composer::$path = $argv[2];
expect(!chromephp_Setup::isLibraryInstalled(), 'Wrong shared version accepted for pinned selection');
core_ProtoSetup::$version = 'shared';
expect(chromephp_Setup::isLibraryInstalled(), 'Legacy shared version rejected');
core_Composer::$path = $argv[1];
core_ProtoSetup::$version = '1.16.1';
expect(chromephp_Setup::isLibraryInstalled(), 'Installed selected version rejected');
expect(strpos($setup->checkConfig(), 'Chrome/Chromium') !== false, 'Missing binary accepted');
chromephp_Browser::$binary = true;
expect($setup->checkConfig() === null, 'Valid configuration rejected');
expect(chromephp_Setup::loadLibrary());
expect(class_exists('HeadlessChromium\\BrowserFactory'));
$reflection = new ReflectionClass('HeadlessChromium\\BrowserFactory');
expect($reflection->getFileName() === realpath($argv[1] . '/vendor/chrome-php/chrome/src/BrowserFactory.php'), 'Wrong library loaded');
core_ProtoSetup::$version = 'shared';
expect(chromephp_Setup::loadLibrary(), 'Shared mode rejected existing selected version');
expect(CHROMEPHP_VERSION === 'shared' && CHROMEPHP_SHOW_PAGE_NUMBERS === 'no' && CHROMEPHP_BIN_PATH === '', 'Defaults changed');
echo 'PHP ' . PHP_VERSION . "; setup, missing dependencies, exact/shared version and autoloader: OK\n";
