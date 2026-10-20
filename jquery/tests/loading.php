<?php

/**
 * Изолирани проверки за реда на jQuery и Migrate, без база данни.
 *
 * @category  tests
 * @package   jquery
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

if (PHP_SAPI !== 'cli') exit("CLI only\n");

class core_Plugin {}
class core_ET
{
    public $files = array();

    public function push($file, $place)
    {
        $this->files[] = $file;
    }
}
class core_Packs
{
    public static function isInstalled($pack)
    {
        return false;
    }
}
class jquery_Setup
{
    public static $config = array();

    public static function get($key)
    {
        return self::$config[$key] ?? '';
    }
}
class page_Html
{
    public static function getFileForAppend($file)
    {
        return $file;
    }
}
class arr
{
    public static function make($items, $associative = false)
    {
        $items = is_array($items) ? $items : array_map('trim', explode(',', $items));
        return $associative ? array_combine($items, $items) : $items;
    }
}
class core_Sbf
{
    public static function getSbfFilePath($file)
    {
        return dirname(__DIR__, 2) . '/' . $file;
    }
}
class compactor_Setup
{
    public static function preparePacksPath($pack, $file)
    {
        return str_replace('[#jquery::JQUERY_VERSION#]', jquery_Setup::get('VERSION'), $file);
    }
}
class log_System
{
    public static function add($class, $message, $id, $type) {}
}
class dt
{
    public static function mysql2timestamp()
    {
        return time();
    }
}
function sbf($file) {}
function countR($items) { return count($items); }
function verify($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS {$message}\n";
}

require dirname(__DIR__) . '/Jquery.class.php';
require dirname(__DIR__, 2) . '/compactor/Plugin.class.php';

$directory = sys_get_temp_dir() . '/jquery-loading-' . uniqid();
mkdir($directory);
define('EF_SBF_PATH', $directory);
$migrate = 'jquery/migrate-4.0.2/jquery-migrate.min.js';
$compactor = new compactor_Plugin();

foreach (array('1.11.2', '3.7.1', '4.0.0') as $version) {
    jquery_Setup::$config = array('VERSION' => $version);
    $jquery = "jquery/{$version}/jquery.min.js";
    $expected = $version === '4.0.0' ? array($jquery, $migrate) : array($jquery);
    $tpl = new core_ET();
    jquery_Jquery::enable($tpl);
    verify($tpl->files === $expected, "local loader {$version}");

    jquery_Setup::$config['CDN_URL'] = "https://example.invalid/jquery-{$version}.js";
    jquery_Setup::$config['CDN_INTEGRITY'] = 'synthetic-integrity';
    $tpl = new core_ET();
    jquery_Jquery::enable($tpl);
    verify(is_object($tpl->files[0]) && strpos($tpl->files[0]->fallback, $jquery) !== false &&
        array_slice($tpl->files, 1) === array_slice($expected, 1), "CDN fallback {$version}");

    foreach (array($jquery, 'jquery/[#jquery::JQUERY_VERSION#]/jquery.min.js') as $configured) {
        $files = $compactor->compactFiles(array('js/efCommon.js'),
            array($configured, 'js/efCommon.js'), $directory);
        $bundle = file_get_contents($directory . '/' . reset($files));
        $jqueryEnd = strpos($bundle, "\nvar shortURL;");
        $migratePos = strpos($bundle, 'jQuery Migrate v4.0.2');
        verify($jqueryEnd !== false && ($version === '4.0.0'
            ? $migratePos !== false && $migratePos < $jqueryEnd
            : $migratePos === false), "compactor order {$configured}");
    }
}

foreach (glob($directory . '/*.js') as $file) {
    unlink($file);
}
rmdir($directory);
echo "12 checks passed\n";
