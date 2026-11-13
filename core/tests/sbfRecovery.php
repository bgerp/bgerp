<?php

/**
 * Изолирани проверки на SBF възстановяването, версиите и обработващите плъгини.
 *
 * @category tests
 * @package core
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'cli-server') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function countR($value) { return count((array) $value); }
function expect($condition) { if (!$condition) throw new RuntimeException('Expectation failed'); }
function getFullPath($name) {
    foreach (array('private', 'source') as $base) {
        $path = TEST_ROOT . '/' . $base . '/' . $name;
        if (is_file($path)) return $path;
    }
    return false;
}
function getFileContent($name) { return file_get_contents(getFullPath($name)); }
function sbf($name, $quotes = '', $absolute = false) { return core_Sbf::getUrl($name, $quotes, $absolute); }
class core_Plugin {}
class core_Mvc
{
    public static function __callStatic($method, $args) {
        if ($method === 'getSbfFilePath') {
            $result = null;
            if ((new sass_Plugin())->on_BeforeGetSbfFilePath(null, $result, $args[0]) === false) return $result;
        } elseif ($method === 'saveFile') {
            minify_Plugin::on_BeforeSaveFile(null, null, $args[0], $args[1], $args[2] ?? false);
        } else {
            throw new RuntimeException('Unexpected method: ' . $method);
        }
        return call_user_func_array(array('core_Sbf', $method . '_'), $args);
    }
}
class core_App {
    public static function getBoot($absolute = false) { return ''; }
    public static function shutdown($output = true) {}
}
class cls { public static function get($name) { return new $name(); } }
class arr {
    public static function make($items, $assoc = false) {
        $items = is_array($items) ? $items : ($items ? explode(',', $items) : array());
        return $assoc && $items ? array_combine($items, $items) : $items;
    }
}
class core_Packs {
    public static function isInstalled($name) { return true; }
    public static function getConfig($name) {
        return (object) array('COMPACTOR_JS_FILES' => 'js/one.js,js/two.js', 'COMPACTOR_CSS_FILES' => 'styles/main.css');
    }
}
class core_Os {
    public static function forceDir($path) { return is_dir($path) || mkdir($path, 0755, true); }
    public static function getTimeOfLastModifiedFile($dir, $exclude, $pattern) { return filemtime($dir . '/main.scss'); }
}
class sass_Setup {
    public static $version = '2.1.0';
    public static function get($key) { return $key === 'VERSION' ? self::$version : 'yes'; }
}
class sass_Converter {
    public static function convert($path, $output) { return '/* compiled */ body { color: red; }'; }
}
class minify_Css {
    public static function process($content) { return str_replace('/* compiled */ ', '', $content); }
}
class minify_Js {
    public static function process($content) { return str_replace('/* source */ ', '', $content); }
}
class log_System { public static function add($class, $message, $id, $type) {} }
class Debug { public static function log($message) {} }
function checkSbf($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
    $GLOBALS['checks']++;
}
function writeSbfSource($name, $content, $time = 1700000000) {
    $path = TEST_ROOT . '/' . $name;
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    file_put_contents($path, $content);
    touch($path, $time, $time);
}
function sbfName($path) { return ltrim(substr($path, strlen(EF_SBF_PATH)), '/'); }
function removeSbfFixtures($path) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($path);
}
define('TEST_ROOT', PHP_SAPI === 'cli-server' ? getenv('SBF_TEST_ROOT') : sys_get_temp_dir() . '/sbf-recovery-' . bin2hex(random_bytes(8)));
if (!TEST_ROOT) exit('SBF_TEST_ROOT is required');
define('EF_INDEX_PATH', TEST_ROOT);
define('EF_SBF', 'sbf');
define('EF_APP_NAME', 'fixture');
require dirname(__DIR__) . '/Sbf.class.php';
require dirname(__DIR__) . '/String.class.php';
class_alias('core_String', 'str');
require dirname(__DIR__, 2) . '/sass/Plugin.class.php';
require dirname(__DIR__, 2) . '/minify/Plugin.class.php';
require dirname(__DIR__, 2) . '/compactor/Plugin.class.php';

if (PHP_SAPI === 'cli-server') {
    // HTTP проверките използват отделна временна директория, подготвена от теста.
    core_Sbf::serveStaticFile(ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    exit;
}
$checks = 0;
mkdir(TEST_ROOT);
try {
    writeSbfSource('source/img/menu.png', 'core image');
    writeSbfSource('private/img/menu.png', 'private image');
    writeSbfSource('source/js/one.js', '/* source */ window.one = 1;');
    writeSbfSource('source/js/two.js', '/* source */ window.two = 2;');
    writeSbfSource('source/js/empty.js', '');
    writeSbfSource('source/js/zero.js', '0');
    writeSbfSource('source/styles/main.scss', '$color: red;');
    writeSbfSource('source/styles/main.css', '/* legacy compiler */ body { color: blue; }');
    writeSbfSource('source/js/exact_0102030405.js', 'literal source with numeric suffix');

    $menuName = sbfName(core_Sbf::getSbfPathByTime('img/menu.png', 1700000000));
    $info = core_Sbf::resolveStaticFile($menuName);
    checkSbf($info['status'] === 200 && file_get_contents($info['file']) === 'private image', 'restore current image with private override');
    checkSbf(is_file(EF_SBF_PATH . '/' . $menuName), 'restore the actual requested canonical copy');
    foreach (array('img/menu_9999999998.png', 'img/menu_9999999999.png') as $name) {
        $info = core_Sbf::resolveStaticFile($name);
        checkSbf($info['status'] === 302 && $info['url'] === '/sbf/fixture/' . $menuName, 'redirect alternate image names');
        checkSbf(!is_file(EF_SBF_PATH . '/' . $name), 'do not persist alternate names');
    }
    foreach (array('js/one.js', 'js/empty.js', 'js/zero.js') as $source) {
        $name = sbfName(core_Sbf::getSbfPathByTime($source, 1700000000));
        $info = core_Sbf::resolveStaticFile($name);
        checkSbf($info['status'] === 200, 'restore JS including empty and zero content');
        checkSbf(file_get_contents($info['file']) === str_replace('/* source */ ', '', getFileContent($source)), 'use minify when recovering JS');
    }
    checkSbf(core_Sbf::resolveStaticFile('js/one_0101000000.js')['status'] === 302, 'redirect lost versions to the current URL');
    checkSbf(!is_file(EF_SBF_PATH . '/js/one_0101000000.js'), 'do not save current code under old version');
    writeSbfSource('sbf/fixture/js/one_0101000000.js', 'previous version', 1600000000);
    $info = core_Sbf::resolveStaticFile('js/one_0101000000.js');
    checkSbf($info['status'] === 200 && file_get_contents($info['file']) === 'previous version', 'retain existing historical version');
    clearstatcache(true, $info['file']);
    checkSbf(filemtime($info['file']) === 1600000000 && fileatime($info['file']) > time() - 60, 'refresh atime explicitly without changing mtime');
    $info = core_Sbf::resolveStaticFile('js/exact_0102030405.js');
    checkSbf($info['status'] === 302 && strpos($info['url'], 'exact_0102030405_') !== false, 'literal source filename has priority');

    $sassName = sbfName(core_Sbf::getSbfPathByTime('styles/main.css', 1700000000));
    $sassName = substr($sassName, 0, -4) . '_scssphp2_1_0.css';
    $info = core_Sbf::resolveStaticFile($sassName);
    checkSbf($info['status'] === 200 && file_get_contents($info['file']) === 'body { color: red; }', 'restore SASS with its compiler and minify');
    checkSbf(getFileContent('styles/main.css') === '/* legacy compiler */ body { color: blue; }', 'do not serve or overwrite legacy CSS');
    checkSbf(core_Sbf::resolveStaticFile(str_replace('scssphp2_1_0', 'scssphp1_13_0', $sassName))['status'] === 302, 'redirect an unavailable compiler version without replacing its file');
    unlink(TEST_ROOT . '/source/styles/main.css');
    unlink(EF_SBF_PATH . '/' . $sassName);
    checkSbf(core_Sbf::resolveStaticFile('styles/main.css')['status'] === 302, 'resolve SASS with no CSS source');

    $plugin = new compactor_Plugin();
    $files = $plugin->compactFiles(array('js/one.js'), array('js/one.js', 'js/two.js'), EF_SBF_PATH . '/js');
    $name = reset($files);
    $content = file_get_contents(EF_SBF_PATH . '/' . $name);
    unlink(EF_SBF_PATH . '/' . $name);
    unlink(core_Sbf::getSbfFilePath('js/one.js'));
    unlink(core_Sbf::getSbfFilePath('js/two.js'));
    clearstatcache();
    $info = core_Sbf::resolveStaticFile($name);
    checkSbf($info['status'] === 200 && file_get_contents($info['file']) === $content, 'restore bundle after all its SBF inputs are removed');
    checkSbf(core_Sbf::resolveStaticFile('js/' . str_repeat('f', 32) . '.js')['status'] === 410, 'unknown bundle is not replaced');
    checkSbf(!is_file(EF_SBF_PATH . '/js/' . str_repeat('f', 32) . '.js'), 'unknown bundle does not create an alias');
    $cssFiles = $plugin->compactFiles(array('styles/main.css'), array('styles/main.css'), EF_SBF_PATH . '/css');
    $cssName = reset($cssFiles);
    unlink(EF_SBF_PATH . '/' . $cssName);
    checkSbf(core_Sbf::resolveStaticFile($cssName)['status'] === 200, 'restore CSS bundle with standard URL processing');

    foreach (array('../js/one.js', 'js/../js/one.js', '/js/one.js', 'js\\one.js', "js/a\n.js", 'js/one%2ejs', 'js/one.php', array('js/one.js')) as $name) {
        checkSbf(core_Sbf::resolveStaticFile($name)['status'] === 404, 'reject invalid static request');
    }
    checkSbf(core_Sbf::resolveStaticFile('img/missing_0101000000.png')['status'] === 404, 'missing source remains missing');
    echo "PASS: {$checks} SBF recovery checks on PHP " . PHP_VERSION . "\n";
} finally {
    removeSbfFixtures(TEST_ROOT);
    restore_error_handler();
}
