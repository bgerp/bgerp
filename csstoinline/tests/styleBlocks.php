<?php

/**
 * Проверки на вградените CSS блокове при пречистване на HTML.
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
require dirname(__DIR__) . '/Setup.class.php';
require dirname(__DIR__) . '/Emogrifier.class.php';
require dirname(__DIR__) . '/CssToInline.class.php';
require dirname(__DIR__) . '/ToInline.class.php';
define('EF_TEMP_PATH', sys_get_temp_dir());
define('PURIFIER_TEMP_PATH', sys_get_temp_dir() . '/bgerp-style-block-tests-' . getmypid());
class hclean_Setup
{
    /** Версията се избира за отделен CLI процес. */
    public static function get($name) { global $argv; return $argv[2] ?? '4.19.1'; }
}
require dirname(__DIR__, 2) . '/hclean/Purifier.class.php';

$checks = 0;
/** Проверява крайния HTML след CSS конвертиране и пречистване. */
function check($condition, $message) { global $checks; expect($condition, $message); $checks++; }

$css = '.prices { border-collapse:collapse; } .prices th { background-color:#245a72; color:white; }'
    . ' .prices td { padding:12px; border:1px solid #456789; color:blue; text-align:right; }';
$table = '<table class="prices"><tr><th>Услуга</th></tr><tr><td style="font-weight:bold">12,00 &amp; 10,00</td></tr></table>';
$styles = array('<style>', '<style >', "<style\n>", '<STYLE>', '<style type="text/css">',
    "<style type='text/css'>", '<style type=text/css>', "<style\n type = \"text/css\" >", '<style type=" text/css">');

foreach (array('csstoinline_Emogrifier', 'csstoinline_CssToInline') as $class) {
    core_Packs::$config = (object) array('CSSTOINLINE_CONVERTER_CLASS' => $class,
        'CSSTOINLINE_EMOGRIFIER_VERSION' => '2014.11.10', 'CSSTOINLINE_CSSTOINLINE_VERSION' => '1.0.3');
    foreach ($styles as $style) {
        $html = hclean_Purifier::clean($style . $css . '</style>' . $table, 'UTF-8');
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $header = strtolower($dom->getElementsByTagName('th')->item(0)->getAttribute('style'));
        $cell = strtolower($dom->getElementsByTagName('td')->item(0)->getAttribute('style'));
        check(strpos($header, 'background-color:#245a72;') !== false, "$class $style: header background");
        check(strpos($cell, 'padding:12px;') !== false && strpos($cell, 'border:1px solid #456789;') !== false,
            "$class $style: cell padding and border");
        check(strpos($cell, 'text-align:right;') !== false && strpos($cell, 'font-weight:bold;') !== false,
            "$class $style: alignment and existing inline style");
        check(strpos($dom->textContent, 'Услуга') !== false && strpos($dom->textContent, '12,00 & 10,00') !== false,
            "$class $style: text and values");
    }

    $html = hclean_Purifier::clean('<style>.prices td {color:blue}</style>'
        . '<style>.prices td {color:green}</style><table class="prices"><tr><td>Value</td></tr></table>');
    check(strpos(strtolower($html), 'color:#008000;') !== false, "$class: multiple blocks retain order");
    check(csstoinline_ToInline::inlineCssFromHtml($table) === $table, "$class: HTML without style blocks is unchanged");

    $html = hclean_Purifier::clean('<style>.prices {color:red; background-image:url(javascript:void(0));}</style>'
        . '<p class="prices" onclick="f()">Text</p><script>f()</script><a href="javascript:void(0)">Link</a><iframe></iframe>');
    check(!preg_match('/<script|<iframe|onclick|javascript:/i', $html), "$class: HTML and CSS are still purified");
    check(strpos($html, 'color:#FF0000;') !== false, "$class: valid declaration survives purification");
}

// Структурните селектори и CSS приоритетите изискват Emogrifier или CssToInline 2.x.
$css = '.prices th { background-color:#f0f0f0; } .prices th { background-color:#6b7071 !important; color:#fff; }'
    . ' .prices.columns td:nth-child(3) { background-color:#e3f2e6; }'
    . ' .prices.columns td:nth-child(4) { background-color:#dbf2fd; }'
    . ' .prices.columns td:nth-child(5) { background-color:#fce0e7; }'
    . ' .prices.rows tr:nth-child(1) td { background-color:#e3f2e6; }'
    . ' .prices.rows tr:nth-child(2) td { background-color:#dbf2fd; }'
    . ' .prices.rows tr:nth-child(3) td { background-color:#fce0e7; }';
$table = '<table class="prices columns"><thead><tr><th>Plan</th></tr></thead><tbody>'
    . '<tr><td>Visit</td><td>12</td><td>10</td><td>9</td><td>8</td></tr></tbody></table>'
    . '<table class="prices rows"><thead><tr><th>Level</th></tr></thead><tbody>'
    . '<tr><td>Silver</td></tr><tr><td>Gold</td></tr><tr><td>Platinum</td></tr></tbody></table>';
foreach (array('csstoinline_Emogrifier' => array('2011.10.26', '2014.11.10', '3.0.0', '8.3.0'),
    'csstoinline_CssToInline' => array('2.2.2', '2.4.0')) as $class => $versions) {
    foreach ($versions as $version) {
        core_Packs::$config = (object) array('CSSTOINLINE_CONVERTER_CLASS' => $class,
            'CSSTOINLINE_EMOGRIFIER_VERSION' => $version, 'CSSTOINLINE_CSSTOINLINE_VERSION' => $version);
        $html = hclean_Purifier::clean('<style>' . $css . '</style>' . $table);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $header = strtolower($xpath->query('(//th)[1]')->item(0)->getAttribute('style'));
        check(strpos($header, 'background-color:#6b7071;') !== false, "$class $version: important header background");
        foreach (array('#e3f2e6', '#dbf2fd', '#fce0e7') as $index => $color) {
            $column = $index + 3;
            $row = $index + 1;
            foreach (array('(//table)[1]/tbody/tr/td[' . $column . ']', '(//table)[2]/tbody/tr[' . $row . ']/td') as $selector) {
                $style = strtolower($xpath->query($selector)->item(0)->getAttribute('style'));
                check(strpos($style, 'background-color:' . $color . ';') !== false, "$class $version: $selector color");
            }
        }
    }
}

echo "PASS: {$checks} style block checks\n";
