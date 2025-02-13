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
        setIfNot($mvc->allowInstantProductionBatches, true);
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
     * Каква грешка да се показва при контиране/възстановяване на контиран документ
     *
     * @param $mvc
     * @param $res
     * @param $rec
     * @return false|void
     */
    private static function getContoError($mvc, &$res, $rec)
    {
        $actions = type_Set::toArray($rec->contoActions);

        // Ако няма избран склад, няма какво да се прави
        if(empty($rec->{$mvc->storeFieldName}) || ($mvc instanceof sales_Sales && !isset($actions['ship']))) {

            return;
        }

        static $cache = array();

        // Гледат се детайлите на документа
        $productsWithoutBatchesArr = $productsWithNotExistingBatchesArr = $batchDiffArr = array();
        $detailMvcs = ($mvc instanceof store_ConsignmentProtocols) ? array('store_ConsignmentProtocolDetailsReceived', 'store_ConsignmentProtocolDetailsSend') : (isset($mvc->mainDetail) ? array($mvc->mainDetail) : array());
        $batchesWithSerials = array();

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

            foreach ($dRecs as $k => $dRec){
                $dRec->detMvcId = (empty($dRec->detMvcId)) ? $Detail->getClassId() : $dRec->detMvcId;
                $defRec = batch_Defs::fetch("#productId = {$dRec->{$Detail->productFld}}");
                if(empty($defRec)) continue;

                if($Detail instanceof store_InternalDocumentDetail){
                    $dRec->quantity = $dRec->quantityInPack * $dRec->packQuantity;
                }

                $checkIfBatchExists = ($defRec->onlyExistingBatches == 'auto') ? batch_Templates::fetchField($defRec->templateId, 'onlyExistingBatches') : $defRec->onlyExistingBatches;
                $checkIfBatchExists = haveRole('contoNegativeBatches') ? false : $checkIfBatchExists;
                $checkIfBatchIsMandatory = ($defRec->alwaysRequire == 'auto') ? batch_Templates::fetchField($defRec->templateId, 'alwaysRequire') : $defRec->alwaysRequire;

                if($Detail instanceof planning_DirectProductNoteDetails && $k > 0){
                    if(empty($dRec->storeId)){
                        $checkIfBatchIsMandatory = 'no';
                    }
                }

                $Def = batch_Defs::getBatchDef($dRec->{$Detail->productFld});
                $bdQuery = batch_BatchesInDocuments::getQuery();
                $bdQuery->where("#detailClassId = {$dRec->detMvcId} AND #detailRecId = {$dRec->id}");

                $sum = 0;
                while($bdRec = $bdQuery->fetch()){
                    $sum += $bdRec->quantity;
                    $batchesArr = array_keys($Def->makeArray($bdRec->batch));
                    if($bdRec->operation == 'in' && !($Detail instanceof store_TransfersDetails)){
                        if($Def instanceof batch_definitions_Serial){
                            foreach ($batchesArr as $b){
                                $batchesWithSerials[$bdRec->productId][$b] = $b;
                            }
                        }
                    }

                    // Ако е МСТ се гледат само излизащите
                    if($Detail instanceof store_TransfersDetails && $bdRec->operation = 'in') continue;

                    // Проверка дали посочената партида на изходящите документи е налична
                    if($checkIfBatchExists == 'yes' && $bdRec->operation == 'out'){
                        if(!array_key_exists("{$bdRec->productId}|{$bdRec->storeId}", $cache)){
                            $cache["{$bdRec->productId}|{$bdRec->storeId}"] = batch_Items::getBatchQuantitiesInStore($bdRec->productId, $bdRec->storeId);
                        }
                        $quantitiesInStore = $cache["{$bdRec->productId}|{$bdRec->storeId}"];

                        $quantity = ($Def instanceof batch_definitions_Serial) ? 1 : $bdRec->quantity;
                        foreach ($batchesArr as $batchValue){
                            $inStore = isset($quantitiesInStore[$batchValue]) ? $quantitiesInStore[$batchValue] : 0;
                            if(round($quantity, 5) > round($inStore, 5)){
                                wp($bdRec, $batchValue, $quantitiesInStore, round($quantity, 5), round($inStore, 5));
                                $productsWithNotExistingBatchesArr[$dRec->{$Detail->productFld}] = "<b>" . cat_Products::getTitleById($dRec->{$Detail->productFld}, false) . "</b>";
                            }
                        }
                    }
                }

                // Ако някои от тях нямат посочена партида, документа няма да се контира
                if($checkIfBatchIsMandatory == 'yes' && round($sum, 3) < round($dRec->{$Detail->quantityFld}, 3)){
                    $productsWithoutBatchesArr[$dRec->{$Detail->productFld}] = "<b>" . cat_Products::getTitleById($dRec->{$Detail->productFld}, false) . "</b>";
                }

                if(round($sum, 3) > round($dRec->{$Detail->quantityFld}, 3)){
                    $batchDiffArr[$dRec->{$Detail->productFld}] = "<b>" . cat_Products::getTitleById($dRec->{$Detail->productFld}, false) . "</b>";
                }
            }
        }

        $errMsgSerials = '';
        foreach ($batchesWithSerials as $pId => $bArr){
            $batchQuantityInAllStores = batch_Items::getBatchQuantitiesInStore($pId);
            $serialErrArr = array();
            foreach ($bArr as $b1){
                if($batchQuantityInAllStores[$b1] >= 1){
                    $serialErrArr[$b1] = $b1;
                }
            }

            if(countR($serialErrArr)){
                $errMsgSerials .= " " . cat_Products::getTitleById($pId) . ": " . implode(',', $serialErrArr);
            }
        }

        // Ако има артикули, с задължителни партидности, които не са посочени няма да може да се контира
        if(countR($productsWithoutBatchesArr) || countR($productsWithNotExistingBatchesArr) || countR($batchDiffArr) || $errMsgSerials){
            if(countR($productsWithoutBatchesArr)){
                $productMsg = implode(', ', $productsWithoutBatchesArr);
                core_Statuses::newStatus("Артикулите не могат да са без партида|*: {$productMsg}", 'error');
            }

            if(countR($productsWithNotExistingBatchesArr)){
                $productMsg = implode(', ', $productsWithNotExistingBatchesArr);
                core_Statuses::newStatus("Артикули с неналични партиди|*: {$productMsg}", 'error');
            }

            if(countR($batchDiffArr)){
                $productMsg = implode(', ', $batchDiffArr);
                core_Statuses::newStatus("Разпределеното по партиди е повече от количеството на артикула|*: {$productMsg}", 'error');
            }

            if($errMsgSerials){
                core_Statuses::newStatus("Следните серийни номера са вече налични в системата|*: {$errMsgSerials}", 'error');
            }

            $res = false;

            return false;
        }
    }


    /**
     * Изпълнява се преди възстановяването на документа
     */
    public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if(!in_array($rec->brState, array('active', 'closed'))) return;

        return self::getContoError($mvc, $res, $rec);
    }


    /**
     * Изпълнява се преди контиране на документа
     */
    public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        return self::getContoError($mvc, $res, $rec);
    }


    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        batch_BatchesInDocuments::delete("#containerId = {$rec->containerId} AND #isInstant = 'yes'");
    }


    /**
     * Ре-контиране на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_BeforeReConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);

        batch_BatchesInDocuments::delete("#containerId = {$rec->containerId} AND #isInstant = 'yes'");
    }


    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     * @param null|mixed $saveFields
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFields = null)
    {
        // Ако документа се променя от бутона за промяна или при преизчисляване на курса да не се дублират партидите
        if($rec->__isBeingChanged || $rec->_recalcRate || $rec->_changeLine) return;

        if ($rec->state == 'active') {
            if ($mvc->hasPlugin('acc_plg_Contable')) {

                if (isset($saveFields)) return;
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
            if(batch_Movements::count("#docType = {$mvc->getClassId()} AND #docId = {$data->rec->id}")){
                $data->toolbar->addBtn('Партиди', array('batch_Movements', 'list', 'document' => $mvc->getHandle($data->rec->id)), 'ef_icon = img/16/wooden-box.png,title=Показване на движенията на партидите генерирани от документа,row=2');
            }

            if(batch_BatchesInDocuments::haveRightFor('list') && batch_BatchesInDocuments::count("#containerId = {$data->rec->containerId}")){
                $data->toolbar->addBtn('Партиди (Чер.)', array('batch_BatchesInDocuments', 'list', 'document' => $mvc->getHandle($data->rec)), 'ef_icon = img/16/bug.png,title=Показване на черновите движения на партидите генерирани от документа,row=2');
            }
        }
    }
}
