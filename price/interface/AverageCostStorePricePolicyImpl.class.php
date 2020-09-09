<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Средна складова"
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
 * @title Мениджърска себестойност "Средна складова"
 *
 */
class price_interface_AverageCostStorePricePolicyImpl extends price_interface_BaseCostPolicy
{
    
    
    /**
     * Може ли кешираната цена да бъде редактирана от потребителя
     *
     * @var boolean
     */
    protected $canEditPrice = true;
    
    
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
        $res = ($verbal) ? tr('Средна складова') : 'averageStorePrice';
        
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
        
        $lastRecs = array();
        $lastQuery = price_ProductCosts::getQuery();
        $lastQuery->where("#classId = {$this->getClassId()}");
        $lastQuery->in("productId", $affectedTargetedProducts);
        while($lastRec = $lastQuery->fetch()){
            $lastRecs[$lastRec->productId] = $lastRec;
        }
       
        // Нормализираме записите
        $accCosts = cls::get('price_interface_LastAccCostPolicyImpl')->getCosts($affectedTargetedProducts);
       
        foreach ($affectedTargetedProducts as $productId){
            if(!is_object($accCosts[$productId])){
                wp($accCosts[$productId]); 
            }
            
            $debitAccPrice = $accCosts[$productId]->price;
            $debitAccQuantity = $accCosts[$productId]->quantity;
            $obj = (object) array('sourceClassId' => null,
                                  'sourceId'      => null,
                                  'productId'     => $productId,
                                  'accPrice'      => $debitAccPrice,
                                  'quantity'      => $debitAccQuantity,
                                  'classId'       => $this->getClassId(),);
            
            if(array_key_exists($productId, $lastRecs)){
                $oldQuantity = $lastRecs[$productId]->quantity;
                $oldAccPrice = $lastRecs[$productId]->accPrice;
                $oldAveragePrice = $lastRecs[$productId]->price;
                
                if($debitAccQuantity > $oldQuantity){
                    $debitAccQuantityDelimeter = (empty($debitAccQuantity)) ? -1 : $debitAccQuantity;
                    $averagePrice = ($oldQuantity * $oldAveragePrice + ($debitAccQuantity * $debitAccPrice - $oldQuantity * $oldAccPrice)) / $debitAccQuantityDelimeter;
                } else {
                    $averagePrice = $oldAveragePrice;
                }
                
            } else {
                $averagePrice = $debitAccPrice;
            }
            
            $obj->quantity = $debitAccQuantity;
            $obj->price = $averagePrice;
            $res[$productId] = $obj;
        }
        
        return $res;
    }
}