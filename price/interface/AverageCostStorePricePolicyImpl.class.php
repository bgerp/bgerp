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
     *              ['classId']       - клас ид на политиката
     *              ['productId']     - ид на артикул
     *              ['quantity']      - количество
     *              ['price']         - ед. цена
     *              ['valior']        - вальор
     *              ['sourceClassId'] - ид на класа на източника
     *              ['sourceId']      - ид на източника
     */
    public function getCosts($affectedTargetedProducts)
    {
        $res = array();
        
        if (!countR($affectedTargetedProducts)) {
            return $res;
        }
        
        $storeData = $this->getStoreInfo();
        $storeIds = $storeData['storeIds'];
        $storeItems = $storeData['storeItemIds'];
        
        if (!countR($storeIds)) {
            return $res;
        }
        
        $map = $this->getProductItemMap($affectedTargetedProducts);
        $dRecs = $this->getLastDebitRecs(array_keys($map), $storeItems);
        $classId = $this->getClassId();
        
        $exRecs = array();
        $aQuery = price_ProductCosts::getQuery();
        $aQuery->where("#classId = {$classId}");
        $aQuery->in('productId', $affectedTargetedProducts);
        while ($aRec = $aQuery->fetch()) {
            $exRecs[$aRec->productId] = $aRec;
        }
        
        foreach ($map as $itemId => $iMap) {
            $lastDebitRec = $dRecs[$itemId];
            
            if (!is_object($lastDebitRec)) {
                continue;
            }
            
            $debitPrice = (!empty($lastDebitRec->debitQuantity)) ? round($lastDebitRec->amount / $lastDebitRec->debitQuantity, 6) : 0;
            $lastDebitRec->valior = dt::today();
            
            $obj = (object) array('sourceClassId' => null,
                'sourceId' => null,
                'productId' => $iMap->productId,
                'valior' => $lastDebitRec->valior,
                'quantity' => $lastDebitRec->debitQuantity,
                'price' => $debitPrice,
                'classId' => $classId,);
           
            $oldDate = '';
            $oldPrice = 0;
            
            // Ако има съществуващ запис, взимат се данните от него
            if (is_object($exRecs[$iMap->productId])) {
                $lastRec = clone $exRecs[$iMap->productId];
                $oldDate = $lastRec->valior;
                $oldPrice = $lastRec->price;
            }
            
            // Ако вальора на последния дебит е по-голям или равен от съществуващия запис
            if ($lastDebitRec->valior >= $oldDate) {
                
                // Изчисляване на количеството към датата от общите складове
                $Balance = new acc_ActiveShortBalance(array('from' => $lastDebitRec->valior, 'to' => $lastDebitRec->valior, 'accs' => '321', false, true, 'item1' => $storeItems, 'item2' => $itemId, 'null'));
                $bRecs = $Balance->getBalance('321');
                $iQuantity = arr::sumValuesArray($bRecs, 'blQuantity');
                
                if ($iQuantity > 0) {
                    
                    // Взема се новата наличност на артикула, след дебита Q1 и ако тя е положителна:
                    // В модела нека да имаме Q за количество и P за цена. Нека дебита е за сума Qd и цена Pd.
                    // Правим нова цена: (P * (Q1 - Qd) + Pd*Qd)/(Q1), а новото количество е Q1. записваме датата на дебита.
                    $nQuantity = ($iQuantity - $lastDebitRec->debitQuantity);
                    $price = ($oldPrice * $nQuantity + $lastDebitRec->amount) / $iQuantity;
                    $price = round($price, 6);
                    
                    // Ако има сметната цена
                    if ($price) {
                        $obj->price = $price;
                        $obj->quantity = $iQuantity;
                        $res[$iMap->productId] = $obj;
                    }
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * Последните дебити на артикулите
     *
     * @param array $productItemIds   - пера на артикули
     * @param array $storeItemIds     - пера на складове
     * @param boolean $useCachedDate  - използване на кешираната дата
     *
     * @return array $debitRecs
     */
    private function getLastDebitRecs($productItemIds, $storeItemIds, $useCachedDate = true)
    {
        $storeAccId = acc_Accounts::getRecBySystemId('321')->id;
        
        // Дали да се използва кешираната дата
        $lastCalcedDebitTime = null;
        if($useCachedDate){
            $lastCalcedDebitTime = core_Permanent::get('lastCalcedDebitTime');
        }
        
        $debitRecs = array();
        foreach ($productItemIds as $itemId) {
            $jQuery = acc_JournalDetails::getQuery();
            $jQuery->where("#debitAccId = {$storeAccId}");
            $jQuery->EXT('valior', 'acc_Journal', 'externalKey=journalId');
            $jQuery->EXT('journalCreatedOn', 'acc_Journal', 'externalKey=journalId,externalName=createdOn');
            $jQuery->XPR('maxValior', 'double', 'MAX(#valior)');
            $jQuery->XPR('sumDebitQuantity', 'double', 'SUM(#debitQuantity)');
            $jQuery->XPR('sumDebitAmount', 'double', 'SUM(#amount)');
            $jQuery->where("#debitItem2 = {$itemId} AND #debitQuantity >= 0");
            $jQuery->in('debitItem1', $storeItemIds);
            
            $jQuery->show('debitItem1,debitItem2,amount,debitQuantity,valior,journalId,sumDebitQuantity,sumDebitAmount,maxValior');
            $jQuery->orderBy('valior,id', 'desc');
            if(empty($lastCalcedDebitTime)){
                $jQuery->groupBy('journalId');
            } else { 
                $jQuery->where("#journalCreatedOn >= '{$lastCalcedDebitTime}'");
            }
            
            $jRec = $jQuery->fetch();
           
            if (is_object($jRec)) {
                $jRec->debitQuantity = $jRec->sumDebitQuantity;
                $jRec->amount = $jRec->sumDebitAmount;
                $jRec->valior = $jRec->maxValior;
                
                unset($jRec->sumDebitQuantity);
                unset($jRec->sumDebitAmount);
                unset($jRec->maxValior);
                
                $debitRecs[$itemId] = $jRec;
            }
        }

        $lastCalcedDebitTime = dt::now();
        core_Permanent::set('lastCalcedDebitTime', $lastCalcedDebitTime, core_Permanent::IMMORTAL_VALUE);
        
        return $debitRecs;
    }
    
    
    /**
     * Връща масив със съответствието между артикули и техните пера
     *
     * @param array $products
     *
     * @return array $map
     */
    private function getProductItemMap($products, $excludeProducts = array())
    {
        $map = array();
        $productClassId = cat_Products::getClassId();
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#state = 'active' AND #classId = {$productClassId}");// AND #lastUseOn IS NOT NULL
        $iQuery->in('objectId', $products);
        $iQuery->show('id,objectId');
        while ($iRec = $iQuery->fetch()) {
            if (array_key_exists($iRec->objectId, $excludeProducts)) {
                continue;
            }
            
            $map[$iRec->id] = (object) array('id' => $iRec->id, 'productId' => $iRec->objectId);
        }
        
        return $map;
    }
    
    
    /**
     * Помощна ф-я за избраните в настройките складове
     * 
     * @return array $res
     */
    private function getStoreInfo()
    {
        $res = array('storeItemIds' => array());
        $storesKeylist = price_Setup::get('STORE_AVERAGE_PRICES');
        $res['storeIds'] = keylist::toArray($storesKeylist);
        
        foreach ($res['storeIds'] as $storeId) {
            $storeItemId = acc_Items::fetchItem('store_Stores', $storeId)->id;
            $res['storeItemIds'][$storeItemId] = $storeItemId;
        }
        
        return $res;
    }
    
    
    /**
     * Запис за цените в модела
     */
    public static function saveAvgPrices($Type, $oldValue, $newValue)
    {
        // Има ли избрани складове за усредняване
        $me = cls::get(get_called_class());
        
        $storeData = $me->getStoreInfo();
        $storeIds = $storeData['storeIds'];
        $storeItems = $storeData['storeItemIds'];
        
        if (!countR($storeIds)) {
            return;
        }
        
        // Има ли стандартни артикули
        $toSave = array();
        $query = cat_Products::getQuery();
        $query->where("#state = 'active'  AND #canStore = 'yes' AND (#canBuy = 'yes' OR #canManifacture = 'yes' OR #canSell = 'yes')");
        $query->show('id');
        $publicProductIds = arr::extractValuesFromArray($query->fetchAll(), 'id');
        
        if (!countR($publicProductIds)) {
            return;
        }
        
        $now = dt::now();
        $classId = price_interface_AverageCostStorePricePolicyImpl::getClassId();
        
        $query = price_ProductCosts::getQuery();
        $query->where("#classId = {$classId}");
        $exRecs = $query->fetchAll();
        
        // На кои артикули, вече има стойност
        $alreadyCalculatedProductIds = arr::extractValuesFromArray($exRecs, 'productId');
        
        // Кои от тях имат избрана такава политика за обновяване
        $productIdsWithThisPolicyArr = $me->getAffectedTargetedProducts($publicProductIds);
        $count = countR($productIdsWithThisPolicyArr);
        
        if (!$count) {
            return;
        }
        
        core_App::setTimeLimit($count * 0.8, 900);
        
        // Мапване на артикулите с перата и намиране на последните им дебити в посочените складове
        $map = $me->getProductItemMap($productIdsWithThisPolicyArr, $alreadyCalculatedProductIds);
        $dRecs = $me->getLastDebitRecs(array_keys($map), $storeItems, false);
       
        $valiorMap = array();
        foreach ($dRecs as $jRec) {
            $obj = (object) array('sourceClassId' => null,
                'sourceId' => null,
                'productId' => $map[$jRec->debitItem2]->productId,
                'accPrice' => null,
                'quantity' => 0,
                'updatedOn' => $now,
                'valior' => $jRec->valior,
                'classId' => $classId);
            
            $toSave[$jRec->debitItem2] = $obj;
            
            // Записите ще се гръпират по вальор
            $valiorMap[$jRec->valior][] = $jRec->debitItem2;
        }
        
        foreach ($valiorMap as $valior => $pItems) {
            
            // За всяка уникална дата се смятат к-та на артикулите към нея
            $Balance = new acc_ActiveShortBalance(array('from' => $valior, 'to' => $valior, 'accs' => '321', false, true, 'item1' => $storeItems, 'item2' => $pItems, 'null'));
            $bRecs = $Balance->getBalance('321');
            foreach ($pItems as $iId) {
                
                // Сумира се количеството в посочените складове
                $iQuantity = 0;
                array_walk($bRecs, function ($a) use ($iId, &$iQuantity) {
                    if ($a->ent2Id == $iId) {
                        $iQuantity += $a->blQuantity;
                    }
                });
                $toSave[$iId]->quantity = $iQuantity;
                
                // Изчислява се среднопритеглената цена към тази дата за общото количество в посочените складове
                $amount = cat_Products::getWacAmountInStore($toSave[$iId]->quantity, $toSave[$iId]->productId, $valior, $storeIds);
                if ($toSave[$iId]->quantity) {
                    $toSave[$iId]->price = round($amount / $toSave[$iId]->quantity, 6);
                }
                
                // Ако няма цена или има отрицателни количества не ни интересуват
                if ($iQuantity <= 0 || empty($toSave[$iId]->price)) {
                    unset($toSave[$iId]);
                }
            }
        }
        
        $ProductCache = cls::get('price_ProductCosts');
        $ProductCache->saveArray($toSave);
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
        $affected = array();
        
        // Ако има избрани складове, гледа се има ли дебити в тях
        $storeData = $this->getStoreInfo();
        if(countR($storeData['storeItemIds'])){
            $affected = parent::getAffectedProductWithStoreMovement($datetime, 'debit', $storeData['storeItemIds']);
        }
        
        return $affected;
    }
}
