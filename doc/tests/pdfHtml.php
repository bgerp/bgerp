<?php

/**
 * Подготовка на цял HTML и фрагменти за PDF с всички налични CSS адаптери.
 *
 * @category bgerp
 * @package doc
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
// php doc/tests/pdfHtml.php [vendor/autoload.php] [PdfCreator.class.php]
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require $argv[1] ?? dirname(__DIR__, 3) . '/composer/vendor/autoload.php';
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});

class core_Manager
{
    public static $cache = array();
    /** Връща само резултатите, създадени в текущия тест. */
    public static function fetchField($condition, $field) { return self::$cache[$condition] ?? null; }
    /** Изолира изтриването от реалната база. */
    public static function delete($condition) {}
    /** Запомня синтетичния резултат за проверката на кеша. */
    public static function save($rec) { self::$cache["#md5='" . ($rec->md5 ?? '') . "'"] = $rec->fileHnd ?? null; }
}
class core_ProtoSetup
{
    /** Избира адаптера от текущия сценарий. */
    public static function get($name) { return core_Packs::$config->{'CSSTOINLINE_' . $name} ?? null; }
}
class core_Packs
{
    public static $config;
    /** Предоставя настройките без база данни. */
    public static function getConfig($name) { return self::$config; }
}
class core_Composer
{
    /** Autoload вече е зареден от теста. */
    public static function isInUse() { return true; }
}
class cls
{
    /** Създава реалния CSS адаптер. */
    public static function get($name) { return new $name(); }
    /** Заменя единствено външния PDF процес. */
    public static function getInterface($interface, $name) { return new PdfHtmlRecorder(); }
}
class doc_Setup
{
    /** Външният PDF процес е изолиран от теста. */
    public static function get($name, $force) { return 'PdfHtmlRecorder'; }
}
class Mode
{
    public static $stack = array();
    /** Следи режима при преобразуване. */
    public static function push($name, $value) { self::$stack[] = array($name, $value); }
    /** Възстановява тестовия режим. */
    public static function pop($name) { array_pop(self::$stack); }
}
class core_ET
{
    public $html;
    /** Синтетичен шаблон със стилове и JavaScript. */
    public function __construct($html) { $this->html = $html; }
    /** Предава съдържанието на шаблона. */
    public function __toString() { return $this->html; }
    /** Връща ресурсите, които PDF подготовката трябва да запази. */
    public function getArray($name, $remove) {
        $resources = array('CSS' => array('fixture.css'), 'STYLES' => array('#sample {font-weight:bold}'),
            'JS' => array('fixture.js'), 'JQUERY_CODE' => array('fixture();'));
        return $resources[$name] ?? array();
    }
}
class PdfHtmlRecorder
{
    public static $calls = array();
    /** Прихваща точно съдържанието, подадено към PDF генератора. */
    public function convert($html, $name, $bucket, $js) {
        self::$calls[] = array($html, $name, $bucket, $js);
        return 'synthetic-pdf-' . count(self::$calls);
    }
}
/** Дефинира настройка, ако липсва. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Намира реалните библиотеки в хранилището. */
function getFullPath($path) { return dirname(__DIR__, 2) . '/' . $path; }
/** Симулира общите и шаблонните стилове, включително правилото, задействащо дефекта. */
function getFileContent($path) { return $path === 'css/pdf.css' ? '@page {size:A4} .wide p {color:blue}' : ''; }
/** Не заобикаля кеша заради debug режим. */
function isDebug() { return false; }
/** Прекратява теста при несъответствие. */
function expect($condition, $message = 'Expectation failed') { if (!$condition) throw new RuntimeException($message); }
/** Брои само масиви. */
function countR($value) { return is_array($value) ? count($value) : 0; }
$checks = 0;
/** Отчита успешно проверено условие. */
function checkPdfHtml($condition, $message) { global $checks; expect($condition, $message); $checks++; }

require dirname(__DIR__, 2) . '/core/String.class.php';
class_alias('core_String', 'str');
require dirname(__DIR__, 2) . '/csstoinline/Setup.class.php';
require dirname(__DIR__, 2) . '/csstoinline/Emogrifier.class.php';
require dirname(__DIR__, 2) . '/csstoinline/CssToInline.class.php';
require $argv[2] ?? dirname(__DIR__) . '/PdfCreator.class.php';
$png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWuQAAAAASUVORK5CYII=';
$body = '<p id="sample">България 00123 &amp; EUR</p><img src="' . $png . '">'
    . '<table><tr><td>12.50</td></tr></table><form><input value="not for PDF"></form>';
$fixtures = array(
    'fragment' => $body,
    'full' => '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>#sample {color:green}</style></head><body>' . $body . '</body></html>',
    'uppercase' => '<HTML><HEAD><META charset="UTF-8"></HEAD><BODY>' . $body . '</BODY></HTML>',
    'implicitBody' => '<html><head><meta charset="UTF-8"></head>' . $body . '</html>',
    'implicitHtml' => '<body>' . $body . '</body>',
    'template' => new core_ET($body),
);
$versions = array('csstoinline_Emogrifier' => array('8.3.0', '3.0.0', '2014.11.10', '2011.10.26'),
    'csstoinline_CssToInline' => array('1.0.3', '2.2.2', '2.4.0'));
foreach ($versions as $class => $available) {
    foreach ($available as $version) {
        core_Manager::$cache = array();
        core_Packs::$config = (object) array('CSSTOINLINE_CONVERTER_CLASS' => $class,
            'CSSTOINLINE_EMOGRIFIER_VERSION' => $version, 'CSSTOINLINE_CSSTOINLINE_VERSION' => $version);
        foreach ($fixtures as $case => $source) {
            // Шаблонът има същия HTML като фрагмента, затова го проверяваме без кеша му.
            core_Manager::$cache = array();
            $name = 'Synthetic.pdf';
            $fh = doc_PdfCreator::convert($source, $name);
            list($html, $fileName, $bucket, $js) = end(PdfHtmlRecorder::$calls);
            $dom = new DOMDocument();
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new DOMXPath($dom);
            $context = "$class $version $case";
            checkPdfHtml(strpos($dom->textContent, 'България 00123 & EUR') !== false, "$context: Cyrillic, zeros and entities");
            checkPdfHtml($xpath->query('//div[@class="wide"]')->length === 1, "$context: wide wrapper");
            checkPdfHtml($xpath->query('//img')->item(0)->getAttribute('src') === $png, "$context: exact embedded PNG");
            checkPdfHtml($xpath->query('//td')->item(0)->textContent === '12.50', "$context: table amount");
            checkPdfHtml($xpath->query('//form|//input')->length === 0, "$context: forms remain excluded");
            $style = $xpath->query('//*[@id="sample"]')->item(0)->getAttribute('style');
            // CssToInline 1.0.3 използва само подадения CSS, без стиловете от head.
            $embeddedCss = $case === 'full' && !($class === 'csstoinline_CssToInline' && $version === '1.0.3');
            checkPdfHtml(strpos($style, $embeddedCss ? 'green' : 'blue') !== false, "$context: CSS still inlined");
            checkPdfHtml($fileName === 'synthetic.pdf' && $bucket === 'pdf', "$context: filename and bucket");
            if ($case === 'fragment' || $case === 'template') {
                $legacy = '<div id="begin"><div class=\'wide\'>' . doc_PdfCreator::removeFormAttr((string) $source) . '</div><div id="end">';
                $legacy = (new $class())->convert($legacy, doc_PdfCreator::getCssStr($source));
                checkPdfHtml($html === str::cut($legacy, '<div id="begin">', '<div id="end">'), "$context: identical fragment output");
            }
            if ($case === 'template') {
                checkPdfHtml($js === array('JS' => array('fixture.js'), 'JQUERY_CODE' => array('fixture();')), "$context: JS preserved");
                checkPdfHtml(strpos($style, 'bold') !== false, "$context: template styles preserved");
            }
            $calls = count(PdfHtmlRecorder::$calls);
            checkPdfHtml(doc_PdfCreator::convert($source, $name) === $fh && count(PdfHtmlRecorder::$calls) === $calls, "$context: cache avoids conversion");
            $key = "#md5='" . md5("<div class='wide'>" . $source . '</div>') . "'";
            checkPdfHtml(isset(core_Manager::$cache[$key]), "$context: existing cache key preserved");
            checkPdfHtml(Mode::$stack === array(), "$context: text mode restored");
        }
        foreach (array('', '<!-- <html> -->', '<html><head><title>Empty</title></head></html>') as $empty) {
            $name = 'Empty.pdf';
            checkPdfHtml((bool) doc_PdfCreator::convert($empty, $name), "$class $version: empty document or comment");
        }
        echo "PASS: $class $version\n";
    }
}
echo "PASS: $checks PDF HTML checks\n";
