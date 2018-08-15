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
        
        $rec = $data->rec;
        if (rack_Zones::haveRightFor('selectdocument', (object)array('containerId' => $rec->containerId))){
             $data->toolbar->addBtn('Зона', array(rack_Zones, 'selectdocument', 'containerId' => $rec->containerId, 'ret_url' => true), "ef_icon=img/16/bug.png,title=Събиране на документа");
        }
    }
    
    
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
        
        if ($zoneId = rack_Zones::fetch("#containerId = {$rec->containerId}")){
            cls::get('rack_Zones')->updateMaster($zoneId);
        }
    }
    
    
    /**
     * Изпълнява се преди контиране на документа
     */
    public static function on_BeforeConto11(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        //rack_DocumentRows::checkQuantities($rec->containerId);
        core_Statuses::newStatus('|love', 'warning');
        
        return false;
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
}