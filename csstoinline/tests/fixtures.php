<?php

/**
 * Синтетични документи за сравнение с официалните библиотеки и браузърна проверка.
 *
 * @category vendors
 * @package csstoinline
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */

/**
 * Връща HTML/CSS двойки без зависимости от записи в конкретна инсталация.
 *
 * @return array
 */
function csstoinlineTestFixtures()
{
    $fixtures = array();
    $fixtures['invoice'] = array(
        '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Синтетична фактура</title>'
        . '<style>.title { color: navy; } .embedded { color: green; }</style></head><body>'
        . '<h1 class="title">Фактура за хартиени торби</h1><p id="inline" class="priority" style="color: purple">Кирилица: Щъркел, € и &amp;</p>'
        . '<p id="important" class="priority" style="color: purple">Важен стил</p><p id="inline-important" class="priority" style="color: purple !important">Важен inline стил</p>'
        . '<p id="order" class="same">Ред на правилата</p><p id="embedded" class="embedded">Вграден стил</p>'
        . '<table class="invoice"><thead><tr><th>Описание</th><th>Количество</th><th>Сума</th></tr></thead>'
        . '<tbody><tr><td>Торби</td><td class="numeric">2</td><td class="numeric amount">42.00 EUR</td></tr>'
        . '<tr><td colspan="2">ДДС</td><td class="numeric">8.40 EUR</td></tr></tbody>'
        . '<tfoot><tr><td colspan="2">Общо</td><td id="total" class="numeric amount">50.40 EUR</td></tr></tfoot></table>'
        . '<p id="hidden" class="hidden">Скрито съдържание</p><p id="visible">Видимо съдържание</p></body></html>',
        'body { font-family: Arial; font-size: 14px; } .title { color: red; }'
        . ' .priority { color: blue; } #important { color: green !important; } #inline-important { color: green !important; }'
        . ' .same { color: red; } .same { color: blue; } .embedded { color: red; }'
        . ' .invoice { border-collapse: collapse; width: 100%; } .invoice td, .invoice th { border: 1px solid #999; padding: 6px; }'
        . ' .numeric { text-align: right; } tfoot .amount { font-weight: bold; } .hidden { display: none; } .absent { color: red; }'
    );
    $fixtures['selectors'] = array(
        '<div class="present second"><p id="first" class="Case qa-name" title=".missing">Едно</p>'
        . '<p class="other" data-label="a,b">Две</p><span class="qa&#45;name">Три</span>'
        . '<p class="escaped.name кирилица">Четири</p></div>',
        '.missing { color:red; } .present.missing { color:red; } .missing > p { color:red; }'
        . ' .present + .missing { color:red; } .present ~ .missing { color:red; } #first.missing { color:red; }'
        . ' .present.second > p.Case { color:blue; } .qa-name { font-weight:bold; } .case { color:red; }'
        . ' .Case + .other { color:green; } .Case ~ span { color:navy; } [title=".missing"] { background:yellow; }'
        . ' :not(.missing) { margin:0; } .other:nth-child(2) { padding:1px; } p:last-of-type { border:0; }'
        . ' .escaped\\.name { color:teal; } .кирилица { font-size:15px; } .missing, .other { text-align:right; }'
        . ' [data-label="a,b"] { font-weight:bold; }'
    );
    $fixtures['preserved-css'] = array(
        '<html><head><style>.present { color:green; }</style></head><body><p class="present">Стилове</p></body></html>',
        '@font-face { font-family: test; src: url(test.woff); } @media screen and (max-width:600px) {'
        . ' .present { color:red; } .missing { color:blue; } } .present:hover { color:orange; }'
        . ' .present::before { content:".missing, { ; }"; } :root { --Accent:blue; } .present { color:var(--Accent); }'
    );
    $fixtures['malformed-selector'] = array('<p class="present">Продължение</p>',
        '.missing..broken { color:red; } .missing > { color:red; } .present { color:green; }');
    $fixtures['class-whitespace'] = array('<p class="one&#9;two&#10;three&#13;four">Интервали</p>',
        '.one.two.three.four { color:blue; } .missing { color:red; }');
    $rows = '';
    for ($i = 0; $i < 1000; $i++) {
        $rows .= '<tr><td>Артикул ' . $i . '</td><td class="numeric">2</td><td class="numeric amount">42.00 EUR</td></tr>';
    }
    $css = '';
    for ($i = 0; $i < 2000; $i++) {
        $css .= '.unused' . $i . ' td { color:red; }';
    }
    $fixtures['large'] = array('<html><head><meta charset="utf-8"></head><body><h1>Голям документ</h1><table>' . $rows
        . '<tfoot><tr><td colspan="2">Общо</td><td class="numeric amount">42000.00 EUR</td></tr></tfoot></table></body></html>',
        $css . 'table { width:100%; border-collapse:collapse; } td { padding:2px; } .numeric { text-align:right; } .amount { font-weight:bold; }');
    $frameworkCss = '';
    foreach (array('common', 'Application', 'email', 'pdf') as $name) {
        $frameworkCss .= file_get_contents(dirname(__DIR__, 2) . '/css/' . $name . '.css') . "\n";
    }
    $fixtures['framework-css'] = array(str_replace('<table>', '<table class="listTable detail-table">', $fixtures['large'][0]), $frameworkCss);

    return $fixtures;
}
