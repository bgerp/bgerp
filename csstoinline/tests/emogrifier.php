<?php

/**
 * Изолирани проверки за CSS селектори и приоритети при вграждане на стилове.
 *
 * @category vendors
 * @package csstoinline
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require dirname(__DIR__) . '/emogrifier/2014.11.10/Emogrifier.php';

$checks = 0;
/** Проверява условие и отчита успешно изпълнена проверка. */
function check($ok, $message)
{
    global $checks;
    if (!$ok) throw new RuntimeException($message);
    $checks++;
}
/** Конвертира синтетичен HTML и връща резултата с XPath за проверките. */
function convertFixture($html, $css, $converter = null)
{
    $converter = $converter ?? new \Pelago\Emogrifier();
    $converter->setHtml($html);
    $converter->setCss($css);
    $result = @$converter->emogrify();
    $dom = new DOMDocument();
    @$dom->loadHTML($result);
    return array($result, new DOMXPath($dom));
}
/** Сравнява декларациите в стила на тестов елемент независимо от реда им. */
function checkStyle($xpath, $id, $style, $message)
{
    $nodes = $xpath->query('//*[@id="' . $id . '"]');
    check($nodes->length === 1, $message . ': element exists');
    $actual = array_filter(array_map('trim', explode(';', $nodes->item(0)->getAttribute('style'))));
    $expected = array_filter(array_map('trim', explode(';', $style)));
    sort($actual);
    sort($expected);
    check($actual === $expected, $message . ': style ' . $nodes->item(0)->getAttribute('style'));
}

list($html, $xpath) = convertFixture(
    '<div class="document"><table class="listTable"><tr><td id="cell" class="numeric price">42.00</td></tr></table></div>',
    '.absent .listTable td { color:red; } .document .listTable td { color:blue; }'
    . ' .numeric.price { text-align:right; } .numeric.missing { color:red; }'
);
checkStyle($xpath, 'cell', 'color: blue;text-align: right;', 'Present and missing compound classes');
check(strpos($html, '42.00') !== false, 'Cell value is preserved');

list($html, $xpath) = convertFixture('<div><p id="first" class="one">A</p><p id="second" class="two">B</p></div>',
    'div > .one { color:blue; } .one + .two { color:green; } .missing + .two { color:red; }');
checkStyle($xpath, 'first', 'color: blue;', 'Child selector');
checkStyle($xpath, 'second', 'color: green;', 'Adjacent sibling');

list($html, $xpath) = convertFixture('<p id="case" class="Present qa-name_2">A</p>',
    '.present { color:red; } .Present { color:blue; } .qa-name_2 { font-weight:bold; }');
checkStyle($xpath, 'case', 'color: blue;font-weight: bold;', 'Case and identifier characters');

list($html, $xpath) = convertFixture('<p id="entity" class="qa&#45;name">A</p>', '.qa-name { color:blue; }');
checkStyle($xpath, 'entity', 'color: blue;', 'HTML entity in class');

// Точки в атрибути и сложни селектори не са доказателство за липсващ клас.
list($html, $xpath) = convertFixture('<p id="attribute" title="sample">A</p><p id="literal" title=".missing">B</p><table><tr><td id="odd">1</td><td id="even">2</td></tr></table>',
    'p[title="sample"] { color:blue; } [title=".missing"] { color:red; } td:nth-child(odd) { color:green; } td:nth-child(even) { color:red; }');
checkStyle($xpath, 'attribute', 'color: blue;', 'Attribute selector');
checkStyle($xpath, 'literal', '', 'Unsupported literal-dot selector retains existing behavior');
checkStyle($xpath, 'odd', 'color: green;', 'Structural selector odd');
checkStyle($xpath, 'even', 'color: red;', 'Structural selector even');

list($html, $xpath) = convertFixture('<style>.present { color:green; }</style><p id="cascade" class="present" style="color:purple">A</p>',
    '.absent, .present { color:blue; font-weight:bold; } #cascade { color:red; }');
checkStyle($xpath, 'cascade', 'color: purple;font-weight: bold;', 'Inline styles retain priority');
list($html, $xpath) = convertFixture('<style>.present { color:green; }</style><p id="embedded" class="present">A</p>',
    '.present { color:blue; }');
checkStyle($xpath, 'embedded', 'color: green;', 'Embedded styles retain order');

list($html, $xpath) = convertFixture('<p id="hidden" class="hidden">Secret</p><p id="visible">Visible</p>',
    '.hidden { display:none; } @media screen { .absent { color:red; } }');
check($xpath->query('//*[@id="hidden"]')->length === 0, 'Hidden nodes are still removed');
check($xpath->query('//*[@id="visible"]')->length === 1, 'Visible nodes remain');
check(strpos($html, '.absent') !== false, 'Media rules remain unchanged');

$converter = new \Pelago\Emogrifier();
convertFixture('<p class="old">Old</p>', '.old { color:red; }', $converter);
list($html, $xpath) = convertFixture('<p id="fresh" class="new">New</p>', '.new { color:blue; }', $converter);
checkStyle($xpath, 'fresh', 'color: blue;', 'Class inventory is rebuilt for each conversion');

echo "PASS: {$checks} CSS checks\n";
