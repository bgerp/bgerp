<?php

/**
 * Синтетични HTML документи за PDF конверторите.
 *
 * @category tests
 * @package chromephp
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class chromephp_tests_Fixtures
{
    /** Връща документ с кирилица, суми, стилове, скрити елементи и таблица. */
    public static function html($name)
    {
        $html = '<style>body{font:14px "DejaVu Sans",sans-serif;color:#243447}h1{color:#146c94}.hidden{display:none!important}table{border-collapse:collapse;width:100%}th,td{border:1px solid #8398a8;padding:6px}th{background:#dcebf2}.amount{text-align:right}.total{font-weight:bold;background:#edf6df}thead{display:table-header-group}tr{break-inside:avoid}</style>';
        $html .= '<h1>Тестова фактура - България</h1><p>София | Код 00123 | EUR</p><p class="hidden">HIDDEN_SENTINEL</p>';
        if ($name === 'wide') {
            $html .= '<div class="reportHolder"><table class="frame2Table" style="min-width:1250px"><thead><tr>';
            for ($i = 1; $i <= 12; $i++) $html .= '<th>Колона ' . $i . '</th>';
            $html .= '</tr></thead><tbody><tr>';
            for ($i = 1; $i <= 12; $i++) $html .= '<td>Стойност ' . $i . '</td>';
            return $html . '</tr></tbody></table></div><p>Край на широката таблица</p>';
        }
        $html .= '<table><thead><tr><th>Код</th><th>Описание</th><th>Сума EUR</th></tr></thead><tbody>';
        $rows = $name === 'large' ? 600 : 2;
        for ($i = 1; $i <= $rows; $i++) {
            $html .= '<tr><td>' . sprintf('%05d', $i) . '</td><td>Артикул ' . $i . '</td><td class="amount">' . ($i === 1 ? '12,50' : '25,00') . '</td></tr>';
        }
        $html .= '</tbody><tfoot><tr class="total"><td colspan="2">Общо</td><td class="amount">37,50</td></tr></tfoot></table><p>Край на документа</p>';
        if ($name === 'javascript') {
            $html .= '<p id="script-result">WAITING_SENTINEL</p><script>window.addEventListener("load",function(){document.getElementById("script-result").textContent="JavaScript готов";});</script>';
        }
        if ($name === 'image') {
            $html .= '<svg width="140" height="60" xmlns="http://www.w3.org/2000/svg"><rect width="140" height="60" fill="#16826b"/><text x="8" y="35" fill="white">PDF образец</text></svg>';
        }
        return $html;
    }
}
