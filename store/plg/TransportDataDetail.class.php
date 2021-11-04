<?php


/**
 * Клас 'store_plg_TransportDataDetail' добавящ транспортната информация на детайл на складов документ
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_plg_TransportDataDetail extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('store_iface_DetailsTransportData');
        setIfNot($mvc->weightField, 'weight');
        setIfNot($mvc->volumeField, 'volume');
        setIfNot($mvc->productFld, 'productId');
        setIfNot($mvc->packagingFld, 'packagingId');
        setIfNot($mvc->quantityFld, 'quantity');
        
        $mvc->FLD($mvc->weightField, 'cat_type_Weight', 'input=none,caption=Логистична информация->Бруто,forceField,autohide');
        $mvc->FLD($mvc->volumeField, 'cat_type_Volume', 'input=none,caption=Логистична информация->Обем,forceField,autohide');
        $mvc->FLD('transUnitId', 'key(mvc=trans_TransportUnits,select=name,allowEmpty)', "caption=Логистична информация->Единици,forceField,autohide,tdClass=nowrap,after={$mvc->volumeField},smartCenter,input=none");
        $mvc->FLD('transUnitQuantity', 'int(min=1)', 'caption=Логистична информация->К-во,autohide,inlineTo=transUnitId,forceField,unit=бр.,input=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $remFields = $form->getFieldParam($mvc->packagingFld, 'removeAndRefreshForm') . '|transUnitId|transUnitQuantity';
        $form->setField($mvc->packagingFld, "removeAndRefreshForm={$remFields}");

        if (isset($rec->{$mvc->productFld})) {
            
            // Ако артикула е складируем, показват се полетата за тегло/обем
            $isStorable = cat_Products::fetchField($rec->{$mvc->productFld}, 'canStore');
            if ($isStorable == 'yes') {
                $form->setField('weight', 'input');
                $form->setField('volume', 'input');
                $form->setField('transUnitId', 'input');
                $form->setField('transUnitQuantity', 'input');
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $canStore = cat_Products::fetchField($rec->{$mvc->productFld}, 'canStore');
        if($canStore != 'yes') return;

        // Показване на транспортното тегло/обем/лог.ед ако няма, сизчисляват динамично
        $row->weight = deals_Helper::getWeightRow($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->weightField});
        $row->volume = deals_Helper::getVolumeRow($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->volumeField});
        $row->transUnitId = deals_Helper::getTransUnitRow($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->transUnitId, $rec->transUnitQuantity);
    }
    
    
    /**
     * Изчисляване на общото тегло и обем на редовете
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     *                           - weight - теглото на реда
     *                           - volume - теглото на реда
     * @param int      $masterId
     * @param bool     $force
     */
    public static function on_AfterGetTransportInfo($mvc, &$res, $masterId, $force = false)
    {
        $masterId = $mvc->Master->fetchRec($masterId)->id;
        $cWeight = $cVolume = 0;
        $query = $mvc->getQuery();
        $query->where("#{$mvc->masterKey} = {$masterId}");
        $units = array();
        
        // За всеки запис
        while ($rec = $query->fetch()) {
            $canStore = cat_Products::fetchField($rec->{$mvc->productFld}, 'canStore');
            if($canStore != 'yes') continue;
            
            // Изчислява се теглото
            $w = $mvc->getWeight($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->weightField});
            
            // Форсира се при нужда
            if ($force === true && empty($rec->{$mvc->weightField}) && !empty($w)) {
                $clone = clone $rec;
                $clone->{$mvc->weightField} = $w;
                $mvc->save_($clone, $mvc->weightField);
            }
            
            // Сумира се
            if (empty($rec->{$mvc->quantityFld}) || (!empty($w) && !is_null($cWeight))) {
                $cWeight += $w;
            } else {
                $cWeight = null;
            }
            
            // Изчислява се обема
            $v = $mvc->getVolume($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->volumeField});
            
            // Форсира се при нужда
            if ($force === true && empty($rec->{$mvc->volumeField}) && !empty($v)) {
                $clone = clone $rec;
                $clone->{$mvc->volumeField} = $v;
                $mvc->save_($clone, $mvc->volumeField);
            }
            
            // Сумира се
            if (empty($rec->{$mvc->quantityFld}) || (!empty($v) && !is_null($cVolume))) {
                $cVolume += $v;
            } else {
                $cVolume = null;
            }

            // Изчисляват се логистичните единици
            $unitId = $uQuantity = null;
            if(isset($rec->transUnitId) && isset($rec->transUnitQuantity)){
                $unitId = $rec->transUnitId;
                $uQuantity = $rec->transUnitQuantity;
            } else {
                $bestUnits = trans_TransportUnits::getBestUnit($rec->{$mvc->productFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->packagingFld});
                if(is_array($bestUnits)){
                    $unitId = $bestUnits['unitId'];
                    $uQuantity = $bestUnits['quantity'];
                }
            }

            if(isset($unitId) && isset($uQuantity)){
                $units[$unitId] += $uQuantity;
            }

            if ($force === true && empty($rec->{$mvc->transUnitId}) && !empty($unitId)) {
                $clone = clone $rec;
                $clone->transUnitId = $unitId;
                $mvc->save_($clone, 'transUnitId');
            }

            if ($force === true && empty($rec->{$mvc->transUnitQuantity}) && !empty($uQuantity)) {
                $clone = clone $rec;
                $clone->transUnitQuantity = $uQuantity;
                $mvc->save_($clone, 'transUnitQuantity');
            }
        }
        
        // Връщане на обема и теглото
        $weight = (!empty($cWeight)) ? $cWeight : null;
        $volume = (!empty($cVolume)) ? $cVolume : null;

        $res = (object) array('weight' => $weight, 'volume' => $volume, 'transUnits' => $units);
    }
    
    
    /**
     * Връща теглото на реда, ако няма изчислява го на момента
     *
     * @param core_Mvc   $mvc
     * @param float|NULL $res
     * @param int        $productId
     * @param int        $packagingId
     * @param float      $quantity
     * @param float|NULL $weight
     */
    public function on_AfterGetWeight($mvc, &$res, $productId, $packagingId, $quantity, $weight = null)
    {
        if (!isset($weight)) {
            $weight = cat_Products::getTransportWeight($productId, $quantity);
            $weight = deals_Helper::roundPrice($weight, 3);
        }
        
        $res = $weight;
    }
    
    
    /**
     * Връща обема на реда, ако няма изчислява го на момента
     *
     * @param core_Mvc   $mvc
     * @param float|NULL $res
     * @param int        $productId
     * @param int        $packagingId
     * @param float      $quantity
     * @param float|NULL $weight
     */
    public function on_AfterGetVolume($mvc, &$res, $productId, $packagingId, $quantity, $volume = null)
    {
        if (!isset($volume)) {
            $volume = cat_Products::getTransportVolume($productId, $quantity);
            $volume = deals_Helper::roundPrice($volume, 3);
        }
        
        $res = $volume;
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $masterRec = $data->masterData->rec;
        
        if (!empty($masterRec->weightInput) && $masterRec->weightInput != $masterRec->calcedWeight) {
            unset($data->listFields['weight']);
        }
        
        if (!empty($masterRec->volumeInput) && $masterRec->volumeInput != $masterRec->calcedVolume) {
            unset($data->listFields['volume']);
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            if (empty($rec->transUnitId) && !empty($rec->transUnitQuantity)) {
                $form->setError('transUnitId,transUnitQuantity', 'Липсва логистична единица');
            } elseif(empty($rec->transUnitQuantity) && !empty($rec->transUnitId)){
                $form->setError('transUnitId,transUnitQuantity', 'Липсва количеството на логистичната единица');
            }
        }
    }
}
