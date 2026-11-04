<?php

/**
 * Native thumbnail paths with an optional file preview policy and synthetic sources.
 *
 * @category  bgerp
 * @package   thumb
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// php thumb/tests/previewPolicy.php. No database or network is used.
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
function checkPreview($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function expect($ok, ...$args) { if (!$ok) throw new RuntimeException('Expectation failed'); }
function expect404($ok) { expect($ok); }
function countR($value) { return is_array($value) ? count($value) : 0; }
function defIfNot($key, $value) { if (!defined($key)) define($key, $value); }
function sbf($path, $unused = '', $absolute = false) { return ($absolute ? 'https://example.test' : '') . '/sbf/' . $path; }
function toUrl($url, $type = 'relative') { return ($type === 'absolute' ? 'https://example.test' : '') . '/thumb_M/R/?t=' . $url['t']; }
class PreviewDenied extends RuntimeException {}
class PreviewFinished extends RuntimeException {}
class core_exception_Redirect extends RuntimeException { public $url; public function __construct($url) { $this->url = $url; parent::__construct('redirect'); } }
function redirect($url) { throw new core_exception_Redirect($url); }
function shutdown() { throw new PreviewFinished(); }
class Redirect { public $url; public function __construct($url) { $this->url = $url; } }
class arr { public static function make($value) { return array_map('trim', explode(',', $value)); } }
class Mode { public static function is($name, $value = true) { return false; } }
class Request { public static $values = array(); public static function get($name) { return self::$values[$name] ?? null; } }
class thumb_Setup { public static function get($name) { return $name === 'QUALITY' ? 80 : 'no'; } }
class core_Packs
{
    public static $directory;
    public static function getConfig($name) { return (object) array('THUMB_IMG_DIR' => 'thumb', 'THUMB_IMG_PATH' => self::$directory); }
}
class core_Crypt
{
    public static $arguments;
    public static function encodeVar($value, $key) { self::$arguments = $value; return 'synthetic-preview'; }
    public static function decodeVar($value, $key) { return self::$arguments; }
}
define('EF_SALT', 'synthetic-test-salt');
require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
class core_Mvc extends core_BaseClass {}
class fileman_Files extends core_BaseClass
{
    public function __construct($policy = false) { if ($policy) $this->_plugins = array(new PreviewPolicy()); }
    public static function getExt($name) { return pathinfo($name, PATHINFO_EXTENSION); }
}
class cls
{
    public static $files;
    public static function get($name) { expect($name === 'fileman_Files'); return self::$files; }
}
class PreviewPolicy
{
    public static $allowed = true, $calls = array();
    public static function on_BeforeGetPreviewUrl($mvc, &$url, $source, $type, $absolute)
    {
        self::$calls[] = array($source, $type, $absolute);
        if ($source !== 'synthetic-private') return;
        if (!self::$allowed) throw new PreviewDenied('403');
        $url = ($absolute ? 'https://example.test' : '') . '/private-preview';

        return false;
    }
}
require dirname(__DIR__) . '/Img.class.php';
require dirname(__DIR__) . '/M.class.php';
class PreviewFixture extends thumb_Img
{
    public $pathCalls = 0;
    public function getThumbPath() { $this->pathCalls++; return parent::getThumbPath(); }
}
$directory = sys_get_temp_dir() . '/bgerp-preview-' . bin2hex(random_bytes(6));
mkdir($directory);
core_Packs::$directory = $directory;
$cache = $directory . '/existing.jpg';
file_put_contents($cache, 'synthetic cached preview');
$arguments = array('source' => 'synthetic-private', 'boxWidth' => 100, 'boxHeight' => 100,
    'sourceType' => 'fileman', 'format' => 'jpg', 'quality' => 80, 'thumbPath' => $cache);
cls::$files = new fileman_Files();
try {
    $plain = new thumb_Img($arguments);
    checkPreview($plain->getPreviewUrlOverride() === null, 'Without a policy there is no override');
    $oldUrl = $plain->getUrl();
    checkPreview(strpos($oldUrl, '/sbf/thumb/') === 0, 'Without a policy existing public cache behavior remains');
    cls::$files = new fileman_Files(true);
    foreach (array(false, true) as $absolute) {
        foreach (array('auto', 'deferred', 'forced', 'forceDeferred') as $mode) {
            $image = new PreviewFixture($arguments + array('isAbsolute' => $absolute));
            checkPreview($image->getUrl($mode) === ($absolute ? 'https://example.test' : '') . '/private-preview'
                && $image->pathCalls === 0, 'A policy override precedes every thumbnail cache/generation mode');
        }
    }
    $ordinary = new thumb_Img(array_merge($arguments, array('source' => 'synthetic-ordinary')));
    checkPreview(strpos($ordinary->getUrl(), '/sbf/thumb/') === 0, 'An unrelated source retains its static cache URL');
    $deferred = $ordinary->getUrl('forceDeferred');
    checkPreview(strpos($deferred, '/thumb_M/R/?t=') === 0, 'An unrelated source retains deferred rendering');
    foreach (array(true, false) as $allowed) {
        PreviewPolicy::$allowed = $allowed;
        foreach (array('getUrl', 'forceDownload') as $method) {
            $image = new PreviewFixture($arguments);
            $result = null;
            try {
                $result = $image->{$method}();
                checkPreview($allowed && $method === 'getUrl' && $result === '/private-preview', 'Allowed preview resolves normally');
            } catch (core_exception_Redirect $e) {
                checkPreview($allowed && $method === 'forceDownload' && $e->url === '/private-preview', 'Forced download redirects before thumbnail generation');
            } catch (PreviewDenied $e) {
                checkPreview(!$allowed, 'A denied policy is propagated without reading a cached image');
            }
            checkPreview($image->pathCalls === 0, 'Policy decisions precede public cache access');
        }
        core_Crypt::$arguments = $arguments;
        Request::$values = array('t' => 'synthetic-preview.jpg');
        $controller = new thumb_M();
        ob_start();
        try {
            $result = $controller->act_R();
            checkPreview($allowed && $result instanceof Redirect && $result->url === '/private-preview',
                'A cached deferred request uses the policy URL before reading its existing file');
        } catch (PreviewDenied $e) {
            checkPreview(!$allowed, 'Anonymous deferred requests preserve the policy denial');
        } finally {
            $output = ob_get_clean();
        }
        checkPreview($output === '', 'A protected deferred request never streams public cached bytes');
        $property = new ReflectionProperty('thumb_M', 'thumb');
        $property->setAccessible(true);
        checkPreview($property->getValue($controller) === null, 'Policy-managed requests cannot regenerate public cache during shutdown');
    }
    PreviewPolicy::$allowed = false;
    core_Crypt::$arguments = array_merge($arguments, array('source' => 'synthetic-ordinary'));
    $controller = new thumb_M();
    ob_start();
    try { $controller->act_R(); } catch (PreviewFinished $e) { $completed = true; }
    finally { $output = ob_get_clean(); }
    checkPreview(!empty($completed) && $output === 'synthetic cached preview', 'Unrelated deferred requests still stream the native cached content');
    checkPreview(file_get_contents($cache) === 'synthetic cached preview', 'Policy decisions do not delete unrelated or existing cache files');
    checkPreview(PreviewPolicy::$calls[0] === array('synthetic-private', 'fileman', false), 'The hook receives source identity, source type and absolute URL preference');
} finally {
    unlink($cache);
    rmdir($directory);
}
echo "PASS: {$checks} thumbnail preview policy checks\n";
