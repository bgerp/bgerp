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

class core_Packs
{
    public static function getConfig($package)
    {
        return (object) array('CORE_TEMP_PATH_MAX_AGE' => 10 * 86400);
    }
}

class core_Manager
{
}

class fconv_Setup
{
    public static function get($name)
    {
        return FCONV_TEMP_PATH;
    }
}

require dirname(__DIR__, 2) . '/fconv/Remote.class.php';

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
define('EF_TEMP_PATH', $base . '/temp & <fixture>');
define('EF_SBF_PATH', $base . '/sbf "fixture"');
define('FILEMAN_TEMP_PATH', $base . '/fileman');
define('FCONV_TEMP_PATH', $base . '/fconv & <fixture>');
define('THUMB_IMG_PATH', $base . '/thumb');
foreach (array(EF_TEMP_PATH, EF_SBF_PATH, FILEMAN_TEMP_PATH, FCONV_TEMP_PATH, THUMB_IMG_PATH) as $path) {
    mkdir($path);
}
$work = EF_TEMP_PATH . '/fixtures';
mkdir($work);
$age = 10 * 86400;
$old = time() - 11 * 86400;
$recent = time() - 3600;

try {
    // Validate system paths without passing them to any deletion method.
    $validatePath = new ReflectionMethod('core_Os', 'getCleanupPath');
    $validatePath->setAccessible(true);
    $normalizePath = new ReflectionMethod('core_Os', 'normalizeCleanupPath');
    $normalizePath->setAccessible(true);
    foreach (array('', '/', '///', '/.', '//./', null, array(), "invalid\0path") as $path) {
        cleanupCheck($normalizePath->invoke(null, $path) === false, 'Reject invalid or filesystem-root paths even in configuration');
        if ($path !== null) {
            cleanupCheck($validatePath->invoke(null, $work, $path) === false, 'An explicit root does not bypass path protection');
        }
    }
    if (DIRECTORY_SEPARATOR === '/') {
        foreach (array('/etc', '/var', '/usr', '/home', '/opt', '/srv') as $path) {
            if (!is_dir($path)) {
                continue;
            }
            foreach (array($path, $path . '/', $path . '/.', $path . '/../' . basename($path)) as $alias) {
                cleanupCheck($normalizePath->invoke(null, $alias) === false, 'Reject top-level directories and their aliases');
                cleanupCheck($validatePath->invoke(null, $alias, $alias) === false, 'An explicit root cannot authorize a top-level directory');
            }
        }
        if (is_dir('/var/cache')) {
            cleanupCheck($validatePath->invoke(null, '/var/cache', '/var') === false, 'Reject a top-level allowed root even when the selected directory is deeper');
        }
    }
    $appPath = realpath(dirname(__DIR__, 2));
    for ($path = $appPath; ; $path = $parent) {
        cleanupCheck($normalizePath->invoke(null, $path) === false, 'Preserve application roots and their ancestors');
        cleanupCheck($validatePath->invoke(null, $work, $path) === false, 'Reject protected ancestors as explicit roots');
        cleanupCheck($normalizePath->invoke(null, $path . '/.') === false, 'Preserve aliases of protected roots');
        $parent = dirname($path);
        cleanupCheck($normalizePath->invoke(null, $path . '/..') === false, 'Resolve parent components before validation');
        if ($path === $parent) {
            break;
        }
    }
    cleanupCheck($normalizePath->invoke(null, sys_get_temp_dir()) === false, 'Preserve the shared system temp directory');
    cleanupCheck($validatePath->invoke(null, $work, sys_get_temp_dir()) === false, 'Reject the shared system temp directory as an explicit root');
    cleanupCheck($validatePath->invoke(null, $work . '/.') === realpath($work), 'Resolve a permitted work directory');

    foreach (array($base . '/unrelated', EF_TEMP_PATH . '-sibling') as $path) {
        mkdir($path);
        cleanupFile($path . '/old', $old, $old);
        cleanupCheck(core_Os::deleteOldFiles($path, $age) === 0, 'Reject directories outside the configured roots');
        cleanupCheck(file_exists($path . '/old'), 'Preserve old files outside the configured roots');
    }
    cleanupCheck(core_Os::deleteOldFiles(EF_TEMP_PATH . '/../unrelated', $age) === 0, 'Resolve parent components before checking allowed roots');
    cleanupCheck(file_exists($base . '/unrelated/old'), 'Preserve files reached through a parent component');
    foreach (array(FILEMAN_TEMP_PATH, FCONV_TEMP_PATH, THUMB_IMG_PATH) as $path) {
        cleanupFile($path . '/old', $old, $old);
        cleanupCheck(core_Os::deleteOldFiles($path, $age) === 1, 'Support configured package temp and cache roots');
    }

    $moduleRoot = $base . '/module-generated';
    cleanupCheck($validatePath->invoke(null, $base, $base) === realpath($base), 'A dedicated directory directly inside system temp remains usable');
    mkdir($moduleRoot . '/finished/nested', 0700, true);
    cleanupFile($moduleRoot . '/finished/nested/old', $old, $old);
    cleanupFile($moduleRoot . '/finished/nested/recent', $recent, $recent);
    cleanupFile($moduleRoot . '/outside-selection', $old, $old);
    cleanupCheck(core_Os::deleteOldFiles($moduleRoot, $age) === 0, 'Custom module directories require an explicit root');
    cleanupCheck(core_Os::deleteOldFiles($moduleRoot . '/finished', $age, null, null, $moduleRoot) === 1, 'A module can clean a subtree outside the standard directories');
    cleanupCheck(file_exists($moduleRoot . '/finished/nested/recent'), 'Explicit roots preserve recent files');
    cleanupCheck(file_exists($moduleRoot . '/outside-selection'), 'Preserve files outside the selected subtree');
    cleanupCheck(core_Os::deleteOldFiles($moduleRoot . '/../unrelated', $age, null, null, $moduleRoot) === 0, 'An explicit root prevents parent traversal');
    mkdir($moduleRoot . '-sibling');
    cleanupFile($moduleRoot . '-sibling/old', $old, $old);
    cleanupCheck(core_Os::deleteOldFiles($moduleRoot . '-sibling', $age, null, null, $moduleRoot) === 0, 'A shared name prefix is not a child directory');
    cleanupCheck(file_exists($moduleRoot . '-sibling/old'), 'Preserve files in a similarly named sibling');
    cleanupCheck(core_Os::deleteOldFiles($moduleRoot, $age, null, null, $moduleRoot) === 1, 'Allow cleaning the explicit root itself');
    cleanupCheck(is_dir($moduleRoot), 'Preserve the explicit root directory');

    $root = $work . '/files';
    mkdir($root);
    cleanupFile($root . '/old', $old, $old);
    cleanupFile($root . '/modified', $recent, $old);
    cleanupFile($root . '/accessed', $old, $recent);
    cleanupFile($root . '/nine-days', time() - 9 * 86400, time() - 9 * 86400);
    foreach (array(0, -1, null, 'invalid', array(), true, INF, NAN) as $invalidAge) {
        cleanupCheck(core_Os::deleteOldFiles($root, $invalidAge) === 0, 'Reject invalid retention periods');
        cleanupCheck(file_exists($root . '/old'), 'An invalid period leaves old files intact');
    }
    foreach (array('', false, array(), $base . '/missing', $moduleRoot) as $invalidRoot) {
        cleanupCheck(core_Os::deleteOldFiles($root, $age, null, null, $invalidRoot) === 0, 'An invalid or unrelated explicit root does not fall back to defaults');
        cleanupCheck(file_exists($root . '/old'), 'A rejected explicit root preserves the selected files');
    }
    cleanupCheck(core_Os::deleteOldFiles($root, $age) === 1, 'Only an inactive file older than ten days is removed');
    cleanupCheck(!file_exists($root . '/old'), 'Old file is removed');
    foreach (array('modified', 'accessed', 'nine-days') as $name) {
        cleanupCheck(file_exists($root . '/' . $name), 'Preserve ' . $name);
    }
    cleanupCheck(is_dir($root), 'Preserve the cleanup root');
    cleanupCheck(core_Os::deleteOldFiles($base . '/missing', $age) === 0, 'A missing root is harmless');
    cleanupCheck(core_Os::getFileLastAccessTime($root . '/modified') === $old, 'The public access-time helper keeps its contract');

    $root = $work . '/links';
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
    cleanupCheck(core_Os::deleteOldFiles($base . '/root-link/.', $age) === 0, 'Preserve targets behind a dotted link path');
    mkdir($base . '/outside/child');
    cleanupFile($base . '/outside/child/old', $old, $old);
    cleanupCheck(core_Os::deleteOldFiles($base . '/root-link/child', $age) === 0, 'Do not follow links in parent path components');
    cleanupCheck(core_Os::deleteOldFiles($base . '/root-link/child', $age, null, null, $base . '/outside') === 0, 'Explicit roots do not permit linked parent components');
    cleanupCheck(core_Os::deleteOldFiles($base . '/outside', $age, null, null, $base . '/root-link') === 0, 'Reject a linked explicit root');
    cleanupCheck(file_exists($base . '/outside/child/old'), 'Preserve files behind a linked parent');
    cleanupCheck(file_exists($base . '/outside/old'), 'Preserve files behind a linked root');
    cleanupLink($root, $base . '/allowed-link', $old);
    cleanupCheck(core_Os::deleteOldFiles($base . '/allowed-link/.', $age) === 0, 'Reject a link alias even when its target is permitted');
    cleanupCheck(is_link($root . '/fresh-input'), 'Preserve files behind a rejected alias');
    cleanupLink($root, $root . '/cycle', $old);
    cleanupCheck(core_Os::deleteOldFiles($root, $age) === 1, 'A directory link cycle is removed without recursion');
    cleanupLink($base . '/outside', $moduleRoot . '/directory-link', $old);
    cleanupCheck(core_Os::deleteOldFiles($moduleRoot, $age, null, null, $moduleRoot) === 1, 'Custom module cleanup removes only an expired link');
    cleanupCheck(file_exists($base . '/outside/old'), 'Custom module cleanup preserves link targets');

    $root = $work . '/directories';
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

    $root = $work . '/patterns';
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

    foreach (array('#[invalid#', array(), true, 123) as $invalidPattern) {
        cleanupCheck(core_Os::deleteOldFiles($root, $age, $invalidPattern) === 0, 'Reject invalid negative filters before deletion');
        cleanupCheck(core_Os::deleteOldFiles($root, $age, null, $invalidPattern) === 0, 'Reject invalid positive filters before deletion');
        cleanupCheck(file_exists($root . '/keep.css'), 'Invalid filters preserve old files');
    }
    $root = $work . '/pattern-errors';
    mkdir($root);
    $path = $root . '/' . str_repeat('a', 80) . '!';
    cleanupFile($path, $old, $old);
    $limitedPattern = '#(*NO_JIT)(*LIMIT_MATCH=10)(a+)+$#';
    cleanupCheck(preg_match($limitedPattern, '') === 0 && preg_match($limitedPattern, $path) === false, 'The fixture triggers a match-time error in a valid regular expression');
    cleanupCheck(core_Os::deleteOldFiles($root, $age, $limitedPattern) === 0, 'A match-time negative filter error preserves the file');
    cleanupCheck(core_Os::deleteOldFiles($root, $age, null, $limitedPattern) === 0, 'A match-time positive filter error preserves the file');
    cleanupCheck(file_exists($path), 'Preserve files after match-time errors');

    $root = $work . '/literal names';
    mkdir($root);
    foreach (array('space name', 'quote\'name', 'double"name', 'semi;colon', '$(literal)', '`literal`', '*.css', 'file?.txt', '[name]', "line\nbreak", 'кирилица') as $name) {
        cleanupFile($root . '/' . $name, $old, $old);
    }
    cleanupCheck(core_Os::deleteOldFiles($root, $age, null, '#/' . preg_quote('*.css', '#') . '$#') === 1, 'Escaped regular expressions select a literal filename');
    cleanupCheck(core_Os::deleteOldFiles($root, $age) === 10, 'Special characters in filenames have no shell or wildcard meaning');
    if (DIRECTORY_SEPARATOR === '/') {
        mkdir($root . '/name');
        mkdir($root . '/name\\');
        cleanupFile($root . '/name/old', $old, $old);
        cleanupFile($root . '/name\\/old', $old, $old);
        cleanupCheck(core_Os::deleteOldFiles($root . '/name\\', $age) === 1, 'A trailing backslash is part of a Unix directory name');
        cleanupCheck(file_exists($root . '/name/old') && !file_exists($root . '/name\\/old'), 'Do not delete from the similarly named directory without the backslash');
    }

    $root = $work . '/existing-callers';
    mkdir($root);
    $setupPattern = '#^_[a-z0-9\-\/_]+#i';
    $minifyPattern = '#[a-z0-9\-\/_]+(.js|.css)$#i';
    foreach (array('bundle.js', 'bundle.css', 'keep.txt') as $name) {
        cleanupFile($root . '/' . $name, $old, $old);
    }
    cleanupCheck(core_Os::deleteOldFiles($root, 1, $setupPattern, $minifyPattern) === 2, 'The existing four-argument minify call removes JS and CSS');
    cleanupCheck(file_exists($root . '/keep.txt'), 'The existing minify filter preserves other files');
    cleanupCheck(core_Os::deleteOldFiles($root, 2 * 30 * 86400, $setupPattern) === 0, 'The core setup call retains its sixty-day period');
    cleanupFile($root . '/expired.txt', time() - 61 * 86400, time() - 61 * 86400);
    cleanupCheck(core_Os::deleteOldFiles($root, 2 * 30 * 86400, $setupPattern) === 1, 'The existing three-argument core setup call still removes expired files');
    cleanupCheck(core_Os::deleteOldFiles($root, 2 * 60 * 60) === 1, 'The existing remote-converter two-hour period remains supported');
    cleanupFile($root . '/thumb', $old, $old);
    cleanupCheck(core_Os::deleteOldFiles($root, 1) === 1, 'The thumbnail clear call retains its short period');
    cleanupFile($root . '/default', time() - 2 * 86400, time() - 2 * 86400);
    cleanupCheck(core_Os::deleteOldFiles($root) === 1, 'The public helper retains its one-day default');

    cleanupFixtures($work);
    cleanupFile(EF_TEMP_PATH . '/old', $old, $old);
    cleanupFile(EF_TEMP_PATH . '/nine-days', time() - 9 * 86400, time() - 9 * 86400);
    $report = core_Os::cron_clearOldFiles();
    cleanupCheck(strpos($report, '1 файл от ' . htmlspecialchars(EF_TEMP_PATH, ENT_QUOTES, 'UTF-8')) !== false, 'Report the tmp count with an HTML-escaped path');
    cleanupCheck(strpos($report, EF_TEMP_PATH) === false, 'Do not insert raw HTML from a temp path into the cron report');
    cleanupCheck(strpos($report, htmlspecialchars(EF_SBF_PATH, ENT_QUOTES, 'UTF-8')) === false, 'An empty sbf has no deletions to report');
    cleanupCheck(file_exists(EF_TEMP_PATH . '/nine-days'), 'Cron retains the ten-day lifetime');
    foreach (array('first', 'second', '_keep') as $name) {
        mkdir(EF_SBF_PATH . '/' . $name);
        cleanupFile(EF_SBF_PATH . '/' . $name . '/old', $old, $old);
    }
    cleanupFile(EF_SBF_PATH . '/second/another', $old, $old);
    cleanupLink($base . '/outside', EF_SBF_PATH . '/linked', $old);
    $report = core_Os::cron_clearOldFiles();
    cleanupCheck(strpos($report, '3 файла от ' . htmlspecialchars(EF_SBF_PATH, ENT_QUOTES, 'UTF-8')) !== false, 'Sum deletions across sbf subdirectories with an HTML-escaped path');
    cleanupCheck(strpos($report, EF_SBF_PATH) === false, 'Escape quotes in the sbf path in the cron report');
    cleanupCheck(strpos($report, htmlspecialchars(EF_TEMP_PATH, ENT_QUOTES, 'UTF-8')) === false, 'Do not report tmp deletions from an earlier run');
    cleanupCheck(file_exists(EF_SBF_PATH . '/_keep/old'), 'Preserve excluded sbf directories');
    cleanupCheck(file_exists($base . '/outside/old'), 'Preserve targets of sbf directory links');

    cleanupFixtures(EF_SBF_PATH);
    cleanupLink($base . '/outside', EF_SBF_PATH, $old);
    cleanupCheck(core_Os::cron_clearOldFiles() === '', 'Do not scan a linked sbf root');
    cleanupCheck(file_exists($base . '/outside/old'), 'Preserve files behind the linked sbf root');
    unlink(EF_SBF_PATH);
    cleanupCheck(core_Os::cron_clearOldFiles() === '', 'A missing sbf directory is harmless');
    cleanupCheck(file_exists($base . '/unrelated/old') && file_exists(EF_TEMP_PATH . '-sibling/old'), 'Cron preserves unrelated directories');

    foreach (array(FILEMAN_TEMP_PATH, FCONV_TEMP_PATH) as $path) {
        cleanupFile($path . '/expired', $old, $old);
        cleanupFile($path . '/recent', $recent, $recent);
    }
    $report = core_Os::cron_clearOldFiles();
    foreach (array(FILEMAN_TEMP_PATH, FCONV_TEMP_PATH) as $path) {
        cleanupCheck(!file_exists($path . '/expired'), 'Cron cleans configured package temp roots outside the common temp directory');
        cleanupCheck(file_exists($path . '/recent'), 'Cron retains recent files in custom package temp roots');
        cleanupCheck(strpos($report, '1 файл от ' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8')) !== false, 'Report each custom package temp root separately');
    }
    cleanupCheck(core_Os::cron_clearOldFiles() === '', 'A second run does not count earlier package deletions');

    $remoteRoot = FCONV_TEMP_PATH . '/remoteDownloadedFiles';
    mkdir($remoteRoot);
    mkdir($remoteRoot . '/fresh');
    mkdir($remoteRoot . '/old-empty');
    touch($remoteRoot . '/old-empty', $old, $old);
    mkdir($base . '/remote-outside/empty', 0700, true);
    cleanupFile($base . '/remote-outside/source', $old, $old);
    cleanupFile($remoteRoot . '/old', time() - 10800, time() - 10800);
    cleanupFile($remoteRoot . '/recent', $recent, $recent);
    cleanupLink($base . '/remote-outside', $remoteRoot . '/fresh-directory-link', $recent);
    cleanupLink($base . '/remote-outside/source', $remoteRoot . '/fresh-input-link', $recent);
    $remote = new fconv_Remote();
    $report = $remote->cron_DeleteOldDir();
    cleanupCheck(!file_exists($remoteRoot . '/old'), 'Remote cleanup retains its two-hour lifetime');
    cleanupCheck(file_exists($remoteRoot . '/recent'), 'Remote cleanup preserves recent files');
    cleanupCheck(is_dir($remoteRoot . '/fresh'), 'Remote cleanup preserves recent empty work directories');
    cleanupCheck(!is_dir($remoteRoot . '/old-empty'), 'Remote cleanup removes old empty work directories');
    cleanupCheck(is_dir($base . '/remote-outside/empty'), 'Remote cleanup preserves directories behind a link');
    cleanupCheck(file_exists($base . '/remote-outside/source'), 'Remote cleanup preserves original input files');
    cleanupCheck(is_link($remoteRoot . '/fresh-directory-link') && is_link($remoteRoot . '/fresh-input-link'), 'Remote cleanup preserves recent links');
    cleanupCheck(strpos($report, '1 файла в ' . htmlspecialchars($remoteRoot, ENT_QUOTES, 'UTF-8')) !== false, 'Remote cleanup escapes the reported path');
    mkdir($remoteRoot . '/another-old-empty');
    touch($remoteRoot . '/another-old-empty', $old, $old);
    cleanupCheck($remote->cron_DeleteOldDir() === null, 'Removing only directories does not report file deletions');
    cleanupCheck(!is_dir($remoteRoot . '/another-old-empty'), 'Remove old empty remote directories even without expired files');

    define('EF_UPLOADS_PATH', $base . '/uploads/application');
    mkdir(EF_UPLOADS_PATH, 0700, true);
    mkdir($base . '/uploads/fileman/bucket', 0700, true);
    cleanupFile($base . '/uploads/fileman/bucket/original', $old, $old);
    foreach (array(EF_UPLOADS_PATH, $base . '/uploads/fileman', $base . '/uploads/fileman/bucket', $base . '/uploads') as $path) {
        cleanupCheck($validatePath->invoke(null, $path, $path) === false, 'Protect permanent uploads and their ancestors even with an explicit root');
    }
    cleanupCheck(core_Os::deleteOldFiles($base . '/uploads/fileman', $age, null, null, $base . '/uploads/fileman') === 0, 'Reject permanent file storage as a cleanup root');
    cleanupCheck(file_exists($base . '/uploads/fileman/bucket/original'), 'Preserve original uploaded content');
    define('FILEMAN_UPLOADS_PATH', $base . '/custom-permanent-files');
    mkdir(FILEMAN_UPLOADS_PATH . '/bucket', 0700, true);
    cleanupFile(FILEMAN_UPLOADS_PATH . '/bucket/original', $old, $old);
    cleanupCheck($validatePath->invoke(null, FILEMAN_UPLOADS_PATH . '/bucket', FILEMAN_UPLOADS_PATH . '/bucket') === false, 'Protect a custom permanent file store');
    cleanupCheck(core_Os::deleteOldFiles(FILEMAN_UPLOADS_PATH, $age, null, null, FILEMAN_UPLOADS_PATH) === 0, 'Reject custom permanent storage as a cleanup root');
    cleanupCheck(file_exists(FILEMAN_UPLOADS_PATH . '/bucket/original'), 'Preserve custom original uploaded content');
    cleanupFile(FILEMAN_TEMP_PATH . '/expired', $old, $old);
    cleanupFile(FCONV_TEMP_PATH . '/expired', $old, $old);
    core_Os::cron_clearOldFiles();
    cleanupCheck(!file_exists(FILEMAN_TEMP_PATH . '/expired') && !file_exists(FCONV_TEMP_PATH . '/expired'), 'Permanent upload protection does not block package temp cleanup');
    cleanupCheck(file_exists(FILEMAN_UPLOADS_PATH . '/bucket/original'), 'Cron preserves permanent uploads');

    echo "OK: {$checks} filesystem cleanup checks on PHP " . PHP_VERSION . ".\n";
} finally {
    cleanupFixtures($base);
    restore_error_handler();
}
