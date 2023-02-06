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
class planning_interface_ImportFromPreviousTasks extends planning_interface_ImportDriver
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
     *
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
            $pNoteQuery->show('id,productId,threadId,pMeasureId,quantityInPack');
            while($pRec = $pNoteQuery->fetch()){
                $notesAll[$pRec->id] = $pRec->id;
                $producedProducts[$pRec->productId] = array('batches' => array(), 'productId' => $pRec->productId, 'packagingId' => $pRec->pMeasureId, 'quantityInPack' => 1);
            }
        }

        // За произведените артикули се групират по партиди
        if(core_Packs::isInstalled('batch') && isset($masterRec->storeId)){
            if(countR($notesAll)){
                $batchClassId = planning_DirectProductionNote::getClassId();
                $bQuery = batch_BatchesInDocuments::getQuery();
                $bQuery->where("#detailClassId = {$batchClassId}");
                $bQuery->in("detailRecId", $notesAll);
                while($bRec = $bQuery->fetch()){
                    if($batchDef = batch_Defs::getBatchDef($bRec->productId)){
                        $bArr = array_keys($batchDef->makeArray($bRec->batch));
                        foreach ($bArr as $b){
                            $producedProducts[$bRec->productId]['batches']["{$b}"] = $b;
                        }
                    }
                }
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
                    $producedProducts[$cRec->productId] = array('batches' => array(), 'productId' => $cRec->productId, 'packagingId' => $cRec->pMeasureId, 'quantityInPack' => 1);
                }

                if(core_Packs::isInstalled('batch') && isset($masterRec->storeId)){
                    $bQuery = batch_BatchesInDocuments::getQuery();
                    $bQuery->where("#detailClassId = {$batchClassId} AND #detailRecId = {$cRec->id}");
                    while($bRec = $bQuery->fetch()){
                        if($batchDef = batch_Defs::getBatchDef($bRec->productId)){
                            $bArr = array_keys($batchDef->makeArray($bRec->batch));
                            foreach ($bArr as $b){
                                $producedProducts[$cRec->productId]['batches']["{$b}"] = $b;
                            }
                        }
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

            $batchQuantities = array();
            if(core_Packs::isInstalled('batch') && isset($masterRec->storeId)){
                $batchQuantities = batch_Items::getBatchQuantitiesInStore($pData['productId'], $masterRec->storeId, $masterRec->valior);
            }

            $noBatchCaption = 'К-во';
            if(countR($pData['batches'])){
                $noBatchCaption = 'Без партида';
                foreach ($pData['batches'] as $b){
                    if($batchDef = batch_Defs::getBatchDef($pData['productId'])){
                        $key = "{$pData['productId']}|" . md5($b);
                        $subCaption = $batchDef->toVerbal($b);
                        $form->FLD($key, 'int(min=0)', "caption={$caption}->{$subCaption}");
                        $pData['batch'] = $b;
                        $rec->_details[$key] = $pData;

                        if(isset($batchQuantities[$b])){
                            if($batchQuantities[$b] > 0){
                                $fRec = batch_BatchesInDocuments::fetch(array("#containerId = {$masterRec->containerId} AND #productId = {$pData['productId']} AND #packagingId = {$pData['packagingId']} AND #batch = '[#1#]'", $b));
                                if(!$fRec){
                                    $form->setDefault($key, $batchQuantities[$b]);
                                }
                            }
                        }
                    }
                }
            }

            $key = "{$pData['productId']}|";
            $form->FLD($key, 'int(min=0)', "caption={$caption}->{$noBatchCaption}");
            $pData['batch'] = null;
            $rec->_details[$key] = $pData;

            if(!countR($pData['batches'])){
                $res = store_Products::getQuantities($pData['productId'], $masterRec->storeId, $masterRec->valior);
                if($res->free > 0){
                    $form->setDefault($key, $res->free);
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
     * Връща записите, подходящи за импорт в детайла
     *
     * @param array $recs
     *                    o productId        - ид на артикула
     *                    o quantity         - к-во в основна мярка
     *                    o quantityInPack   - к-во в опаковка
     *                    o packagingId      - ид на опаковка
     *                    o batch            - дефолтна партида, ако може
     *                    o notes            - забележки
     *                    o $this->masterKey - ид на мастър ключа
     * @return void
     */
    private function getImportRecs(core_Manager $mvc, $rec)
    {
        $recs = array();
        if (!is_array($rec->_details)) return $recs;

        foreach ($rec->_details as $key => $data) {

            // Ако има въведено количество групира се по к-во и партиди
            if (!empty($rec->{$key})) {
                $k = "{$data['productId']}|{$data['packagingId']}";
                if(!array_key_exists($k, $recs)){
                    $importRec = (object)array('productId' => $data['productId'],
                                               'packagingId' => $data['packagingId'],
                                               'quantityInPack' => $data['quantityInPack'],
                                               'noteId' => $rec->{$mvc->masterKey},
                                               'batches' => array(),
                                               'quantity' => 0);
                    $recs[$k] = $importRec;
                }
                $recs[$k]->quantity += $rec->{$key};
                if(!empty($data['batch'])){
                    $recs[$k]->batches[$data['batch']] = $rec->{$key};
                }
            }
        }

        return $recs;
    }


    /**
     * Импортиране на детайла (@see import2_DriverIntf)
     *
     * @param object $rec
     *
     * @return void
     */
    public function doImport(core_Manager $mvc, $rec)
    {
        if (!is_array($rec->importRecs)) return;

        foreach ($rec->importRecs as $rec) {
            $fields = array();
            $exRec = null;
            if (!$mvc->isUnique($rec, $fields, $exRec)) {
                core_Statuses::newStatus('Записът не е импортиран, защото има дублиране');
                continue;
            }

            $rec->_clonedWithBatches = true;
            $rec->autoAllocate = false;

            $mvc->save($rec);
            if(countR($rec->batches)){
                batch_BatchesInDocuments::saveBatches($mvc, $rec->id, $rec->batches);
            }
        }
    }


    /**
     * Проверява събмитнатата форма
     *
     * @param core_Manager  $mvc
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function checkImportForm($mvc, core_FieldSet $form)
    {
        $rec = &$form->rec;
        if ($form->isSubmitted()) {
            $rec->importRecs = $this->getImportRecs($mvc, $rec);
        }
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
