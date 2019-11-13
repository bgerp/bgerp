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
        
        // Всички дефиниции, със задължителни партиди
        $templateQuery = batch_Templates::getQuery();
        $templateQuery->where("#alwaysRequire = 'yes' AND #state != 'closed'");
        $templateQuery->show('id');
        $templateIds = arr::extractValuesFromArray($templateQuery->fetchAll(), 'id');
       
        // Кои са артикулите със задължителни партиди, или с избрано автоматично и партидноста им по дефолт да е задължителна
        $where = "#alwaysRequire = 'yes'";
        if(count($templateIds)){
            $templateIds = implode(',', $templateIds);
            $where .= " OR (#alwaysRequire = 'auto' && #templateId IN ({$templateIds}))";
        }
        
        $defQuery = batch_Defs::getQuery();
        $defQuery->where($where);
        $defQuery->show('productId');
        $productIds = arr::extractValuesFromArray($defQuery->fetchAll(), 'productId');
        
        if(!count($productIds)){
            
            return;
        }
       
        // Гледат се детайлите на документа
        $productsWithoutBatchesArr = array();
        $detailMvcs = ($mvc instanceof store_ConsignmentProtocols) ? array('store_ConsignmentProtocolDetailsReceived', 'store_ConsignmentProtocolDetailsSend') : (isset($mvc->mainDetail) ? array($mvc->mainDetail) : array());
        foreach ($detailMvcs as $det){
            
            // Има ли в тях артикули, от тези, на които задължително трябва да е посочена партида
            $Detail = cls::get($det);
            $dQuery = $Detail->getQuery();
            $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
            $dQuery->in("{$Detail->productFld}", $productIds);
            $dQuery->show("id,{$Detail->productFld},{$Detail->quantityFld}");
          
            while($dRec = $dQuery->fetch()){
                
                $bdQuery = batch_BatchesInDocuments::getQuery();
                $bdQuery->where("#detailClassId = {$Detail->getClassId()} AND #detailRecId = {$dRec->id}");
                $bdQuery->XPR('sum', 'double', 'SUM(#quantity)');
                $bdQuery->show('sum');
                $bsRec = $bdQuery->fetch();
                $sum = (is_object($bsRec)) ? $bsRec->sum : 0;
                
                // Ако някои от тях нямат посочена партида, документа няма да се контира
                if($sum < $dRec->{$Detail->quantityFld}){
                    $productsWithoutBatchesArr[$dRec->{$Detail->productFld}] = "<b>" . cat_Products::getTitleById($dRec->{$Detail->productFld}, false) . "</b>";
                }
            }
        }
        
        // Ако има артикули, с задължителни партидности, които не са посочени няма да може да се контира
        if(count($productsWithoutBatchesArr)){
            $productMsg = implode(', ', $productsWithoutBatchesArr);
            core_Statuses::newStatus("Следните артикули, не могат да са без партида|*: {$productMsg}", 'error');
            
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
