<?php
/**
 * Плъгин който прави извадка от данни за покупките
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class purchase_plg_ExtractPurchasesData extends core_Plugin
{
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec    Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields Имена на полетата, които трябва да бъдат записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if($mvc instanceof store_ShipmentOrders && $rec->isReverse != 'yes') return;
        
        if ($rec->state == 'rejected') {
            
            $docClassId = core_Classes::getId($mvc);
            $dQuery = purchase_PurchasesData::getQuery();
            $dQuery->where("#docClassId = {$docClassId} AND #docId = {$rec->id} ");
            
            $ids = arr::extractValuesFromArray($dQuery->fetchAll(), 'id');
            
            foreach ($ids as $id) {
                $dRec = (object) array('id' => $id, 'state' => $rec->state);
                purchase_PurchasesData::save($dRec, 'id,state');
            }
        }
    }
    
    
    /**
     * Добавя запис в модела за доставките
     */
    public static function add($mvc, $rec)
    {
        $clone = clone $rec;
        
        $clone->threadId = (isset($clone->threadId)) ? $clone->threadId : $mvc->fetchField($clone->id, 'threadId');
        $clone->folderId = (isset($clone->folderId)) ? $clone->folderId : $mvc->fetchField($clone->id, 'folderId');
        
        $Master = doc_Containers::getDocument($clone->containerId);                                                       // На активния документ
        
        $docClassId = core_Classes::getId($Master);                                                                       // на активния документ( ДП, СР или ПП)
        
        $detailClassName = $Master->mainDetail;
        
        $Detail = cls::get($detailClassName);
        
        $masterKey = $Detail->masterKey;
        
        $detailClassId = core_Classes::getId($detailClassName);                                                        // на детайлите на активния документ( ДП при бърза продажба, СР - при засклаждане, ПП - при приемане)
        
        $detQuery = $detailClassName::getQuery();
        
        if ($Master->className == 'store_InventoryNotes') {
            $detQuery->where('#quantity IS NOT NULL');
        }
        
        $detQuery->where("#{$masterKey} = {$clone->id} ");
        
        $details = $detQuery->fetchAll();
        
        $firstDocument = doc_Threads::getFirstDocument($clone->threadId);                                                 // на първия документ в нишката на активния документ
        $className = $firstDocument->className;
        
        $dealerId = $className::fetch($firstDocument->that)->dealerId;
        
        $isFromInventory = ($Master->className == 'store_InventoryNotes') ? 'true' : 'false';
        
        
        //Проверка за бърза прокупка или продажба
        if (!is_null($clone->contoActions)) {
            $cond = (strrpos($clone->contoActions, 'ship') !== false);
        } else {
            $cond = true;
        }
        
        if ($cond) {
            foreach ($details as $detail) {
                
                //Заприходено количество
                $quantity = ($Master->className == 'store_InventoryNotes') ?(round($detail->quantity - $detail->blQuantity, 4)) : $detail->quantity;
                
                //Артикул
                $productId = $detail->productId;
                
                if ($productId) {
                    $measureId = cat_Products::fetchField($productId, 'measureId');
                }
                
                $price = $detail->price;
                $currencyId = $clone->currencyId;
                $currencyRate = $clone->currencyRate;
                $amount = $detail->amount;
                
                //Склад
                $storeId = $clone->storeId;
                
                
                //Ако документа е мемориален ордер
                if ($Master->className == 'acc_Articles') {
                    $price = $detail->debitPrice;
                    if ($amount <= 0 || $price <= 0) {
                        continue;
                    }
                    
                    if ($detail->debitAccId && (acc_Accounts::fetch('#num = 321')->id != $detail->debitAccId)) {
                        continue;
                    }
                    
                    //Артикул
                    $productId = acc_Items::fetch("{$detail->debitEnt2}")->objectId;
                    $measureId = acc_Items::fetch("{$detail->debitEnt2}")->uomId;
                    
                    $currencyId = acc_Items::fetch("{$detail->debitEnt3}")->id;
                    $currencyRate = $detail->creditPrice;
                    
                    //Склад
                    $storeId = acc_Items::fetch("{$detail->debitEnt1}")->id;
                    
                    //Заприходено количество
                    $quantity = $detail->debitQuantity;
                }
                
                if ($quantity < 0) {
                    continue;
                }
                
                $dRec = array();
                $sign = ($rec->isReverse == 'yes') ? -1 : 1;
                
                $dRec = (object) array(
                    
                    'valior' => $clone->valior,
                    'detailClassId' => $detailClassId,
                    'detailRecId' => $detail->id,
                    'state' => $clone->state,
                    'contragentClassId' => $clone->contragentClassId,
                    'contragentId' => $clone->contragentId,
                    'dealerId' => $dealerId,
                    'productId' => $productId,
                    'measureId' => $measureId,
                    'docId' => $clone->id,
                    'docClassId' => $docClassId,
                    'quantity' => $sign * $quantity,
                    'packagingId' => $detail->packagingId,
                    'storeId' => $storeId,
                    'price' => $price,
                    'expenses' => '',
                    'discount' => $detail->discount,
                    'amount' => $sign * $amount,
                    'weight' => $detail->weight,
                    'currencyId' => $currencyId,
                    'currencyRate' => $currencyRate,
                    'createdBy' => $detail->createdBy,
                    'threadId' => $clone->threadId,
                    'folderId' => $clone->folderId,
                    'containerId' => $clone->containerId,
                    'isFromInventory' => $isFromInventory,
                    'canStore' => cat_Products::getProductInfo($productId)->meta['canStore'],
                );
                
                $id = purchase_PurchasesData::fetchField("#detailClassId = {$dRec->detailClassId} AND #detailRecId = {$dRec->detailRecId}");
                
                if (!empty($id)) {
                    $dRec->id = $id;
                }
                
                purchase_PurchasesData::save($dRec);
                
                self::setUpdateOnShutdown($mvc, $rec);
            }
        }
    }
    
    
    /**
     * Записване на кои нишки на се обновят на шътдаун
     */
    public static function setUpdateOnShutdown($mvc, $rec)
    {
        $threadsArr = self::getTrhreadsForUpdate($mvc, $rec);
        
        if (!is_array($mvc->allocateThreadsOnShutdown)) {
            $mvc->allocateThreadsOnShutdown = $threadsArr;
        } else {
            $mvc->allocateThreadsOnShutdown += $threadsArr;
        }
    }
    
    
    /**
     * При оттегляне на документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        if (($mvc instanceof store_Receipts && $rec->isReverse == 'yes') || ($mvc instanceof store_ShipmentOrders && $rec->isReverse != 'yes')) {
            return ;
        }
        
        self::setUpdateOnShutdown($mvc, $rec);
    }
    
    
    /**
     * След всеки запис в журнала
     */
    public static function on_AfterSaveJournalTransaction($mvc, $res, $rec)
    {
        if (($mvc instanceof store_Receipts && $rec->isReverse == 'yes') || ($mvc instanceof store_ShipmentOrders && $rec->isReverse != 'yes')) {
            
            return ;
        }
        
        self::add($mvc, $rec);
        self::setUpdateOnShutdown($mvc, $rec);
    }
    
    
    /**
     * Обновява списъците със свойства на номенклатурите от които е имало засегнати пера
     *
     * @param acc_Items $mvc
     */
    public static function on_Shutdown($mvc)
    {
       if (is_array($mvc->allocateThreadsOnShutdown)) {
            foreach ($mvc->allocateThreadsOnShutdown as $threadId) {
                self::getAllocatedCostsByProduct($threadId);
            }
        }
    }
    
    
    /**
     * Ъпдейтва разпределените разходи по артикули в подадената нишка
     *
     *
     * @param $threadId - нишката в която ще се ъпдетват разходите
     *
     * @return array $res
     */
    public static function getAllocatedCostsByProduct($threadId)
    {
        $res = array();
        $classesForCheck = array('sales_Sales','sales_Services','purchase_Purchases','purchase_Services');
        
        $firstDocument = doc_Threads::getFirstDocument($threadId);
        $firstDocClass = cls::get($firstDocument)->className;
        $firstDocClassId = core_Classes::getId($firstDocClass);
        
        if (!in_array($firstDocClass, $classesForCheck)) {
            
            return $res;
        }
        
        $exItem = acc_Items::fetchItem($firstDocClassId, $firstDocument->that);
        if (!$exItem) {
            
            return $res;
        }
        
        if ($firstDocClass == 'sales_Sales') {
            $prodQuery = sales_PrimeCostByDocument::getQuery();
            $prodQuery->where('#sellCost IS NOT NULL');
        } else {
            $prodQuery = purchase_PurchasesData::getQuery();
        }
        $prodQuery->where("#threadId = {$threadId} AND #state != 'rejected'");
        $prodQuery->orderBy('id', 'DESC');
        
        $prods = $saveRecs = array();
        while ($prodRec = $prodQuery->fetch()) {
            $id = $prodRec->id;
            $measureId = cat_Products::fetchField($prodRec->productId, 'measureId');
            if($prodRec->quantity < 0) continue;
            
            if (! array_key_exists($id, $prods)) {
                $prods[$id] = (object) array(
                    'productId' => $prodRec->productId,
                    'measureId' => $measureId,
                    'amount' => (($firstDocClass == 'sales_Sales') ? $prodRec->sellCost : $prodRec->amount),
                    'weight' => $prodRec->weight,
                    'quantity' => $prodRec->quantity,
                );
                
                $saveRecs[$id] = (object) array('id' => $id, 'expenses' => 0);
            }
        }
        
        $costAlocQuery = acc_CostAllocations::getQuery();
        $costAlocQuery->where("#expenseItemId = {$exItem->id}");
        $costAlocQuery->EXT('state', 'doc_Containers', 'externalName=state,externalKey=containerId');
        $costAlocQuery->where('#productsData IS NOT NULL');
       
        $costsArr = array();
        while ($cost = $costAlocQuery->fetch()) {
            $costClassName = core_Classes::getName($cost->detailClassId);
            $costProdAmount = $costClassName::fetch($cost->detailRecId)->amount;
            $costProdAmount = ($cost->quantity < 1) ? $costProdAmount : ($costProdAmount / $cost->quantity);
            
            foreach ($cost->productsData as $costProd) {
                if (!in_array($cost->state, array('active','closed'))) {
                    $costProdAmount = 0;
                }
                
                // Намираме колко е еденичната цена, и я умножаваме по преразпределеното количество
                $costsArr[$costProd->productId] += $costProdAmount * $costProd->allocated;
            }
        }
        
        $prodsAmount = array();
        foreach ($prods as $purKey => $prod) {
            $prodsAmount[$prod->productId] += $prod->amount;
        }
       
        foreach ($costsArr as $costKey => $cost) {
            foreach ($prods as $purKey => $prod) {
                if ($costKey == $prod->productId) {
                    $expenses = ($cost / $prodsAmount[$prod->productId]) * $prod->amount;
                    
                    $saveRecs[$purKey]->expenses += $expenses;
                }
            }
        }
        
        $className = 'purchase_PurchasesData';
        if ($firstDocClass == 'sales_Sales') {
            $className = 'sales_PrimeCostByDocument';
        }
        
        cls::get($className)->saveArray($saveRecs, 'id,expenses');
    }
    
    
    /**
     * Връща ThreadId-тата на нишките в които има реконтирани записи при разпределяне на разходите
     *
     * @return array $threadsArr
     */
    public static function getTrhreadsForUpdate(core_Mvc $mvc, $rec)
    {
        $threadsArr = array();
        
        $detailClassName = $mvc->mainDetail;
        $Detail = cls::get($detailClassName);
        $masterKey = $Detail->masterKey;
        
        $detQuery = $detailClassName::getQuery();
        $detQuery->where("#{$masterKey} = {$rec->id}");
        $detQuery->show('id');
        $detRecArr = arr::extractValuesFromArray($detQuery->fetchAll(), 'id');
        
        $detClassId = core_Classes::getId($detailClassName);
        
        $costAlocQuery = acc_CostAllocations::getQuery();
        $costAlocQuery->where("#detailClassId = {$detClassId}");
        $costAlocQuery->in('detailRecId', $detRecArr);
        $exItems = arr::extractValuesFromArray($costAlocQuery->fetchAll(), 'expenseItemId');
        
        $threadsArr[$rec->threadId] = $rec->threadId;
        foreach ($exItems as $expense) {
            $exItem = acc_Items::fetch($expense);
            $exItemDocClassName = core_Classes::getName($exItem->classId);
            if ($threadId = $exItemDocClassName::fetch($exItem->objectId)->threadId) {
                $threadsArr[$threadId] = $threadId;
            }
        }
        
        return $threadsArr;
    }
}
