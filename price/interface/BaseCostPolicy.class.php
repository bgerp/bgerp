<?php


/**
 * Базов интерфейс-имплементация за изчисление на мениджърска себестойност
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
 * @see базов интерфейс за изчисление на мениджърска себестойност
 *
 */
abstract class price_interface_BaseCostPolicy extends core_BaseClass
{
    
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
        $affectedTargetedProducts = $this->getAffectedTargetedProducts($affectedProducts);
        $result = $this->getCosts($affectedTargetedProducts);
        
        return $result;
    }
    
    
    /**
     * Връща всички покупки, в които участват подадените артикули.
     * Покупките са подредени в низходящ ред, така най-първите са последните.
     *
     * @param array $productKeys  - масив с ид-та на артикули
     * @param bool  $withDelivery - дали да има доставено по покупката или не
     * @param bool  $onlyActive   - дали да търси само по активните покупки
     *
     * @return array $res           - намерените последни доставни цени
     */
    protected function getPurchasesWithProducts($productKeys, $withDelivery = false, $onlyActive = false)
    {
        $pQuery = purchase_PurchasesDetails::getQuery();
        $pQuery->EXT('state', 'purchase_Purchases', 'externalName=state,externalKey=requestId');
        $pQuery->EXT('containerId', 'purchase_Purchases', 'externalName=containerId,externalKey=requestId');
        $pQuery->EXT('valior', 'purchase_Purchases', 'externalName=valior,externalKey=requestId');
        $pQuery->EXT('modifiedOn', 'purchase_Purchases', 'externalName=modifiedOn,externalKey=requestId');
        $pQuery->EXT('amountDelivered', 'purchase_Purchases', 'externalName=amountDelivered,externalKey=requestId');
        
        // Всички активни
        if ($onlyActive === true) {
            $pQuery->where("#state = 'active'");
        } else {
            $pQuery->where("#state = 'active' OR #state = 'closed'");
        }
        
        // и тези които са затворени и са последно модифицирани до два часа
        //$from = dt::addSecs(-2 * 60 * 60, dt::now());
        $pQuery->orWhere("#state = 'closed'");// AND #modifiedOn >= '{$from}'
        
        if ($withDelivery === true) {
            $pQuery->EXT('threadId', 'purchase_Purchases', 'externalName=threadId,externalKey=requestId');
            $pQuery->where('#amountDelivered IS NOT NULL AND #amountDelivered != 0');
            $pQuery->show('price,productId,threadId,requestId,containerId,quantity');
        } else {
            $pQuery->where('#amountDelivered IS NULL OR #amountDelivered = 0');
            $pQuery->show('price,productId,requestId,quantity,containerId');
        }
        
        if(countR($productKeys)){
            $pQuery->in('productId', $productKeys);
        } else {
            $pQuery->where("1=2");
        }
        $pQuery->orderBy('valior,id', 'DESC');
        
        // Връщаме намерените резултати
        return $pQuery->fetchAll();
    }
    
    
    /**
     * За кои от посочените артикули има права за обновяване
     * 
     * @param array $affectedProducts
     * 
     * @return array $result
     */
    protected function getAffectedTargetedProducts($affectedProducts)
    {
        $result = array();
        $affectedProducts = arr::make($affectedProducts);
        if(!countR($affectedProducts)) {
            
            return $result;
        }
       
        $uQuery = price_Updates::getQuery();
        $uQuery->where("#sourceClass1 = {$this->getClassId()} OR #sourceClass2 = {$this->getClassId()} OR #sourceClass3 = {$this->getClassId()}");
        $uQuery->show('type,objectId');
        $uRecs = $uQuery->fetchAll();
        
        $categoryClassId = cat_Categories::getClassId();
        $pQuery = cat_Products::getQuery();
        $pQuery->EXT('folderClassId', 'doc_Folders', 'externalName=coverClass,externalKey=folderId');
        $pQuery->EXT('folderCoverId', 'doc_Folders', 'externalName=coverId,externalKey=folderId');
        $pQuery->where("#state = 'active' AND #folderClassId = {$categoryClassId} AND #isPublic = 'yes' AND #canStore = 'yes' AND (#canBuy = 'yes' OR #canManifacture = 'yes' OR #canSell = 'yes')");
        $pQuery->in('id', $affectedProducts);
        $pQuery->show('id,folderCoverId');
      
        while($pRec = $pQuery->fetch()){
            array_walk($uRecs, function ($a) use ($pRec, &$result) {
                if(($a->type == 'product' && $a->objectId == $pRec->id) || ($a->type == 'category' && $a->objectId == $pRec->folderCoverId)){
                    $result[$pRec->id] = $pRec->id;
                }});
        }
        
        return $result;
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
    abstract public function getCosts($affectedTargetedProducts);
    
    
    /**
     * Дали има самостоятелен крон процес за изчисление
     *
     * @return boolean
     */
    public function hasSeparateCalcProcess()
    {
        return false;
    }
}


