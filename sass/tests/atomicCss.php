<?php

/**
 * Проверки на междинния CSS при четене по време на запис.
 *
 * @category tests
 * @package sass
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    if (error_reporting() & $level) throw new ErrorException($message, 0, $level, $file, $line);
    return false;
});
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function expect($condition) { if (!$condition) throw new RuntimeException('Expectation failed'); }
function getFullPath($name) {
    $file = 'atomiccss://' . TEST_ROOT . '/source/' . $name;
    return is_file($file) ? $file : false;
}
function getFileContent($name) { return file_get_contents(getFullPath($name)); }
class core_Plugin {}
class core_Mvc {
    public static function __callStatic($method, $args) {
        if ($method === 'getSbfFilePath') {
            $result = null;
            if ((new sass_Plugin())->on_BeforeGetSbfFilePath(null, $result, $args[0]) === false) return $result;
        } elseif ($method === 'saveFile') {
            $args[0] = str_replace('/* compiled */ ', '', $args[0]);
        }
        return call_user_func_array(array('core_Sbf', $method . '_'), $args);
    }
}
class core_Os {
    public static function forceDir($path) { return is_dir($path) || @mkdir($path, 0755, true); }
    public static function getTimeOfLastModifiedFile($dir, $exclude, $pattern) { return filemtime($dir . '/main.scss'); }
}
class core_App { public static function getBoot($absolute = false) { return ''; } }
class sass_Setup { public static function get($key) { return $key === 'VERSION' ? '1.0.4' : 'no'; } }
class sass_Converter {
    public static $content;
    public static function convert($file, $syntax) { return self::$content; }
}

/** Наблюдава истинския файл след отваряне и след всяка част от записа. */
class AtomicCssStream
{
    public $context;
    private $handle;
    public static $target;
    public static $before;
    public static $after;
    public static $observations = 0;
    public static $failRename = false;

    public static function path($url) { return substr($url, strlen('atomiccss://')); }
    public static function observe() {
        clearstatcache();
        $content = is_file(self::$target) ? file_get_contents(self::$target) : null;
        if ($content !== self::$before && $content !== self::$after) {
            throw new RuntimeException('Reader observed empty or partial intermediate CSS');
        }
        self::$observations++;
    }
    public function stream_open($path, $mode, $options, &$openedPath) {
        $this->handle = fopen(self::path($path), $mode);
        if (strpos($mode, 'w') !== false) self::observe();
        return is_resource($this->handle);
    }
    public function stream_write($data) {
        $written = fwrite($this->handle, $data);
        self::observe();
        return $written;
    }
    public function stream_read($length) { return fread($this->handle, $length); }
    public function stream_eof() { return feof($this->handle); }
    public function stream_stat() { return fstat($this->handle); }
    public function stream_close() { fclose($this->handle); }
    public function url_stat($path, $flags) { return @stat(self::path($path)); }
    public function unlink($path) { return unlink(self::path($path)); }
    public function rename($from, $to) {
        if (self::$failRename) return false;
        $result = rename(self::path($from), self::path($to));
        self::observe();
        return $result;
    }
}

define('TEST_ROOT', sys_get_temp_dir() . '/sass-atomic-' . bin2hex(random_bytes(8)));
define('EF_INDEX_PATH', TEST_ROOT);
define('EF_SBF', 'sbf');
define('EF_APP_NAME', 'fixture');
require dirname(__DIR__, 2) . '/core/Sbf.class.php';
require getenv('SASS_TEST_PLUGIN') ?: dirname(__DIR__) . '/Plugin.class.php';
stream_wrapper_register('atomiccss', 'AtomicCssStream');
$checks = 0;
try {
    foreach (array('existing', 'missing', 'rename-failure', 'empty') as $case) {
        $dir = TEST_ROOT . '/source/' . $case;
        mkdir($dir, 0755, true);
        file_put_contents($dir . '/main.scss', '$color: red;');
        touch($dir . '/main.scss', 1700000000);
        AtomicCssStream::$target = $dir . '/main.css';
        AtomicCssStream::$before = $case === 'missing' ? null : 'body { color: blue; }';
        if (AtomicCssStream::$before !== null) {
            file_put_contents(AtomicCssStream::$target, AtomicCssStream::$before);
            touch(AtomicCssStream::$target, 1600000000);
        }
        sass_Converter::$content = $case === 'empty' ? '' : str_repeat('/* compiled */ body { color: red; }' . "\n", 4096);
        AtomicCssStream::$after = sass_Converter::$content;
        AtomicCssStream::$failRename = $case === 'rename-failure';
        clearstatcache();
        $url = core_Sbf::getUrl($case . '/main.css', '');
        $copy = core_Sbf::convertUrlToPath($url);
        expect(file_get_contents($copy) === str_replace('/* compiled */ ', '', sass_Converter::$content));
        expect(file_get_contents(AtomicCssStream::$target) === (AtomicCssStream::$failRename ? AtomicCssStream::$before : sass_Converter::$content));
        expect(!glob($dir . '/*.tmp'));
        $checks += 3;
    }
    echo "PASS: {$checks} CSS checks and " . AtomicCssStream::$observations . ' reads during writes on PHP ' . PHP_VERSION . "\n";
} finally {
    stream_wrapper_unregister('atomiccss');
    if (is_dir(TEST_ROOT)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(TEST_ROOT, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir(TEST_ROOT);
    }
    restore_error_handler();
}
