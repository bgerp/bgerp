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
     * Работен кеш
     */
    protected static $itemCache = array();
    
    
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
    
    
    /**
     * Кои са засегнатите артикули с движения в посочените складове
     * 
     * @param datetime $beforeDate - преди коя дата
     * @param string $type         - дебит, кредит или всички
     * @param array $storeItems    - избрани складове, ако има
     * 
     * @return array $res          - масив с артикулите
     */
    protected function getAffectedProductWithStoreMovement($beforeDate, $type, $storeItems = array())
    {
        expect(in_array($type, array('debit', 'credit', 'all')));
        $itemsWithMovement = $res = array();
        $key = "{$beforeDate}|{$type}|" . implode('-', $storeItems);
        
        if(!array_key_exists($key, static::$itemCache)){
            
            $storeAccId = acc_Accounts::getRecBySystemId('321')->id;
            $jQuery = acc_JournalDetails::getQuery();
            $jQuery->EXT('valior', 'acc_Journal', 'externalKey=journalId');
            $jQuery->EXT('journalCreatedOn', 'acc_Journal', 'externalName=createdOn,externalKey=journalId');
            $jQuery2 = clone $jQuery;
            
            if($type == 'all' || $type == 'debit'){
                
                // Кои пера на артикули са участвали в дебитирането на склад след посочената дата
                $jQuery->where("#debitAccId = {$storeAccId} AND #journalCreatedOn >= '{$beforeDate}'");
                $jQuery->show('debitItem2');
                $jQuery->groupBy('debitItem2');
                
                if(countR($storeItems)){
                    $jQuery->in("debitItem1", $storeItems);
                }
                
                $itemsWithMovement = arr::extractValuesFromArray($jQuery->fetchAll(), 'debitItem2');
            }
            
            
            // Кои пера на артикули са участвали в кредитирането на склад след посочената дата
            if($type == 'all' || $type == 'credit'){
                $jQuery2->where("#creditAccId = {$storeAccId} AND #journalCreatedOn >= '{$beforeDate}'");
                $jQuery2->show('creditItem2');
                $jQuery2->groupBy('creditItem2');
                
                if(countR($storeItems)){
                    $jQuery2->in("creditItem1", $storeItems);
                }
                
                $itemsWithMovement += arr::extractValuesFromArray($jQuery2->fetchAll(), 'creditItem2');
            }
            
            if (countR($itemsWithMovement)) {
                
                // + атикулите, чиито пера са участвали в дебитирането или кредитирането на склад
                $iQuery = acc_Items::getQuery();
                $iQuery->EXT('productState', 'cat_Products', 'externalName=state,externalKey=objectId');
                $iQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=objectId');
                $iQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=objectId');
                $iQuery->EXT('canSell', 'cat_Products', 'externalName=canStore,externalKey=objectId');
                $iQuery->EXT('canBuy', 'cat_Products', 'externalName=canBuy,externalKey=objectId');
                $iQuery->EXT('canManifacture', 'cat_Products', 'externalName=canManifacture,externalKey=objectId');
                $iQuery->where("#state = 'active' AND #classId= " . cat_Products::getClassId());
                $iQuery->where("#isPublic = 'yes' AND #canStore = 'yes' AND #productState = 'active' AND (#canBuy = 'yes' OR #canManifacture = 'yes' OR #canSell = 'yes')");
                $iQuery->in('id', $itemsWithMovement);
                $iQuery->show('id,objectId');
                $iQuery->notIn('objectId', $res);
                $res = arr::extractValuesFromArray($iQuery->fetchAll(), 'objectId');
            }
            
            static::$itemCache[$key] = $res;
        }
        
        return static::$itemCache[$key];
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
        // Всички артикули с движения във всички складове
        $affected = $this->getAffectedProductWithStoreMovement($datetime, 'all');
        
        return $affected;
    }
}