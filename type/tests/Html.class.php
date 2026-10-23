<?php


/**
 * Unit тестове за type_Html
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class type_tests_Html extends unit_Class
{
    /**
     * Без параметъра balance незатворените фрагменти (напр. шаблони) остават непроменени
     */
    public static function test_ToVerbalKeepsFragments(type_Html $Html)
    {
        foreach (array(
            "<div class='document'>[#content#]" => "<div class='document'>[#content#]",
            '</div></div>' => '</div></div>',
            '<p><b>x</p>' => '<b>x',
        ) as $html => $expected) {
            ut::expectEqual($Html->toVerbal($html), $expected);
            ut::expectEqual(core_Type::getByName('html(tinyEditor=no)')->toVerbal($html), $expected);
        }
    }


    /**
     * С html(balance) таговете се балансират, а коректният HTML не се променя
     */
    public static function test_ToVerbalBalance(type_Html $Html)
    {
        $Type = core_Type::getByName('html(rows=5,oembed=none,balance)');

        foreach (array(
            '<div class="info"><b>a</b> <a href="#">b</a><br>c</div>',
            '<ul><li>a<li>b</ul><table><tr><td>a<td>b</table>',
        ) as $html) {
            ut::expectEqual($Type->toVerbal($html), $html);
        }

        foreach (array(
            '<div class="info"><b>текст' => '<div class="info"><b>текст</b></div>',
            'a</div></td></tr> b' => 'a b',
            '<p><b>x</p>' => '<b>x</b>',
        ) as $html => $expected) {
            ut::expectEqual($Type->toVerbal($html), $expected);
        }

        // Текстовият режим не е HTML страница
        Mode::push('text', 'plain');
        try {
            ut::expectEqual($Type->toVerbal('<b>x'), '<b>x');
        } finally {
            Mode::pop('text');
        }
    }


    /**
     * Параметърът не променя полето в базата данни
     */
    public static function test_BalanceKeepsDbField(type_Html $Html)
    {
        foreach (array('html' => 'html(balance)', 'html(128)' => 'html(128,balance)') as $name => $balanced) {
            ut::expectEqual(json_encode(core_Type::getByName($balanced)->getMysqlAttr()), json_encode(core_Type::getByName($name)->getMysqlAttr()));
        }
    }
}
