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
     * Преди запис в модела
     */
    public static function on_AfterSave($mvc, $id, $rec)
    {
        
       // bp($rec,$mvc,store_ReceiptDetails::fetch("#receiptId = $rec->id")->details,doc_Containers::getDocument($rec->containerId));
      //  bp($rec);
    }
    
    
 
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        $clone = clone $rec;
        
        $clone->threadId = (isset($clone->threadId)) ? $clone->threadId : $mvc->fetchField($clone->id, 'threadId');
        $clone->folderId = (isset($clone->folderId)) ? $clone->folderId : $mvc->fetchField($clone->id, 'folderId');
        
      
        $Detail = cls::getDocument($clone->containerId)->mainDetail;
        
        
        if(is_array($clone->details)){
            foreach ($clone->details as &$dRec) {
                $dRec->threadId = $clone->threadId;
                $dRec->folderId = $clone->folderId;
                $dRec->containerId = $clone->containerId;
                
         //       $id = purchase_PurchasesData::fetchField("#detailClassId = {$dRec->detailClassId} AND #detailRecId = {$dRec->detailRecId}");
                if (!empty($id)) {
                    $dRec->id = $id;
                }
            }  
        }
        
        // Запис на делтите
   //     cls::get('purchase_PurchasesData')->saveArray($save);
    }
    
  
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}