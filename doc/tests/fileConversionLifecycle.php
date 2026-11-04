<?php

/**
 * Default document conversion events through native object references and dispatch.
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */

// php doc/tests/fileConversionLifecycle.php. No database, filesystem or network changes.
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
function checkConversion($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function expect($ok, ...$args) { if (!$ok) throw new RuntimeException('Expectation failed'); }
function countR($value) { return is_array($value) ? count($value) : 0; }
class core_exception_Redirect extends RuntimeException {}
require dirname(__DIR__, 2) . '/core/BaseClass.class.php';
require dirname(__DIR__, 2) . '/core/Plugin.class.php';
require dirname(__DIR__, 2) . '/core/ObjectReference.class.php';
require dirname(__DIR__) . '/DocumentPlg.class.php';
class core_Users { public static function getCurrent() { return 1; } }
class Mode { public static function push($name, $value) {} public static function pop($name) {} }
class doclog_Documents
{
    const ACTION_PDF = 'pdf';
    public static $actions = array();
    public static function pushAction($action) { self::$actions[] = $action; }
    public static function popAction() { array_pop(self::$actions); }
}
class doc_PdfCreator
{
    public static $callback, $calls = array();
    public static function convert($html, &$name)
    {
        self::$calls[] = array($html, $name, ConversionPolicy::$active);

        return (self::$callback)($html, $name);
    }
}
class cls
{
    public static $instances = array();
    public static function get($class) { return self::$instances[$class]; }
    public static function getClassName($class) { return is_object($class) ? get_class($class) : $class; }
}
class ConversionDocument extends core_BaseClass
{
    public $abbr = 'fixture';
    public function __construct($policy = false)
    {
        $this->_plugins = array(new doc_DocumentPlg());
        if ($policy) $this->_plugins[] = new ConversionPolicy();
    }
    public function getContainer($id) { return (object) array('id' => $id + 100); }
    public function getDocumentBody($id, $mode) { return '<b>' . $id . ':' . $mode . '</b>'; }
    public static function fetchByHandle($info) { return (object) array('containerId' => 141); }
    public function prepareDocument($id) { return (object) array('id' => $id); }
    public function renderDocument($id, $data) { return ''; }
}
class ConversionPolicy
{
    public static $active = null, $events = array(), $beginException = null, $append = null;
    public static function on_BeginFileConversion($mvc, $scope, $id, $type, $name)
    {
        $scope->previous = self::$active;
        self::$active = $id;
        self::$events[] = array('begin', $id, $type, $name);
        if (self::$beginException) throw self::$beginException;
    }
    public static function on_EndFileConversion($mvc, $scope, &$result)
    {
        self::$events[] = array('end', self::$active, $result);
        self::$active = $scope->previous;
        if (self::$append) $result[self::$append] = self::$append;
    }
}
class doc_RichTextPlg
{
    public static $valid = true;
    public static function getFileInfo($name) { return self::$valid ? array('className' => 'ConversionDocument') : null; }
}
class doc_Containers
{
    public static function getDocument($id) { return new core_ObjectReference('ConversionDocument', $id - 100); }
}
function conversionException($callback, $expected)
{
    $actual = null;
    try { $callback(); } catch (Throwable $error) {
        // redirect-catch: terminal - the test compares the exact propagated exception.
        $actual = $error;
    }
    checkConversion($actual === $expected, 'The original conversion failure or redirect propagates');
    checkConversion(ConversionPolicy::$active === null, 'The previous scope is restored on failure or redirect');
}
cls::$instances['ConversionDocument'] = new ConversionDocument();
$reference = new core_ObjectReference('ConversionDocument', 41);
doc_PdfCreator::$callback = function () { return 'synthetic-file'; };
checkConversion($reference->convertTo('pdf') === array('synthetic-file' => 'synthetic-file'), 'No-policy native reference returns the same file array');
checkConversion(end(doc_PdfCreator::$calls) === array('<b>41:xhtml</b>', 'FIXTURE.pdf', null), 'No-policy conversion retains object id, body and default filename');
checkConversion($reference->convertTo('txt') === array(), 'Unimplemented conversion type remains empty');
checkConversion($reference->convertDocumentAsFile('fixture.pdf', 'pdf') === array('synthetic-file' => 'synthetic-file'), 'The deprecated route retains its native argument order and result');
checkConversion(ConversionPolicy::$events === array(), 'No-policy conversion emits no policy side effects');
cls::$instances['ConversionDocument'] = new ConversionDocument(true);
$reference = new core_ObjectReference('ConversionDocument', 41);
checkConversion($reference->convertTo('pdf', 'custom.pdf') === array('synthetic-file' => 'synthetic-file'), 'Successful policy conversion preserves the result');
checkConversion(ConversionPolicy::$events === array(array('begin', 41, 'pdf', 'custom.pdf'), array('end', 41, array('synthetic-file' => 'synthetic-file'))), 'Events receive the actual document identity and final handles');
checkConversion(end(doc_PdfCreator::$calls)[2] === 41 && ConversionPolicy::$active === null, 'The scope covers the actual converter and closes afterwards');
ConversionPolicy::$events = array();
$reference->convertDocumentAsFile('legacy.pdf', 'pdf');
checkConversion(ConversionPolicy::$events[0] === array('begin', 41, 'pdf', 'legacy.pdf') && ConversionPolicy::$active === null, 'Deprecated conversion uses the same event contract');
doc_RichTextPlg::$valid = false;
ConversionPolicy::$events = array();
checkConversion($reference->convertDocumentAsFile('missing.pdf', 'pdf') === array() && count(ConversionPolicy::$events) === 2
    && ConversionPolicy::$active === null, 'An early return still closes the conversion scope');
doc_RichTextPlg::$valid = true;
foreach (array(new RuntimeException('converter'), new Error('converter'), new core_exception_Redirect('redirect')) as $exception) {
    doc_PdfCreator::$callback = function () use ($exception) { throw $exception; };
    conversionException(function () use ($reference) { $reference->convertTo('pdf'); }, $exception);
    conversionException(function () use ($reference) { $reference->convertDocumentAsFile('legacy.pdf', 'pdf'); }, $exception);
}
$exception = new RuntimeException('begin denied');
ConversionPolicy::$beginException = $exception;
$before = count(doc_PdfCreator::$calls);
conversionException(function () use ($reference) { $reference->convertTo('pdf'); }, $exception);
checkConversion(count(doc_PdfCreator::$calls) === $before, 'A failed begin event prevents the converter from running');
ConversionPolicy::$beginException = null;
$nested = new core_ObjectReference('ConversionDocument', 42);
doc_PdfCreator::$callback = function ($html) use ($nested) {
    if (strpos($html, '41:') !== false) {
        checkConversion($nested->convertTo('pdf') === array('inner-file' => 'inner-file'), 'Nested conversion returns its own file');
        checkConversion(ConversionPolicy::$active === 41, 'Nested conversion restores the outer scope');

        return 'outer-file';
    }
    checkConversion(ConversionPolicy::$active === 42, 'Nested conversion carries its own document identity');

    return 'inner-file';
};
checkConversion($reference->convertTo('pdf') === array('outer-file' => 'outer-file') && ConversionPolicy::$active === null,
    'Nested success restores the preexisting scope');
doc_PdfCreator::$callback = function () { return false; };
checkConversion($reference->convertTo('pdf') === array(), 'Failed conversion retains an empty result');
ConversionPolicy::$append = 'policy-file';
checkConversion($reference->convertTo('pdf') === array('policy-file' => 'policy-file'), 'End event receives the result by reference');
echo "PASS: {$checks} document conversion lifecycle checks\n";
