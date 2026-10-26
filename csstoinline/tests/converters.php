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
// По желание: php csstoinline/tests/converters.php /path/to/vendor/autoload.php
$autoload = $argv[1] ?? dirname(__DIR__, 3) . '/composer/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Missing Composer autoloader; pass its path as the first argument.\n");
    exit(1);
}
require $autoload;
if (!class_exists('Symfony\Component\CssSelector\CssSelectorConverter')) {
    fwrite(STDERR, "Install symfony/css-selector:^5.4 before running all-version tests.\n");
    exit(1);
}
class core_Manager {}
class core_ProtoSetup
{
    /** Замества инсталацията на framework-а без странични ефекти. */
    public function install() { return ''; }
    /** Връща настройката от текущия синтетичен сценарий. */
    public static function get($name) { return core_Packs::$config->{'CSSTOINLINE_' . $name}; }
}
class core_Packs
{
    public static $config;
    /** Предоставя изолираната конфигурация на адаптера. */
    public static function getConfig($name) { return self::$config; }
}
class core_Composer
{
    public static $requested = array(), $loads = 0;
    /** Отчита дали адаптерът е поискал Composer autoload. */
    public static function isInUse() { self::$loads++; return true; }
    /** Записва заявената зависимост, без да инсталира пакети. */
    public static function install($name, $version) { self::$requested[] = array($name, $version); return ''; }
}
class cls
{
    /** В тестовете конфигурацията съдържа името вместо ID на клас. */
    public static function getClassName($name) { return $name; }
}
/** Дефинира настройките по същия начин като framework-а. */
function defIfNot($name, $value) { if (!defined($name)) define($name, $value); }
/** Намира bundled библиотека спрямо хранилището. */
function getFullPath($path) { return dirname(__DIR__, 2) . '/' . $path; }
/** Прекъсва теста при нарушено условие. */
function expect($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$checks = 0;
/** Проверява условие и отчита успешно изпълнена проверка. */
function check($condition, $message) { global $checks; expect($condition, $message); $checks++; }
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Emogrifier.class.php';
require dirname(__DIR__) . '/CssToInline.class.php';
require dirname(__DIR__) . '/SelectorFilter.class.php';

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

check(CSSTOINLINE_EMOGRIFIER_VERSION === '2014.11.10', 'Legacy Emogrifier default');
check(CSSTOINLINE_CSSTOINLINE_VERSION === '1.0.3', 'Legacy CssToInline default');
$setup = new csstoinline_Setup();
check(isset($setup->configDescription['CSSTOINLINE_EMOGRIFIER_VERSION']), 'Emogrifier version in web config');
check(isset($setup->configDescription['CSSTOINLINE_CSSTOINLINE_VERSION']), 'CssToInline version in web config');
$versions = array('csstoinline_Emogrifier' => array('2011.10.26', '2014.11.10', '3.0.0'),
    'csstoinline_CssToInline' => array('1.0.3', '2.2.2'));
foreach ($versions as $class => $available) {
    foreach ($available as $version) {
        core_Packs::$config = (object) array('CSSTOINLINE_CONVERTER_CLASS' => $class,
            'CSSTOINLINE_EMOGRIFIER_VERSION' => $class === 'csstoinline_Emogrifier' ? $version : '2014.11.10',
            'CSSTOINLINE_CSSTOINLINE_VERSION' => $class === 'csstoinline_CssToInline' ? $version : '1.0.3');
        core_Composer::$requested = array();
        core_Composer::$loads = 0;
        $setup->install();
        $modern = in_array($version, array('3.0.0', '2.2.2'), true);
        check(core_Composer::$requested === ($modern ? array(array('symfony/css-selector', '^5.4')) : array()), "$class $version dependency only when selected");
        $converter = new $class();
        $html = '<html><body><table><tr><td class="numeric" id="amount">42.00 EUR</td></tr></table><p class="title">Синтетичен текст</p></body></html>';
        $css = '.missing { color:red; } td.numeric { color:blue; } p.title { font-weight:bold; }';
        $out = @$converter->convert($html, $css);
        $parsed = new DOMDocument();
        @$parsed->loadHTML('<?xml encoding="UTF-8">' . $out);
        $xpath = new DOMXPath($parsed);
        check(strpos($xpath->query('//*[@id="amount"]')->item(0)->getAttribute('style'), 'blue') !== false, "$class $version: numeric style");
        check(strpos($out, 'red') === false, "$class $version: absent class ignored");
        check(strpos($parsed->textContent, 'Синтетичен текст') !== false, "$class $version: Cyrillic preserved");
        check(strpos($parsed->textContent, '42.00 EUR') !== false, "$class $version: amount preserved");
        check((core_Composer::$loads > 0) === $modern, "$class $version: old versions do not load Composer");
        $again = @$converter->convert('<p class="fresh">Next</p>', '.fresh { color:green; }');
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
echo "PASS: {$checks} converter checks\n";
