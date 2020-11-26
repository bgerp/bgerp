<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Складова себестойност"
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
 * @title Мениджърска себестойност "Складова себестойност"
 *
 */
class price_interface_LastAccCostPolicyImpl extends price_interface_BaseCostPolicy
{
    
    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'price_CostPolicyIntf';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * Как се казва политиката
     *
     * @param bool $verbal - вербалното име или системното
     *
     * @return string
     */
    public function getName($verbal = false)
    {
        $res = ($verbal) ? tr('Складова себестойност') : 'accCost';
        
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
     *         ['valior']        - вальор
     *         ['sourceClassId'] - ид на класа на източника
     *         ['sourceId']      - ид на източника
     */
    public function getCosts($affectedTargetedProducts)
    {
        $tmpArr = $res = array();
        $balanceRec = acc_Balances::getLastBalance();
        
        // Ако няма баланс няма какво да подготвяме
        if (empty($balanceRec)) {
            
            return $res;
        } else {
            if(is_array($affectedTargetedProducts)){
                foreach ($affectedTargetedProducts as $key => $productId){
                    if(array_key_exists($productId, self::$cache)){
                        $res[$productId] = self::$cache[$productId];
                        unset($affectedTargetedProducts[$key]);
                    }
                }
            }
            
            if(!countR($affectedTargetedProducts)){
                
                return $res;
            }
        }
        
        $productClassId = cat_Products::getClassId();
        $productMap = array();
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#classId={$productClassId}");
        $iQuery->in('objectId', $affectedTargetedProducts);
        $iQuery->show('id,objectId');
        while($iRec = $iQuery->fetch()){
            $productMap[$iRec->objectId] = $iRec->id;
        }
       
        if(!countR($productMap)) {
            
            return $res;
        }
        
        // Филтриране да се показват само записите от зададените сметки
        $dQuery = acc_BalanceDetails::getQuery();
        acc_BalanceDetails::filterQuery($dQuery, $balanceRec->id, '321', null, null, $productMap);
        $positionId = acc_Lists::getPosition('321', 'cat_ProductAccRegIntf');
        
        // За всеки запис в баланса
        while ($dRec = $dQuery->fetch()) {
            $itemId = $dRec->{"ent{$positionId}Id"};
            if (!array_key_exists($itemId, $tmpArr)) {
                $tmpArr[$itemId] = new stdClass();
            }
            
            // Сумираме сумите и количествата
            if ($dRec->blQuantity >= 0) {
                $tmpArr[$itemId]->quantity += $dRec->blQuantity;
                $tmpArr[$itemId]->amount += $dRec->blAmount;
            }
        }
        
        // Намираме цената
        $productMap = array_flip($productMap);
        foreach ($tmpArr as $index => $r) {
            $pId = $productMap[$index];
            $amount = (!$r->quantity) ? 0 : round($r->amount / $r->quantity, 5);
            $r->quantity = (!$r->quantity) ? 0 : $r->quantity;
            
            $res[$pId] = (object)array('productId'     => $pId,
                                       'price'         => $amount,
                                       'classId'       => $this->getClassId(),
                                       'valior'        => null,
                                       'sourceClassId' => null,
                                       'sourceId'      => null,
                                       'quantity'      => $r->quantity);
           
            self::$cache[$pId] = $res[$pId];
        }
        
        // Връщаме резултатите
        return $res;
    }
}