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
 * @copyright 2006 - 2018 Experta OOD
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
        setIfNot($mvc->detailToPlaceInZones, $mvc->mainDetail);
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
                $form->setReadOnly($mvc->storeFieldName);
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
        $currentStoreId = store_Stores::getCurrent('id', false);
        if (empty($currentStoreId)) return;

        $zoneOptions = rack_Zones::getZones($rec->{$mvc->storeFieldName}, true);
        $attr = arr::make('ef_icon=img/16/hand-point.png,title=Избор на зона за нагласяне', true);

        if (rack_Zones::haveRightFor('selectdocument', (object)array('containerId' => $rec->containerId))) {
            $url = array('rack_Zones', 'selectdocument', 'containerId' => $rec->containerId, 'ret_url' => true);
            if (empty($zoneOptions)) {
                $zoneId = rack_Zones::fetchField("#containerId = {$rec->containerId} and #storeId = {$rec->{$mvc->storeFieldName}}");
                if(!isset($zoneId)){
                    $attr['error'] = "Няма свободни зони в избрания склад|*!";
                } else {
                    $attr['row'] = 2;
                }
            }

            $data->toolbar->addBtn('Зона', $url, null, $attr);
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
        if(is_array($mvc->syncWithZone)){
            foreach ($mvc->syncWithZone as $containerId => $zoneRec){

                // Синхронизиране на документа със зоната
                rack_ZoneDetails::syncWithDoc($zoneRec->id, $containerId);

                // Ще се регенерират движенията само за артикулите в тази зона
                $zdQuery = rack_ZoneDetails::getQuery();
                $zdQuery->XPR('documentQuantityRound', 'double', 'ROUND(COALESCE(#documentQuantity, 0), 2)');
                $zdQuery->XPR('movementQuantityRound', 'double', 'ROUND(COALESCE(#movementQuantity, 0), 2)');
                $zdQuery->where("#zoneId = {$zoneRec->id} AND (#documentQuantityRound != #movementQuantityRound OR #documentQuantityRound = 0)");
                $zdQuery->show('productId');

                $productIdsInZone = arr::extractValuesFromArray($zdQuery->fetchAll(), 'productId');
                rack_Movements::logDebug("RACK ZONE ({$zoneRec->id}) UPDATE '" . implode('|', $productIdsInZone) . "'");
                rack_Zones::pickupAll($zoneRec->storeId, $zoneRec->defaultUserId, $productIdsInZone);
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
}