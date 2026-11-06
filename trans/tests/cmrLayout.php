<?php
/**
 * Изолирани проверки на реалния ЧМР шаблон с Chromium и Poppler.
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 5) exit("Usage: php cmrLayout.php COMPOSER_ROOT CHROME_BIN OUTPUT GSIGN_PACKAGE\n");
require $argv[1] . '/vendor/autoload.php';
define('EF_TEMP_PATH', $argv[3]);
if (!is_dir(EF_TEMP_PATH)) mkdir(EF_TEMP_PATH, 0755, true);
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
class chromephp_Setup { public static function get($name) { return $name === 'LOAD_TIMEOUT' ? 5 : 'no'; } }
class core_Debug { public static function startTimer($name) {} public static function stopTimer($name) {} }
require dirname(__DIR__, 2) . '/chromephp/Browser.class.php';
class CmrLayoutPdf extends chromephp_Browser
{
    public function render($browser, $html) { return $this->renderPdf($browser, $html); }
}
$root = dirname(__DIR__, 2);
$css = '';
foreach (array('css/common.css', 'css/Application.css', 'trans/tpl/Cmr.css') as $file) $css .= file_get_contents($root . '/' . $file);
$css .= file_get_contents($argv[4] . '/css/style.css');
// Печатът е синтетичен; няма външни картинки или реални подписи.
$css .= '.documentSignStamp{mask-image:none!important;-webkit-mask-image:none!important}';
$template = file_get_contents(dirname(__DIR__) . '/tpl/SingleLayoutCMR.shtml');
$template = str_replace(array('[#/doc/tpl/DocumentsSingleLayoutHeader.shtml#]', '[#/doc/tpl/DocumentsSingleLayoutFooter.shtml#]', '[#/doc/tpl/Copyright.shtml#]'), array('<div class="document">', '</div>', 'TEST FOOTER END'), $template);
$template = preg_replace('/<!--ET_BEGIN originId-->.*?<!--ET_END originId-->/s', '', $template);
$minimal = array('cmrNumber' => '0000123456', 'senderData' => 'ТЕСТ София', 'consigneeData' => 'TEST Berlin', 'loadingPlace' => 'София', 'deliveryPlace' => 'Berlin', 'loadingDate' => '03.11.26', 'natureOfGoods1' => 'TEST GOODS 1 &lt;&gt;&amp; "', 'numOfPacks1' => '0', 'grossWeight1' => '0', 'volume1' => '0');
$full = $minimal + array('establishedDate' => '02.11.2026');
foreach (array('senderData', 'consigneeData', 'cariersData') as $field) $full[$field] = 'TEST ' . $field . '<br>International transport and distribution department<br>125 Industrial Boulevard, Building 4, Office 123<br>1000 София, България<br>TEST END ' . $field;
foreach (array('loadingPlace', 'deliveryPlace', 'documentsAttached', 'successiveCarriers', 'senderInstructions', 'specialagreements', 'instructionsPayment', 'cashOnDelivery', 'establishedPlace') as $field) $full[$field] = 'TEST ' . $field . ' — Дълъг текст с кирилица, адрес и инструкции за доставка до склад и получател.';
foreach (array('carragePaid', 'sumPaid', 'class', 'number', 'letter', 'natureofGoods') as $field) $full[$field] = '001234567890';
$full['vehicleReg'] = 'CB 1234 AB / CB 5678 CD — TEST TRAILER';
for ($i = 1; $i <= 5; $i++) foreach (array('mark' => 'TEST-MARK-ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 'numOfPacks' => '00123', 'methodOfPacking' => 'TEST cardboard boxes', 'natureOfGoods' => 'TEST GOODS ' . $i . ' — Хартиени опаковки и защитни транспортни материали, различни размери', 'statNum' => '048194000', 'grossWeight' => '12345.67', 'volume' => '123.456') as $field => $value) $full[$field . $i] = $value;
$mixed = $minimal;
$mixed['natureOfGoods3'] = 'TEST GOODS 3';
$mixed['natureOfGoods5'] = 'TEST GOODS 5';
$mixed['vehicleReg'] = 'CB1234AB';
$long = $full;
$long['vehicleReg'] = str_repeat('ABC0123456', 20);
$long['natureOfGoods5'] = 'TEST GOODS 5 ' . str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 8);
$fixtures = array('minimal' => $minimal, 'mixed' => $mixed, 'full' => $full, 'unbroken' => $long);
$stampSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="120"><rect x="4" y="4" width="152" height="112" fill="none" stroke="blue" stroke-width="5"/><text x="25" y="65" fill="blue" font-size="22">TEST ONLY</text></svg>';
$signSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="75"><path d="M5 60L35 10L60 65L100 20L140 50" fill="none" stroke="red" stroke-width="3"/></svg>';
function signatureHtml($stamp, $sign, $rotation, $stampSvg, $signSvg)
{
    $html = '<div class="documentSignatureHolder" style="margin:0 auto"><div class="documentSignSign" style="left:4px;margin-top:-6px;rotate:14deg">';
    if ($sign) $html .= '<img width="150" height="75" src="data:image/svg+xml;base64,' . base64_encode($signSvg) . '">';
    $html .= '</div>';
    if ($stamp) $html .= '<div class="documentSignHolder"><img class="documentSignStamp" width="160" height="120" style="position:relative;bottom:-25px;margin-left:9px;transform:rotate(' . $rotation . 'deg)" src="data:image/svg+xml;base64,' . base64_encode($stampSvg) . '"></div>';
    return $html . '</div>';
}
$browser = (new HeadlessChromium\BrowserFactory($argv[2]))->createBrowser(array('noSandbox' => true, 'customFlags' => array('--remote-allow-origins=*')));
try {
    foreach ($fixtures as $name => $values) {
        foreach (array('none', 'stamp', 'signature', 'both') as $signKind) {
            $v = $values;
            $stamp = in_array($signKind, array('stamp', 'both'));
            $sign = in_array($signKind, array('signature', 'both'));
            $v['CARRIER_STAMP'] = signatureHtml($stamp, false, -15, $stampSvg, $signSvg);
            $v['SENDER_SIGN'] = signatureHtml($stamp, $sign, 14, $stampSvg, $signSvg);
            $v['CARRIER_SIGN'] = signatureHtml($stamp, $sign, -15, $stampSvg, $signSvg);
            foreach (array('export' => 1, 'print-single' => 1, 'print' => 5) as $mode => $copies) {
                $body = '';
                for ($copy = 1; $copy <= $copies; $copy++) {
                    $t = $template;
                    if ($mode === 'export') $t = preg_replace('/<!--ET_BEGIN copyNum-->.*?<!--ET_END copyNum-->/s', '', $t);
                    $v['copyNum'] = $copy;
                    $v['copyTitle'] = 'TEST COPY ' . $copy;
                    $v['colorClass'] = array(1 => 'cmr-red', 2 => 'cmr-blue', 3 => 'cmr-green')[$copy] ?? '';
                    $t = preg_replace_callback('/\[#(\w+)#\]/', function ($m) use ($v) { return $v[$m[1]] ?? ''; }, $t);
                    $body .= $copies === 1 ? $t : '<div class="' . ($copy < $copies ? 'print-break' : 'print-break-last') . '">' . $t . '</div>';
                }
                $script = $mode !== 'export' && is_file(dirname(__DIR__) . '/js/Cmr.js') ? '<script>' . file_get_contents(dirname(__DIR__) . '/js/Cmr.js') . '</script>' : '';
                $html = '<!doctype html><html><head><meta charset="UTF-8"><style>' . $css . '</style></head><body><div class="printing"><div class="wide">' . $body . '</div></div>' . $script . '</body></html>';
                $path = EF_TEMP_PATH . '/' . $name . '-' . $signKind . '-' . $mode;
                file_put_contents($path . '.html', $html);
                $layout = $browser->createPage();
                $layout->setViewport(1000, 900)->await();
                $layout->setHtml($html);
                $errors = $layout->evaluate(<<<'JS'
(() => {
    const errors = [];
    document.querySelectorAll('.cmr-form img').forEach(img => {
        // Печатите запазват естествения си размер и могат да излизат от клетката.
        if (img.classList.contains('documentSignStamp')) return;
        const r = img.getBoundingClientRect(), c = img.closest('td').getBoundingClientRect();
        if (r.left < c.left - 1 || r.right > c.right + 1 || r.top < c.top - 1 || r.bottom > c.bottom + 1) errors.push('Image outside cell');
    });
    document.querySelectorAll('.cmr-form td').forEach(cell => {
        const box = cell.getBoundingClientRect();
        const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT);
        while (walker.nextNode()) {
            const node = walker.currentNode;
            if (!node.textContent.trim() || node.parentElement.closest('td') !== cell) continue;
            const range = document.createRange();
            range.selectNodeContents(node);
            Array.from(range.getClientRects()).forEach(r => {
                if (r.width && (r.left < box.left - 1 || r.right > box.right + 1 || r.bottom > box.bottom + 1)) errors.push('Text outside cell: ' + node.textContent);
            });
        }
    });
    const label = document.querySelector('.cmr-carrier b');
    const stamp = document.querySelector('.cmr-stamp');
    if (+getComputedStyle(label).zIndex <= +getComputedStyle(stamp).zIndex) errors.push('Text below stamp');
    return errors;
})()
JS
                )->getReturnValue();
                $layout->close();
                check(!$errors, $path . ': ' . implode('; ', $errors));
                if ($mode === 'export') {
                    $bytes = (new CmrLayoutPdf())->render($browser, $html);
                } else {
                    $page = $browser->createPage();
                    $page->setViewport(1000, 900)->await();
                    $page->getSession()->sendMessage(new HeadlessChromium\Communication\Message('Emulation.setEmulatedMedia', array('media' => 'print')))->waitForResponse();
                    $page->setHtml($html);
                    $bytes = base64_decode($page->pdf(array('paperWidth' => 8.27, 'paperHeight' => 11.69, 'marginTop' => 0.4, 'marginBottom' => 0.4, 'marginLeft' => 0.4, 'marginRight' => 0.4, 'preferCSSPageSize' => true, 'printBackground' => true))->getBase64());
                    $page->close();
                }
                file_put_contents($path . '.pdf', $bytes);
                $info = array();
                exec('pdfinfo ' . escapeshellarg($path . '.pdf'), $info, $code);
                preg_match('/^Pages:\s+(\d+)/m', implode("\n", $info), $match);
                check($code === 0 && (int) ($match[1] ?? 0) === $copies, "$name/$signKind/$mode: expected $copies pages, got " . ($match[1] ?? '?'));
                exec('pdftotext -layout ' . escapeshellarg($path . '.pdf') . ' ' . escapeshellarg($path . '.txt'), $output, $code);
                $text = file_get_contents($path . '.txt');
                $pages = array_values(array_filter(explode("\f", $text), function ($text) { return trim($text) !== ''; }));
                foreach ($pages as $index => $pageText) {
                    check(substr(trim($pageText), -15) === 'TEST FOOTER END', "$path: content spills after footer on page " . ($index + 1));
                    if ($mode !== 'export') check(substr_count($pageText, 'TEST COPY ' . ($index + 1)) === 1, "$path: wrong copy header");
                }
                foreach (array('0000123456', 'TEST GOODS 1', 'TEST FOOTER END') as $token) check(substr_count($text, $token) === $copies, "$path: missing $token");
                foreach (array(3, 5) as $i) if (isset($values['natureOfGoods' . $i])) check(substr_count($text, 'TEST GOODS ' . $i) === $copies, "$path: missing goods $i");
                echo "$name/$signKind/$mode: OK ($copies pages)\n";
            }
        }
    }
} finally {
    $browser->close();
}
