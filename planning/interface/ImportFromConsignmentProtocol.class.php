<?php


/**
 * Помощен клас-имплементация на интерфейса import_DriverIntf
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Импорт от ПОП (чужди) в сделката
 */
class planning_interface_ImportFromConsignmentProtocol extends planning_interface_ConsumptionNoteImportProto
{
    /**
     * Заглавие
     */
    public $title = 'ПОП (чужди) в сделката';


    /**
     * Добавя специфични полета към формата за импорт на драйвера
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function addImportFields($mvc, core_FieldSet $form)
    {
        $rec = &$form->rec;
        $rec->detailsDef = array();
        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        $firstDocument = doc_Threads::getFirstDocument($masterRec->threadId);
        if($firstDocument->isInstanceOf('planning_Tasks')){
            $firstDocument = doc_Containers::getDocument($firstDocument->fetchField('originId'));
        }
        $jobRec = $firstDocument->fetch();

        $form->FLD("forQuantity", 'int', "input,caption=За количество,silent");
        $form->setDefault('forQuantity', $jobRec->quantity);
        $form->setField('forQuantity', "unit=по задание|*: {$jobRec->quantity}");
        $form->input('forQuantity', 'silent');

        $receivedProducts = store_ConsignmentProtocolDetailsReceived::getReceivedOtherProductsFromSale($masterRec->threadId);
        $ratio = $rec->forQuantity / $jobRec->quantity;
        $parsedArr = $receivedProducts;

        $classId = planning_ConsumptionNoteDetails::getClassId();

        // Приспадане на вече вкараните за влагане в протокол за влагане чужди артикули (в този протокол или в други активни)
        $dQuery = planning_ConsumptionNoteDetails::getQuery();
        $dQuery->EXT('threadId', 'planning_ConsumptionNotes', 'externalName=threadId,externalKey=noteId');
        $dQuery->EXT('state', 'planning_ConsumptionNotes', 'externalName=state,externalKey=noteId');
        $dQuery->where("#threadId = {$masterRec->threadId} AND (#state = 'active' OR #noteId = {$masterRec->id})");

        while($dRec = $dQuery->fetch()){
            if(isset($parsedArr[$dRec->productId][$dRec->packagingId])){
                $parsedArr[$dRec->productId][$dRec->packagingId]['totalQuantity'] -= $dRec->quantity;
                $bQuery = batch_BatchesInDocuments::getQuery();
                $bQuery->where("#detailClassId = {$classId} AND #detailRecId = {$dRec->id}");
                $bQuery->show('quantity,batch');
                while($bRec = $bQuery->fetch()){
                    $bKey = md5($bRec->batch);
                    if(array_key_exists($bKey, $parsedArr[$dRec->productId][$dRec->packagingId]['batches'])){
                        $parsedArr[$dRec->productId][$dRec->packagingId]['batches'][$bKey]['quantity'] -= $bRec->quantity;
                    }
                }
            }
        }

        // Намерените артикули се предлагат
        foreach ($parsedArr as $packData) {
            foreach ($packData as $pData) {
                $shortUom = cat_UoM::getShortName($pData['packagingId']);
                $caption = cat_Products::getTitleById($pData['productId']) . " {$shortUom}";
                $caption = str_replace(',', ' ', $caption);
                $caption = "{$caption}";

                $round = cat_UoM::fetchField($pData['packagingId'], 'round');
                $totalQuantity = $pData['totalQuantity'] / $pData['quantityInPack'];
                $noBatchCaption = 'К-во';

                if(countR($pData['batches'])){
                    $noBatchCaption = 'Без партида';
                    foreach ($pData['batches'] as $bVal => $bArr){
                        if($batchDef = batch_Defs::getBatchDef($pData['productId'])){
                            $key = "{$pData['productId']}+{$pData['packagingId']}+" . md5($bArr['batch']);
                            $subCaption = $batchDef->toVerbal($bArr['batch']);

                            if($batchDef instanceof batch_definitions_Serial && $bArr['quantity'] <= 0) continue;

                            $form->FLD($key, 'double(min=0)', "caption={$caption}->{$subCaption},maxRadio=0,unit={$shortUom}");
                            if($bArr['quantity'] > 0){
                                $newQuantity = round($bArr['quantity'] * $ratio / $pData['quantityInPack'], $round);
                                $form->setDefault($key, $newQuantity);
                                if($batchDef instanceof batch_definitions_Serial){
                                    $form->setOptions($key, array($newQuantity => $newQuantity, '0' => '0'));
                                }
                            }

                            $pData['batch'] = $bArr['batch'];
                            $rec->_details[$key] = $pData;
                            $totalQuantity -= $bArr['quantity'];
                        }
                    }
                }

                // Даване и възможност за без партида
                $key = "{$pData['productId']}+{$pData['packagingId']}+";
                $form->FLD($key, 'double(min=0)', "caption={$caption}->{$noBatchCaption},unit={$shortUom}");
                if($totalQuantity > 0){
                    $totalQuantity = round($totalQuantity * $ratio, $round);
                    $form->setDefault($key, $totalQuantity);
                }
                $pData['batch'] = null;
                $rec->_details[$key] = $pData;
            }
        }

        $refreshFields = implode('|', array_keys($rec->_details));
        $form->setField('forQuantity', "removeAndRefreshForm={$refreshFields}");
    }


    /**
     * Може ли драйвера за импорт да бъде избран
     *
     * @param core_Manager $mvc      - клас в който ще се импортира
     * @param int|NULL     $masterId - ако импортираме в детайл, id на записа на мастъра му
     * @param int|NULL     $userId   - ид на потребител
     *
     * @return bool - може ли драйвера да бъде избран
     */
    public function canSelectDriver(core_Manager $mvc, $masterId = null, $userId = null)
    {
        if (!($mvc instanceof planning_ConsumptionNoteDetails)) return false;
        if(isset($masterId)) {
            $masterRec = $mvc->Master->fetch($masterId);
            if(empty($masterRec->storeId)) return false;

            $firstDocument = doc_Threads::getFirstDocument($masterRec->threadId);
            if(!$firstDocument->isInstanceOf('planning_Jobs') && !$firstDocument->isInstanceOf('planning_Tasks')) return false;
            $receivedProducts = store_ConsignmentProtocolDetailsReceived::getReceivedOtherProductsFromSale($masterRec->threadId, false);

            return countR($receivedProducts) > 0;
        }

        return true;
    }
}