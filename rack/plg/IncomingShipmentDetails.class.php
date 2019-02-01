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
        if (!count($rows)) {
            
            return;
        }
       
        foreach ($rows as $id => &$row) {
            $rec = $data->recs[$id];
            
            if($palletImgLink = rack_Pallets::getFloorToPalletImgLink($data->masterData->rec->storeId, $rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->packQuantityFieldName})){
                $row->packQuantity .= $palletImgLink;
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal111111111111111($mvc, &$row, $rec, $fields = array())
    {
       
        
        
        
        
        return;
        
        if (rack_Movements::haveRightFor('add', (object) array('productId' => $rec->productId)) && $rec->quantityNotOnPallets > 0) {
            $measureId = cat_Products::fetchField($rec->productId, 'measureId');
            
            // ОТ URL е махнато количеството, защото (1) винаги предлага с това количество палет; (2) Неща, които ги има в базата, не трябва да се предават в URL
            $row->_rowTools->addLink('Палетиране', array('rack_Movements', 'add', 'productId' => $rec->productId, 'packagingId' => $measureId, 'movementType' => 'floor2rack', 'ret_url' => true), 'ef_icon=img/16/pallet1.png,title=Палетиране на артикул');
        }
        
        
        
        
        if ($mvc->haveRightFor('prepare', $rec)) {
            $url = array($mvc, 'prepare', 'id' => $rec->id, 'ret_url' => true);
            $row->_rowTools->addLink('Подготвяне', $url, array('ef_icon' => 'img/16/tick-circle-frame.png', 'title' => 'Ръчна подготовка на документа'));
        }
    }
    
}