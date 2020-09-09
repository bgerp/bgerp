<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Средна доставна за наличното"
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
 * @title Мениджърска себестойност "Средна доставна за наличното"
 *
 */
class price_interface_AverageCostPricePolicyImpl extends price_interface_BaseCostPolicy
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
        $res = ($verbal) ? tr('Средна доставна за наличното') : 'averageAccPrice';
        
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
       
        // Извличане на данните за покупки за тези артикули
        $valiorFrom = dt::verbal2mysql(dt::addMonths(-12), false);
        
        $purQuery = purchase_PurchasesData::getQuery();
        $purQuery->in('productId', $affectedTargetedProducts);
        $purQuery->where("#state != 'rejected' AND #valior >= '{$valiorFrom}'");
        $purQuery->show('quantity,price,productId,expenses');
        $purQuery->orderBy('valior,id', "DESC");
        $all = $purQuery->fetchAll();
        
        $groupedArr = array();
        foreach($all as $purRec) {
            $groupedArr[$purRec->productId][] = $purRec;
        }
       
        // Нормализираме записите
        $accCosts = cls::get('price_interface_LastAccCostPolicyImpl')->getCosts($affectedTargetedProducts);
        
        foreach ($affectedTargetedProducts as $productId) {
            
            // Всички покупки на търсения артикул
            $accObject = $accCosts[$productId];
            $foundIn = array_key_exists($productId, $groupedArr) ? $groupedArr[$productId] : array();
            
            $useFirstPurchase = true;
            $averageAmount = 0;
            
            // Ако има положителна наличност
            if(!empty($accObject->quantity)){
                if($accObject->quantity > 0){
                    $useFirstPurchase = false;
                    $availableQuantity = $accObject->quantity;
                    $sum = $quantityByNow = 0;
                    
                    // За всяка покупка от последната към първата
                    foreach ($foundIn as $delData){
                        $delData->quantity = round($delData->quantity, 6);
                        $expensesPerPcs = (!empty($delData->quantity)) ? ($delData->expenses / $delData->quantity) : 0;
                        
                        $quantityByNow += $delData->quantity;
                        if($delData->quantity <= $availableQuantity){
                            $quantity = $delData->quantity;
                        } else {
                            $quantity = $availableQuantity;
                        }
                        
                        $availableQuantity -= $quantity;
                        $sum += $quantity * ($delData->price + $expensesPerPcs);
                        
                        if($availableQuantity <= 0) break;
                    }
                    
                    // Изчисляване на колко е средната
                    if($quantityByNow){
                        $delimeter = min($quantityByNow, $accObject->quantity);
                    } else {
                        $delimeter = $accObject->quantity;
                    }
                    
                    $averageAmount = @round($sum / $delimeter, 4);
                    $averageAmount = core_Math::roundNumber($averageAmount);
                }
            }
            
            // Ако има НЕ положителна наличност, но има покупки, взима се цената от първата + разходите към нея
            if($useFirstPurchase === true && !empty($foundIn)){
                $foundDelRec =  $foundIn[key($foundIn)];
                $foundDelRec->quantity = round($foundDelRec->quantity, 6);
                $expensesPerPcs = (!empty($foundDelRec->quantity)) ? ($foundDelRec->expenses / $foundDelRec->quantity) : 0;
                
                $foundIn = $foundIn[key($foundIn)];
                $averageAmount = $foundIn->price + $expensesPerPcs;
            }
            
            // Искаме средната цена да е Не нулева
            $res[$productId] = (object) array('price'         => $averageAmount,
                                              'productId'     => $productId, 
                                              'classId'       => $this->getClassId(),
                                              'accPrice'      => null,
                                              'sourceClassId' => null,
                                              'sourceId'      => null,
                                              'quantity'      => $accObject->quantity);
        }
        
        return $res;
    }
}
