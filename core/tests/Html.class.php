<?php


/**
 * Unit тестове за HTML помощните методи
 *
 * @category  ef
 * @package   core
 *
 * @copyright 2006 - 2026 Experta OOD
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
