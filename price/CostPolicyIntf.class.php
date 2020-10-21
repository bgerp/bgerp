<?php


/**
 * Интерфейс за мениджърски себестойности
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за мениджърски себестойности
 */
class price_CostPolicyIntf
{
    
    
    /**
     * Как се казва политиката
     * 
     * @param bool $verbal - вербалното име или системното
     * 
     * @return string
     */
    public function getName($verbal = false)
    {
        return $this->class->getName($verbal);
    }
    
    
    /**
     * Изчислява себестойностите на засегнатите артикули
     *
     * @param array $affectedTargetedProducts
     *
     * @return $res
     *         ['classId']       - клас ид на политиката
     *         ['productId']     - ид на артикул
     *         ['quantity']      - количество
     *         ['price']         - ед. цена
     *         ['valior']        - вальор
     *         ['sourceClassId'] - ид на класа на източника
     *         ['sourceId']      - ид на източника
     */
    public function calcCosts($affectedProducts)
    {
       return $this->class->calcCosts($affectedProducts);
    }
}