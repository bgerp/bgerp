<?php


/**
 * Unit тестове за компонентите на артикулите
 *
 * @category  ef
 * @package   cat
 *
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 */
class cat_tests_Products extends unit_Class
{
    /**
     * Проверява мащабирането на формулите спрямо базовото количество на рецептата
     */
    public static function test_ComponentQuantityScale(cat_Products $Products)
    {
        $scale = $Products->getComponentQuantityScale(6, 4);
        ut::expectEqual($scale, 1.5);

        $params = array('$T' => $scale);
        $cases = array(
            '$Начално=2' => 2,
            '(1.23 / 2.40) * (4 - $Начално=1.60)' => 2.255,
            '(0.71 / 2.40) * (4 - $Начално=1.60)' => 1.302,
            '(0.23 / 2.40) * (4 - $Начално=1.60)' => 0.422,
        );

        foreach ($cases as $formula => $expected) {
            $quantity = cat_BomDetails::calcExpr($formula, $params) * $scale;
            ut::expectEqual(round($quantity, 3), $expected);
        }

        $unitScale = $Products->getComponentQuantityScale(6, 1);
        $unitQuantity = cat_BomDetails::calcExpr('$Начално=2', array('$T' => $unitScale)) * $unitScale;
        ut::expectEqual(round($unitQuantity, 3), 2.0);
    }
}
