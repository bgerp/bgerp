<?php

// php -n -d extension=json mistral/tests/ocr.php (PHP 8.2: без extension=json).
// При вграден curl добавете -d disable_functions=curl_init,curl_setopt,curl_exec,curl_close.
// Синтетични файлове и заместен HTTP транспорт, без API заявки и база данни.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
if (function_exists('curl_init')) { fwrite(STDERR, "Disable curl functions for this isolated test.\n"); exit(1); }
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class core_Manager {}
class core_Users { public static function getCurrent($part) { return 7; } }
class core_App { public static function setTimeLimit($seconds) {} }
class core_exception_Redirect extends RuntimeException {}
class core_Locks
{
    public static $held = array();
    public static function isLocked($key) { return !empty(self::$held[$key]); }
    public static function obtain($key, ...$args) { self::$held[$key] = true; return true; }
    public static function release($key) { unset(self::$held[$key]); }
}
class fileman_webdrv_Generic
{
    public static function prepareLockId($file) { return is_object($file) ? $file->dataId : md5($file); }
    public static function getLockId($type, $id) { return $type . $id; }
}
class fileman_Data
{
    public static $logs = array();
    public static function logWrite($message, $id) { self::$logs[] = array($message, $id); }
}
class fileman_Files
{
    public static $rec, $content;
    public static $logs = array();
    public static function fetchByFh($fh) { expect($fh === 'TEST01', 'A path was used as a file handle'); return self::$rec; }
    public static function getExt($name) { return pathinfo($name, PATHINFO_EXTENSION); }
    public static function logWrite($message, $id) { self::$logs[] = array($message, $id); }
}
class fileman extends fileman_Files
{
    public static function getType($name) { return pathinfo($name, PATHINFO_EXTENSION) === 'pdf' ? 'application/pdf' : 'image/png'; }
    public static function extractStr($fh) { self::fetchByFh($fh); return self::$content; }
}
class fileman_Download
{
    public static $calls = array();
    public static function getDownloadUrl($source, $lifeTime = 1, $type = 'handler')
    {
        expect($type === 'path' ? is_file($source) : $source === 'TEST01', 'Wrong download source type');
        self::$calls[] = array($source, $type);
        return 'https://example.invalid/synthetic-file';
    }
}
class fileman_Indexes
{
    public static $saved = array();
    public static function saveContent($params) { if (!empty($params['dataId'])) self::$saved[] = $params; }
}
class mistral_Setup
{
    public static $base64 = 'yes';
    public static function get($key)
    {
        $config = array('OCR_USE_BASE_64_ON_ATTACHED_IMAGES' => self::$base64, 'OCR_MODEL' => 'synthetic-model',
            'API_URL' => 'https://example.invalid/ocr', 'API_KEY' => 'synthetic-key');
        return $config[$key];
    }
}
class markdown_Render { public static function Convert($text) { return 'html:' . $text; } }
class status_Messages { public static function newStatus($text) {} }
function expect($ok, $message = '') { if (!$ok) throw new RuntimeException($message); }
function setIfNot(&$value, $default) { if (!isset($value)) $value = $default; }

foreach (array('CURLOPT_SSL_VERIFYHOST', 'CURLOPT_SSL_VERIFYPEER', 'CURLOPT_RETURNTRANSFER',
    'CURLOPT_POST', 'CURLOPT_POSTFIELDS', 'CURLOPT_HTTPHEADER') as $i => $constant) {
    if (!defined($constant)) define($constant, $i + 1);
}
$requests = array();
$transportError = null;
if (!function_exists('curl_init')) {
    function curl_init($url) { return (object) array('options' => array()); }
    function curl_setopt($curl, $option, $value) { $curl->options[$option] = $value; }
    function curl_exec($curl)
    {
        $GLOBALS['requests'][] = json_decode($curl->options[CURLOPT_POSTFIELDS], true);
        if ($GLOBALS['transportError']) throw $GLOBALS['transportError'];
        return json_encode(array('pages' => array(array('markdown' => 'QA OCR'), array('markdown' => ' 123.45 EUR'))));
    }
    function curl_close($curl) {}
}

require dirname(__DIR__, 2) . '/fileman/FileActionsIntf.class.php';
require dirname(__DIR__, 2) . '/fileman/OCRIntf.class.php';
require dirname(__DIR__) . '/Converter.class.php';
$ocr = new fileman_OCRIntf();
$ocr->class = new mistral_Converter();
$checks = 0;
function check($ok, $message) { expect($ok, $message); $GLOBALS['checks']++; }

$path = tempnam(sys_get_temp_dir(), 'mistral-test-') . '.png';
unlink(substr($path, 0, -4));
$content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j4UkAAAAASUVORK5CYII=');
file_put_contents($path, $content);
fileman_Files::$content = $content;
fileman_Files::$rec = (object) array('id' => 12, 'dataId' => 23, 'fileHnd' => 'TEST01', 'name' => 'synthetic.png');
try {
    check($ocr->getTextByOcr($path) === 'QA OCR 123.45 EUR', 'Temporary file OCR result');
    check(end($requests)['document']['image_url'] === 'data:image/png;base64,' . base64_encode($content), 'Temporary file content');
    check(!fileman_Data::$logs && !fileman_Files::$logs && !fileman_Indexes::$saved, 'Temporary file must not log/index a fileman record');
    check(!core_Locks::$held, 'Temporary file lock released');

    check($ocr->getTextByOcr(fileman_Files::$rec) === 'QA OCR 123.45 EUR', 'Standard file OCR result');
    check(end($requests)['document']['image_url'] === 'data:image/png;base64,' . base64_encode($content), 'Standard file content');
    check(end(fileman_Data::$logs)[1] === 23 && end(fileman_Files::$logs)[1] === 12, 'Standard file logs retained');
    check(count(fileman_Indexes::$saved) === 1 && fileman_Indexes::$saved[0]['content'] === 'QA OCR 123.45 EUR', 'Standard OCR index retained');

    mistral_Setup::$base64 = 'no';
    check($ocr->getTextByOcr($path) === 'QA OCR 123.45 EUR', 'Temporary file URL mode');
    check(end(fileman_Download::$calls) === array($path, 'path'), 'Path download mode');
    check($ocr->getTextByOcr(fileman_Files::$rec) === 'QA OCR 123.45 EUR', 'Standard file URL mode');
    check(end(fileman_Download::$calls) === array('TEST01', 'handler'), 'Standard download mode');
    check(end($requests)['document']['image_url'] === 'https://example.invalid/synthetic-file', 'Download URL sent');

    fileman_Files::$rec->name = 'synthetic.pdf';
    $ocr->getTextByOcr(fileman_Files::$rec);
    check(end($requests)['document']['type'] === 'document_url', 'PDF request retained');
    fileman_Files::$rec->name = 'synthetic.png';
    check(mistral_Converter::getText('TEST01', array('getMarkdown' => false, 'lockId' => 'direct')) === 'html:QA OCR 123.45 EUR', 'HTML result retained');

    $lock = fileman_webdrv_Generic::getLockId('textOcr', md5($path));
    core_Locks::$held[$lock] = true;
    $before = count($requests);
    check($ocr->getTextByOcr($path) === null && count($requests) === $before, 'Locked file is not processed twice');
    core_Locks::release($lock);

    foreach (array(new RuntimeException('transport failed'), new core_exception_Redirect('redirect')) as $error) {
        $transportError = $error;
        try { $ocr->getTextByOcr($path); throw new LogicException('Expected error'); }
        catch (Throwable $e) { check($e === $error, 'Original exception/redirect propagated'); }
        check(!core_Locks::$held, 'Lock released after error/redirect');
    }
    $transportError = null;
    $before = count($requests);
    try { $ocr->getTextByOcr($path . '.missing.png'); throw new LogicException('Missing file accepted'); }
    catch (RuntimeException $e) { check($e->getMessage() === 'Файлът за OCR не е достъпен', 'Missing file rejected'); }
    check(count($requests) === $before && !core_Locks::$held, 'Missing file neither calls API nor leaves a lock');
} finally {
    unlink($path);
}
echo "PASS: {$checks} Mistral OCR checks\n";
