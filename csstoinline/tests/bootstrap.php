<?php

/**
 * Изолирана среда за проверка на адаптерите без връзка с база данни.
 *
 * @category vendors
 * @package csstoinline
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
// По желание: php csstoinline/tests/converters.php /path/to/vendor/autoload.php
$autoload = $argv[1] ?? dirname(__DIR__, 3) . '/composer/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Missing Composer autoloader; pass its path as the first argument.\n");
    exit(1);
}
require $autoload;
// bgERP третира и deprecated извикванията като грешки; не ги скриваме в адаптерните тестове.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});
if (!class_exists('Symfony\Component\CssSelector\CssSelectorConverter')) {
    fwrite(STDERR, "Install symfony/css-selector:^5.4 before running all-version tests.\n");
    exit(1);
}
class core_Manager {}
class core_ProtoSetup
{
    /** Замества инсталацията на framework-а без странични ефекти. */
    public function install() { return ''; }
    /** Връща настройката от текущия синтетичен сценарий. */
    public static function get($name) { return core_Packs::$config->{'CSSTOINLINE_' . $name}; }
}
class core_Packs
{
    public static $config;
    /** Предоставя изолираната конфигурация на адаптера. */
    public static function getConfig($name) { return self::$config; }
}
class core_Composer
{
    public static $requested = array(), $loads = 0;
    /** Отчита дали адаптерът е поискал Composer autoload. */
    public static function isInUse() { self::$loads++; return true; }
    /** Записва заявената зависимост, без да инсталира пакети. */
    public static function install($name, $version) { self::$requested[] = array($name, $version); return ''; }
}
class cls
{
    /** В тестовете конфигурацията съдържа името вместо ID на клас. */
    public static function getClassName($name) { return $name; }
}
/** Дефинира настройките по същия начин като framework-а. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Намира bundled библиотека спрямо хранилището. */
function getFullPath($path) { return dirname(__DIR__, 2) . '/' . $path; }
/** Прекъсва теста при нарушено условие. */
function expect($condition, $message) { if (!$condition) throw new RuntimeException($message); }
