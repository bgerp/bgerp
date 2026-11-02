<?php

/**
 * Изолирани проверки: php draw/tests/phpCompatibility.php
 * Реални команди, изрази и SVG/PDF платна; заместители само за база, заявка и UI.
 *
 * @category bgerp
 * @package draw
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
function check($condition, $message)
{
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function expect($condition, ...$args) { if (!$condition) throw new RuntimeException('Framework expectation'); }
function countR($value) { return is_array($value) ? count($value) : 0; }
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function setIfNot(&$value, $default) { return core_App::setIfNot($value, $default); }
function haveRole($roles) { return true; }
function sbf($path, $quote = '') { return $quote . $path . $quote; }
class Mode { public static function is($key) { return false; } }
class Request {
    public static $values = array();
    public static function get($key, $type = null) { return self::$values[$key] ?? null; }
}
$root = dirname(__DIR__, 2);
foreach (array('BaseClass', 'String', 'App') as $file) require $root . '/core/' . $file . '.class.php';
class_alias('core_String', 'str');
class core_Master extends core_BaseClass {
    public static $records = array();
    public static function fetch($condition) { return self::$records[get_called_class()][$condition[1]] ?? false; }
    public static function getQuery() { return new DrawTestQuery(); }
}
class DrawTestQuery {
    public function fetch() { return false; }
    public function orderBy($field, $direction = null) {}
    public function limit($limit) {}
}
class DrawTestToolbar { public function addSbBtn(...$args) {} }
class core_Form extends stdClass {
    public $fields = array(), $defaults = array(), $toolbar;
    public function __construct() { $this->toolbar = new DrawTestToolbar(); $this->rec = new stdClass(); }
    public function FLD($name, $type, $options) { $this->fields[$name] = $options; }
    public function setDefault($name, $value) { $this->defaults[$name] = $value; }
    public function setSuggestions($name, $values) { $this->suggestions = $values; }
    public function input(...$args) {}
    public function renderHtml() { return '<form></form>'; }
}
class type_Double {
    public function fromVerbal($value) { return $value === null ? null : (is_numeric($value) ? (float) $value : false); }
}
class cls {
    public static function get($class) { return new $class(); }
    public static function load($class, $silent = false) { return class_exists($class); }
    public static function existsMethod($class, $method) { return method_exists($class, $method); }
    public static function getClassName($class) { return get_class($class); }
}
class DrawTestExtension {
    public function draw_value(&$context) { $context->extensionValue = 7; }
}
class DrawTestTemplate {
    public $parts = array();
    public function append($content, $place = '') { $this->parts[$place][] = $content; }
}
class hljs_Adapter { public static function enable($style) { return new DrawTestTemplate(); } }
foreach (array('Canvas', 'SvgCanvas', 'PdfCanvas', 'Shape', 'Vector', 'Ellipse', 'MeasureLine', 'MeasureAngle', 'ArcTo') as $file) require $root . '/cad2/' . $file . '.class.php';
require $root . '/draw/Designs.class.php';
require $root . '/draw/Pens.class.php';
function runDrawing($script, $canvasClass = 'cad2_SvgCanvas', $context = null, $expectedError = false)
{
    $canvas = new $canvasClass();
    $canvas->setPaper(210, 297, 0, 0, 0, 0);
    $context = $context ?? new stdClass();
    $error = '';
    $result = draw_Designs::runScript($script, $canvas, $context, $error);
    check($expectedError ? ($result === false && $error !== '') : ($result !== false && $error === ''), 'Script result: ' . $script . ' / ' . $error);
    return array($canvas, $context, $error);
}

// Относителни координати по подразбиране и изрично абсолютни координати.
foreach (array('cad2_SvgCanvas', 'cad2_PdfCanvas') as $canvasClass) {
    $canvas = new $canvasClass();
    check($canvas->getAttr('fill') === 'none' && $canvas->getAttr('stroke-width') === 0.2, 'Default canvas draws outlines without fill');
    list($canvas) = runDrawing("MoveTo(10, 20)\nLineTo(5, 7)", $canvasClass);
    check($canvas->getCP() == array(15, 27), 'Relative LineTo');
    list($canvas) = runDrawing("MoveTo(10, 20)\nLineTo(5, 7, ABS)", $canvasClass);
    check($canvas->getCP() == array(5, 7), 'Absolute LineTo');
    list($canvas) = runDrawing("MoveTo(10, 20)\nCurveTo(1, 2, 3, 4, 5, 6)", $canvasClass);
    check($canvas->getCP() == array(15, 26), 'Relative CurveTo');
    list($canvas) = runDrawing("MoveTo(10, 20)\nCurveTo(1, 2, 3, 4, 5, 6, abs)", $canvasClass);
    check($canvas->getCP() == array(5, 6), 'Absolute CurveTo');
    list($canvas) = runDrawing("MoveTo(10, 20)\nArcTo(15, 25, 10, abs)", $canvasClass);
    check($canvas->getCP() == array(15, 25), 'Absolute ArcTo uses fourth argument');
    list($canvas) = runDrawing("MoveTo(10, 20)\nArcTo(5, 5, 10)", $canvasClass);
    check($canvas->getCP() == array(15, 25), 'Relative ArcTo');
    list($canvas) = runDrawing("MoveTo(0,0)\nLineTo(10,0,abs)\nLineTo(0,0,abs)\nMoveTo(2,2,subpath)\nLineTo(3,2,abs)\nClosePath()", $canvasClass);
    if ($canvasClass === 'cad2_SvgCanvas') {
        $svg = simplexml_load_string($canvas->render());
        $paths = $svg->xpath('//*[local-name()="path"]');
        check(count($paths) === 1 && substr_count((string) $paths[0]['d'], 'M') === 2, 'SVG inner contour stays in one path');
    } else {
        check(count($canvas->contents) === 1 && count(array_keys(array_column($canvas->contents[0]->data, 0), 'M')) === 2, 'PDF inner contour stays in one path');
    }
}
list($canvas) = runDrawing("OpenLayer()\nOpenGroup(test)\nMoveTo(0, 0)\nLineTo(10, 5)\nClosePath()\nCloseGroup()\nMeasureLine(0, 0, 10, 0)\nCloseLayer()");
$xml = $canvas->render();
check(simplexml_load_string($xml) !== false && strpos($xml, 'id="test"') !== false, 'Named SVG group and default measurement text');
list($canvas) = runDrawing("MoveTo(0, 0)\nDrawEllipse(0, 0, 5, 3)\nDrawPartOfEllipse(0, 0, 5, 3, 180, 0)\nWriteSizeText(1, 1, 5, 3, 45, 24)");
check(strpos($canvas->render(), '5x3') !== false, 'Ellipses and rotated size text');
list($canvas, $context) = runDrawing("Input(\$w, 10)\nSet(\$v, -2)\nIf(\$w > 0)\nSet(\$result, \$w + \$v)\nElse()\nSet(\$result, 99)\nEndIf()\nInfo(Result, \$result)");
check($context->result === 8 && $canvas->info['Result'] === 8, 'Numeric context excludes IF stack');
list($canvas, $context) = runDrawing("If(0)\nSet(\$x, 9)\nElse()\nSet(\$x, 3)\nEndIf()\nMoveTo(\$x, 1)\nSavePoint(\$px, \$py)");
check($context->px == 3 && $context->py == 1, 'Else and saved coordinates');
foreach (array('Else()', 'EndIf()', 'Unknown()', 'MoveTo(1)', 'LineTo(1)', 'CurveTo(1, 2)', 'ArcTo(1, 2)', 'Set($x)', 'Input()', 'MeasureLine(1, 2)', 'MeasureAngle(1, 2)', 'DrawEllipse(1, 2)', 'DrawPartOfEllipse(1, 2)', 'SavePoint($x)', 'Info(Value)', 'ErrorMsg(Value)', 'WriteSizeText(1, 2, invalid, 4, 0, 24)', 'WriteSizeText(1, 2, 3, invalid, 0, 24)', 'GetPen()', 'Call(missing)', 'CallPHP()', 'CallPHP(DrawTestExtension)', 'CallPHP(MissingDrawTestClass::method)', 'CallPHP(DrawTestExtension::missing)') as $script) {
    runDrawing($script, 'cad2_SvgCanvas', null, true);
}
foreach (array('MoveTo', 'LineTo', 'CurveTo', 'ArcTo', 'Set', 'Input', 'MeasureLine', 'MeasureAngle', 'DrawEllipse', 'DrawPartOfEllipse', 'SavePoint', 'Info', 'ErrorMsg', 'WriteSizeText', 'GetPen', 'Call', 'CallPHP') as $command) {
    $canvas = new cad2_SvgCanvas();
    $canvas->setPaper();
    $context = new stdClass();
    $error = '';
    $result = call_user_func_array(array('draw_Designs', 'cmd_' . $command), array(array(), &$canvas, &$context, &$error));
    check($result === false && $error !== '', 'Direct missing arguments: ' . $command);
}
list($canvas, $context) = runDrawing("If(0)\nIf(1)\nSet(\$x,99)\nElse()\nSet(\$x,98)\nEndIf()\nElse()\nSet(\$x,4)\nEndIf()");
check($context->x === 4 && empty($context->_if), 'Nested IF in a skipped branch');
foreach (array('DrawEllipse(0,0,5,0)', 'DrawPartOfEllipse(0,0,5,0,180,0)') as $script) runDrawing($script, 'cad2_SvgCanvas', null, true);
core_Master::$records['draw_Pens']['plain'] = new stdClass();
list($canvas) = runDrawing('GetPen(#plain)');
check($canvas->getAttr('stroke') === '#000' && $canvas->getAttr('fill') === 'none', 'Optional pen properties');
core_Master::$records['draw_Designs']['child'] = (object) array('script' => 'MoveTo($width, 2)');
list($canvas) = runDrawing('Call(child, $width=$source)', 'cad2_SvgCanvas', (object) array('source' => 12));
check($canvas->getCP() == array(12, 2), 'Call passes renamed variables');
list($canvas) = runDrawing("Call(child, \$width=\$missing)\nMoveTo(99,99)", 'cad2_SvgCanvas', null, true);
check($canvas->getCP() == array(0, 0), 'Call propagates errors and stops outer script');
list($canvas, $context) = runDrawing('CallPHP(DrawTestExtension::value)');
check($context->extensionValue === 7, 'CallPHP receives context by reference');
list($canvas) = runDrawing('ErrorMsg(Invalid, 1)');
check($canvas->errorMsg['Invalid'] === 1, 'ErrorMsg stores result');
foreach (array(null, '', 'missing', '1 / 0') as $expr) check(draw_Designs::calcExpr($expr, new stdClass()) === draw_Designs::CALC_ERROR, 'Invalid expression');
check(draw_Designs::parseScript(null) === array(), 'Null script');
$error = '';
$form = draw_Designs::prepareForm("Input(\$w, 10)\nInput(\$h, 20, Height)", $error);
check($form->defaults === array('w' => '10', 'h' => '20'), 'Input defaults in form');
foreach (array('Input($x)', "Input(\$x,1)\nInput(\$x,2)", 'Input($1invalid,1)') as $script) {
    $error = '';
    check(draw_Designs::prepareForm($script, $error) === false && $error !== '', 'Invalid input form');
}
$data = (object) array('form' => new core_Form());
draw_Designs::on_AfterPrepareEditform(new draw_Designs(), $data);
check(isset($data->form->suggestions['MoveTo(']), 'New record suggestions without id');
$form = new core_Form();
$manager = new draw_Designs();
$manager->on_AfterInputEditForm($manager, $form);
check($form->defaults['order'] == 10, 'First record order without existing records');
foreach (array(null, array('default' => 1)) as $cmd) {
    Request::$values = array('Cmd' => $cmd);
    $data = (object) array('rec' => (object) array('script' => "MoveTo(0,0)\nLineTo(2,3)"), 'row' => (object) array('id' => 'test'));
    draw_Designs::on_AfterPrepareSingle($manager, null, $data);
    $tpl = new DrawTestTemplate();
    draw_Designs::on_AfterRenderSingle($manager, $tpl, $data);
    check(!empty($tpl->parts['SVG']), 'Single view without optional fields or messages');
}
echo "PASS: {$checks} checks; PHP " . PHP_VERSION . "; E_ALL\n";
