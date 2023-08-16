<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Последна рецепта (+режийни)"
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see price_CostPolicyIntf
 * @title Мениджърска себестойност "Последна рецепта (+режийни)"
 *
 */
class price_interface_LastActiveBomCostWithExpenses extends price_interface_LastActiveBomCostPolicy
{

    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'price_CostPolicyIntf';


    /**
     * Как се казва политиката
     *
     * @param bool $verbal - вербалното име или системното
     *
     * @return string
     */
    public function getName($verbal = false)
    {
        $res = ($verbal) ? tr('Последна рецепта (+режийни)') : 'lastBomPolicyWithExpenses';

        return $res;
    }


    /**
     * Изчислява себестойностите на засегнатите артикули
     *
     * @param array $affectedTargetedProducts - засегнати артикули
     * @param array $params - параметри
     *
     * @return array
     *         ['classId']       - клас ид на политиката
     *         ['productId']     - ид на артикул
     *         ['quantity']      - количество
     *         ['price']         - ед. цена
     *         ['valior']        - вальор
     *         ['sourceClassId'] - ид на класа на източника
     *         ['sourceId']      - ид на източника
     */
    public function getCosts($affectedTargetedProducts, $params = array())
    {
        // Вика се метода от бащата, но се дига флаг че трябва да се върнат и режийните разходи
        $params['addExpenses'] = true;
        $res = parent::getCosts($affectedTargetedProducts, $params);

        return $res;
    }


    /**
     * Дали има самостоятелен крон процес за изчисление
     *
     * @return boolean
     */
    public function hasSeparateCalcProcess()
    {
        return true;
    }
}