<?php


/**
 * Имплементация на изчисляване на мениджърски себестойности "Текуща поръчка"
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
 * @title Мениджърска себестойност "Текуща поръчка"
 *
 */
class price_interface_LastActiveDeliveryCostPolicyImpl extends price_interface_BaseCostPolicy
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
        $res = ($verbal) ? tr('Текуща поръчка') : 'activeDelivery';
        
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
        $res = array();
       
        // Намираме всички покупки по, които няма доставени
        $allPurchases = $this->getPurchasesWithProducts($affectedTargetedProducts, false, true);
        $classId = purchase_Purchases::getClassId();
        
        // За всяка покупка
        foreach ($allPurchases as $purRec) {
            
            // Намираме първата срещната цена за артикула, покупките са подредени по-последно
            // създаване, така сме сигурни че ще се вземе първата срещната цена, която е цената по
            // последна активна поръчка
            if (!isset($res[$purRec->productId])) {
                $res[$purRec->productId] = (object)array('productId'     => $purRec->productId,
                                                         'classId'       => $this->getClassId(),
                                                         'sourceClassId' => $classId,
                                                         'sourceId'      => $purRec->requestId,
                                                         'valior'        => null,
                                                         'quantity'      => $purRec->quantity,
                                                         'price'         => round($purRec->price, 5));
            };
        }
        
        // Връщаме намерените цени
        return $res;
    }
    
    
    /**
     * Дали има самостоятелен крон процес за изчисление
     *
     * @return datetime $datetime
     *
     * @return array
     */
    public function getAffectedProducts($datetime)
    {
        // Участват артикулите в активирани или оттеглени активни покупки, след посочената дата
        $pQuery = purchase_PurchasesDetails::getQuery();
        $pQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        $pQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
        $pQuery->EXT('activatedOn', 'purchase_Purchases', 'externalName=activatedOn,externalKey=requestId');
        $pQuery->EXT('documentModifiedOn', 'purchase_Purchases', 'externalName=modifiedOn,externalKey=requestId');
        $pQuery->EXT('state', 'purchase_Purchases', 'externalName=state,externalKey=requestId');
        $pQuery->where("((#state = 'active' || #state = 'closed') AND #activatedOn >= '{$datetime}') OR (#state = 'rejected' AND #activatedOn IS NOT NULL AND #documentModifiedOn >= '{$datetime}')");
        $pQuery->where("#canStore = 'yes' AND #isPublic = 'yes'");
        $pQuery->show('productId');
        $affected = arr::extractValuesFromArray($pQuery->fetchAll(), 'productId');
        
        return $affected;
    }
}