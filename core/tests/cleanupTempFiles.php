<?php

/**
 * Isolated filesystem checks: php core/tests/cleanupTempFiles.php
 *
 * @category ef
 * @package core
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'cli-server') {
    exit("Run with PHP CLI or its local development server.\n");
}
if (PHP_SAPI === 'cli-server') {
    header('Content-Type: text/plain; charset=UTF-8');
}

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

require dirname(__DIR__) . '/Os.class.php';
require dirname(__DIR__) . '/String.class.php';
class_alias('core_String', 'str');

class core_Packs
{
    public static function getConfig($package)
    {
        return (object) array('CORE_TEMP_PATH_MAX_AGE' => 10 * 86400);
    }
}

function cleanupCheck($condition, $message)
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
}

function cleanupFile($path, $mtime, $atime)
{
    file_put_contents($path, 'synthetic fixture');
    touch($path, $mtime, $atime);
    clearstatcache(true, $path);
}

function cleanupLink($target, $path, $time)
{
    symlink($target, $path);
    // PHP touch() follows links; the Unix utility can set the link's own timestamps.
    $process = proc_open(array('touch', '-h', '-a', '-m', '-d', '@' . $time, '--', $path), array(), $pipes);
    if (!is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Unable to set fixture link timestamps');
    }
    clearstatcache(true, $path);
}

function cleanupFixtures($path)
{
    if (is_link($path) || !is_dir($path)) {
        unlink($path);
        return;
    }
    foreach (scandir($path) as $name) {
        if ($name !== '.' && $name !== '..') {
            cleanupFixtures($path . '/' . $name);
        }
    }
    rmdir($path);
}

$checks = 0;
$base = sys_get_temp_dir() . '/core-cleanup-' . bin2hex(random_bytes(8));
mkdir($base, 0700);
$age = 10 * 86400;
$old = time() - 11 * 86400;
$recent = time() - 3600;

try {
    $root = $base . '/files';
    mkdir($root);
    cleanupFile($root . '/old', $old, $old);
    cleanupFile($root . '/modified', $recent, $old);
    cleanupFile($root . '/accessed', $old, $recent);
    cleanupFile($root . '/nine-days', time() - 9 * 86400, time() - 9 * 86400);
    cleanupCheck(core_Os::deleteOldFiles($root, $age) === 1, 'Only an inactive file older than ten days is removed');
    cleanupCheck(!file_exists($root . '/old'), 'Old file is removed');
    foreach (array('modified', 'accessed', 'nine-days') as $name) {
        cleanupCheck(file_exists($root . '/' . $name), 'Preserve ' . $name);
    }
    cleanupCheck(is_dir($root), 'Preserve the cleanup root');
    cleanupCheck(core_Os::deleteOldFiles($base . '/missing', $age) === 0, 'A missing root is harmless');
    cleanupCheck(core_Os::getFileLastAccessTime($root . '/modified') === $old, 'The public access-time helper keeps its contract');

    $root = $base . '/links';
    mkdir($root);
    mkdir($base . '/outside');
    cleanupFile($base . '/outside/old', $old, $old);
    cleanupFile($base . '/outside/recent', $recent, $recent);
    cleanupLink($base . '/outside/old', $root . '/fresh-input', $recent);
    cleanupLink($base . '/outside/recent', $root . '/old-input', $old);
    cleanupLink($base . '/outside', $root . '/old-directory-link', $old);
    cleanupLink($base . '/absent', $root . '/old-broken', $old);
    cleanupLink($base . '/absent', $root . '/fresh-broken', $recent);
    cleanupCheck(core_Os::deleteOldFiles($root, $age) === 3, 'Remove old links using their own timestamps');
    cleanupCheck(is_link($root . '/fresh-input'), 'Preserve a recent input link to an old file');
    cleanupCheck(is_link($root . '/fresh-broken'), 'Preserve a recent broken link');
    foreach (array('old-input', 'old-directory-link', 'old-broken') as $name) {
        cleanupCheck(!is_link($root . '/' . $name), 'Remove expired link ' . $name);
    }
    cleanupCheck(file_exists($base . '/outside/old') && file_exists($base . '/outside/recent'), 'Preserve every link target');
    cleanupLink($base . '/outside', $base . '/root-link', $old);
    cleanupCheck(core_Os::deleteOldFiles($base . '/root-link/', $age) === 0, 'Do not traverse a linked root with a trailing slash');
    cleanupCheck(file_exists($base . '/outside/old'), 'Preserve files behind a linked root');
    cleanupLink($root, $root . '/cycle', $old);
    cleanupCheck(core_Os::deleteOldFiles($root, $age) === 1, 'A directory link cycle is removed without recursion');

    $root = $base . '/directories';
    mkdir($root);
    mkdir($root . '/nested/empty', 0700, true);
    mkdir($root . '/expired');
    cleanupFile($root . '/expired/.hidden', $old, $old);
    mkdir($root . '/active');
    cleanupFile($root . '/active/fresh', $recent, $recent);
    foreach (array('nested/empty', 'nested', 'expired', 'active') as $name) {
        touch($root . '/' . $name, $old, $old);
    }
    mkdir($root . '/young-empty');
    cleanupCheck(core_Os::deleteOldFiles($root, $age) === 1, 'Directory removals do not inflate the file count');
    foreach (array('nested', 'expired') as $name) {
        cleanupCheck(!is_dir($root . '/' . $name), 'Remove empty old directory ' . $name);
    }
    cleanupCheck(is_dir($root . '/young-empty'), 'Preserve a newly created empty work directory');
    cleanupCheck(file_exists($root . '/active/fresh'), 'Preserve a directory containing a recent file');

    $root = $base . '/patterns';
    mkdir($root);
    mkdir($root . '/nested');
    mkdir($root . '/keep-empty');
    touch($root . '/keep-empty', $old, $old);
    foreach (array('remove.css', 'keep.css', 'keep.txt', 'nested/remove.css') as $name) {
        cleanupFile($root . '/' . $name, $old, $old);
    }
    cleanupLink($base . '/absent', $root . '/keep-link', $old);
    cleanupCheck(core_Os::deleteOldFiles($root, $age, '#/keep[^/]*$#', '#\.css$#') === 2, 'Keep positive and negative path filters');
    cleanupCheck(file_exists($root . '/keep.css') && file_exists($root . '/keep.txt'), 'Preserve filtered files');
    cleanupCheck(is_link($root . '/keep-link') && is_dir($root . '/keep-empty'), 'Preserve filtered links and directories');

    define('EF_TEMP_PATH', $base . '/cron-tmp');
    define('EF_SBF_PATH', $base . '/cron-sbf');
    mkdir(EF_TEMP_PATH);
    mkdir(EF_SBF_PATH);
    cleanupFile(EF_TEMP_PATH . '/old', $old, $old);
    cleanupFile(EF_TEMP_PATH . '/nine-days', time() - 9 * 86400, time() - 9 * 86400);
    $report = core_Os::cron_clearOldFiles();
    cleanupCheck(strpos($report, '1 файл от ' . EF_TEMP_PATH) !== false, 'Report the tmp count');
    cleanupCheck(strpos($report, EF_SBF_PATH) === false, 'An empty sbf has no deletions to report');
    cleanupCheck(file_exists(EF_TEMP_PATH . '/nine-days'), 'Cron retains the ten-day lifetime');
    foreach (array('first', 'second', '_keep') as $name) {
        mkdir(EF_SBF_PATH . '/' . $name);
        cleanupFile(EF_SBF_PATH . '/' . $name . '/old', $old, $old);
    }
    cleanupFile(EF_SBF_PATH . '/second/another', $old, $old);
    cleanupLink($base . '/outside', EF_SBF_PATH . '/linked', $old);
    $report = core_Os::cron_clearOldFiles();
    cleanupCheck(strpos($report, '3 файла от ' . EF_SBF_PATH) !== false, 'Sum deletions across sbf subdirectories');
    cleanupCheck(strpos($report, EF_TEMP_PATH) === false, 'Do not report tmp deletions from an earlier run');
    cleanupCheck(file_exists(EF_SBF_PATH . '/_keep/old'), 'Preserve excluded sbf directories');
    cleanupCheck(file_exists($base . '/outside/old'), 'Preserve targets of sbf directory links');

    cleanupFixtures(EF_SBF_PATH);
    cleanupLink($base . '/outside', EF_SBF_PATH, $old);
    cleanupCheck(core_Os::cron_clearOldFiles() === '', 'Do not scan a linked sbf root');
    cleanupCheck(file_exists($base . '/outside/old'), 'Preserve files behind the linked sbf root');
    unlink(EF_SBF_PATH);
    cleanupCheck(core_Os::cron_clearOldFiles() === '', 'A missing sbf directory is harmless');

    echo "OK: {$checks} filesystem cleanup checks on PHP " . PHP_VERSION . ".\n";
} finally {
    cleanupFixtures($base);
    restore_error_handler();
}
