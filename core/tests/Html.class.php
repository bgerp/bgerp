<?php


/**
 * Unit тестове за HTML помощните методи
 *
 * @category  ef
 * @package   core
 *
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 */
class core_tests_Html extends unit_Class
{
    /**
     * Проверява видимата празна радио опция с надпис от placeholder
     */
    public static function test_AllowEmptyRadioWithPlaceholder(core_Html $Html)
    {
        $options = array('' => '', 'first' => 'Първа', 'second' => 'Втора');
        $html = $Html->createSmartSelect($options, 'choice', '', array('placeholder' => 'Всички'), 4, 4, 3)->getContent();

        ut::expectEqual(substr_count($html, 'type="radio"'), 3);
        ut::expectEqual(strpos($html, '>Всички</label>') !== false, true);
        ut::expectEqual(self::hasCheckedEmptyRadio($html), true);
        ut::expectEqual(self::hasGrayEmptyRadioLabel($html), true);
    }


    /**
     * Проверява добавянето на празна радио опция без зададен placeholder
     */
    public static function test_AllowEmptyRadioWithoutPlaceholder(core_Html $Html)
    {
        $options = array('first' => 'Първа', 'second' => 'Втора');
        $html = $Html->createSmartSelect($options, 'choice', null, array('_isAllowEmpty' => true), 4, 4, 3)->getContent();

        ut::expectEqual(substr_count($html, 'type="radio"'), 3);
        ut::expectEqual(strpos($html, '>' . tr('Без избор') . '</label>') !== false, true);
        ut::expectEqual(self::hasCheckedEmptyRadio($html), true);
        ut::expectEqual(self::hasGrayEmptyRadioLabel($html), true);
    }


    /**
     * Проверява, че над прага за радио група остава стандартен select
     */
    public static function test_AllowEmptySelectAboveRadioLimit(core_Html $Html)
    {
        $options = array('' => '', 'first' => 'Първа', 'second' => 'Втора', 'third' => 'Трета', 'fourth' => 'Четвърта');
        $html = $Html->createSmartSelect($options, 'choice', '', array('placeholder' => 'Всички'), 4, 4, 4)->getContent();

        ut::expectEqual(strpos($html, '<select') !== false, true);
        ut::expectEqual(strpos($html, 'type="radio"') === false, true);
        ut::expectEqual(strpos($html, '>Всички</option>') !== false, true);
    }


    /**
     * Проверява, че служебният placeholder на задължително поле не става празен избор
     */
    public static function test_RequiredRadioDoesNotGetEmptyOption(core_Html $Html)
    {
        $empty = (object) array('title' => 'Изберете', 'attr' => array('disabled' => 'disabled'));
        $options = array('' => $empty, 'first' => 'Първа', 'second' => 'Втора');
        $html = $Html->createSmartSelect($options, 'choice', null, array(), 4, 4, 3)->getContent();

        ut::expectEqual(substr_count($html, 'type="radio"'), 2);
        ut::expectEqual(strpos($html, 'notAllowEmptyRadioHolder') !== false, true);
    }


    /**
     * Коректният HTML, вкл. пропуснатите незадължителни затварящи тагове, не се променя
     */
    public static function test_BalanceTagsKeepsValidHtml(core_Html $Html)
    {
        foreach (array(
            '<b>a</b> <i>b</i> <span style="color:red">c</span> <div class="x">d</div><ul><li> e<br></li><li> f</li></ul>',
            '<ul><li>a<li>b</ul><dl><dt>a<dd>b</dl><p>a<p>b<div>c</div>',
            '<table><tr><td>a<td>b<tr><td>c</table><select><option>a<option>b</select>',
            '<!-- <div> --><script>var s = "</div>";</script><textarea>[b] <div></textarea><b>x</b>',
            'Текст [beer] <img src="x.png"> <br/> <hr> [#richText1#] <svg><path d="M0"/></svg>',
        ) as $html) {
            ut::expectEqual($Html->balanceTags($html), $html);
        }
    }


    /**
     * Незатворените елементи се затварят, а затварящите тагове без отворен елемент отпадат
     */
    public static function test_BalanceTagsClosesAndDrops(core_Html $Html)
    {
        foreach (array(
            'Текст <b>удебелен' => 'Текст <b>удебелен</b>',
            '<div class="info">x' => '<div class="info">x</div>',
            'x</div> y' => 'x y',
            'a</td><td>b' => 'ab',
            'a <div class="x' => 'a &lt;div class="x',
            'x <!-- y' => 'x <!-- y-->',
        ) as $html => $expected) {
            ut::expectEqual($Html->balanceTags($html), $expected);
            ut::expectEqual($Html->balanceTags($expected), $expected);
        }
    }


    /**
     * Грешно вложените елементи се затварят в обхвата си, а форматирането продължава като в браузъра
     */
    public static function test_BalanceTagsMisnesting(core_Html $Html)
    {
        foreach (array(
            '<i> a <b> b </i> c </b> d' => '<i> a <b> b </b></i><b> c </b> d',
            '<div class="info"><b>x</div>y</b>' => '<div class="info"><b>x</b></div><b>y</b>',
            '<b>a <div class="info">b</b> c</div>' => '<b>a <div class="info">b</b> c</div>',
            '<table><tr><td><div>a</td></tr></table></div> b' => '<table><tr><td><div>a</div></td></tr></table> b',
            '<table><tr><td><b>a</td><td>b</td></tr></table>c' => '<table><tr><td><b>a</b></td><td>b</td></tr></table>c',
            '<ul><li><div>a</li><li>b</li></ul></div> c' => '<ul><li><div>a</div></li><li>b</li></ul> c',
            '<a href="1">x<a href="2">y</a>' => '<a href="1">x</a><a href="2">y</a>',
            '<table><tr><td>a</td></tr><table>b' => '<table><tr><td>a</td></tr></table><table>b</table>',
        ) as $html => $expected) {
            ut::expectEqual($Html->balanceTags($html), $expected);
            ut::expectEqual($Html->balanceTags($expected), $expected);
        }
    }


    /**
     * Проверява дали празният радио бутон е маркиран
     */
    private static function hasCheckedEmptyRadio($html)
    {
        return (bool) preg_match('/<input(?=[^>]*type="radio")(?=[^>]*value="")(?=[^>]*checked="checked")[^>]*>/', $html);
    }


    /**
     * Проверява дали надписът на празния радиобутон е в цвета на placeholder
     */
    private static function hasGrayEmptyRadioLabel($html)
    {
        if (!preg_match('/<input(?=[^>]*type="radio")(?=[^>]*value="")(?=[^>]*id="([^"]+)")[^>]*>/', $html, $matches)) {

            return false;
        }

        $id = preg_quote($matches[1], '/');

        return (bool) preg_match('/<label(?=[^>]*for="' . $id . '")(?=[^>]*style="[^"]*color:#777;?)[^>]*>/', $html);
    }
}
