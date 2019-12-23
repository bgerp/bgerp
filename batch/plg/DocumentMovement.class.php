<?php


/**
 * Клас 'batch_plg_DocumentActions' - За генериране на партидни движения от документите
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 *
 * @todo да се разработи
 */
class batch_plg_DocumentMovement extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->storeFieldName, 'storeId');
        setIfNot($mvc->savedMovements, array());
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        // Ако има вече разпределени партиди, склада не може да се сменя
        if (isset($form->rec->containerId) && $data->action != 'clone') {
            if (batch_BatchesInDocuments::fetchField("#containerId = {$form->rec->containerId}")) {
                $form->setField($mvc->storeFieldName, array('hint' => 'Склада не може да се смени, защото има разпределени партиди от него'));
                $form->setReadOnly($mvc->storeFieldName);
            }
        }
    }
    
    
    /**
     * Изпълнява се преди контиране на документа
     */
    public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        $actions = type_Set::toArray($rec->contoActions);
        
        // Ако няма избран склад, няма какво да се прави
        if(empty($rec->{$mvc->storeFieldName}) || ($mvc instanceof sales_Sales && !isset($actions['ship']))) {
            
            return;
        }
        
        static $cache = array();
        
        // Гледат се детайлите на документа
        $productsWithoutBatchesArr = array();
        $productsWithNotExistingBatchesArr = array();
        $detailMvcs = ($mvc instanceof store_ConsignmentProtocols) ? array('store_ConsignmentProtocolDetailsReceived', 'store_ConsignmentProtocolDetailsSend') : (isset($mvc->mainDetail) ? array($mvc->mainDetail) : array());
        
        foreach ($detailMvcs as $det){
            
            // Има ли в тях артикули, от тези, на които задължително трябва да е посочена партида
            $Detail = cls::get($det);
            $dQuery = $Detail->getQuery();
            $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
            $dRecs = $dQuery->fetchAll();
            
            // хак за мастъра на протокола за производство
            if($mvc instanceof planning_DirectProductionNote){
                $dRecs[0] = (object)array("{$Detail->productFld}" => $rec->productId, "{$Detail->quantityFld}" => $rec->quantity, 'id' => $rec->id, 'detMvcId' => $mvc->getClassId());
            }
            
            foreach ($dRecs as $dRec){
                $dRec->detMvcId = (empty($dRec->detMvcId)) ? $Detail->getClassId() : $dRec->detMvcId;
                $defRec = batch_Defs::fetch("#productId = {$dRec->{$Detail->productFld}}");
                if(empty($defRec)) continue;
                
                if($Detail instanceof store_InternalDocumentDetail){
                    $dRec->quantity = $dRec->quantityInPack * $dRec->packQuantity;
                }
                
                $checkIfBatchExists = ($defRec->onlyExistingBatches == 'auto') ? batch_Templates::fetchField($defRec->templateId, 'onlyExistingBatches') : $defRec->onlyExistingBatches;
                $checkIfBatchIsMandatory = ($defRec->alwaysRequire == 'auto') ? batch_Templates::fetchField($defRec->templateId, 'alwaysRequire') : $defRec->alwaysRequire;
                
                $Def = batch_Defs::getBatchDef($dRec->{$Detail->productFld});
                $bdQuery = batch_BatchesInDocuments::getQuery();
                $bdQuery->where("#detailClassId = {$dRec->detMvcId} AND #detailRecId = {$dRec->id}");
                
                $sum = 0;
                while($bdRec = $bdQuery->fetch()){
                    $sum += $bdRec->quantity;
                    $batchesArr = array_keys($Def->makeArray($bdRec->batch));
                    
                    // Проверка дали посочената партида на изходящите документи е налична
                    if($checkIfBatchExists == 'yes' && $bdRec->operation == 'out'){
                        if(!array_key_exists("{$bdRec->productId}|{$bdRec->storeId}", $cache)){
                            $cache["{$bdRec->productId}|{$bdRec->storeId}"] = batch_Items::getBatchQuantitiesInStore($bdRec->productId, $bdRec->storeId);
                        }
                        $quantitiesInStore = $cache["{$bdRec->productId}|{$bdRec->storeId}"];
                        
                        $quantity = ($Def instanceof batch_definitions_Serial) ? 1 : $bdRec->quantity;
                        foreach ($batchesArr as $batchValue){
                            $inStore = isset($quantitiesInStore[$batchValue]) ? $quantitiesInStore[$batchValue] : 0;
                            if($quantity > $inStore){
                                $productsWithNotExistingBatchesArr[$dRec->{$Detail->productFld}] = "<b>" . cat_Products::getTitleById($dRec->{$Detail->productFld}, false) . "</b>";
                            }
                        }
                    }
                }
               
                // Ако някои от тях нямат посочена партида, документа няма да се контира
                if($checkIfBatchIsMandatory == 'yes' && $sum < $dRec->{$Detail->quantityFld}){
                    $productsWithoutBatchesArr[$dRec->{$Detail->productFld}] = "<b>" . cat_Products::getTitleById($dRec->{$Detail->productFld}, false) . "</b>";
                }
            }
        }
        
        // Ако има артикули, с задължителни партидности, които не са посочени няма да може да се контира
        if(countR($productsWithoutBatchesArr) || countR($productsWithNotExistingBatchesArr)){
            if(countR($productsWithoutBatchesArr)){
                $productMsg = implode(', ', $productsWithoutBatchesArr);
                core_Statuses::newStatus("Следните артикули, не могат да са без партида|*: {$productMsg}", 'error');
            }
            
            if(countR($productsWithNotExistingBatchesArr)){
                $productMsg = implode(', ', $productsWithNotExistingBatchesArr);
                core_Statuses::newStatus("Следните артикули, са с неналични партиди|*: {$productMsg}", 'error');
            }
            
            $res = false;
            
            return false;
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFileds = null)
    {
        if ($rec->state == 'active') {
            if ($mvc->hasPlugin('acc_plg_Contable')) {
                if (isset($saveFileds)) {
                    
                    return;
                }
            }
            
            $containerId = (isset($rec->containerId)) ? $rec->containerId : $mvc->fetchField($rec->id, 'containerId');
            
            // Отразяване на движението, само ако в текущия хит не е отразено за същия документ
            if (!isset($mvc->savedMovements[$containerId])) {
                batch_Movements::saveMovement($containerId);
                
                // Дига се флаг в текущия хит че движението е отразено
                $mvc->savedMovements[$containerId] = true;
            }
        } elseif ($rec->state == 'rejected') {
            $containerId = (isset($rec->containerId)) ? $rec->containerId : $mvc->fetchField($rec->id, 'containerId');
            $doc = doc_Containers::getDocument($containerId);
            batch_Movements::removeMovement($doc->getInstance(), $doc->that);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (batch_Movements::haveRightFor('list') && $data->rec->state == 'active') {
            $data->toolbar->addBtn('Партиди', array('batch_Movements', 'list', 'document' => $mvc->getHandle($data->rec->id)), 'ef_icon = img/16/wooden-box.png,title=Добавяне като ресурс,row=2');
        }
    }
}
