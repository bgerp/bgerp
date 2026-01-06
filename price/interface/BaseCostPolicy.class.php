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
     * @param array $affectedProducts
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
    public function calcCosts($affectedProducts)
    { 
        $result = $this->getCosts($affectedProducts);
        
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
            $pQuery->where("#state IN ('active', 'closed')");
        }
        
        if ($withDelivery === true) {
            $pQuery->EXT('threadId', 'purchase_Purchases', 'externalName=threadId,externalKey=requestId');
            $pQuery->where('#amountDelivered IS NOT NULL AND #amountDelivered != 0');
            $pQuery->show('price,productId,threadId,requestId,containerId,quantity,valior');
        } else {
            $pQuery->where('#amountDelivered IS NULL OR #amountDelivered = 0');
            $pQuery->show('price,productId,requestId,quantity,containerId,valior');
        }
        
        if(countR($productKeys)){
            $pQuery->in('productId', $productKeys);
        } else {
            $pQuery->where("1=2");
        }
        $pQuery->orderBy('valior,requestId', 'DESC');
        
        // Връщаме намерените резултати
        return $pQuery->fetchAll();
    }
    
    
    /**
     * Изчислява себестойностите на засегнатите артикули
     *
     * @param array $affectedTargetedProducts
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
    abstract public function getCosts($affectedTargetedProducts, $params = array());
    
    
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
     * @param datetime $beforeDate       - преди коя дата
     * @param string   $type             - дебит, кредит или всички
     * @param array    $storeItems       - избрани складове, ако има
     * @param array    $skipDocumentArr  - кои документи да се пропуснат
     * @param bool     $onlyStorable     - избрани складове, ако има
     * @return array
     */
    protected function getAffectedProductWithMovement($beforeDate, $type, $storeItems = array(), $skipDocumentArr = array(), $onlyStorable = true)
    {
        expect(in_array($type, array('debit', 'credit', 'all')));
        $itemsWithMovement = $res = array();
        $key = "{$beforeDate}|{$type}|{$onlyStorable}|" . implode('-', $storeItems);
        
        if(!array_key_exists($key, static::$itemCache)){

            $storeAccId = acc_Accounts::getRecBySystemId('321')->id;
            $servicesAccId = acc_Accounts::getRecBySystemId('60201')->id;
            $jQuery = acc_JournalDetails::getQuery();
            $jQuery->EXT('docType', 'acc_Journal', 'externalKey=journalId');
            $jQuery->EXT('valior', 'acc_Journal', 'externalKey=journalId');
            $jQuery->EXT('journalCreatedOn', 'acc_Journal', 'externalName=createdOn,externalKey=journalId');
            
            // Ако има изброени документи за пропускане пропускат се
            if(countR($skipDocumentArr)){
                $jQuery->notIn('docType', $skipDocumentArr);
            }
            
            $jQuery2 = clone $jQuery;
            
            if($type == 'all' || $type == 'debit'){
                if($onlyStorable){

                    // Ако е само за скалдируеми - гледа се само в склада
                    $where = "#debitAccId = {$storeAccId} AND #journalCreatedOn >= '{$beforeDate}' AND #debitQuantity >= 0";
                    $jQuery->where($where);
                } else {

                    // Ако е за всички, гледа се за склада и за сметката на услугите
                    $where = "#debitAccId = {$storeAccId} AND #journalCreatedOn >= '{$beforeDate}' AND #debitQuantity >= 0";
                    $jQuery->setUnion($where);

                    $where = "#debitAccId = {$servicesAccId} AND #journalCreatedOn >= '{$beforeDate}' AND #debitQuantity >= 0";
                    $jQuery->setUnion($where);
                }

                // Кои пера на артикули са участвали в дебитирането на склад след посочената дата, с неотрицателно количество
                $jQuery->show('debitItem2');
                $jQuery->groupBy('debitItem2');
                
                if(countR($storeItems)){
                    $jQuery->in("debitItem1", $storeItems);
                }
                
                $itemsWithMovement = arr::extractValuesFromArray($jQuery->fetchAll(), 'debitItem2');
            }

            // Кои пера на артикули са участвали в кредитирането на склад след посочената дата
            if($type == 'all' || $type == 'credit'){
                if($onlyStorable){

                    // Ако е само за скалдируеми - гледа се само в склада
                    $where = "#creditAccId = {$storeAccId} AND #journalCreatedOn >= '{$beforeDate}' AND #creditQuantity >= 0";
                    $jQuery2->where($where);
                } else {

                    // Ако е за всички, гледа се за склада и за сметката на услугите
                    $where = "#creditAccId = {$storeAccId} AND #journalCreatedOn >= '{$beforeDate}' AND #creditQuantity >= 0";
                    $jQuery2->setUnion($where);

                    $where = "#creditAccId = {$servicesAccId} AND #journalCreatedOn >= '{$beforeDate}' AND #creditQuantity >= 0";
                    $jQuery2->setUnion($where);
                }

                // Кои пера на артикули са участвали в кредитирането на склад след посочената дата, с неотрицателно количество
                //$jQuery2->where($where);
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
                $iQuery->where("#isPublic = 'yes' AND #productState = 'active' AND (#canBuy = 'yes' OR #canManifacture = 'yes' OR #canSell = 'yes')");
                if($onlyStorable){
                    $iQuery->where("#canStore = 'yes' ");
                }
                $iQuery->in('id', $itemsWithMovement);
                $iQuery->show('id,objectId');
                if(countR($res)){
                    $iQuery->notIn('objectId', $res);
                }
                $res = arr::extractValuesFromArray($iQuery->fetchAll(), 'objectId');
            }
            
            static::$itemCache[$key] = $res;
        }

        return static::$itemCache[$key];
    }
    
    
    /**
     * Дали има самостоятелен крон процес за изчисление
     *
     * @param datetime $datetime
     *
     * @return array
     */
    public function getAffectedProducts($datetime)
    {
        // Всички артикули с движения във всички складове
        $affected = $this->getAffectedProductWithMovement($datetime, 'all');
       
        return $affected;
    }
}