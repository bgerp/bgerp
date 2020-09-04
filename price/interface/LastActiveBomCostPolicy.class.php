<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Последна рецепта"
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see price_CostPolicyIntf
 * @title Мениджърска себестойност "Последна рецепта"
 *
 */
class price_interface_LastActiveBomCostPolicy extends price_interface_BaseCostPolicy
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
        $res = ($verbal) ? tr('Последна рецепта') : 'lastBomPolicy';
        
        return $res;
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
     *         ['accPrice']      - счетоводна цена
     *         ['sourceClassId'] - ид на класа на източника
     *         ['sourceId']      - ид на източника
     */
    public function getCosts($affectedTargetedProducts)
    {
        $res = array();
        
        if(!countR($affectedTargetedProducts)){
            
            return $res;
        }
        
        $cache = array();
        $now = dt::now();
        $classId = cat_Boms::getClassId();
        
        // За всеки артикул
        foreach ($affectedTargetedProducts as $productId) {
            
            // Търсим му рецептата
            if ($bomRec = cat_Products::getLastActiveBom($productId)) {
                if (!isset($cache[$bomRec->id])) {
                    
                    // Ако има, намираме и цената
                    $t = ($bomRec->quantityForPrice) ? $bomRec->quantityForPrice : $bomRec->quantity;
                    $cache[$bomRec->id] = cat_Boms::getBomPrice($bomRec, $t, 0, 0, $now, price_ListRules::PRICE_LIST_COST);
                }
                
                $primeCost = $cache[$bomRec->id];
                if ($primeCost) {
                    $res[$productId] = (object) array('sourceClassId' => $classId,
                                                      'sourceId'      => $bomRec->id,
                                                      'productId'     => $productId,
                                                      'quantity'      => $t,
                                                      'accPrice'      => null,
                                                      'classId'       => $this->getClassId(),
                                                      'price'         => $primeCost);
                }
            }
        }
        
        // Връщаме намрените цени
        return $res;
    }
}