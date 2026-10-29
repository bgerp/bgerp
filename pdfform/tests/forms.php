<?php

// php pdfform/tests/forms.php [path/to/python-with-pypdf] - no database or network.
// Without a Python path only the PHP checks run; with it the fixtures are filled through pdfform.py.
if (PHP_SAPI !== 'cli') exit("CLI only\n");
error_reporting(E_ALL);
set_error_handler(function ($s, $m, $f, $l) { throw new ErrorException($m, 0, $s, $f, $l); });
$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
function countR($a) { return is_array($a) ? count($a) : 0; }
function reportException($e) { throw $e; }

$root = dirname(__DIR__, 2);
class tcpdf_Setup { public static function loadLibrary() { require_once $GLOBALS['root'] . '/tcpdf/tcpdf.php'; } }
class cls { public static function load($c) { if ($c === 'tcpdf_Instance') require_once $GLOBALS['root'] . '/tcpdf/Instance.class.php'; return true; } }
class fileman_Files {
    public static $files = array();
    public static function fetchByFh($fh, $field = null) {
        if (!isset(self::$files[$fh])) return null;
        return $field === 'path' ? self::$files[$fh] : (object) array('fileHnd' => $fh, 'name' => basename(self::$files[$fh]));
    }
    public static function getExt($name) { return pathinfo($name, PATHINFO_EXTENSION); }
}
require $root . '/pdfform/Api.class.php';
require $root . '/pdfform/Overlay.class.php';

class FormsApiFixture extends pdfform_Api {
    public static function fields($fields, $defs, &$errors) { return self::normalizeFields($fields, $defs, $errors); }
    public static function items($items, $pages, &$errors) { return self::normalizeItems($items, $pages, $errors); }
    public static function display($def, $value) { return self::displayValue($def, $value); }
    public static function flatten($defs, $values) { return self::flattenFields($defs, $values); }
    public static function publish($fields) { return self::publicFields($fields); }
}

$defs = array(
    array('name' => 'name', 'type' => 'text', 'value' => '', 'maxLength' => 20),
    array('name' => 'note', 'type' => 'text', 'value' => '', 'multiline' => true),
    array('name' => 'agree', 'type' => 'checkbox', 'value' => 'Off', 'onState' => 'On'),
    array('name' => 'pay', 'type' => 'radio', 'value' => '', 'options' => array('cash', 'bank')),
    array('name' => 'city', 'type' => 'dropdown', 'value' => '', 'options' => array('Sofia', array('value' => 'PV', 'label' => 'Plovdiv'))),
    array('name' => 'locked', 'type' => 'text', 'value' => 'x', 'readonly' => true),
    array('name' => 'sign', 'type' => 'signature', 'value' => ''),
);

// Стойностите се привеждат към видовете полета
$errors = array();
$res = FormsApiFixture::fields(array('name' => "Ivan\nPetrov", 'note' => "a\nb", 'agree' => true, 'pay' => 'bank', 'city' => 'Plovdiv'), $defs, $errors);
check(!$errors, 'Valid values pass');
check($res['name'] === 'Ivan Petrov', 'Single-line text joins lines');
check($res['note'] === "a\nb", 'Multiline text keeps lines');
check($res['agree'] === 'On', 'Checkbox true becomes its on-state');
check($res['city'] === 'PV', 'Dropdown label maps to its export value');
check(FormsApiFixture::display($defs[4], 'PV') === 'Plovdiv', 'Drawn dropdown value uses the label');

$errors = array();
FormsApiFixture::fields(array('missing' => 1, 'locked' => 'y', 'sign' => 'z', 'pay' => 'card', 'city' => 'Varna', 'name' => str_repeat('x', 21), 'agree' => 'maybe'), $defs, $errors);
check(countR($errors) === 7, 'Every invalid field is reported, not skipped');
check(strpos($errors[0], '"missing" does not exist') !== false, 'Unknown field names are rejected');

$errors = array();
$res = FormsApiFixture::fields(array('agree' => false, 'pay' => ''), $defs, $errors);
check(!$errors && $res['agree'] === 'Off' && $res['pay'] === 'Off', 'Checkbox and radio can be cleared');

// Елементите за писане
$errors = array();
$items = FormsApiFixture::items(array(
    array('page' => 2, 'x' => 10, 'y' => 20, 'width' => 30, 'text' => 'Текст', 'font_size' => 9, 'align' => 'right'),
    array('x' => 5, 'y' => 5, 'check' => true),
), 2, $errors);
check(!$errors && countR($items) === 2, 'Valid items pass');
check($items[0]['page'] === 2 && $items[0]['fontSize'] === 9.0 && $items[0]['align'] === 'right', 'Text options are kept');
check($items[1]['page'] === 1 && $items[1]['kind'] === 'check', 'Page defaults to 1');

$errors = array();
FormsApiFixture::items(array(
    array('page' => 3, 'x' => 1, 'y' => 1, 'text' => 'a'),
    array('x' => 1, 'y' => 1, 'text' => 'a', 'check' => true),
    array('x' => 101, 'y' => 1, 'text' => 'a'),
    array('x' => 1, 'y' => 1, 'height' => 5, 'text' => 'a'),
    array('x' => 1, 'y' => 1, 'image' => 'nope', 'width' => 5),
    array('y' => 1, 'text' => 'a'),
), 2, $errors);
check(countR($errors) === 6, 'Every invalid item is reported');

// Редовете от OCR
$tsv = "level\tpage_num\tblock_num\tpar_num\tline_num\tword_num\tleft\ttop\twidth\theight\tconf\ttext\n"
    . "1\t1\t0\t0\t0\t0\t0\t0\t1000\t2000\t-1\t\n"
    . "5\t1\t1\t1\t1\t1\t100\t200\t100\t40\t95\tИме:\n"
    . "5\t1\t1\t1\t1\t2\t220\t190\t200\t50\t91\t______\n"
    . "5\t1\t1\t1\t2\t1\t100\t400\t50\t40\t90\tЕГН\n";
$lines = pdfform_Api::parseTsv($tsv);
check(countR($lines) === 2, 'OCR words are grouped into lines');
check($lines[0]['text'] === 'Име: ______' && $lines[0]['x'] == 10 && $lines[0]['y'] == 9.5 && $lines[0]['width'] == 32 && $lines[0]['height'] == 2.5, 'OCR line box is in percents');

// Попълване през pdfform.py: полета, писане по координати и завъртяна страница
$python = $argv[1] ?? '';
if ($python !== '' && is_executable($python)) {
    $dir = sys_get_temp_dir() . '/pdfform-test-' . getmypid() . '/';
    @mkdir($dir);
    $script = $root . '/pdfform/py/pdfform.py';
    $run = function ($args) use ($python, $script, $dir) {
        exec(escapeshellarg($python) . ' -I ' . escapeshellarg($script) . ' ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>&1', $out, $code);
        return json_decode((string) @file_get_contents(end($args)), true);
    };

    cls::load('tcpdf_Instance');
    $pdf = new tcpdf_Instance('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 11);
    $pdf->Cell(40, 8, 'Name:');
    $pdf->TextField('name', 100, 7);
    $pdf->Ln(10);
    $pdf->Cell(40, 8, 'Agree:');
    $pdf->CheckBox('agree', 5, false, array(), array(), 'Yes');
    $pdf->AddPage('L');
    $pdf->Cell(0, 10, 'Signature: ______', 0, 1);
    $pdf->Output($dir . 'form.pdf', 'F');

    $info = $run(array('info', $dir . 'form.pdf', $dir . 'info.json'));
    check(countR($info['pages']) === 2 && $info['pages'][1]['width'] > $info['pages'][1]['height'], 'Pages and their displayed size are read');
    check($info['fields'][0]['name'] === 'name' && $info['fields'][0]['type'] === 'text', 'Text field is read');
    check($info['fields'][1]['type'] === 'checkbox' && $info['fields'][1]['onState'] === 'Yes', 'Checkbox on-state is read');
    check(abs($info['fields'][0]['rect'][0] - 23.81) < 0.1 && $info['fields'][0]['rect'][1] > 0 && $info['fields'][0]['rect'][1] < 10, 'Field rect is in percents from the top-left');

    $drawn = array(array('field' => $info['fields'][0], 'value' => 'Иван Петров'));
    $items = array(array('kind' => 'text', 'page' => 2, 'x' => 30, 'y' => 5, 'text' => 'Подпис', 'fontSize' => 10, 'align' => 'left'));
    check(!pdfform_Overlay::create($dir . 'overlay.pdf', $info['pages'], $items, $drawn), 'Overlay is created');
    file_put_contents($dir . 'spec.json', json_encode(array('fields' => array('name' => 'Иван Петров', 'agree' => 'Yes'),
        'overlay' => $dir . 'overlay.pdf', 'flatten' => true, 'drawn' => array('name'))));
    $res = $run(array('fill', $dir . 'form.pdf', $dir . 'spec.json', $dir . 'out.pdf', $dir . 'fill.json'));
    check(!empty($res['ok']), 'Form is filled');
    $out = $run(array('info', $dir . 'out.pdf', $dir . 'out.json'));
    check(countR($out['fields']) === 0, 'Flattened form has no fields left');
    if (trim((string) shell_exec('command -v pdftotext'))) {
        $text = (string) shell_exec('pdftotext -enc UTF-8 ' . escapeshellarg($dir . 'out.pdf') . ' -');
        check(strpos($text, 'Иван Петров') !== false && strpos($text, 'Подпис') !== false, 'Drawn values are on the pages');
        check(strpos($text, 'TCPDF') === false || strpos((string) shell_exec('pdftotext ' . escapeshellarg($dir . 'form.pdf') . ' -'), 'TCPDF') !== false, 'Overlay adds no TCPDF credit');
    }

    file_put_contents($dir . 'spec.json', json_encode(array('fields' => array('missing' => 'x'))));
    $res = $run(array('fill', $dir . 'form.pdf', $dir . 'spec.json', $dir . 'out2.pdf', $dir . 'fill2.json'));
    check(strpos($res['error'] ?? '', 'Unknown fields') !== false, 'Script rejects unknown fields');

    // Завъртяна страница: правоъгълникът е в показаната страница, а текстът - по посоката на полето
    $rotate = 'import sys; from pypdf import PdfReader, PdfWriter; w = PdfWriter(clone_from=PdfReader(sys.argv[1])); w.pages[0].rotate(90); w.write(sys.argv[2])';
    exec(escapeshellarg($python) . ' -I -c ' . escapeshellarg($rotate) . ' ' . escapeshellarg($dir . 'form.pdf') . ' ' . escapeshellarg($dir . 'rot.pdf'));
    $rot = $run(array('info', $dir . 'rot.pdf', $dir . 'rot.json'));
    check($rot['pages'][0]['rotate'] === 90 && $rot['pages'][0]['width'] > $rot['pages'][0]['height'], 'Rotated page reports its displayed size');
    check(($rot['fields'][0]['rotation'] ?? 0) === 90 && $rot['fields'][0]['rect'][2] < $rot['fields'][0]['rect'][3], 'Field on a rotated page is tall and turned');

    $fixture = function ($kind) use ($python, $dir) {
        exec(escapeshellarg($python) . ' -I ' . escapeshellarg(__DIR__ . '/fixtures.py') . ' ' . $kind . ' ' . escapeshellarg($dir . $kind . '.pdf'), $out, $code);
        check($code === 0 && is_file($dir . $kind . '.pdf'), "Fixture {$kind} is generated");
    };
    $pdftotext = (bool) trim((string) shell_exec('command -v pdftotext'));
    $pageText = function ($pdf, $page) { return (string) shell_exec("pdftotext -f {$page} -l {$page} " . escapeshellarg($pdf) . ' -'); };

    // Едно поле с два widgets на различни страници, скрити (Hidden, NoView) и само с флаг Invisible
    $fixture('fields');
    $info = $run(array('info', $dir . 'fields.pdf', $dir . 'fields.json'));
    $byName = array_column($info['fields'], null, 'name');
    check(array_column($byName['shared']['widgets'], 'page') === array(1, 2), 'Every widget of a repeated field is read with its page');
    check(!empty($byName['hidden']['widgets'][0]['hidden']) && !empty($byName['noview']['widgets'][0]['hidden']) && empty($byName['invisible']['widgets'][0]['hidden']), 'Hidden and NoView widgets are marked, Invisible is not');
    $public = array_column(FormsApiFixture::publish($info['fields']), null, 'name');
    check(countR($public['shared']['widgets']) === 2 && array_keys($public['shared']['widgets'][1]) === array('page', 'rect'), 'Read result lists repeated widgets by page and rect');
    check(!isset($public['invisible']['widgets']) && empty($public['invisible']['hidden']) && !empty($public['hidden']['hidden']) && !isset($public['hidden']['widgets']), 'Single widgets keep the previous read format');

    $values = array('shared' => 'SHARED_VALUE');
    $drawn = FormsApiFixture::flatten($info['fields'], $values);
    check(array_column(array_column($drawn, 'field'), 'name') === array('shared', 'invisible'), 'Hidden fields are not drawn when flattening');
    check(!pdfform_Overlay::create($dir . 'fov.pdf', $info['pages'], array(), $drawn), 'Overlay with repeated fields is created');
    file_put_contents($dir . 'fspec.json', json_encode(array('fields' => $values, 'overlay' => $dir . 'fov.pdf', 'flatten' => true,
        'drawn' => array_column(array_column($drawn, 'field'), 'name'))));
    $res = $run(array('fill', $dir . 'fields.pdf', $dir . 'fspec.json', $dir . 'fout.pdf', $dir . 'ffill.json'));
    check(!empty($res['ok']), 'Form with repeated and hidden fields is filled');
    if ($pdftotext) {
        check(strpos($pageText($dir . 'fout.pdf', 1), 'SHARED_VALUE') !== false && strpos($pageText($dir . 'fout.pdf', 2), 'SHARED_VALUE') !== false, 'Repeated field value is on both pages after flatten');
        $all = $pageText($dir . 'fout.pdf', 1) . $pageText($dir . 'fout.pdf', 2);
        check(strpos($all, 'HIDDEN_') === false && strpos($all, 'NOVIEW_') === false, 'Hidden values and appearances stay hidden after flatten');
        check(strpos($all, 'INVISIBLE_VALUE') !== false, 'Widget with only the Invisible flag stays visible');
    }

    // CropBox != MediaBox, вкл. завъртяна страница: текст, полета, OCR картинка и наслагване в една система
    $fixture('crop');
    $crop = $run(array('info', $dir . 'crop.pdf', $dir . 'crop.json'));
    check($crop['pages'][0]['width'] == 400 && $crop['pages'][0]['height'] == 600 && !empty($crop['pages'][0]['cropped']), 'Cropped page reports the visible size');
    check($crop['pages'][1]['width'] == 600 && $crop['pages'][1]['height'] == 400 && $crop['pages'][1]['rotate'] === 90, 'Cropped rotated page reports the displayed size');
    if ($pdftotext) {
        $layout = function ($pdf) use ($crop) { return pdfform_Api::parseBboxLayout((string) shell_exec('pdftotext -cropbox -bbox-layout ' . escapeshellarg($pdf) . ' -'), $crop['pages']); };
        $text = $layout($dir . 'crop.pdf');
        check($text[1]['lines'][0]['text'] === 'CROPFIELD' && $text[1]['lines'][0]['x'] == 12.5, 'Text x is relative to the CropBox');
        foreach (array(1, 2) as $no) {
            $line = $text[$no]['lines'][0];
            $rect = $crop['fields'][$no - 1]['rect'];
            check(abs($line['x'] - $rect[0]) < 1 && abs($line['y'] - $rect[1]) < 2, "Text and field share coordinates on page {$no}");
        }
        exec('pdftocairo -cropbox -png -r 72 -f 2 -l 2 -singlefile ' . escapeshellarg($dir . 'crop.pdf') . ' ' . escapeshellarg($dir . 'crop2') . ' 2>&1', $o, $code);
        $size = $code === 0 ? @getimagesize($dir . 'crop2.png') : null;
        check($size && $size[0] == 600 && $size[1] == 400, 'OCR image of a cropped rotated page has the displayed size');

        $items = array(array('kind' => 'text', 'page' => 2, 'x' => $text[2]['lines'][0]['x'], 'y' => $text[2]['lines'][0]['y'], 'text' => 'MARK', 'fontSize' => 10, 'align' => 'left'));
        check(!pdfform_Overlay::create($dir . 'cov.pdf', $crop['pages'], $items, array()), 'Overlay for the cropped pages is created');
        file_put_contents($dir . 'cspec.json', json_encode(array('fields' => (object) array(), 'overlay' => $dir . 'cov.pdf', 'flatten' => false)));
        $res = $run(array('fill', $dir . 'crop.pdf', $dir . 'cspec.json', $dir . 'cout.pdf', $dir . 'cfill.json'));
        $mark = null;
        foreach ($layout($dir . 'cout.pdf')[2]['lines'] ?? array() as $line) {
            if ($line['text'] === 'MARK') $mark = $line;
        }
        check(!empty($res['ok']) && $mark && abs($mark['x'] - $items[0]['x']) < 1 && abs($mark['y'] - $items[0]['y']) < 2, 'Item is drawn at the position read from the cropped rotated page');
    }

    array_map('unlink', glob($dir . '*'));
    rmdir($dir);
}

echo "OK {$checks} checks\n";
