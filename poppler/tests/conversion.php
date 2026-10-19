<?php

// php poppler/tests/conversion.php - no database, network or installed pdftocairo required.
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });
$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
class cls {
    public static function load($c) { return true; }
    public static function get($c) { return new PdfScriptFixture(); }
}
class poppler_Setup { public static function get($key) { return $key === 'PDFTOCAIRO_PATH' ? '/configured/pdftocairo' : 150; } }
class fileman_Files { public static function getFileNameWithoutExt($fh) { return 'sample'; } }
class fileman_webdrv_Generic { public static function getErrLogFilePath($path) { return $path . '.err'; } }
class log_System {
    public static $errors = array();
    public static function add($class, $message, $id, $type) { self::$errors[] = $message; }
}
class fileman { public static function absorb($path, $bucket) { return basename($path); } }
class core_Os {
    public static function deleteDir($dir) { foreach (glob($dir . '/*') as $file) unlink($file); rmdir($dir); }
}
class PdfScriptFixture {
    public static $root, $mode = 'missing', $count = 0, $stderr = '';
    public $tempDir, $out, $err, $line;
    public function __construct() { $this->tempDir = self::$root . '/run' . (++self::$count) . '/'; mkdir($this->tempDir); }
    public function setFile($key, $value) { if ($key === 'OUTPUTF') $this->out = $value; }
    public function setProgram($program, $path) { check($path === '/configured/pdftocairo', 'Configured executable is preserved'); }
    public function lineExec($line, $params) { $this->line = $line; $this->err = $params['errFilePath']; }
    public function setCheckProgramsArr($program) { check($program === 'pdftocairo', 'Existing program check is preserved'); }
    public function run($async) {
        check($async === false, 'Synchronous invocation is preserved');
        if (self::$mode === 'missing') return false;
        if (self::$mode === 'failed') file_put_contents($this->err, self::$stderr);
        if (self::$mode === 'success') {
            if (strpos($this->line, '-singlefile') !== false) file_put_contents($this->out . '.jpg', 'fixture');
            else foreach (array(1, 2) as $page) file_put_contents($this->out . '-' . $page . '.jpg', 'fixture');
        }
    }
}
require dirname(__DIR__) . '/Api.class.php';
PdfScriptFixture::$root = sys_get_temp_dir() . '/poppler-conversion-' . uniqid();
mkdir(PdfScriptFixture::$root);
try {
    $error = array('stale');
    check(poppler_Api::convertPdfToJpg('fixture', array('page' => 1), $error) === null && ($error['reason'] ?? '') === 'converter_unavailable', 'Missing converter is distinguished');
    PdfScriptFixture::$mode = 'failed';
    PdfScriptFixture::$stderr = 'Wrong page range given: the first page (999) can not be after the last page (2).';
    $res = poppler_Api::convertPdfToJpg('fixture', array('page' => 999), $error);
    check($res === null && ($error['reason'] ?? '') === 'invalid_page' && ($error['pageCount'] ?? 0) === 2 && ($error['page'] ?? 0) === 999, 'An out-of-range page reports the actual range');
    PdfScriptFixture::$stderr = 'Cannot read /private/source.pdf: password required';
    $res = poppler_Api::convertPdfToJpg('fixture', array('page' => 2), $error);
    check($res === null && ($error['reason'] ?? '') === 'conversion_failed' && !isset($error['pageCount']), 'Other PDF failures do not invent a page count');
    check(strpos(json_encode($error), '/private') === false && strpos(end(log_System::$errors), '/private/source.pdf') !== false, 'Details are logged without exposing source paths');
    PdfScriptFixture::$mode = 'empty';
    poppler_Api::convertPdfToJpg('fixture', array('page' => 2), $error);
    check(($error['reason'] ?? '') === 'conversion_failed' && count(log_System::$errors) === 3, 'Missing output is logged even without stderr');
    PdfScriptFixture::$mode = 'success';
    check(poppler_Api::convertPdfToJpg('fixture', array('page' => 2), $error) === 'sample.jpg' && $error === null, 'Successful page conversion clears a previous error');
    check(poppler_Api::convertPdfToJpg('fixture') === array(1 => 'sample-1.jpg', 2 => 'sample-2.jpg'), 'Existing two-argument/all-pages callers keep the same result');
    check(poppler_Api::convertPdfToJpg(null, array(), $error) === null && ($error['reason'] ?? '') === 'missing_file', 'Empty input retains the null result with an optional explanation');
} finally {
    foreach (glob(PdfScriptFixture::$root . '/*') as $dir) core_Os::deleteDir($dir);
    rmdir(PdfScriptFixture::$root);
}
echo "Poppler conversion: {$checks} checks passed.\n";
