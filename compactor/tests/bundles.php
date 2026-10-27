<?php

/**
 * Регресии за кеш ключа, JS границите и относителните CSS адреси.
 *
 * @category tests
 * @package compactor
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});
class core_Plugin {}
class arr
{
    /** Нормализира тестовите списъци по framework конвенцията. */
    public static function make($items, $associative = false)
    {
        $items = is_array($items) ? $items : ($items ? array_map('trim', explode(',', $items)) : array());
        return $associative && $items ? array_combine($items, $items) : $items;
    }
}
class core_Sbf
{
    /** Връща изолирания SBF път. */
    public static function getSbfFilePath($file) { return EF_SBF_PATH . '/' . $file; }
}
class core_Os
{
    /** Създава директория само в тестовия временен корен. */
    public static function forceDir($path) { return @mkdir($path, 0777, true); }
}
class log_System
{
    /** Допуска очакваните съобщения за липсващи тестови файлове. */
    public static function add($class, $message, $id, $type) {}
}
class Debug
{
    /** Допуска очакваните диагностични съобщения. */
    public static function log($message) {}
}
class dt
{
    /** Връща текущото време за срока на кеша. */
    public static function mysql2timestamp() { return time(); }
}
class str
{
    /** Премахва водещата относителна стъпка. */
    public static function crop($text, $prefix) { return substr($text, strlen($prefix)); }
}
/** Симулира URL адреса на вече генериран статичен файл. */
function sbf($file, $unused = '', $absolute = false) { return '/sbf/' . $file; }
/** Резолвира само съществуващите синтетични файлове. */
function getFullPath($file) { $path = EF_SBF_PATH . '/' . $file; return is_file($path) ? $path : false; }
/** Замества framework брояча. */
function countR($items) { return count((array) $items); }
$checks = 0;
/** Прекъсва при нарушено очакване. */
function verifyBundle($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
    $GLOBALS['checks']++;
}
require dirname(__DIR__) . '/Plugin.class.php';
class TestCompactor extends compactor_Plugin
{
    /** Излага CSS преобразуването само за теста. */
    public function rewrite($css, $path) { return $this->changePaths($css, $path); }
}
$directory = sys_get_temp_dir() . '/compactor-' . uniqid();
define('EF_SBF_PATH', $directory);
mkdir($directory);
$compactor = new TestCompactor();
try {
    foreach (array('a.js' => '"use strict"; var calls = []', 'b.js' => '(function(){ calls.push("Щъркел"); })()', 'c.js' => 'var other = 1') as $file => $text) {
        file_put_contents($directory . '/' . $file, $text);
        touch($directory . '/' . $file, 1700000000);
    }
    $first = $compactor->compactFiles(array('a.js'), array('a.js', 'b.js'), $directory . '/js');
    $second = $compactor->compactFiles(array('a.js'), array('a.js', 'c.js'), $directory . '/js');
    $reverse = $compactor->compactFiles(array('a.js'), array('b.js', 'a.js'), $directory . '/js');
    verifyBundle($first !== $second && $first !== $reverse, 'same mtime must not collide');
    $bundle = file_get_contents($directory . '/' . reset($first));
    verifyBundle(strpos($bundle, "var calls = []\n;\n(function") !== false, 'JS expression boundary');
    verifyBundle(strpos(ltrim($bundle), '"use strict";') === 0, 'keep the first script directive prologue');
    verifyBundle($first === $compactor->compactFiles(array('a.js'), array('a.js', 'b.js'), $directory . '/js'), 'stable cache key');
    $script = (object) array('src' => 'https://example.invalid/jquery.js', 'integrity' => 'synthetic', 'fallbackScript' => 'synthetic');
    $files = array($script, 'a.js');
    verifyBundle($compactor->compactFiles($files, array('a.js'), $directory . '/js') === $files, 'preserve script objects and order');
    verifyBundle($compactor->compactFiles(array('a.js'), array(), $directory . '/js') === array('a.js' => 'a.js'), 'empty configuration');
    verifyBundle($compactor->compactFiles(array('a.js'), array('c.js'), $directory . '/js') === array('a.js' => 'a.js'), 'no intersection');
    verifyBundle($compactor->compactFiles(array('missing.js'), array('missing.js'), $directory . '/js') === array('missing.js' => 'missing.js'), 'all files missing');
    mkdir($directory . '/images');
    file_put_contents($directory . '/images/icon.svg', '<svg/>');
    $css = 'a{background:url( "../images/icon.svg?v=1#mark" )}';
    verifyBundle($compactor->rewrite($css, $directory . '/css/app.css') === 'a{background:url( "/sbf/images/icon.svg?v=1#mark" )}', 'CSS query/fragment and whitespace');
    foreach (array('data:image/png;base64,ABC', 'https://example.invalid/x/../a.png', '//example.invalid/a.png', '/root/a.png', '#mask', 'missing.png') as $url) {
        $css = 'a{background:url("' . $url . '")}';
        verifyBundle($compactor->rewrite($css, $directory . '/css/app.css') === $css, 'preserve external, embedded or missing URL');
    }
    file_put_contents($directory . '/blocked', 'not a directory');
    verifyBundle($compactor->compactFiles(array('c.js'), array('c.js'), $directory . '/blocked') === array('c.js' => 'c.js'), 'failed output directory keeps originals');
    $result = $compactor->compactFiles(array('c.js'), array('c.js', 'b.js'), $directory . '/read-failure', function ($content, $path) use ($directory) {
        // Симулира изчезнал вход след резолвирането на списъка.
        if (basename($path) === 'c.js') unlink($directory . '/b.js');
        return $content;
    });
    verifyBundle($result === array('c.js' => 'c.js'), 'failed input read keeps originals');
} finally {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($directory);
}
echo "PASS: {$checks} compactor checks\n";
