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
        $detailClassId = core_Classes::getId($Master->mainDetail);                                                        // на детайлите на активния документ( ДП при бърза продажба, СР - при засклаждане, ПП - при приемане)
        $detailClassName = core_Classes::getName($detailClassId);                                                         // на детайлите на активния документ( ДП при бърза продажба, СР - при засклаждане, ПП - при приемане)
        
        
        $firstDocument = doc_Threads::getFirstDocument($clone->threadId);                                                 // на първия документ в нишката на активния документ
        $className = $firstDocument->className;                                                                           // на активния документ
        
        
        $dealerId = $className::fetch($firstDocument->that)->dealerId;
        
        if (is_array($clone->details)) {
            $detailRecIds = array();
            
            
            foreach ($clone->details as $detail) {
                $dRec = array();
                
                $dRec = (object) array(
                    
                    'valior' => $clone->valior,
                    'detailClassId' => $detailClassId,
                    'detailRecId' => $detail->id,
                    'state' => $clone->state,
                    'contragentClassId' => $clone->contragentClassId,
                    'contragentId' => $clone->contragentId,
                    'dealerId' => $dealerId,
                    'productId' => $detail->productId,
                    'measureId' => $detail->measureId,
                    'docId' => $clone->id,
                    'docClassId' => $docClassId,
                    'quantity' => $detail->quantity,
                    'packagingId' => $detail->packagingId,
                    'storeId' => $clone->storeId,
                    'price' => $detail->price,
                    'allocatedPrice' => '',
                    'expenses' => '',
                    'discount' => $detail->discount,
                    'amount' => $detail->amount,
                    'weight' => $detail->weight,
                    'currencyId' => $clone->currencyId,
                    'currencyRate' => $clone->currencyRate,
                    'createdBy' => $detail->createdBy,
                    'threadId' => $clone->threadId,
                    'folderId' => $clone->folderId,
                    'containerId' => $clone->containerId,);
                
                
                $id = purchase_PurchasesData::fetchField("#detailClassId = {$dRec->detailClassId} AND #detailRecId = {$dRec->detailRecId}");
                
                if (!empty($id)) {
                    $dRec->id = $id;
                }
                
                purchase_PurchasesData::save($dRec);
            }
        }
    }
    
    
    /**
     * След всеки запис в журнала
     */
    public static function on_AfterSaveJournalTransaction($mvc, $res, $rec)
    {
        
        //bp($res,$rec,$mvc->className);
        
        
        self::getAllocatedCostsByProduct($rec->threadId);
    }
    
    
    /**
     * При оттегляне на документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
         $rec = $mvc->fetchRec($id);
      //  bp($rec);
        
       
       
    }
    
    
    /**
     * Ъпдейтва разпределените разходи по артикули 
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
        
        foreach ($classesForCheck as $clsChek) {
            if ($firstDocClass == $clsChek) {
                $checkMarker = true;
                break;
            }
        }
        
        $exQuery = acc_Items::getQuery();
        $exQuery->where("#classId ={$firstDocClassId} AND #objectId = {$firstDocument->that}");
        
        $exItem = $exQuery->fetchAll();
        
        if ($checkMarker === false) {
            if (empty($exItem)) {
                
                return $res;
            }
        }
        
        $prodQuery = purchase_PurchasesData::getQuery();
        $prodQuery->where("#threadId = ${threadId}");
        
        $prods = array();
        while ($prodRec = $prodQuery->fetch()) {
            $id = $prodRec->id;
            
            if (! array_key_exists($id, $prods)) {
                $prods[$id] = (object) array(
                    
                    'productId' => $prodRec->productId,
                    'measureId' => cat_Products::getproductInfo($prodRec->productId)->productRec->measureId,
                    'amount' => $prodRec->amount,
                    'weight' => $prodRec->weight,
                    'quantity' => $prodRec->quantity,
                
                );
            }
        }
        
        foreach ($exItem as $expense) {
            $costAlocQuery = acc_CostAllocations::getQuery();
            $costAlocQuery->where("#expenseItemId = {$expense->id}");
            $costAlocQuery->where('#productsData IS NOT NULL');
            
            $costsArr = array();
            while ($cost = $costAlocQuery->fetch()) {
                foreach ($cost->productsData as $costProd) {
                    $costClassName = core_Classes::getName($cost->detailClassId);
                    $costProdAmount = $costClassName::fetch($cost->detailRecId)->amount;
                    
                    $costsArr[$costProd->productId] += $costProdAmount * $costProd->allocated;
                }
            }
        }
        
        $prodsAmount = array();
        foreach ($prods as $purKey => $prod) {
            $prodsAmount[$prod->productId] += $prod->amount;
        }
        
        foreach ($costsArr as$costKey => $cost) {
            foreach ($prods as $purKey => $prod) {
                if ($costKey == $prod->productId) {
                    $arr[] = $prod->productId.' '.$purKey;
                    
                    $expenses = ($cost / $prodsAmount[$prod->productId]) * $prod->amount;
                    
                    $purDataRec = (object) array(
                        'id' => $purKey,
                        'expenses' => $expenses
                    );
                    
                    purchase_PurchasesData::save($purDataRec);
                }
            }
        }
    }
}
