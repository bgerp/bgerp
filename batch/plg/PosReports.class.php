<?php


/**
 * Клас 'batch_plg_PosReports' - За генериране на партидни движения от пос отчета
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_plg_PosReports extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->allowInstantProductionBatches, true);
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
    
    
    /**
     * Записва заопашените движения
     */
    public static function on_Shutdown($mvc)
    {
        if (is_array($mvc->saveMovementsOnShutdown)) {
            foreach ($mvc->saveMovementsOnShutdown as $rec) {
                batch_Movements::saveMovement($rec->containerId);
            }
        }
    }


    /**
     * Записване на партидите от документа
     *
     * @param $rec
     * @return void
     */
    public static function saveBatchesToDraft($rec)
    {
        $details = $rec->details['receiptDetails'];
        $pointRec = pos_Points::fetch($rec->pointId);
        $docType = pos_Reports::getClassId();
        $toSave = array();

        if(is_array($details)){
            foreach ($details as $detRec){
                if($detRec->action != 'sale' || empty($detRec->batch)) continue;
                $quantity = round($detRec->quantity * $detRec->quantityInPack, 2);

                $bRec = (object)array('productId' => $detRec->value, 'operation' => 'out', 'storeId' => $pointRec->storeId, 'quantity' => $quantity, 'quantityInPack' => 1, 'packagingId' => cat_Products::fetchField($detRec->value, 'measureId'));
                $bRec->detailClassId = $docType;
                $bRec->detailRecId = $rec->id;
                $bRec->date = $rec->valior;
                $bRec->containerId = $rec->containerId;
                $bRec->batch = $detRec->batch;
                $bRec->isInstant = 'no';
                $toSave[] = $bRec;
            }

            if (countR($toSave)) {
                cls::get('batch_BatchesInDocuments')->saveArray($toSave);
            }
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
        if ($rec->state == 'active') {
            $mvc->saveMovementsOnShutdown[$rec->id] = $rec;
        } elseif ($rec->state == 'rejected') {
            batch_Movements::removeMovement($mvc, $rec->id);
        }
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
        batch_BatchesInDocuments::delete("#containerId = {$rec->containerId}");
    }


    /**
     * Преди редирект след грешка при контиране
     */
    public static function on_BeforeContoRedirectError($mvc, $rec, acc_journal_RejectRedirect $e)
    {
        batch_BatchesInDocuments::delete("#containerId = '{$rec->containerId}'");
    }
}
