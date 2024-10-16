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
 * @title     Импорт от предишни операции
 */
class planning_interface_ImportFromPreviousTasks extends planning_interface_ConsumptionNoteImportProto
{
    /**
     * Заглавие
     */
    public $title = ' Импорт от предишни операции';


    /**
     * Добавя специфични полета към формата за импорт на драйвера
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     * @return void
     */
    public function addImportFields($mvc, core_FieldSet $form)
    {
        $rec = &$form->rec;

        $masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey});
        $firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
        if(!is_object($firstDoc)) return;
        $rec->_details = array();

        // Кои са предходните ПО на тази
        $producedProducts = $notesAll =  array();
        $firstDocRec = $firstDoc->fetch('originId,saoOrder,productId,clonedFromId');
        $prevTaskThreadIds = $this->getPrevTasksThreadId($firstDocRec);

        // Ако има гледа се какви артикули са произведени по тях
        if(countR($prevTaskThreadIds)){
            $pNoteQuery = planning_DirectProductionNote::getQuery();
            $pNoteQuery->EXT('canConvert', 'cat_Products', 'externalName=canConvert,externalKey=productId');
            $pNoteQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $pNoteQuery->EXT('pMeasureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
            $pNoteQuery->where("#state = 'active' AND #canConvert = 'yes'");
            if(empty($masterRec->storeId)){
                $pNoteQuery->where("#canStore = 'no'");
            }
            $pNoteQuery->in('threadId', $prevTaskThreadIds);
            $pNoteQuery->show('id,productId,threadId,pMeasureId,quantityInPack,quantity');

            // Сумират се произведените к-ва по тях
            while($pRec = $pNoteQuery->fetch()){
                $notesAll[$pRec->id] = $pRec->id;
                if(!array_key_exists($pRec->productId, $producedProducts)){
                    $producedProducts[$pRec->productId] = array('batches' => array(), 'productId' => $pRec->productId, 'packagingId' => $pRec->pMeasureId, 'quantityInPack' => 1, 'isProduced' => true);
                }
                $producedProducts[$pRec->productId]['totalQuantity'] += $pRec->quantity;
            }
        }

        // За произведените артикули се групират по партиди
        if(core_Packs::isInstalled('batch') && isset($masterRec->storeId)){
            if(countR($notesAll)){
                $batchClassId = planning_DirectProductionNote::getClassId();
                $bQuery = batch_BatchesInDocuments::getQuery();
                $bQuery->where("#detailClassId = {$batchClassId}");
                $bQuery->in("detailRecId", $notesAll);
                static::addBatchDataToArray($bQuery, $producedProducts);
            }
        }

        // Ако ПО е клонирана от друга - търсят се вложените артикули от нея
        if(isset($firstDocRec->clonedFromId)){
            $batchClassId = planning_ConsumptionNoteDetails::getClassId();
            $clonedRec = planning_Tasks::fetch($firstDocRec->clonedFromId);
            $cQuery = planning_ConsumptionNoteDetails::getQuery();
            $cQuery->EXT('canConvert', 'cat_Products', 'externalName=canConvert,externalKey=productId');
            $cQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            $cQuery->EXT('pMeasureId', 'cat_Products', 'externalName=measureId,externalKey=productId');
            $cQuery->EXT('pState', 'cat_Products', 'externalName=state,externalKey=productId');
            $cQuery->EXT('state', 'planning_ConsumptionNotes', 'externalName=state,externalKey=noteId');
            $cQuery->EXT('threadId', 'planning_ConsumptionNotes', 'externalName=threadId,externalKey=noteId');
            $cQuery->where("#state = 'active' AND #threadId = {$clonedRec->threadId} AND #canConvert = 'yes' AND #pState = 'active'");
            if(empty($masterRec->storeId)){
                $cQuery->where("#canStore = 'no'");
            }

            while($cRec = $cQuery->fetch()){
                if(!array_key_exists($cRec->productId, $producedProducts)){
                    $producedProducts[$cRec->productId] = array('batches' => array(), 'productId' => $cRec->productId, 'packagingId' => $cRec->pMeasureId, 'quantityInPack' => 1, 'totalQuantity' => 0);
                }
                $producedProducts[$cRec->productId]['totalQuantity'] += $cRec->quantity;

                // Ако има партиди към документите, извличат се данните от тях
                if(core_Packs::isInstalled('batch') && isset($masterRec->storeId)){
                    $BatchDef = batch_Defs::getBatchDef($cRec->productId);
                    if(!($BatchDef instanceof batch_definitions_Job)){
                        $bQuery = batch_BatchesInDocuments::getQuery();
                        $bQuery->where("#detailClassId = {$batchClassId} AND #detailRecId = {$cRec->id}");
                        static::addBatchDataToArray($bQuery, $producedProducts, true);
                    }
                }
            }
        }

        // Намерените артикули се предлагат
        foreach ($producedProducts as $pData){
            $caption = cat_Products::getTitleById($pData['productId']);
            $caption = str_replace(',', ' ', $caption);
            $shortUom = cat_UoM::getShortName($pData['packagingId']);
            $caption = "{$caption} [{$shortUom}]";

            if($Def = batch_Defs::getBatchDef($pData['productId'])){
                $defValue = $Def->getAutoValue($mvc->Master, $masterRec->id, $masterRec->valior);
                if(isset($defValue)){
                    $md5DefaultVal = md5($defValue);
                    if(!array_key_exists($md5DefaultVal, $pData)){
                        $pData['batches'] = array($md5DefaultVal => array('batch' => $defValue, 'quantity' => 0)) + $pData['batches'];
                    }
                }
            }

            $batchQuantities = array();
            if(core_Packs::isInstalled('batch') && isset($masterRec->storeId)){
                $batchQuantities = batch_Items::getBatchQuantitiesInStore($pData['productId'], $masterRec->storeId, $masterRec->valior);
            }

            $totalQuantity = $pData['totalQuantity'];
            $noBatchCaption = 'К-во';
            if(countR($pData['batches'])){
                $noBatchCaption = 'Без партида';
                foreach ($pData['batches'] as $bArr){
                    if($batchDef = batch_Defs::getBatchDef($pData['productId'])){
                        $key = "{$pData['productId']}|" . md5($bArr['batch']);
                        $subCaption = $batchDef->toVerbal($bArr['batch']);
                        $subCaption = str_replace(',', ' ', $subCaption);
                        $form->FLD($key, 'double(min=0)', "caption={$caption}->{$subCaption}");
                        $pData['batch'] = $bArr['batch'];
                        $rec->_details[$key] = $pData;

                        if(isset($batchQuantities[$bArr['batch']])){
                            if(!empty($batchQuantities[$bArr['batch']]) && !empty($bArr['quantity'])){
                                $batchQuantityDefault = min($batchQuantities[$bArr['batch']], $bArr['quantity']);
                            } else {
                                $batchQuantityDefault = !empty($batchQuantities[$bArr['batch']]) ? $batchQuantities[$bArr['batch']] : $bArr['quantity'];
                            }
                            $totalQuantity -= $batchQuantityDefault;
                            if($batchQuantityDefault > 0){
                                $fRec = batch_BatchesInDocuments::fetch(array("#containerId = {$masterRec->containerId} AND #productId = {$pData['productId']} AND #packagingId = {$pData['packagingId']} AND #batch = '[#1#]'", $bArr['batch']));
                                if(!$fRec){
                                    $form->setDefault($key, $batchQuantities[$bArr['batch']]);
                                }
                            }
                        } else {
                            $totalQuantity -= $bArr['quantity'];
                        }
                    }
                }
            }

            // Даване и възможност за без партида
            $key = "{$pData['productId']}|";
            $form->FLD($key, 'double(min=0)', "caption={$caption}->{$noBatchCaption}");
            $pData['batch'] = null;
            $rec->_details[$key] = $pData;

            if($pData['isProduced']){
                $res = store_Products::getQuantities($pData['productId'], $masterRec->storeId, $masterRec->valior);
                $defaultNoBatchQuantity = min($res->free, core_Math::roundNumber($totalQuantity));
                if($defaultNoBatchQuantity > 0){
                    $form->setDefault($key, $defaultNoBatchQuantity);
                }
            }
        }
    }


    /**
     * Кои са нишките от предходната операция
     *
     * @param $firstDocRec
     * @return array
     */
    private function getPrevTasksThreadId($firstDocRec)
    {
        $prevTaskQuery = planning_Tasks::getQuery();
        $prevTaskQuery->where("#originId = {$firstDocRec->originId} AND #state IN ('active', 'stopped', 'wakeup', 'closed') AND #id != {$firstDocRec->id}");
        if($firstDocRec->saoOrder){
            $prevTaskQuery->where("#saoOrder < '{$firstDocRec->saoOrder}'");
        } else {
            $prevTaskQuery->where("#id < '{$firstDocRec->id}'");
        }
        $prevTaskQuery->orderBy('saoOrder', "DESC");
        $prevTaskQuery->show('threadId');
        $prevTasks = $prevTaskQuery->fetchAll();
        $threads = arr::extractValuesFromArray($prevTasks, 'threadId');

        return $threads;
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
        if(isset($masterId)){
            $masterRec = $mvc->Master->fetch($masterId);
            $firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
            if(empty($firstDoc) || !$firstDoc->isInstanceOf('planning_Tasks')) return false;

            // Ако има ПП в предходни операции ще може да се избира
            $firstDocRec = $firstDoc->fetch();
            $prevThreadIds = $this->getPrevTasksThreadId($firstDocRec);
            if(countR($prevThreadIds)){
                $prevThreadIdString = implode(',', $prevThreadIds);
                $nQuery = planning_DirectProductionNote::getQuery();
                $nQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
                $nQuery->where("#threadId IN ({$prevThreadIdString}) AND #state = 'active'");
                if(empty($masterRec->storeId)){
                    $nQuery->where("#canStore = 'no'");
                }
                if($nQuery->count()) return true;
            }

            // Ако текущата ПО е клонирана
            if(isset($firstDocRec->clonedFromId)){

                // И оригиналната има протоколи за влагане ще може да се избира
                $clonedThreadId = planning_Tasks::fetchField($firstDocRec->clonedFromId, 'threadId');
                $cQuery = planning_ConsumptionNoteDetails::getQuery();
                $cQuery->EXT('threadId', 'planning_ConsumptionNotes', 'externalName=threadId,externalKey=noteId');
                $cQuery->EXT('state', 'planning_ConsumptionNotes', 'externalName=state,externalKey=noteId');
                $cQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=noteId');
                $cQuery->where("#threadId = {$clonedThreadId} AND #state = 'active'");
                if(empty($masterRec->storeId)){
                    $cQuery->where("#canStore = 'no'");
                }

                if($cQuery->count()) return true;
            }

            return false;
        }

        return true;
    }
}
