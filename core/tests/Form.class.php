<?php


/**
 * Unit тестове за формите
 *
 * @category  ef
 * @package   core
 *
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 */
class core_tests_Form extends unit_Class
{
    /**
     * Проверява групирането по първите две части на трикомпонентните кепшъни
     */
    public static function test_MiddleCaptionGrouping(core_Form $Form)
    {
        $fields = array(
            'first' => self::getField('first', 'Section->Shared group->Allow'),
            'different' => self::getField('different', 'Section->Other group->Choose'),
            'second' => self::getField('second', 'Section->Shared group->Alternative'),
            'otherSection' => self::getField('otherSection', 'Other section->Shared group->Allow'),
            'legacyFirst' => self::getField('legacyFirst', 'Legacy section->First'),
            'legacySecond' => self::getField('legacySecond', 'Legacy section->Second'),
        );

        $html = $Form->renderFieldsLayout($fields, array())->getContent();

        ut::expectEqual(substr_count($html, "<div class='formMiddleCaption'>Shared group</div>"), 2);
        ut::expectEqual(substr_count($html, "<div class='formMiddleCaption'>Other group</div>"), 1);
        ut::expectEqual(substr_count($html, '<div class="formGroup" >Section'), 1);
        ut::expectEqual(substr_count($html, '<div class="formGroup" >Legacy section'), 1);

        $firstPos = strpos($html, 'filed-first');
        $secondPos = strpos($html, 'filed-second');
        $differentPos = strpos($html, 'filed-different');
        ut::expectEqual($firstPos !== false && $firstPos < $secondPos, true);
        ut::expectEqual($secondPos !== false && $secondPos < $differentPos, true);
    }


    /**
     * Създава минимално поле за проверка на общия рендер на формите
     */
    private static function getField($name, $caption)
    {
        return (object) array(
            'name' => $name,
            'kind' => 'FNC',
            'caption' => $caption,
            'input' => 'input',
        );
    }
}
