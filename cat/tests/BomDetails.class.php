<?php


/**
 * Unit тестове за смятането на формулите в рецептите и параметрите
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_tests_BomDetails extends unit_Class
{
    /**
     * Отрицателна стойност на параметър се смята и след минус
     */
    public static function test_NegativeParams(cat_BomDetails $BomDetails)
    {
        $params = array('#1#' => 70, '#2#' => -5, '#3#' => '-2.5', '$Ширина' => -5);
        $cases = array(
            '#1# - #2#' => 75,
            '-#2#' => 5,
            '#1# - #3#' => 72.5,
            '#1# - $Ширина' => 75,
            '#1# * #2#' => -350,
            '#1# + #2#' => 65,
            'abs(#2#)' => 5,
            'defIfNot(#2#, 0)' => -5,
            'max(#2#, 3)' => 3,
        );

        foreach ($cases as $formula => $expected) {
            ut::expectEqual($BomDetails::calcExpr($formula, $params), $expected);
        }
    }
}
