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
        
       
        if ($rec->state == 'rejected'){
            
            $dRec=array();
            
            $docClassId = core_Classes::getId($mvc);
            
            $dQuery = purchase_PurchasesData::getQuery();
            $dQuery->where("#docClassId = {$docClassId} AND #docId = {$rec->id} ");
            
            $ids = arr::extractValuesFromArray($dQuery->fetchAll(), 'id');
            
            foreach ($ids as $id){
                
                $dRec= (object) array(
                    
                    'id' => $id,
                    'state' => $rec->state);
              
                purchase_PurchasesData::save($dRec,'id,state');
                
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
         
        $Master = doc_Containers::getDocument($clone->containerId);
        $docClassId = core_Classes::getId($Master);
        $detailClassId = core_Classes::getId($Master->mainDetail);
        
        $firstDocument = doc_Threads::getFirstDocument($clone->threadId);
        $className = $firstDocument->className;
        
        $dealerId = $className::fetchField($firstDocument->that,'dealerId');
        
        
        if(is_array($clone->details)){
            foreach ($clone->details as $detail) {
                
                $dRec=array();
                
                $dRec= (object) array(
                    
                    'valior' => $clone->valior,
                    'detailClassId' => $detailClassId,
                    'detailRecId' => $detail->id,
                    'state' => $clone->state,
                    'contragentClassId' => $clone->contragentClassId,
                    'contragentId' => $clone->contragentId,
                    'dealerId' => $dealerId,
                    'productId' => $detail->productId,
                    'docId' => $clone->id,
                    'docClassId' => $docClassId,
                    'quantity' => $detail->quantity,
                    'packagingId' => $detail->packagingId,
                    'storeId' => $clone->storeId,
                    'price' => $detail->price,
                    'discount' => $detail->discount,
                    'amount' => $detail->amount,
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
    
    }
    
  
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
