<?php

/**
 * Public download extension points and scoped revocation with synthetic files.
 *
 * @category  bgerp
 * @package   fileman
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// php fileman/tests/downloadPolicy.php. Uses only a temporary directory; no DB or network.
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$framework = dirname(__DIR__, 2);
$fixtureRoot = sys_get_temp_dir() . '/bgerp-download-policy-' . bin2hex(random_bytes(6));
mkdir($fixtureRoot, 0700);
define('EF_DOWNLOAD_DIR', $fixtureRoot . '/downloads');
define('EF_INDEX_PATH', $fixtureRoot);
define('EF_SBF', 'sbf');
define('EF_APP_NAME', 'synthetic');
$checks = 0;
function verifyDownload($condition, $message) { global $checks; if (!$condition) throw new RuntimeException($message); $checks++; }
function expect($condition, $message = '') { if (!$condition) throw new RuntimeException($message); }
function countR($value) { return is_array($value) ? count($value) : 0; }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function getFullPath($path) { global $framework; return $framework . '/' . $path; }
function sbf($path, $unused = '', $absolute = false) { return ($absolute ? 'https://example.invalid' : '') . '/sbf/' . $path; }
require $framework . '/core/BaseClass.class.php';
class core_Manager extends core_BaseClass
{
    public static $records = array(), $fetchCalls = 0;
    public $Files, $db;
    public function __construct($plugins = array())
    {
        $this->_plugins = $plugins;
        $this->Files = new fileman_Files();
        $this->db = new DownloadDb();
    }
    public static function fetch($condition)
    {
        self::$fetchCalls++;
        expect(preg_match('/^#(fileId|prefix) = \'([^\']*)\'$/D', $condition, $match), 'Native fetch condition');
        foreach (self::$records as $rec) if ((string) $rec->{$match[1]} === $match[2]) return clone $rec;

        return false;
    }
    public static function save($rec)
    {
        if (empty($rec->id)) $rec->id = count(self::$records) + 1;
        self::$records[$rec->id] = clone $rec;

        return $rec->id;
    }
    public static function getQuery() { return new DownloadQuery(); }
    public static function delete($condition)
    {
        expect(is_array($condition) && $condition[0] === "#id = '[#1#]'", 'Revocation uses a parameterized row identity');
        $id = $condition[1];
        if (!isset(self::$records[$id])) return 0;
        unset(self::$records[$id]);

        return 1;
    }
}
class DownloadDb { public function escape($value) { return $value; } }
class DownloadQuery
{
    private $rows = array(), $position = 0;
    public function __construct() { foreach (core_Manager::$records as $rec) $this->rows[] = clone $rec; }
    public function where($condition)
    {
        expect(is_array($condition) && $condition[0] === "#fileId = '[#1#]'", 'Revocation filters the canonical file identity');
        $this->rows = array_values(array_filter($this->rows, function ($rec) use ($condition) {
            return (string) $rec->fileId === $condition[1];
        }));
    }
    public function show($fields) { expect(in_array($fields, array('id,prefix,fileName', 'fileId,prefix,fileName'), true), 'Read only revocation metadata'); }
    public function fetch() { return $this->rows[$this->position++] ?? false; }
}
class cls
{
    public static $instance;
    public static function get($class) { return self::$instance ?? (self::$instance = new $class()); }
}
class dt { public static function timestamp2Mysql($stamp) { return date('Y-m-d H:i:s', $stamp); } }
class str
{
    private static $sequence = 0;
    public static function getRand($pattern) { return 'A' . str_pad((string) ++self::$sequence, 5, '0', STR_PAD_LEFT); }
}
class core_Os
{
    public static function requireDir($path) { if (!is_dir($path)) mkdir($path, 0700, true); }
}
class fileman_Files
{
    public static $records = array(), $fetchCalls = 0;
    public static function fetchByFh($fh, $field = null)
    {
        self::$fetchCalls++;
        $rec = self::$records[$fh] ?? null;

        return $field ? ($rec->{$field} ?? null) : ($rec ? clone $rec : false);
    }
}
class ServeBodyReached extends RuntimeException {}
class fileman
{
    public static $lastUse = array(), $extracted = array();
    public static function getExt($name) { return pathinfo($name, PATHINFO_EXTENSION); }
    public static function getType($name) { return 'text/plain'; }
    public static function updateLastUse($rec) { self::$lastUse[] = $rec->id; }
    public static function extract($fh) { self::$extracted[] = $fh; throw new ServeBodyReached(); }
}
class Request { public static $values = array(); public static function get($name) { return self::$values[$name] ?? null; } }
class log_Browsers { public static function checkUserAgent($name) { return false; } }
class DownloadPolicy
{
    public static $override = false, $result, $calls = array(), $headers, $private = false, $deny = false;
    public static function on_BeforeGetDownloadUrl($mvc, &$result, $src, $lifeTime, $type, $absolute)
    {
        self::$calls[] = array($src, $lifeTime, $type, $absolute);
        if (self::$override) { $result = self::$result; return false; }
    }
    public static function on_BeforeServeFile($mvc, $rec, &$headers)
    {
        if (self::$deny) throw new RuntimeException('Synthetic denial');
        if (self::$private) {
            $headers['Cache-Control'] = 'private, no-store';
            $headers['Pragma'] = 'no-cache';
        }
        self::$headers = $headers;
    }
}
require $framework . '/fileman/Download.class.php';
function failedDownloadOperation($call, $message)
{
    $failed = false;
    try { $call(); } catch (RuntimeException $error) { $failed = true; }
    verifyDownload($failed, $message);
}
function serveDownloadFixture()
{
    try { cls::$instance->act_Serve(); } catch (ServeBodyReached $done) { return; }
    throw new RuntimeException('Serve did not reach the file body');
}
function removeDownloadFixture($path)
{
    if (is_link($path) || !is_dir($path)) { @unlink($path); return; }
    foreach (scandir($path) as $name) if ($name !== '.' && $name !== '..') removeDownloadFixture($path . '/' . $name);
    rmdir($path);
}
try {
    $original = $fixtureRoot . '/source.txt';
    file_put_contents($original, 'Synthetic report content');
    fileman_Files::$records['synthetic-handler'] = (object) array('id' => 11, 'fileHnd' => 'synthetic-handler',
        'name' => 'report.txt', 'path' => $original, 'fileLen' => filesize($original));
    cls::$instance = new fileman_Download();
    verifyDownload(fileman_Download::getDownloadUrl('https://example.invalid/plain', 1, 'url') === 'https://example.invalid/plain',
        'A URL source remains unchanged without a plugin');
    $url = fileman_Download::getDownloadUrl('synthetic-handler');
    $first = clone core_Manager::$records[1];
    $publicPath = EF_DOWNLOAD_DIR . '/' . $first->prefix . '/' . $first->fileName;
    verifyDownload($url === 'https://example.invalid/sbf/_dl_/' . $first->prefix . '/report.txt'
        && file_get_contents($publicPath) === file_get_contents($original), 'Unmodified native publication retains its URL and byte content');
    verifyDownload(fileman_Download::getDownloadUrl('synthetic-handler', 3) === $url && count(core_Manager::$records) === 1,
        'Existing public copies are still reused');
    $pathUrl = fileman_Download::getDownloadUrl($original, 1, 'path');
    $pathCopy = core_Manager::$records[2];
    verifyDownload($pathCopy->fileId === md5($original . filemtime($original))
        && $pathUrl === 'https://example.invalid/sbf/_dl_/' . $pathCopy->prefix . '/source.txt',
        'Native path publication retains its identity and URL');
    verifyDownload(fileman_Download::revokePublicFile($pathCopy->fileId) === 1 && is_file($original)
        && !file_exists(EF_DOWNLOAD_DIR . '/' . $pathCopy->prefix . '/source.txt'),
        'Revocation also removes a native path publication without touching its source');
    cls::$instance = new fileman_Download(array(new DownloadPolicy()));
    verifyDownload(fileman_Download::getDownloadUrl('synthetic-handler', 1, 'handler', false) === '/sbf/_dl_/' . $first->prefix . '/report.txt',
        'An observing hook preserves the native relative URL');
    DownloadPolicy::$override = true;
    DownloadPolicy::$result = '/authenticated/file';
    $before = fileman_Files::$fetchCalls + core_Manager::$fetchCalls;
    $rows = serialize(core_Manager::$records);
    foreach (array('handler', 'path', 'url') as $type) {
        verifyDownload(fileman_Download::getDownloadUrl('synthetic-source', 7, $type, false) === '/authenticated/file',
            'The hook can replace each source type before public lookup or publication');
        verifyDownload(end(DownloadPolicy::$calls) === array('synthetic-source', 7, $type, false), 'Hook receives all download arguments');
    }
    verifyDownload($before === fileman_Files::$fetchCalls + core_Manager::$fetchCalls && $rows === serialize(core_Manager::$records),
        'Replacement creates no file metadata and performs no public-source lookup');
    DownloadPolicy::$result = false;
    verifyDownload(fileman_Download::getDownloadUrl('synthetic-handler') === false, 'A hook may explicitly decline publication');
    DownloadPolicy::$override = false;
    Request::$values = array('fh' => 'synthetic-handler');
    serveDownloadFixture();
    $expectedHeaders = array('Content-Type' => 'text/plain', 'Content-Length' => filesize($original),
        'Content-Disposition' => 'inline; filename="report.txt"', 'Cache-Control' => 'public, max-age=3600', 'Pragma' => 'public');
    verifyDownload(DownloadPolicy::$headers === $expectedHeaders, 'Unmodified serve headers retain every native value');
    DownloadPolicy::$private = true;
    serveDownloadFixture();
    $expectedHeaders['Cache-Control'] = 'private, no-store';
    $expectedHeaders['Pragma'] = 'no-cache';
    verifyDownload(DownloadPolicy::$headers === $expectedHeaders, 'A policy changes only the requested serve headers');
    DownloadPolicy::$deny = true;
    $before = count(fileman::$extracted);
    failedDownloadOperation(function () { cls::$instance->act_Serve(); }, 'A serve policy may refuse before reading file content');
    verifyDownload(count(fileman::$extracted) === $before, 'Denied serve does not extract the source file');
    DownloadPolicy::$deny = false;

    $duplicate = (object) array('id' => 2, 'fileId' => 11, 'prefix' => 'B00002', 'fileName' => 'copy.txt');
    core_Manager::$records[2] = $duplicate;
    mkdir(EF_DOWNLOAD_DIR . '/B00002');
    file_put_contents(EF_DOWNLOAD_DIR . '/B00002/copy.txt', 'Synthetic report content');
    file_put_contents(EF_DOWNLOAD_DIR . '/B00002/keep.txt', 'Unrelated file');
    $unrelated = (object) array('id' => 3, 'fileId' => 22, 'prefix' => 'C00003', 'fileName' => 'public.txt');
    core_Manager::$records[3] = $unrelated;
    mkdir(EF_DOWNLOAD_DIR . '/C00003');
    file_put_contents(EF_DOWNLOAD_DIR . '/C00003/public.txt', 'Public fixture');
    verifyDownload(fileman_Download::revokePublicFile(11) === 2, 'All generated addresses for the canonical file are revoked');
    verifyDownload(is_file($original) && !file_exists($publicPath) && !file_exists(EF_DOWNLOAD_DIR . '/B00002/copy.txt')
        && is_file(EF_DOWNLOAD_DIR . '/B00002/keep.txt') && is_file(EF_DOWNLOAD_DIR . '/C00003/public.txt')
        && isset(core_Manager::$records[3]), 'Revocation preserves source, unrelated files and unrelated addresses');
    verifyDownload(!is_dir(EF_DOWNLOAD_DIR . '/' . $first->prefix) && is_dir(EF_DOWNLOAD_DIR . '/B00002'),
        'Only empty generated directories are removed');
    verifyDownload(fileman_Download::revokePublicFile(11) === 0, 'Revocation is idempotent');

    $hash = md5('synthetic-path-source');
    core_Manager::$records[4] = (object) array('id' => 4, 'fileId' => $hash, 'prefix' => 'D00004', 'fileName' => 'missing.txt');
    verifyDownload(fileman_Download::revokePublicFile($hash) === 1, 'Path-hash records and already absent copies are supported');
    foreach (array(null, array(), 0, -1, '0', '../source', "11' OR 1", 'xyz') as $bad) {
        failedDownloadOperation(function () use ($bad) { fileman_Download::revokePublicFile($bad); }, 'Invalid source identities are rejected');
    }
    foreach (array(array('../bad', 'x.txt'), array('A00005', '../source.txt'), array('A00005', 'nested\\file.txt'),
        array('A00005', "bad\0.txt"), array('A00005', '.'), array('A00005', '..')) as $bad) {
        core_Manager::$records[5] = (object) array('id' => 5, 'fileId' => 33, 'prefix' => $bad[0], 'fileName' => $bad[1]);
        failedDownloadOperation(function () { fileman_Download::revokePublicFile(33); }, 'Unsafe stored paths cannot escape the public-copy root');
        verifyDownload(isset(core_Manager::$records[5]) && is_file($original), 'Rejected paths preserve metadata and source files');
    }
    $outside = $fixtureRoot . '/outside';
    mkdir($outside);
    file_put_contents($outside . '/source.txt', 'Outside fixture');
    symlink($outside, EF_DOWNLOAD_DIR . '/E00005');
    core_Manager::$records[5] = (object) array('id' => 5, 'fileId' => 33, 'prefix' => 'E00005', 'fileName' => 'source.txt');
    failedDownloadOperation(function () { fileman_Download::revokePublicFile(33); }, 'Directory symlinks cannot redirect cleanup');
    verifyDownload(is_file($outside . '/source.txt') && isset(core_Manager::$records[5]), 'Outside symlink targets remain untouched');
    unlink(EF_DOWNLOAD_DIR . '/E00005');
    mkdir(EF_DOWNLOAD_DIR . '/E00005');
    mkdir(EF_DOWNLOAD_DIR . '/E00005/source.txt');
    failedDownloadOperation(function () { fileman_Download::revokePublicFile(33); }, 'Unexpected directories are reported as cleanup failures');
    verifyDownload(isset(core_Manager::$records[5]), 'Failed cleanup retains metadata for retry');
    rmdir(EF_DOWNLOAD_DIR . '/E00005/source.txt');
    symlink($outside . '/source.txt', EF_DOWNLOAD_DIR . '/E00005/source.txt');
    verifyDownload(fileman_Download::revokePublicFile(33) === 1 && is_file($outside . '/source.txt'),
        'A generated file symlink is removed without following it to the original');

    // A former temporary source has a different path/mtime identity, but identical bytes.
    $ordinaryContent = str_repeat('x', filesize($original));
    file_put_contents(EF_DOWNLOAD_DIR . '/C00003/public.txt', $ordinaryContent);
    $aliasId = md5('/no-longer-existing/temporary-source.txt' . '123456');
    core_Manager::$records[6] = (object) array('id' => 6, 'fileId' => $aliasId, 'prefix' => 'F00006', 'fileName' => 'renamed.txt');
    mkdir(EF_DOWNLOAD_DIR . '/F00006');
    $alias = EF_DOWNLOAD_DIR . '/F00006/renamed.txt';
    copy($original, $alias);
    core_Manager::$records[7] = (object) array('id' => 7, 'fileId' => 44, 'prefix' => 'G00007', 'fileName' => 'other-name.txt');
    mkdir(EF_DOWNLOAD_DIR . '/G00007');
    copy($original, EF_DOWNLOAD_DIR . '/G00007/other-name.txt');
    failedDownloadOperation(function () use ($alias) { fileman_Download::revokePublicContent($alias); },
        'Content cleanup refuses an original inside the public-copy directory');
    verifyDownload(fileman_Download::revokePublicContent($original) === 2 && !file_exists($alias)
        && !isset(core_Manager::$records[6]) && !isset(core_Manager::$records[7]),
        'Matching content revokes old alias-path and handler copies regardless of filename');
    verifyDownload(isset(core_Manager::$records[3]) && file_get_contents(EF_DOWNLOAD_DIR . '/C00003/public.txt') === $ordinaryContent
        && file_get_contents($original) === 'Synthetic report content', 'Content cleanup preserves different bytes of the same length and the source');
    verifyDownload(fileman_Download::revokePublicContent($original) === 0, 'Content cleanup is idempotent');

    core_Manager::$records[6] = (object) array('id' => 6, 'fileId' => $aliasId, 'prefix' => 'F00006', 'fileName' => 'renamed.txt');
    mkdir(EF_DOWNLOAD_DIR . '/F00006');
    symlink($original, $alias);
    failedDownloadOperation(function () use ($original) { fileman_Download::revokePublicContent($original); },
        'Content matching refuses symlinks without following the source');
    verifyDownload(isset(core_Manager::$records[6]) && is_link($alias) && is_file($original), 'Failed symlink inspection preserves metadata and source');
    unlink($alias);
    mkdir($alias);
    failedDownloadOperation(function () use ($original) { fileman_Download::revokePublicContent($original); },
        'Invalid public copy types are reported instead of silently losing retry metadata');
    verifyDownload(isset(core_Manager::$records[6]), 'Failed content cleanup retains metadata for retry');
    rmdir($alias);
    copy($original, $alias);
    core_Manager::$records[6]->fileName = '../source.txt';
    failedDownloadOperation(function () use ($original) { fileman_Download::revokePublicContent($original); },
        'Content matching validates paths before opening files');
    core_Manager::$records[6]->fileName = 'renamed.txt';
    verifyDownload(fileman_Download::revokePublicContent($original) === 1 && !file_exists($alias),
        'A retry succeeds after invalid metadata is repaired');
    verifyDownload(file_get_contents($original) === 'Synthetic report content', 'All checks leave the source bytes unchanged');
} finally {
    removeDownloadFixture($fixtureRoot);
}
echo "PASS: {$checks} download policy checks\n";
