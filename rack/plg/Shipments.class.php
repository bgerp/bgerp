<?php


/**
 * Клас 'rack_plg_Shipments'
 * Плъгин за връзка между експедиционни документи и палетния склад
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_plg_Shipments extends core_Plugin
{
    
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->storeFieldName, 'storeId');
        setIfNot($mvc->rackStoreFieldName, $mvc->storeFieldName);
        setIfNot($mvc->detailToPlaceInZones, $mvc->mainDetail);
        setIfNot($mvc->canPrintzonemovements, 'no_one');
        setIfNot($mvc->canDoallmovements, 'no_one');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        if(isset($form->rec->id)){
            $cid = $form->rec->containerId;
            if (!$cid) {
                $cid = $mvc->fetchField($form->rec->id, 'containerId');
            }

            if ($cid && rack_Zones::fetch("#containerId = {$cid}")){
                $form->setReadOnly($mvc->rackStoreFieldName);
                $form->setField($mvc->rackStoreFieldName, array('hint' => 'Складът не може да се променя, докато документа е нагласен в зоната|*!'));
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!(Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf'))) {
            if ($zoneRec = rack_Zones::fetch("#containerId = {$rec->containerId}")){
                $row->zoneId = rack_Zones::getDisplayZone($zoneRec, true);
                $row->zoneReadiness = rack_Zones::getVerbal($zoneRec, 'readiness');
                $row->zoneReadiness = isset($zoneRec->readiness) ? $row->zoneReadiness : 'none';
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return boolean|null
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;

        $zoneOptions = rack_Zones::getZones($rec->{$mvc->rackStoreFieldName}, true);
        $attr = arr::make('ef_icon=img/16/hand-point.png,title=Избор на зона за нагласяне', true);

        if (rack_Zones::haveRightFor('selectdocument', (object)array('containerId' => $rec->containerId, 'storeId' => $rec->{$mvc->rackStoreFieldName}))) {
            $url = array('rack_Zones', 'selectdocument', 'containerId' => $rec->containerId, 'ret_url' => true);
            if (empty($zoneOptions)) {
                $zoneId = rack_Zones::fetchField("#containerId = {$rec->containerId} and #storeId = {$rec->{$mvc->rackStoreFieldName}}");
                if(!isset($zoneId)){
                    $attr['error'] = "Няма свободни зони в избрания склад|*!";
                } else {
                    $attr['row'] = 2;
                }
            }

            $data->toolbar->addBtn('Зона', $url, null, $attr);
        }

        if ($mvc->haveRightFor('printzonemovements', $rec)){
            $data->toolbar->addBtn('Печат (Движ.)', array($mvc, 'printzonemovements', 'id' => $rec->id, 'Printing' => 'yes'), 'title=Печат на движенията към зоната в документа,ef_icon=img/16/printer.png,target=_blank,row=2');
        }

        if ($mvc->haveRightFor('doallmovements', $rec)){
            $data->toolbar->addBtn('Движ. (Започв.)', array($mvc, 'doallmovements', 'do' => 'start', 'id' => $rec->id, 'ret_url' => true), 'title=Започване на всички движения,ef_icon=img/16/control_play.png,row=2');
            $data->toolbar->addBtn('Движ. (Прикл.)', array($mvc, 'doallmovements', 'do' => 'close', 'id' => $rec->id, 'ret_url' => true), 'title=Приключване на всички движения,ef_icon=img/16/gray-close.png,row=2');
        }
    }
    
    
    /**
     * Обобщение на артикулите в документа
     * 
     * @param core_Mvc $mvc
     * @param array $res
     * @param stdClass $rec
     * @return void
     */
    public static function on_AfterGetProductsSummary($mvc, &$res, $rec)
    {
        if(!isset($res)){
            $rec = $mvc->fetchRec($rec);
            
            $res = array();
            if(isset($mvc->detailToPlaceInZones)){
                $Detail = cls::get($mvc->detailToPlaceInZones);

                $dQuery = $Detail->getQuery();
                $dQuery->EXT('canStore', 'cat_Products', "externalName=canStore,externalKey={$Detail->productFld}");
                $dQuery->where("#{$Detail->masterKey} = {$rec->id} AND #canStore = 'yes'");
                $Detail->invoke('AfterGetZoneSummaryQuery', array($rec, &$dQuery));

                while($dRec = $dQuery->fetch()){
                    $key = "{$dRec->{$Detail->productFld}}|{$dRec->packagingId}";
                    $rest = $dRec->{$Detail->quantityFld};
                    $Def = batch_Defs::getBatchDef($dRec->{$Detail->productFld});

                    if (is_object($Def)) {
                        $bQuery = batch_BatchesInDocuments::getQuery();
                        $bQuery->where("#detailClassId = {$Detail->getClassId()} AND #detailRecId = {$dRec->id} AND #productId = {$dRec->{$Detail->productFld}} AND #operation = 'out'");

                        while($bRec = $bQuery->fetch()){
                            $batches = batch_Defs::getBatchArray($dRec->{$Detail->productFld}, $bRec->batch);
                            $quantity = (countR($batches) == 1) ? $bRec->quantity : $bRec->quantity / countR($batches);

                            foreach ($batches as $k => $b) {
                                $key2 = "{$key}|{$k}";
                                if(!array_key_exists($key2, $res)){
                                    $res[$key2] = (object)array('productId' => $dRec->{$Detail->productFld}, 'packagingId' => $dRec->packagingId, 'batch' => $k);
                                }
                                $res[$key2]->quantity += $quantity;
                                $rest -= $quantity;
                            }
                        }
                    }

                    if(round($rest, 5) > 0){
                        $key3 = "{$key}|||";
                        if(!array_key_exists($key3, $res)){
                            $res[$key3] = (object)array('productId' => $dRec->{$Detail->productFld}, 'packagingId' => $dRec->packagingId, 'batch' => '');
                        }
                        $res[$key3]->quantity += $rest;
                    }
                }
            }
        }
    }
    
    
    /**
     * Поддържа точна информацията за записите в детайла
     */
    protected static function on_AfterUpdateDetail(core_Master $mvc, $id, core_Manager $detailMvc)
    {
        $rec = $mvc->fetchRec($id);
        if (!in_array($rec->state, array('draft', 'pending'))) return;
        
        if ($zoneId = rack_Zones::fetchField("#containerId = {$rec->containerId}", 'id')){
            rack_ZoneDetails::syncWithDoc($zoneId, $rec->containerId);
        }

        // Ако документа е изполван в зона да се синхронизира
        if($zoneRec = rack_Zones::fetch("#containerId = {$rec->containerId}", 'id,defaultUserId,storeId')){
            $mvc->syncWithZone[$rec->containerId] = $zoneRec;
        }
    }


    /**
     * Изчиства записите, заопашени за запис
     */
    public static function on_Shutdown($mvc)
    {
        // Заопашените за синхронизиране зони се синхронизират
        if(is_array($mvc->syncWithZone)){
            foreach ($mvc->syncWithZone as $containerId => $zoneRec){
                rack_Zones::forceSync($containerId, $zoneRec);
            }
        }
    }


    /**
     * Изпълнява се преди контиране на документа
     */
    public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        $zoneRec = rack_Zones::fetch("#containerId = {$rec->containerId}", 'id,readiness');
        if(is_object($zoneRec)){
            if(isset($zoneRec->readiness)){
                if($zoneRec->readiness != 1){
                    core_Statuses::newStatus('Документът не може да се контира. Не е нагласен в зоните на палетния склад|*!', 'error');

                    return false;
                }
            }

            if(rack_Movements::fetchField("LOCATE('|{$zoneRec->id}|', #zoneList) AND (#state = 'active' OR #state = 'waiting')")){
                core_Statuses::newStatus('Документът не може да се контира. Има започнати и/или запазени движения към зоната в която е закачен|*!', 'error');

                return false;
            }
        }
    }
    
    
    /**
     * Контиране на счетоводен документ
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        rack_Zones::clearZone($rec->containerId);
    }


    /**
     * След ръчно реконтиране на документа
     *
     * @param core_Mvc   $mvc
     * @param mixed      $res
     * @param int|object $id  първичен ключ или запис на $mvc
     */
    public static function on_AfterDebugReconto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        rack_Zones::clearZone($rec->containerId);
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
        rack_Zones::clearZone($rec->containerId);
    }
    
    
    /**
     * Изпълнява се преди оттеглянето на документа
     */
    public static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
    {
        // Ако има, се спира оттеглянето
        $rec = $mvc->fetchRec($id);

        if(rack_Zones::hasRackMovements($rec->containerId)){
            core_Statuses::newStatus('Документа не може да се оттегли, докато има нагласени количества в зоната', 'error');
            return false;
        }
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if(in_array($action, array('doallmovements', 'printzonemovements')) && isset($rec)){
            if(!rack_Zones::fetchField("#containerId = {$rec->containerId}")){
                $requiredRoles = 'no_one';
            }
        }

        // Ако има неприключени движения да се появява бутона за приключване на всички
        if($action == 'doallmovements' && isset($rec)){
            if($requiredRoles != 'no_one'){
                $zoneId = rack_Zones::fetchField("#containerId = {$rec->containerId}");
                $ready = rack_Movements::count("LOCATE('|{$zoneId}|', #zoneList) AND #state IN ('pending', 'waiting', 'active')");
                if(!$ready){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if($action == 'printzonemovements'){
            $mvc->requireRightFor('printzonemovements');
            expect($id = Request::get('id', 'int'));
            expect($rec = $mvc->fetch($id));
            $mvc->requireRightFor('printzonemovements', $rec);

            $res = getTplFromFile('rack/tpl/ZoneMovementPrintLayout.shtml');

            expect($zoneId = rack_Zones::fetchField("#containerId = {$rec->containerId}"));

            $data = (object)array('recs' => array(), 'rows' => array(), 'zoneId' => $zoneId);
            static::preparePrintMovements($data);

            $fieldset = new core_FieldSet();
            $fieldset->FLD('batch', 'varchar');
            $fieldset->FLD('quantity', 'double');
            $fieldset->FLD('code', 'varchar','tdClass=small');
            $table = cls::get('core_TableView', array('mvc' => $fieldset));
            $fields = arr::make('code=Код,productId=Артикул,batch=Партида,quantity=Общо,positions=Позиции');
            $fields = core_TableView::filterEmptyColumns($data->rows, $fields, 'batch');
            $details = $table->get($data->rows, $fields);
            $singleFields = $mvc->selectFields();
            $singleFields['-single'] = true;
            Mode::push('text', 'plain');
            $documentRow = $mvc->recToVerbal($rec, $singleFields);
            Mode::pop('text');
            $res->append($documentRow->deliveryTo, 'deliveryTo');
            if(!empty($documentRow->logisticInfo)){
                $res->append($documentRow->logisticInfo, 'logisticInfo');
            }

            if($mvc->lineFieldName){
                if($rec->{$mvc->lineFieldName}){
                    $lineRow = trans_Lines::recToVerbal($rec->{$mvc->lineFieldName});
                    $res->append(trans_Lines::getTitleById($rec->{$mvc->lineFieldName}), 'lineId');
                    if(!empty($lineRow->vehicle)){
                        $res->append($lineRow->vehicle, 'vehicle');
                    }
                }
            }

            $res->append($mvc->getHandle($rec), 'containerId');
            $res->append($details, 'DETAILS');
            $mvc->logWrite('Печат на палетни движения', $rec->id);

            return false;
        }

        // Екшън за масово приключване на движения
        if($action == 'doallmovements'){
            $mvc->requireRightFor('doallmovements');
            expect($id = Request::get('id', 'int'));
            expect($do = Request::get('do', 'enum(start,close)'));
            expect($rec = $mvc->fetch($id));
            $mvc->requireRightFor('doallmovements', $rec);

            expect($zoneId = rack_Zones::fetchField("#containerId = {$rec->containerId}"));
            $movementRecs = rack_Zones::getCurrentMovementRecs($zoneId, 'notClosed');

            $ok = 0;
            $errors = $closeRecs = array();
            $Movements = cls::get('rack_Movements');

            // Първо се проверяват дали всичките може да се приключат
            foreach ($movementRecs as $mRec){
                if($do == 'start'){
                    $transaction = $Movements->getTransaction($mRec);
                    $transaction = $Movements->validateTransaction($transaction);
                    if (!empty($transaction->errors)) {
                        $errors[] = "[{$mRec->id}] " . $transaction->errors;
                    } elseif(rack_Movements::haveRightFor('start', $mRec)){
                        $closeRecs[] = $mRec;
                    }
                } else {
                    if(rack_Movements::haveRightFor('done', $mRec)){
                        $closeRecs[] = $mRec;
                    }
                }
            }

            if(countR($errors)){
                $msg = "Не може групово да стартирате всички движения" . implode(',', $errors);
                $msgType = 'error';
            } else {

                // Ако има такива дето може се приключват
                foreach ($closeRecs as $mRec){
                    if (!core_Locks::get("movement{$mRec->id}", 120, 0)) {
                        continue;
                    }

                    $mRec->workerId = core_Users::getCurrent();

                    if($do == 'start'){
                        $mRec->brState = $rec->state;
                        $mRec->state = 'active';
                    } else {
                        $mRec->state = 'closed';
                        $mRec->brState = 'active';
                    }

                    $Movements->save($mRec, 'state,brState,packagings,workerId,modifiedOn,modifiedBy');
                    $ok++;

                    core_Locks::release("movement{$mRec->id}");
                }

                $msgType = 'notice';
                $msg = $do == 'start' ? 'Стартирани са' : 'Приключени са';
                $msg = "{$msg}|*: {$ok}";
            }

            followRetUrl(null, $msg, $msgType);
        }
    }


    /**
     * Подготовка на данните за печата на движенията от документа
     *
     * @param stdClass $data
     * @return void
     */
    private static function preparePrintMovements(&$data)
    {
        $movementRecs = rack_Zones::getCurrentMovementRecs($data->zoneId, 'pendingAndWaiting');
        arr::sortObjects($movementRecs, 'id', 'ASC');

        $zDetail = rack_ZoneDetails::getQuery();
        $zDetail->where("#zoneId = {$data->zoneId}");
        $zDetail->orderBy('id', 'ASC');
        while($zRec = $zDetail->fetch()){
            $data->recs[$zRec->id] = (object)array('productId'   => $zRec->productId,
                                                   'packagingId' => $zRec->packagingId,
                                                   'batch'       => $zRec->batch,
                                                   'positions'   => array(),
                                                   'quantity'    => $zRec->documentQuantity);

            foreach ($movementRecs as $mRec){
                if($mRec->productId == $zRec->productId && $mRec->packagingId == $zRec->packagingId && $mRec->batch == $zRec->batch){
                    $data->recs[$zRec->id]->positions[$mRec->position] += $mRec->quantity;
                }
            }
        }

        // Еднократно извличане на последните ЦД където е произвеждан артикула
        $lastTasks = array();
        foreach ($data->recs as $moveRec1){
            if(!array_key_exists($moveRec1->productId, $lastTasks)){
                $tQuery = planning_Tasks::getQuery();
                $tQuery->where("#state NOT IN ('draft', 'rejected') AND #isFinal='yes'");
                $tQuery->orderBy('id', 'DESC');
                $tQuery->EXT('jProductId', 'planning_Jobs', 'externalName=productId,remoteKey=containerId,externalFieldName=originId');
                $tQuery->where("#jProductId = {$moveRec1->productId}");
                $tQuery->show('folderId');
                $lastTaskRec = $tQuery->fetch();
                $lastTasks[$moveRec1->productId] = is_object($lastTaskRec) ? doc_Folders::getTitleById($lastTaskRec->folderId) : null;
            }
        }

        // Вербализиране на движенията
        foreach ($data->recs as $k => $moveRec){
            $packs = cls::get('rack_Movements')->getCurrentPackagings($moveRec->productId);
            $pRec = cat_Products::fetch($moveRec->productId, 'name,nameEn,isPublic,id,code');
            $row = new stdClass();
            $row->code = !empty($pRec->code) ? cat_Products::getVerbal($pRec, 'code') : "Art{$pRec->id}";
            $row->quantity = core_Type::getByName('double(smartRound)')->toVerbal($moveRec->quantity);
            $row->quantity .= " " . cat_UoM::getSmartName($moveRec->packagingId, $moveRec->quantity);

            $row->productId = cat_Products::getVerbal($pRec, 'name');
            $row->batch = null;
            if($Def = batch_Defs::getBatchDef($moveRec->productId)){
                $row->batch = strlen($moveRec->batch) ? $Def->toVerbal($moveRec->batch) : tr('Без партида');
            }

            // В една колонка ще се показват всички позиции от, които ще се взимат
            $positionArr = array();
            foreach ($moveRec->positions as $position => $quantity){
                $positionStr = $position == rack_PositionType::FLOOR ? 'Под' : $position;
                $convertedQuantity = rack_Movements::getSmartPackagings($moveRec->productId, $packs, $quantity, $moveRec->packagingId);
                $positionStr = "<b>{$positionStr}</b> ({$convertedQuantity})";
                if($position == rack_PositionType::FLOOR){
                    if(isset($lastTasks[$moveRec->productId])){
                        $positionStr .= " (" . $lastTasks[$moveRec->productId] . ")";
                    }
                }

                $positionArr[] = $positionStr;
            }
            $row->positions = implode('<br/> ', $positionArr);
            $data->rows[$k] = $row;
        }
    }
}