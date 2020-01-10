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
            if (rack_Zones::fetch("#containerId = {$form->rec->containerId}")){
                $form->setReadOnly($mvc->storeFieldName);
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($zoneRec = rack_Zones::fetch("#containerId = {$rec->containerId}")){
            $row->zoneId = rack_Zones::getHyperlink($zoneRec);
            $row->zoneReadiness = rack_Zones::getVerbal($zoneRec, 'readiness');
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
        $currentStoreId = store_Stores::getCurrent('id', false);
        if (empty($currentStoreId)) return;
        
        $btnData = rack_Zones::getBtnToZone($data->rec->containerId);
        if(countR($btnData->url)){
            $data->toolbar->addBtn($btnData->caption, $btnData->url, $btnData->attr);
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
                    
                    $Def = batch_Defs::getBatchDef($dRec->productId);
                    if (is_object($Def)) {
                        
                        $bQuery = batch_BatchesInDocuments::getQuery();
                        $bQuery->where("#detailClassId = {$Detail->getClassId()} AND #detailRecId = {$dRec->id} AND #productId = {$dRec->{$Detail->productFld}}");
                        while($bRec = $bQuery->fetch()){
                            $batches = batch_Defs::getBatchArray($bRec->productId, $bRec->batch);
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
                    
                    if(round($rest, 2) > 0){
                        $key3 = "{$key}|{$k}||";
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
    }
    
    
    /**
     * Изпълнява се преди контиране на документа
     */
    public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        $readiness = rack_Zones::fetchField("#containerId = {$rec->containerId}", 'readiness');
        if(isset($readiness)){
            if($readiness != 1){
                core_Statuses::newStatus('Документът не може да се контира. Не е нагласен в зоните на палетния склад', 'error');
                
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
        $rec = $mvc->fetchRec($id);
        
        // Има ли нагласени количества за артикула в зоната?
        $zQuery = rack_ZoneDetails::getQuery();
        $zQuery->XPR('movementQuantityRound', 'varchar', 'ROUND(COALESCE(#movementQuantity, 0), 3)');
        $zQuery->EXT('containerId', 'rack_Zones', 'externalName=containerId,externalKey=zoneId');
        $zQuery->where("#containerId = {$rec->containerId} AND #movementQuantityRound != 0");
        $zQuery->show('id');
        
        // Ако има, се спира оттеглянето
        if($zQuery->fetch()){
            core_Statuses::newStatus('Документа не може да се оттегли, докато има нагласени количества в зоната', 'error');
            return false;
        }
    }
}