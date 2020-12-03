<?php


/**
 * Клас 'rack_plg_IncomingShipmentDetails'
 * Плъгин за връзка между детайла на входящи складови документи и палетния склад
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
class rack_plg_IncomingShipmentDetails extends core_Plugin
{
    
    
    /**
     * Преди рендиране на таблицата
     */
    public static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $rows = &$data->rows;
        if (!countR($rows)) {
            
            return;
        }
        
        $storeId = isset($data->masterMvc->toStoreFieldName) ? $data->masterData->rec->{$data->masterMvc->toStoreFieldName} : $data->masterData->rec->{$data->masterMvc->storeFieldName}; 
        
        foreach ($rows as $id => &$row) {
            $rec = $data->recs[$id];
            $canStore = cat_Products::fetchField($rec->{$mvc->productFld}, 'canStore');
            if($canStore != 'yes') continue;
            
            $batchDef = batch_Defs::getBatchDef($rec->{$mvc->productFld});
            if(empty($batchDef)){
                if($palletImgLink = rack_Pallets::getFloorToPalletImgLink($storeId, $rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->packQuantityFld}, null, $data->masterData->rec->containerId)){
                    $row->packQuantity = $palletImgLink . $row->packQuantity;
                }
            }
        }
    }
}