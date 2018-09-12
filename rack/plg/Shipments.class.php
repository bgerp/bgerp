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
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($zoneId = rack_Zones::fetch("#containerId = {$rec->containerId}")){
            $row->zoneId = rack_Zones::getHyperlink($zoneId);
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
        if(count($btnData->url)){
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
            if(isset($mvc->mainDetail)){
                $Detail = cls::get($mvc->mainDetail);
                $dQuery = $Detail->getQuery();
                $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
                
                while($dRec = $dQuery->fetch()){
                    $key = "{$dRec->{$Detail->productFld}}|{$dRec->packagingId}";
                    if(!array_key_exists($key, $res)){
                        $res[$key] = (object)array('productId' => $dRec->{$Detail->productFld}, 'packagingId' => $dRec->packagingId);
                    }
                    $res[$key]->quantity += $dRec->{$Detail->quantityFld};
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
                core_Statuses::newStatus('Документът не може да се контира, докато има още за нагласяне', 'error');
                
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
}