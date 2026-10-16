<?php

/**
 * CLI regression for deferred original-document previews; no database required.
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function expect($ok) { if (!$ok) throw new RuntimeException('Framework expectation'); }
function countR($value) { return is_countable($value) ? count($value) : 0; }
class cls
{
    private static $instances = array();
    public static function &get($name) { if (!isset(self::$instances[$name])) self::$instances[$name] = new $name(); return self::$instances[$name]; }
    public static function getClassName($name) { return is_object($name) ? get_class($name) : $name; }
    public static function load($name, $silent = false) { return class_exists($name); }
    public static function isSubclass($name, $parent) { return is_subclass_of($name, $parent); }
}
class Mode
{
    public static $values = array();
    public static function is($name) { return !empty(self::$values[$name]); }
}
$root = dirname(__DIR__, 2);
require $root . '/core/BaseClass.class.php';
require $root . '/core/Plugin.class.php';
require $root . '/core/ET.class.php';
require $root . '/doc/DocumentPlg.class.php';

class PreviewDocumentFixture
{
    public $renders = 0, $allowed = true, $label;
    public function __construct($label) { $this->label = $label; }
    public function haveRightFor($action) { return $this->allowed; }
    public function getInlineDocumentBody()
    {
        $this->renders++;
        $body = new core_ET('<p>' . $this->label . '</p>');
        $body->push('preview.css', 'CSS');
        $body->push('preview.js', 'JS');

        return $body;
    }
}
// Real plugin dispatch and templates, with a minimal form-rendering boundary.
class PreviewFormFixture extends core_BaseClass
{
    public $layout, $error = '';
    public function __construct($document, $withPreview = true)
    {
        $this->layout = new core_ET('<form>[#ERROR#]</form>' . ($withPreview ? '[#ORIGINAL_DOCUMENT#]' : ''));
        $this->_originPreview = array('document' => $document, 'template' => new core_ET('<aside>[#DOCUMENT#]</aside>'));
    }
    public function renderHtml_()
    {
        $tpl = new core_ET($this->layout);
        $tpl->replace($this->error, 'ERROR');

        return $tpl;
    }
}

class PreviewManagerFixture extends core_BaseClass
{
    public function __construct() { $this->_plugins = array(cls::get('doc_DocumentPlg')); }
    public function renderForm($form, $wrapped = false)
    {
        $tpl = $form->renderHtml();
        if ($wrapped) $tpl = new core_ET('<main>[#1#]</main>', $tpl);
        $this->invoke('AfterRenderPrepareEditForm', array($tpl, $form));

        return $tpl;
    }
}

$mvc = new PreviewManagerFixture();
$first = new PreviewDocumentFixture('First original');
$second = new PreviewDocumentFixture('Second original');
$form = new PreviewFormFixture($first);
$other = new PreviewFormFixture($second);
check($first->renders === 0 && $second->renders === 0, 'Unrendered forms must not prepare original documents');
$form->error = 'Missing mandatory field';
Mode::$values = array('saveObjectsToCid' => 101, 'getLinkedObj' => true);
$modes = Mode::$values;
$html = $mvc->renderForm($form);
check(strpos($html->content, 'Missing mandatory field') !== false, 'Validation errors remain visible');
check(strpos($html->content, 'First original') !== false, 'An error form still shows its original');
check($html->getArray('CSS') === array('preview.css') && $html->getArray('JS') === array('preview.js'), 'Original assets reach the form template');
check($first->renders === 1 && $second->renders === 0, 'Plugin singleton must not mix independent forms');
check(Mode::$values === $modes, 'Object-collection modes must remain intact');
$again = $mvc->renderForm($form);
check($first->renders === 1 && substr_count($again->content, 'First original') === 1, 'Repeated rendering must not duplicate the preview');
$wrapped = $mvc->renderForm($other, true);
check(strpos($wrapped->content, '<main>') !== false && strpos($wrapped->content, 'Second original') !== false, 'Change forms retain their preview after wrapping');
check($wrapped->getArray('JS') === array('preview.js'), 'Preview assets survive the change-form wrapper');
$mvc->invoke('AfterRenderPrepareEditForm', array($wrapped, $other));
check($second->renders === 1 && substr_count($wrapped->content, 'Second original') === 1, 'Repeated hook must not duplicate a rendered preview');
$first->allowed = false;
check(strpos($mvc->renderForm($form)->content, 'First original') === false, 'Cached preview must not bypass a later rights check');

foreach (array('denied', 'stopped', 'refresh') as $case) {
    Mode::$values = array('stopRenderOrigin' => $case === 'stopped');
    $document = new PreviewDocumentFixture('Hidden original');
    $form = new PreviewFormFixture($document, $case !== 'refresh');
    $document->allowed = $case !== 'denied';
    $html = $mvc->renderForm($form);
    check($document->renders === 0 && strpos($html->content, 'Hidden original') === false, "$case: do not prepare or expose the original");
}
echo "OK: {$checks} checks\n";
