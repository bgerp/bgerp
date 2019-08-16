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
        if ($rec->state == 'rejected') {
            $dRec = array();
            
            $docClassId = core_Classes::getId($mvc);
            
            $dQuery = purchase_PurchasesData::getQuery();
            $dQuery->where("#docClassId = {$docClassId} AND #docId = {$rec->id} ");
            
            $ids = arr::extractValuesFromArray($dQuery->fetchAll(), 'id');
            
            foreach ($ids as $id) {
                $dRec = (object) array(
                    
                    'id' => $id,
                    'state' => $rec->state);
                
                purchase_PurchasesData::save($dRec, 'id,state');
            }
        }
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
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
          $cond = (strrpos($clone->contoActions, 'ship') !== false);   ;
        }else{
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
                    if($amount <= 0 || $price <= 0) continue;
                    
                    if ($detail->debitAccId && (acc_Accounts::fetch("#num = 321")->id != $detail->debitAccId)) continue;
                   
                    //Артикул
                    $productId = acc_Items::fetch("$detail->debitEnt2")->objectId;
                    $measureId = acc_Items::fetch("$detail->debitEnt2")->uomId;
                    
                    $currencyId = acc_Items::fetch("$detail->debitEnt3")->id;
                    $currencyRate = $detail->creditPrice;
                    
                    //Склад
                    $storeId = acc_Items::fetch("$detail->debitEnt1")->id;
                    
                    //Заприходено количество
                    $quantity =  $detail->debitQuantity;
                    
                
                
                }
                
                if ($quantity < 0) continue;
                
                $dRec = array();
                
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
                    'quantity' => $quantity,
                    'packagingId' => $detail->packagingId,
                    'storeId' => $storeId,
                    'price' =>$price ,
                    'expenses' => '',
                    'discount' => $detail->discount,
                    'amount' => $amount,
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
                
                $saveRecs[]=$dRec;
                cls::get('purchase_PurchasesData')->saveArray($saveRecs);
                
            }
            
            $threadsArr = self::getTrhreadsForUpdate($mvc, $rec);
            
            foreach ($threadsArr as $threadId) {
                self::getAllocatedCostsByProduct($threadId);
            }
            
        }
    }
    
    
    /**
     * При оттегляне на документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        $threadsArr = self::getTrhreadsForUpdate($mvc, $rec);
        
        foreach ($threadsArr as $threadId) {
            self::getAllocatedCostsByProduct($threadId);
        }
    }
    
    
    /**
     * След всеки запис в журнала
     */
    public static function on_AfterSaveJournalTransaction($mvc, $res, $rec)
    {
        $threadsArr = self::getTrhreadsForUpdate($mvc, $rec);
       
        foreach ($threadsArr as $threadId) {
            self::getAllocatedCostsByProduct($threadId);
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
        $checkMarker = false;
        $classesForCheck = array('sales_Sales','sales_Services','purchase_Purchases','purchase_Services');
        
        $firstDocument = doc_Threads::getFirstDocument($threadId);
        $firstDocClass = cls::get($firstDocument)->className;
        $firstDocClassId = core_Classes::getId($firstDocClass);
        
        
        // Дали нишката е покупка или продажба
        foreach ($classesForCheck as $clsChek) {
            if ($firstDocClass == $clsChek) {
                $checkMarker = true;
                break;
            }
        }
        
        $exItem = acc_Items::fetch("#classId ={$firstDocClassId} AND #objectId = {$firstDocument->that}");
        
        //Дали нишката е разходно перо
        if ($checkMarker === false) {
            if (!($exItem)) {
                
                return $res;
            }
        }
        
        $prodQuery = purchase_PurchasesData::getQuery();
        $prodQuery->where("#threadId = ${threadId}");
        
        $prods = array();
        while ($prodRec = $prodQuery->fetch()) {
            $id = $prodRec->id;
            
            if ($prodRec->productId) {
                $measureId = cat_Products::getproductInfo($prodRec->productId)->productRec->measureId;
            }
            
            if (! array_key_exists($id, $prods)) {
                $prods[$id] = (object) array(
                    
                    'productId' => $prodRec->productId,
                    'measureId' => $measureId,
                    'amount' => $prodRec->amount,
                    'weight' => $prodRec->weight,
                    'quantity' => $prodRec->quantity,
                
                );
            }
        }
        
     
        $costAlocQuery = acc_CostAllocations::getQuery();
        $costAlocQuery->where("#expenseItemId = {$exItem->id}");
        $costAlocQuery->EXT('state', 'doc_Containers', 'externalName=state,externalKey=containerId');
        $costAlocQuery->where('#productsData IS NOT NULL');
        
        $costsArr = array();
        while ($cost = $costAlocQuery->fetch()) {
            foreach ($cost->productsData as $costProd) {
                $costClassName = core_Classes::getName($cost->detailClassId);
                $costProdAmount = $costClassName::fetch($cost->detailRecId)->amount;
                
                $stareArr = array('active','closed');
                
                if (!in_array($cost->state, $stareArr)){
                    $costProdAmount =0;
                }
                
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
                    
                    $purDataRec = (object) array(
                        'id' => $purKey,
                        'expenses' => $expenses
                    );
                    
                    $saveRecs[]=$purDataRec;
                    cls::get('purchase_PurchasesData')->saveArray($saveRecs,'id,expenses');
                }
            }
        }
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
        
        $detRecArr = arr::extractValuesFromArray($detQuery->fetchAll(), 'id');
        
        $detClassId = core_Classes::getId($detailClassName);
       
        $costAlocQuery = acc_CostAllocations::getQuery();
        $costAlocQuery->where("#detailClassId = {$detClassId}");
        $costAlocQuery->in('detailRecId', $detRecArr);
        $exItems = arr::extractValuesFromArray($costAlocQuery->fetchAll(), 'expenseItemId');
        
        foreach ($exItems as $expense) {
            $exItem = acc_Items::fetch($expense);
            $exItemDocClassName = core_Classes::getName($exItem->classId);
            $threadId = $exItemDocClassName::fetch($exItem->objectId)->threadId;
            
            
            $threadsArr[$threadId] = $threadId;
        }
     
        return $threadsArr;
    }
}
