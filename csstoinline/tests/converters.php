<?php

/**
 * Изолирани проверки на адаптерите, версиите и общия филтър за CSS селектори.
 *
 * @category vendors
 * @package csstoinline
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require __DIR__ . '/bootstrap.php';
$checks = 0;
/** Проверява условие и отчита успешно изпълнена проверка. */
function check($condition, $message) { global $checks; expect($condition, $message); $checks++; }
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Emogrifier.class.php';
require dirname(__DIR__) . '/CssToInline.class.php';
require dirname(__DIR__) . '/SelectorFilter.class.php';
require __DIR__ . '/fixtures.php';

class TestConfigForm
{
    public $rec;
    public $hidden = array();
    /** Формата вече съдържа синтетичния избор. */
    public function input($name, $mode) {}
    /** Отчита кои полета остават скрити след смяна на класа. */
    public function setField($name, $params) { $this->hidden[$name] = $params; }
}

$dom = new DOMDocument();
$dom->loadHTML('<div class="present second"><p class="Case qa-name">Text</p></div>');
$filter = new csstoinline_SelectorFilter(new DOMXPath($dom));
foreach (array('.missing', '.present.missing', '.missing > p', '.present + .missing', '.present ~ .missing', '#id.missing') as $selector) {
    check(!$filter->mayMatch($selector), 'Absent class: ' . $selector);
}
foreach (array('.present', '.present.second > p.Case', '.qa-name', '*', 'p', ' .Case ',
    '[title=".missing"]', ':not(.missing)', ':is(.present,.missing)', '.missing:nth-child(2)',
    '.escaped\\.missing', '.кирилица', '.missing..broken', '.missing >', '.missing, .present') as $selector) {
    check($filter->mayMatch($selector), 'Present or delegated selector: ' . $selector);
}
check(!$filter->mayMatch('.case'), 'Classes remain case-sensitive');

check(CSSTOINLINE_CONVERTER_CLASS === 'csstoinline_Emogrifier', 'Legacy converter default');
check(CSSTOINLINE_EMOGRIFIER_VERSION === '2014.11.10', 'Legacy Emogrifier default');
check(CSSTOINLINE_CSSTOINLINE_VERSION === '1.0.3', 'Legacy CssToInline default');
$setup = new csstoinline_Setup();
check(isset($setup->configDescription['CSSTOINLINE_EMOGRIFIER_VERSION']), 'Emogrifier version in web config');
check(isset($setup->configDescription['CSSTOINLINE_CSSTOINLINE_VERSION']), 'CssToInline version in web config');
foreach (array('csstoinline_Emogrifier' => 'CSSTOINLINE_CSSTOINLINE_VERSION', 'csstoinline_CssToInline' => 'CSSTOINLINE_EMOGRIFIER_VERSION') as $class => $hidden) {
    $form = new TestConfigForm();
    $form->rec = (object) array('CSSTOINLINE_CONVERTER_CLASS' => $class,
        'CSSTOINLINE_EMOGRIFIER_VERSION' => '8.3.0', 'CSSTOINLINE_CSSTOINLINE_VERSION' => '2.4.0');
    $before = clone $form->rec;
    $setup->manageConfigDescriptionForm($form);
    check($form->hidden === array($hidden => 'input=hidden'), 'Only selected converter version is visible');
    check($form->rec == $before, 'Hidden version and selected class are preserved');
}
$versions = array('csstoinline_Emogrifier' => array('2011.10.26', '2014.11.10', '3.0.0', '8.3.0'),
    'csstoinline_CssToInline' => array('1.0.3', '2.2.2', '2.4.0'));
foreach ($versions as $class => $available) {
    foreach (array_merge($available, array_reverse($available)) as $version) {
        core_Packs::$config = (object) array('CSSTOINLINE_CONVERTER_CLASS' => $class,
            'CSSTOINLINE_EMOGRIFIER_VERSION' => $class === 'csstoinline_Emogrifier' ? $version : '2014.11.10',
            'CSSTOINLINE_CSSTOINLINE_VERSION' => $class === 'csstoinline_CssToInline' ? $version : '1.0.3');
        core_Composer::$requested = array();
        core_Composer::$loads = 0;
        $setup->install();
        $modern = in_array($version, array('3.0.0', '8.3.0', '2.2.2', '2.4.0'), true);
        $dependencies = $version === '8.3.0' ? array(array('symfony/css-selector', '5.4.45'), array('sabberworm/php-css-parser', '9.5.0'))
            : ($version === '2.4.0' ? array(array('symfony/css-selector', '5.4.45'))
                : ($modern ? array(array('symfony/css-selector', '^5.4')) : array()));
        check(core_Composer::$requested === $dependencies, "$class $version dependency only when selected");
        $converter = new $class();
        $html = '<html><body><table><tr><td class="numeric" id="amount">42.00 EUR</td></tr></table><p class="title">Синтетичен текст</p></body></html>';
        $css = '.missing { color:red; } td.numeric { color:blue; } p.title { font-weight:bold; }';
        $out = $converter->convert($html, $css);
        $parsed = new DOMDocument();
        @$parsed->loadHTML('<?xml encoding="UTF-8">' . $out);
        $xpath = new DOMXPath($parsed);
        check(strpos($xpath->query('//*[@id="amount"]')->item(0)->getAttribute('style'), 'blue') !== false, "$class $version: numeric style");
        check(strpos($out, 'red') === false, "$class $version: absent class ignored");
        check(strpos($parsed->textContent, 'Синтетичен текст') !== false, "$class $version: Cyrillic preserved");
        check(strpos($parsed->textContent, '42.00 EUR') !== false, "$class $version: amount preserved");
        check((core_Composer::$loads > 0) === $modern, "$class $version: old versions do not load Composer");
        $again = $converter->convert('<p class="fresh">Next</p>', '.fresh { color:green; }');
        check(strpos($again, 'green') !== false, "$class $version: independent next document");
        if ($version === '3.0.0') {
            $unsupportedCss = '.title:last-of-type { color:red; } p.title { color:blue; }';
            $unsupported = $converter->convert('<p class="title">Text</p>', $unsupportedCss);
            check(strpos($unsupported, 'blue') !== false, 'Unsupported selector does not stop subsequent valid rules');
            $thrown = false;
            try {
                $debugInliner = \Pelago\Emogrifier\CssInliner::fromHtml('<p class="title">Text</p>');
                $debugInliner->setDebug(true);
                $debugInliner->inlineCss($unsupportedCss);
            } catch (\Symfony\Component\CssSelector\Exception\ExceptionInterface $e) {
                $thrown = true;
            }
            check($thrown, 'Debug mode still reports unsupported selectors');
        }
        echo "PASS: $class $version\n";
    }
}

$fixtures = csstoinlineTestFixtures();
foreach (array('csstoinline_Emogrifier', 'csstoinline_CssToInline') as $class) {
    core_Packs::$config = (object) array('CSSTOINLINE_EMOGRIFIER_VERSION' => '8.3.0', 'CSSTOINLINE_CSSTOINLINE_VERSION' => '2.4.0');
    $converter = new $class();
    foreach (array('invoice', 'large') as $name) {
        list($html, $css) = $fixtures[$name];
        $out = $converter->convert($html, $css);
        $dom = new DOMDocument();
        $dom->loadHTML($out);
        $xpath = new DOMXPath($dom);
        $expectedTotal = $name === 'invoice' ? '50.40 EUR' : '42000.00 EUR';
        check(strpos($dom->textContent, $expectedTotal) !== false, "$class $name: total unchanged");
        check($xpath->query('//tr')->length === ($name === 'invoice' ? 4 : 1001), "$class $name: all table rows retained");
        check(strpos($xpath->query('//td[@class="numeric amount"]')->item(0)->getAttribute('style'), 'text-align: right') !== false,
            "$class $name: amounts right-aligned");
        if ($name === 'invoice') {
            // CssToInlineStyles дава предимство на inline стила дори пред външен !important.
            $important = $class === 'csstoinline_Emogrifier' ? 'green' : 'purple';
            $embedded = $class === 'csstoinline_Emogrifier' ? 'green' : 'red';
            foreach (array('inline' => 'purple', 'important' => $important, 'inline-important' => 'purple', 'order' => 'blue', 'embedded' => $embedded) as $id => $color) {
                $style = $xpath->query('//*[@id="' . $id . '"]')->item(0)->getAttribute('style');
                check((bool) preg_match('/(?:^|;)\s*color:\s*' . $color . '\s*(?:!important)?\s*(?:;|$)/i', $style),
                    "$class: cascade $id ($style)");
            }
            check(strpos($dom->textContent, 'Кирилица: Щъркел, € и &') !== false, "$class: UTF-8 text and entities retained");
            $hidden = $xpath->query('//*[@id="hidden"]');
            check($class === 'csstoinline_Emogrifier' ? $hidden->length === 0
                : strpos($hidden->item(0)->getAttribute('style'), 'display: none') !== false, "$class: hidden-element policy retained");
        }
    }
    // Инсталации без записана настройка продължават да използват досегашната версия.
    core_Packs::$config = (object) array();
    $fallback = $converter->convert('<p class="test">Кирилица</p>', '.test { color:blue; }');
    core_Packs::$config = (object) array('CSSTOINLINE_EMOGRIFIER_VERSION' => '2014.11.10', 'CSSTOINLINE_CSSTOINLINE_VERSION' => '1.0.3');
    check($fallback === $converter->convert('<p class="test">Кирилица</p>', '.test { color:blue; }'), "$class: missing setting uses legacy default");
}
echo "PASS: {$checks} converter checks\n";
