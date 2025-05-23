<?php


/**
 * Плъгин позволяващ промяна на редовете на детайлите при клониране
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_EditClonedDetails extends core_Plugin
{
    /**
     * Кои детайли да се клонират с промяна
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @return array $res
     *          ['recs'] - записи за промяна
     *          ['detailMvc] - модел от който са
     *
     */
    public static function on_AfterGetDetailsToCloneAndChange($mvc, &$res, $rec)
    {
        if (!$res) {
            $res = array();
            if (!$rec->clonedFromId) return;

            $recs = array();
            $Detail = cls::get($mvc->mainDetail);
            $dQuery = $Detail->getQuery();
            $dQuery->where("#{$Detail->masterKey} = {$rec->clonedFromId}");
            while($dRec = $dQuery->fetch()){
                if($genericProductId = planning_GenericProductPerDocuments::getRec($Detail, $dRec->id)){
                    $dRec->_genericProductId = $genericProductId;
                }
                $recs[$dRec->id] = $dRec;
            }
            $res = array('recs' => $recs, 'detailMvc' => $Detail);
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;

        setIfNot($mvc->autoAddDetailsToChange, false);
        if ($data->action != 'clone' && !$mvc->autoAddDetailsToChange) return;
        if ($data->action != 'clone' && $mvc->autoAddDetailsToChange && isset($rec->id)) return;

        $MainDetail = cls::get($mvc->mainDetail);
        $detailsToCloneArr = $mvc->getDetailsToCloneAndChange($rec);

        $detailsToClone = $detailsToCloneArr['recs'];
        $Detail = $detailsToCloneArr['detailMvc'];

        if (!countR($detailsToClone)) return;
        setIfNot($Detail->productFld, 'productId');
        setIfNot($Detail->quantityFld, 'quantity');
        $rec->details = array();

        // Ако ориджина има артикули
        $num = 1;
        $detailId = $Detail->getClassId();
        $installedBatch = core_Packs::isInstalled('batch');

        // Ако мастъра на детайла има поле за подреждане на редовете
        if(isset($Detail->Master->detailOrderByField)){
            $firstKey = key($detailsToClone);
            if(isset($firstKey)){

                // и то е за сортиране по код. Извлича се кода и се сортират
                $masterKeyId = $detailsToClone[$firstKey]->{$Detail->masterKey};
                $orderByField = $Detail->Master->fetchField($masterKeyId, $Detail->Master->detailOrderByField);
                if($orderByField == 'code'){
                    array_walk($detailsToClone, function($a) use ($Detail) {
                        $a->_code = cat_Products::fetchField($a->{$Detail->productFld}, 'code');
                        if(empty($a->_code)){
                            $a->_code = "Art{$a->{$Detail->productFld}}";
                        }
                    });
                    arr::sortObjects($detailsToClone, '_code', 'ASC', 'natural');
                }
            }
        }

        foreach ($detailsToClone as $dRec) {
            if($Detail->className != $MainDetail->className && empty($dRec->{$Detail->quantityFld})) continue;
            $caption = cat_Products::getTitleById($dRec->{$Detail->productFld});
            $caption .= ' / ' . cat_UoM::getShortName($dRec->packagingId);
            $caption = str_replace('=', ' ', $caption);
            $caption = str_replace(',', ' ', $caption);
            $caption = "{$num}. {$caption}";
            
            if ($installedBatch !== false) {
                $Def = batch_Defs::getBatchDef($dRec->{$Detail->productFld});
            }
            
            // Ако е инсталиран пакета за партиди, ще се показват и те
            if ($installedBatch && is_object($Def) && $dRec->autoBatches !== true) {
                $subCaption = 'Без партида';
                $bQuery = batch_BatchesInDocuments::getQuery();
                $bQuery->where("#detailClassId = {$detailId} AND #detailRecId = {$dRec->id} AND #productId = {$dRec->{$Detail->productFld}}");
                $bQuery->groupBy('batch');
                $bQuery->orderBy('id', 'ASC');
                
                if (!array_key_exists($dRec->id, $rec->details)) {
                    $rec->details[$dRec->id] = $dRec;
                }
                $rec->details[$dRec->id]->newPackQuantity = 0;
                
                $quantity = $dRec->{$Detail->quantityFld} / $dRec->quantityInPack;
                while ($bRec = $bQuery->fetch()) {
                    $verbal = strip_tags($Def->toVerbal($bRec->batch));
                    $batchMd5 = md5($bRec->batch);

                    $bQuantity = $bRec->{$Detail->quantityFld} / $bRec->quantityInPack;
                    $quantity -= $bQuantity;
                    
                    $max = ($Def instanceof batch_definitions_Serial) ? 'max=1' : '';
                    $key = "quantity|{$batchMd5}|{$dRec->id}|";
                    $form->FLD($key, "double(Min=0,{$max})", "input,caption={$caption}->|*{$verbal}");
                    
                    $rec->details[$dRec->id]->batches[$bRec->id] = $bRec;
                    if($bQuantity > 0){
                        $form->setDefault($key, $bQuantity);
                    }
                }
                
                // Показване на полетата без партиди
                $form->FLD("quantity||{$dRec->id}|", 'double(Min=0)', "input,caption={$caption}->{$subCaption}");
                $quantity = round($quantity, 5);
                if ($quantity > 0) {
                    $form->setDefault("quantity||{$dRec->id}|", $quantity);
                }
            } else {
                // Показване на полетата без партиди
                $form->FLD("quantity||{$dRec->id}|", 'double(Min=0)', "input,caption={$caption}->Количество");
                if($dRec->packQuantity > 0){
                    $form->setDefault("quantity||{$dRec->id}|", $dRec->packQuantity);
                }
                
                if ($dRec->autoBatches === true && $installedBatch) {
                    $type = $Detail->getBatchMovementDocument($dRec);
                    if ($type == 'out') {
                        $dRec->autoAllocate = true;
                    } elseif ($type == 'in') {
                        $dRec->isEdited = true;
                    }
                }
                
                $rec->details["quantity||{$dRec->id}|"] = $dRec;
            }

            $rec->cloneAndChange = true;
            $num++;
        }

        if (!isset($rec->clonedFromId)) {
            
            return;
        }
        
        $clonedState = $mvc->fetchField($rec->clonedFromId, 'state');
        if ($clonedState == 'pending') {
            $form->FLD('deduct', 'enum(yes=Да,no=Не)', 'input,caption=Приспадане от заявката->Избор,formOrder=10000');
        }
        
        // Показване на оригиналния документ
        $data->form->layout = $data->form->renderLayout();
        $tpl = new ET("<div class='preview-holder'><div style='margin-top:20px; margin-bottom:-10px; padding:5px;'><b>" . tr('Оригинален документ') . "</b></div><div class='scrolling-holder'>[#DOCUMENT#]</div></div>");
        $document = doc_Containers::getDocument($mvc->fetchField($rec->clonedFromId, 'containerId'));
        $docHtml = $document->getInlineDocumentBody();
        $tpl->append($docHtml, 'DOCUMENT');
        $data->form->layout->append($tpl);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->rec->deduct == 'yes'){
            $form->setWarning('deduct', "Наистина ли искате да приспаднете количествата от заявката, която клонирате|*?");
        }
    }
    
    
    /**
     * Преди запис на клонираните детайли
     */
    public static function on_BeforeSaveCloneDetails($mvc, &$newRec, &$detailArray)
    {
        if ($newRec->cloneAndChange) {
            
            // Занулява се за да не се клонира нищо
            $detailArray = array();
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFields = null)
    {
        $Detail = cls::get($mvc->mainDetail);
        $detailClassId = $Detail->getClassId();
        
        $dontCloneFields = arr::make($Detail->fieldsNotToClone, true);

        if (countR($rec->details)) {

            foreach ($rec->details as $det) {
                if (!empty($det->baseQuantity)) {
                    $det->quantityInPack = $det->baseQuantity / $det->packQuantity;
                    $det->price = $det->packPrice / $det->quantityInPack;
                }
                
                $newPackQuantity = $updatePackQuantity = 0;
                if (is_array($det->batches) && core_Packs::isInstalled('batch')) {
                    foreach ($det->batches as &$bRec) {
                        $bMd5 = md5($bRec->batch);
                        $key = "quantity|{$bMd5}|{$det->id}|";

                        $q = $rec->{$key};
                        if ($q > ($bRec->quantity / $bRec->quantityInPack)) {
                            $q = $bRec->quantity / $bRec->quantityInPack;
                        }
                        $updatePackQuantity += $q;
                        
                        $newPackQuantity += $rec->{$key};
                        $bRec->oldQuantity = $bRec->quantity;
                        $bRec->quantity = $rec->{$key} * $bRec->quantityInPack;
                        $bRec->containerId = $rec->containerId;
                        $bRec->storeId = $rec->storeId;
                    }
                }
                $newPackQuantity += $rec->{"quantity||{$det->id}|"};
                $updatePackQuantity += $rec->{"quantity||{$det->id}|"};
                if (!empty($newPackQuantity)) {
                    if (!empty($det->baseQuantity)) {
                        $det->quantityInPack = $det->baseQuantity / $newPackQuantity;
                        $det->price = $det->packPrice / $det->quantityInPack;
                    }
                    
                    $oldQuantity = $det->quantity;
                    $det->quantity = $newPackQuantity * $det->quantityInPack;
                    $diff = $oldQuantity - $det->quantity;
                    $oldDetailId = $det->id;
                    $det->_clonedWithBatches = true;
                    
                    if ($rec->deduct == 'yes') {
                        if ($diff <= 0) {
                            $Detail->delete($det->id);
                            if (core_Packs::isInstalled('batch')) {
                                batch_BatchesInDocuments::delete("#detailClassId = {$detailClassId} AND #detailRecId = {$det->id}");
                            }
                        } else {
                            $diff1 = $oldQuantity - ($updatePackQuantity * $det->quantityInPack);
                            $updateRec = (object) array('id' => $oldDetailId, 'quantity' => $diff1);
                            $Detail->save_($updateRec, 'quantity');
                        }
                    }
                    unset($det->id, $det->createdOn, $det->createdBy);
                    
                    // Махане на полетата, които не трябва да се клонират
                    if(countR($dontCloneFields)){
                        foreach ($dontCloneFields as $unsetField) {
                            unset($det->{$unsetField});
                        }
                    }
                    
                    $det->{$Detail->masterKey} = $rec->id;
                    $Detail->save($det);
                    if (is_array($det->batches) && core_Packs::isInstalled('batch')) {
                        $batchesArr = array();
                        foreach ($det->batches as $batch) {
                            $d1 = $batch->oldQuantity - $batch->quantity;
                            if ($rec->deduct == 'yes') {
                                if ($d1 <= 0) {
                                    batch_BatchesInDocuments::delete("#id = {$batch->id}");
                                } else {
                                    $updateRec = (object) array('id' => $batch->id, 'quantity' => $d1);
                                    cls::get('batch_BatchesInDocuments')->save_($updateRec, 'quantity');
                                }
                            }
                            
                            if (!empty($batch->quantity)) {
                                $batchesArr[$batch->batch] = $batch->quantity;
                            }
                        }
                        batch_BatchesInDocuments::saveBatches($detailClassId, $det->id, $batchesArr);
                    }
                }
            }
        }
    }
}
