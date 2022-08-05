<?php


/**
 * Клас 'batch_plg_TaskDetails' - За добавяне на партиди в прогреса на производствените операции
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_plg_TaskDetails extends core_Plugin
{


    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('batch', 'text', 'caption=Партида,before=employees,input=none');
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
        $rec = &$form->rec;
        $taskRec = planning_Tasks::fetch($rec->taskId);

        $Job = doc_Containers::getDocument($taskRec->originId);
        $jobProductId = $Job->fetchField('productId');
        $BatchClass = batch_Defs::getBatchDef($jobProductId);

        if($rec->type != 'production' || $taskRec->followBatchesForFinalProduct != 'yes' || !$BatchClass) return;

        $form->setField('batch', 'input,unit=|*<small>|на|* ' . cat_Products::getTitleById($jobProductId) . "</small>");
        $batchClassType = $BatchClass->getBatchClassType();


        $form->setFieldType('batch', $batchClassType);
        if (isset($BatchClass->fieldPlaceholder)) {
            $form->setField('batch', "placeholder={$BatchClass->fieldPlaceholder}");
        }

        // Ако има само позволени опции само тях
        $rec->_jobProductId = $jobProductId;
        $allowedOptions = $mvc->getAllowedInBatches($rec);
        if(is_array($allowedOptions)){
            $form->setOptions('batch', array('' => '') + $allowedOptions);
        }

        // Ако има налични партиди в склада да се показват като предложения
        if(isset($taskRec->storeId)){
            $exBatches = batch_Items::getBatchQuantitiesInStore($jobProductId, $taskRec->storeId);
            if (countR($exBatches)) {
                $suggestions = array();
                foreach ($exBatches as $b => $q) {
                    $verbal = strip_tags($BatchClass->toVerbal($b));
                    $suggestions[$verbal] = $verbal;
                }

                $form->setSuggestions('batch', $suggestions);
            }
        }

        $fieldCaption = $BatchClass->getFieldCaption();
        if (!empty($fieldCaption)) {
            $form->setField('batch', "caption={$fieldCaption}");
        }

        if(isset($rec->id)){
            $form->setReadOnly('batch');
        }
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        $taskRec = planning_Tasks::fetch($rec->taskId);
        $Job = doc_Containers::getDocument($taskRec->originId);

        $jobProductId = $Job->fetchField('productId');
        if($rec->type != 'production' || $taskRec->followBatchesForFinalProduct != 'yes') return;

        if (isset($jobProductId)) {
            $BatchClass = batch_Defs::getBatchDef($jobProductId);
            if ($BatchClass) {
                $form->setField('batch', 'input,class=w50');
                if (!empty($rec->batch)) {
                    $rec->batch = $BatchClass->denormalize($rec->batch);
                }
            } else {
                $form->setField('batch', 'input=none');
                unset($rec->batch);
            }

            if ($form->isSubmitted()) {
                $rec->isEdited = true;
                if (is_object($BatchClass)) {
                    if (!empty($rec->batch)) {

                        $rInfo  = planning_ProductionTaskProducts::getInfo($taskRec, $rec->productId, $rec->type);
                        $quantity = $rInfo->quantityInPack * $rec->quantity;

                        $msg = null;
                        if (!$BatchClass->isValid($rec->batch, $quantity, $msg)) {
                            $form->setError('batch', $msg);
                        }

                        if(!$form->gotErrors()){
                            $BatchClass = batch_Defs::getBatchDef($jobProductId);
                            if (is_object($BatchClass)) {
                                $rec->batch = $BatchClass->normalize($rec->batch);
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Преди подготовката на полетата за листовия изглед
     */
    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        arr::placeInAssocArray($data->listFields, array('batch' => 'Партида'), null, 'serial');
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if(!empty($rec->batch)){
            $taskOriginId = planning_Tasks::fetchField($rec->taskId, 'originId');
            $Job = doc_Containers::getDocument($taskOriginId);
            $jobProductId = $Job->fetchField('productId');

            $batch = batch_Movements::getLinkArr($jobProductId, $rec->batch);
            $row->batch = implode(', ', $batch);
        }
    }


    /**
     * Метод по подразбиране за позволени партиди за заприхождаване
     */
    public static function on_AfterGetAllowedInBatches($mvc, &$res, $rec)
    {
        if(!$res){
            $taskRec = planning_Tasks::fetch($rec->taskId);
            $jobDoc = doc_Containers::getDocument($taskRec->originId);
            $res = $jobDoc->getInstance()->getAllowedBatchesForJob($taskRec->originId);
        }
    }


    /**
     * Сумарно рендиране на партидите от детайлите на една ПО
     *
     * @param $mvc
     * @param $res
     * @param $masterId
     * @return void
     */
    public static function on_AfterRenderBatchesSummary($mvc, &$res, $masterId)
    {
        $masterRec = planning_Tasks::fetchRec($masterId);
        $jobRec = planning_Jobs::fetch("#containerId = {$masterRec->originId}");
        $batchDef = batch_Defs::getBatchDef($jobRec->productId);
        if(!is_object($batchDef)) return;

        $batchesSummary = array();
        $bQuery = batch_BatchesInDocuments::getQuery();
        $bQuery->where("#containerId = {$masterRec->originId}");
        while($bRec = $bQuery->fetch()){
            $batchesSummary[$bRec->batch] = array('planned' => $bRec->quantity / $masterRec->quantityInPack, 'produced' => 0, 'batch' => $batchDef->toVerbal($bRec->batch));
        }
        if(!countR($batchesSummary)) return;

        $plannedByNow = arr::sumValuesArray($batchesSummary, 'planned');
        $withoutBatch = ($jobRec->quantity / $masterRec->quantityInPack) - $plannedByNow;
        if($withoutBatch > 0){
            $batchesSummary[null] = array('planned' => $withoutBatch, 'produced' => 0, 'batch' => null);
        }

        $dQuery = planning_ProductionTaskDetails::getQuery();
        $dQuery->where("#taskId = {$masterRec->id} AND #type = 'production'");
        while($dRec = $dQuery->fetch()){
            $batchesSummary[$dRec->batch]['produced'] += $dRec->quantity / $masterRec->quantityInPack;
        }

        $tpl = new core_ET("<table>[#ROWS#]</table>");
        $block = new core_ET("<tr ><td><span style='font-weight:normal'><!--ET_BEGIN label-->[#label#]: <!--ET_END label-->[#batch#]</span></td><td>[#produced#] <i style='font-weight:normal'>([#planned#])</i></td>");
        foreach ($batchesSummary as $arr){
            $arr['planned'] = core_Type::getByName('double(smartRound)')->toVerbal($arr['planned']) . " " . cat_UoM::getShortName($masterRec->measureId);
            $arr['produced'] = core_Type::getByName('double(smartRound)')->toVerbal($arr['produced']);

            $batchArr = array();
            if(!empty($arr['batch'])){
                $caption = $batchDef->getFieldCaption();
                $arr['label'] = (!empty($caption)) ? tr($caption) : 'lot';
                $batch = batch_Movements::getLinkArr($jobRec->productId, $arr['batch']);

                if (is_array($batch)) {
                    foreach ($batch as $key => &$b) {
                        $clone = $arr;
                        $clone['batch'] = $b;
                        $batchArr[] = $clone;
                    }
                }
            } else {
                $arr['batch'] = "<i>" . tr('Без партида') . "</i>: ";
                $batchArr[] = $arr;
            }

            foreach ($batchArr as $arr2) {
                $bTpl = clone $block;
                $bTpl->placeArray($arr2);
                $bTpl->removeBlocksAndPlaces();
                $tpl->append($bTpl, 'ROWS');
            }
        }

        $res = $tpl;
    }
}