<?php


/**
 * Клас 'store_plg_TransportDataDetail' добавящ транспортната информация на детайл на складов документ
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
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
        setIfNot($mvc->netWeightField, 'netWeight');
        setIfNot($mvc->tareWeightField, 'tareWeight');
        setIfNot($mvc->volumeField, 'volume');
        setIfNot($mvc->productFld, 'productId');
        setIfNot($mvc->packagingFld, 'packagingId');
        setIfNot($mvc->quantityFld, 'quantity');

        $mvc->FLD($mvc->weightField, 'cat_type_Weight', 'input=none,caption=Логистична информация->Бруто,forceField');
        $mvc->FLD($mvc->netWeightField, 'cat_type_Weight', 'input=none,caption=Логистична информация->Нето,forceField');
        $mvc->FLD($mvc->tareWeightField, 'cat_type_Weight', 'input=none,caption=Логистична информация->Тара,forceField');
        $mvc->FLD($mvc->volumeField, 'cat_type_Volume', 'input=none,caption=Логистична информация->Обем,forceField');
        $mvc->FLD('transUnitId', 'key(mvc=trans_TransportUnits,select=name,allowEmpty)', "caption=Логистична информация->Единици,forceField,tdClass=nowrap,after={$mvc->volumeField},smartCenter,input=none");
        $mvc->FLD('transUnitQuantity', 'int(min=1)', 'caption=Логистична информация->К-во,inlineTo=transUnitId,forceField,unit=бр.,input=none');
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
                $form->setField('netWeight', 'input');
                $form->setField('volume', 'input');
                $form->setField('tareWeight', 'input');
                $form->setField('transUnitId', 'input');
                $form->setField('transUnitQuantity', 'input');
            }
        }

        if(!Mode::get(plg_PrevAndNext::getModeKey($mvc))){
            foreach (array('tariffCode', $mvc->weightField, $mvc->netWeightField, $mvc->tareWeightField, $mvc->volumeField, 'transUnitId', 'transUnitQuantity') as $fld){
                if($form->getField($fld, false)){
                    $form->setField($fld, 'autohide');
                }
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
        $masterState = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state');
        $row->weight = deals_Helper::getWeightRow($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $masterState, $rec->{$mvc->weightField});
        $row->volume = deals_Helper::getVolumeRow($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $masterState, $rec->{$mvc->volumeField});
        $row->netWeight = deals_Helper::getNetWeightRow($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $masterState, $rec->{$mvc->netWeightField});

        if(empty($rec->tareWeight)){
            if(!empty($rec->weight) && !empty($rec->netWeight)){
                $rec->tareWeight = $rec->weight - $rec->netWeight;
                if($rec->tareWeight > 0){
                    $row->tareWeight = core_Type::getByName('cat_type_Weight')->toVerbal($rec->tareWeight);
                    $row->tareWeight = "<span style='color:blue'>{$row->tareWeight}</span>";
                    $row->tareWeight = ht::createHint($row->tareWeight, 'Тарата е сметната на база брутото и нетото', 'notice', false);
                }
            }
        }

        $row->transUnitId = deals_Helper::getTransUnitRow($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $masterState, $rec->transUnitId, $rec->transUnitQuantity);
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
        $masterRec = $mvc->Master->fetchRec($masterId);
        $masterId = $masterRec->id;
        $cWeight = $cVolume = $cNetWeight = $cTareWeight = 0;
        $query = $mvc->getQuery();
        $query->where("#{$mvc->masterKey} = {$masterId}");
        $calcLive = $force || in_array($masterRec->state, array('draft', 'pending'));

        // За всеки запис
        $units = array();
        while ($rec = $query->fetch()) {
            $canStore = cat_Products::fetchField($rec->{$mvc->productFld}, 'canStore');
            if($canStore != 'yes') continue;
            $saveFields = array();
            $clone = clone $rec;

            // Изчислява се теглото
            if($calcLive){
                $w = $mvc->getWeight($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->weightField});
            } else {
                $w = $rec->{$mvc->weightField};
            }

            // Форсира се при нужда
            if ($force === true && empty($rec->{$mvc->weightField}) && !empty($w)) {
                $clone->{$mvc->weightField} = $w;
                $saveFields[] = $mvc->weightField;
            }
            
            // Сумира се
            if (empty($rec->{$mvc->quantityFld}) || (!empty($w) && !is_null($cWeight))) {
                $cWeight += $w;
            } else {
                $cWeight = null;
            }

            // Изчислява се теглото
            if($calcLive){
                $w1 = $mvc->getNetWeight($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->netWeightField});
            } else {
                $w1 = $rec->{$mvc->netWeightField};
            }

            // Форсира се при нужда
            if ($force === true && empty($rec->{$mvc->netWeightField}) && !empty($w1)) {
                $clone->{$mvc->netWeightField} = $w1;
                $saveFields[] = $mvc->netWeightField;
            }

            // Сумира се
            if (empty($rec->{$mvc->quantityFld}) || (!empty($w1) && !is_null($cNetWeight))) {
                $cNetWeight += $w1;
            } else {
                $cNetWeight = null;
            }

            // Изчислява се обема
            if($calcLive){
                $v = $mvc->getVolume($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->volumeField});
            } else {
                $v = $rec->{$mvc->volumeField};
            }
            
            // Форсира се при нужда
            if ($force === true && empty($rec->{$mvc->volumeField}) && !empty($v)) {
                $clone->{$mvc->volumeField} = $v;
                $saveFields[] = $mvc->volumeField;
            }
            
            // Сумира се
            if (empty($rec->{$mvc->quantityFld}) || (!empty($v) && !is_null($cVolume))) {
                $cVolume += $v;
            } else {
                $cVolume = null;
            }

            // Изчислява се теглото
            if($calcLive){
                $tW = $mvc->getTareWeight($rec->{$mvc->productFld}, $rec->{$mvc->packagingFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->tareWeightField}, $w, $w1);
            } else {
                $tW = $rec->{$mvc->tareWeightField};
            }

            // Форсира се при нужда
            if ($force === true && empty($rec->{$mvc->tareWeightField}) && !empty($tW)) {
                $clone->{$mvc->tareWeightField} = $tW;
                $saveFields[] = $mvc->tareWeightField;
            }

            // Сумира се
            if (empty($rec->{$mvc->quantityFld}) || (!empty($tW) && !is_null($cTareWeight))) {
                $cTareWeight += $tW;
            } else {
                $cTareWeight = null;
            }

            // Изчисляват се логистичните единици
            $unitId = $uQuantity = null;
            if(isset($rec->transUnitId) && isset($rec->transUnitQuantity)){
                $unitId = $rec->transUnitId;
                $uQuantity = $rec->transUnitQuantity;
            } elseif($calcLive) {
                $bestUnits = trans_TransportUnits::getBestUnit($rec->{$mvc->productFld}, $rec->{$mvc->quantityFld}, $rec->{$mvc->packagingFld});
                if(is_array($bestUnits)){
                    $unitId = $bestUnits['unitId'];
                    $uQuantity = $bestUnits['quantity'];
                }
            }

            if(isset($unitId) && isset($uQuantity)){
                $units[$unitId] += $uQuantity;
            }

            if ($force === true && empty($rec->transUnitId) && !empty($unitId)) {
                $clone->transUnitId = $unitId;
                $saveFields[] = 'transUnitId';
            }

            if ($force === true && empty($rec->transUnitQuantity) && !empty($uQuantity)) {
                $clone->transUnitQuantity = $uQuantity;
                $saveFields[] = 'transUnitQuantity';
            }

            if(countR($saveFields)){
                $mvc->save_($clone, $saveFields);
            }
        }
        
        // Връщане на обема и теглото
        $weight = (!empty($cWeight)) ? $cWeight : null;
        $netWeight = (!empty($cNetWeight)) ? $cNetWeight : null;
        $tareWeight = (!empty($cTareWeight)) ? $cTareWeight : null;
        $volume = (!empty($cVolume)) ? $cVolume : null;

        $res = (object) array('weight' => $weight, 'volume' => $volume, 'transUnits' => $units, 'netWeight' => $netWeight, 'tareWeight' => $tareWeight);
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
     * Връща нето теглото на реда, ако няма изчислява го на момента
     *
     * @param core_Mvc   $mvc
     * @param float|NULL $res
     * @param int        $productId
     * @param int        $packagingId
     * @param float      $quantity
     * @param float|NULL $netWeight
     */
    public function on_AfterGetNetWeight($mvc, &$res, $productId, $packagingId, $quantity, $netWeight = null)
    {
        if (!isset($netWeight)) {
            $netWeight = cat_Products::convertToUom($productId, 'kg');
            if($netWeight){
                $netWeight *= $quantity;
            }
            $netWeight = deals_Helper::roundPrice($netWeight, 3);
        }

        $res = $netWeight;
    }

    /**
     * Връща нето теглото на реда, ако няма изчислява го на момента
     *
     * @param core_Mvc   $mvc
     * @param float|NULL $res
     * @param int        $productId
     * @param int        $packagingId
     * @param float      $quantity
     * @param float|NULL $tareWeight
     * @param float|NULL $weight
     * @param float|NULL $netWeight
     */
    public function on_AfterGetTareWeight($mvc, &$res, $productId, $packagingId, $quantity, $tareWeight = null, $weight = null, $netWeight = null)
    {
        $res = $tareWeight;
        if(!isset($res)){
            if(isset($weight) && isset($netWeight)){
                $res = $weight - $netWeight;

                if($res <= 0){
                    $res = null;
                }
            }
        }
    }


    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $masterRec = $data->masterData->rec;

        if (!empty($masterRec->netWeightInput) && $masterRec->netWeightInput != $masterRec->calcedNetWeight) {
            unset($data->listFields['netWeight']);
        }

        if (!empty($masterRec->weightInput) && $masterRec->weightInput != $masterRec->calcedWeight) {
            unset($data->listFields['weight']);
        }
        
        if (!empty($masterRec->volumeInput) && $masterRec->volumeInput != $masterRec->calcedVolume) {
            unset($data->listFields['volume']);
        }

        if (!empty($masterRec->tareWeightInput) && $masterRec->tareWeightInput != $masterRec->calcedTareWeight) {
            unset($data->listFields['tareWeight']);
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

            // Проверка на логистичната информация
            $checkTransData = deals_Helper::checkTransData($rec->{$mvc->weightField}, $rec->{$mvc->netWeightField}, $rec->{$mvc->tareWeightField}, $mvc->weightField, $mvc->netWeightField, $mvc->tareWeightField);
            if(countR($checkTransData['errors'])){
                foreach ($checkTransData['errors'] as $errArr){
                    $form->setError($errArr['fields'], $errArr['text']);
                }
            }
        }
    }
}
