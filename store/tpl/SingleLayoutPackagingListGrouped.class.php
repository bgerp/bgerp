<?php


/**
 * Клас закачащ се към шаблона за Packaging List за митница
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
 * @title     Обработвач на шаблона за ЕН за митница
 */
class store_tpl_SingleLayoutPackagingListGrouped extends doc_TplScript
{

    /**
     * Към шаблоните на кой документ да може да се избира
     */
    public $addToClassTemplate = 'store_ShipmentOrders';


    /**
     * Константа за празен тарифен номер
     */
    const EMPTY_TARIFF_NUMBER = '_';


    /**
     * Префикс за тарифен код
     */
    protected $tariffCodeCaption = 'HS Code / CTN';


    /**
     * Метод който подава данните на мастъра за обработка на скрипта
     *
     * @param core_Mvc $mvc  - мастър на документа
     * @param stdClass $data - данни
     *
     * @return void
     */
    public function modifyMasterData(core_Mvc $mvc, &$data)
    {
        
    }
    
    
    /**
     * Метод който подава данните на детайла на мастъра, за обработка на скрипта
     *
     * @param core_Mvc $detail - Детайл на документа
     * @param stdClass $data   - данни
     *
     * @return void
     */
    public function modifyDetailData(core_Mvc $detail, &$data)
    {
        if(!countR($data->recs) || Mode::is('renderHtmlInLine')) return;

        // Извлича се тарифния номер на артикулите
        $length = store_Setup::get('TARIFF_NUMBER_LENGTH');
        $getLiveTariffCode = in_array($data->masterData->rec->state, array('pending', 'draft'));

        foreach ($data->rows as $id => &$row){
            $rec = $data->recs[$id];

            $tariffNumber = $rec->tariffCode;
            if(empty($tariffNumber) && $getLiveTariffCode){
                $tariffNumber = cat_Products::getParams($rec->productId, 'customsTariffNumber', true);
                $tariffNumber = !empty($tariffNumber) ? $tariffNumber : self::EMPTY_TARIFF_NUMBER;
            }

            $tariffNumber = !empty($tariffNumber) ? mb_substr($tariffNumber, 0, $length) : self::EMPTY_TARIFF_NUMBER;
            $rec->tariffNumber = $tariffNumber;
            $row->tariffNumber = $tariffNumber;
        }
    }


    /**
     * Вербално показване на реда
     *
     * @param $value
     * @param $type
     * @param $exValue
     * @return core_ET|string
     */
    private function getVerbalRow($value, $type, $exValue)
    {
        // Показване на полето като лайв или ръчно въведеното;
        $isReadOnly = Mode::isReadOnly();
        $res = core_Type::getByName($type)->toVerbal($value);
        if(!empty($exValue)){
            $weightRecVerbal = core_Type::getByName($type)->toVerbal($exValue);
            if(!$isReadOnly){
                $res = ht::createHint($weightRecVerbal, "Автоматично: {$res}", 'noicon');
            }
        } else {
            if(!$isReadOnly && $res != self::EMPTY_TARIFF_NUMBER){
                $res = "<span style='color:black'>{$res}</span>";
            }
        }

        return $res;
    }


    /**
     * Преди рендиране на шаблона на детайла
     *
     * @param core_Mvc $detail
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    public function beforeRenderListTable(core_Mvc $detail, &$tpl, &$data)
    {
        if(!countR($data->recs) || Mode::is('renderHtmlInLine')) return;
        if($detail instanceof store_DocumentPackagingDetail) return;

        $columns = countR($data->listFields);
        $masterRec = $data->masterData->rec;

        $totalInPackListWithTariffCodeVal = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, 'totalInPackListWithTariffCode');
        $totalTareInPackListWithTariffCodeVal = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, 'tareInPackListWithTariffCode');

        // Извличане на всички уникални тарифни номера и сумиране на данните им
        $tariffCodes = array();

        foreach ($data->rows as $id => $row) {
            $rec1 = $data->recs[$id];
            if(!array_key_exists($rec1->tariffNumber, $tariffCodes)){
                $tariffCodes[$rec1->tariffNumber] = (object)array('code' => $rec1->tariffNumber, 'weight' => null, 'netWeight' => null, 'transUnits' => array());
            }

            $transUnitId = $transUnitQuantity = null;
            if(!empty($rec1->transUnitId) && !empty($rec1->transUnitQuantity)){
                $transUnitId = $rec1->transUnitId;
                $transUnitQuantity = $rec1->transUnitQuantity;
            } else {
                if($bestPack = trans_TransportUnits::getBestUnit($rec1->productId, $rec1->quantity, $rec1->packagingId)){
                    $transUnitId = $bestPack['unitId'];
                    $transUnitQuantity = $bestPack['quantity'];
                }
            }

            if(!empty($transUnitQuantity)){
                $tariffCodes[$rec1->tariffNumber]->transUnits[$transUnitId] += $transUnitQuantity;
            }

            $netWeight = $detail->getNetWeight($rec1->productId, $rec1->packagingId, $rec1->quantity, $rec1->netWeight);
            $weight = $detail->getWeight($rec1->productId, $rec1->packagingId, $rec1->quantity, $rec1->weight);

            if($totalInPackListWithTariffCodeVal == 'yes'){
                $amountR = $rec1->amount * (1 - $rec1->discount);
                if($masterRec->chargeVat == 'separate'){
                    $vat = cat_Products::getVat($rec1->productId, $masterRec->valior);
                    $amountR += $amountR * $vat;
                }

                $tariffCodes[$rec1->tariffNumber]->amount += $amountR;
            }

            $tariffCodes[$rec1->tariffNumber]->weight += $weight;
            $tariffCodes[$rec1->tariffNumber]->netWeight += $netWeight;
            if($totalTareInPackListWithTariffCodeVal == 'yes'){
                $tareWeight = $detail->getTareWeight($rec1->productId, $rec1->packagingId, $rec1->quantity, $rec1->tareWeight, $weight, $netWeight);
                if($tareWeight > 0){
                    $tariffCodes[$rec1->tariffNumber]->tareWeight += $tareWeight;
                }
            }
        }

        ksort($tariffCodes, SORT_STRING);
        $rows = array();
        $isReadOnly = Mode::isReadOnly();

        // За всяко поле за групиране
        foreach ($tariffCodes as $tariffNumber => $tariffObject) {
            $tariffCodeRec = store_ShipmentOrderTariffCodeSummary::getRec($masterRec->id, $tariffNumber);


            $weightVerbal = $this->getVerbalRow($tariffObject->weight, 'cat_type_Weight', $tariffCodeRec->weight);
            $netWeightVerbal = $this->getVerbalRow($tariffObject->netWeight, 'cat_type_Weight', $tariffCodeRec->netWeight);
            $displayTariffCode = $this->getVerbalRow($tariffObject->code, 'varchar', $tariffCodeRec->displayTariffCode);

            if($displayTariffCode != self::EMPTY_TARIFF_NUMBER){
                $code = "{$this->tariffCodeCaption} {$displayTariffCode}";
                $tariffDescription = cond_TariffCodes::getDescriptionByCode($tariffObject->code, $masterRec->tplLang);
                $tariffDescriptionVerbal = $this->getVerbalRow($tariffDescription, 'varchar', $tariffCodeRec->displayDescription);
            } else {
                $code = tr('Без тарифен код');
                $tariffDescriptionVerbal = null;
            }

            // Показване на полето като лайв или ръчно въведеното;
            $transUnitsVerbal = trans_Helper::displayTransUnits($tariffObject->transUnits);
            if(isset($tariffCodeRec->transUnits)){
                $transUnitsConverted = trans_Helper::convertTableToNormalArr($tariffCodeRec->transUnits);
                $transUnitsInputVerbal = trans_Helper::displayTransUnits($transUnitsConverted);
                if(!$isReadOnly){
                    $transUnitsVerbal = ht::createHint($transUnitsInputVerbal, "Автоматично: {$transUnitsVerbal}", 'noicon');
                }
            } else {
                if(!$isReadOnly && !empty($transUnitsVerbal)){
                    $transUnitsVerbal = "<span style='color:blue'>{$transUnitsVerbal}</span>";
                }
            }

            $groupBlock = getTplFromFile('store/tpl/HScodeBlock.shtml');
            $groupBlock->append($code, 'code');
            $groupBlock->append($weightVerbal, 'weight');
            $groupBlock->append($netWeightVerbal, 'netWeight');
            $groupBlock->append($tariffDescriptionVerbal, 'description');
            if($totalTareInPackListWithTariffCodeVal == 'yes'){
                $tareWeightVerbal = $this->getVerbalRow($tariffObject->tareWeight, 'cat_type_Weight', $tariffCodeRec->tareWeight);
                $groupBlock->append($tareWeightVerbal, 'tareWeight');
            }

            $groupBlock->append($transUnitsVerbal, 'transUnits');
            if($totalInPackListWithTariffCodeVal == 'yes'){
                $groupAmountVerbal = $this->getVerbalRow($tariffObject->amount, 'double(decimals=2)', $tariffCodeRec->amount);
                $groupAmountVerbal .= "<span style='font-weight:normal;'> {$masterRec->currencyId} " . (($masterRec->chargeVat == 'yes' || $masterRec->chargeVat == 'separate') ? tr('|с ДДС|*') : tr('|без ДДС|*')) . "</span>";
                $groupBlock->append($groupAmountVerbal, 'groupAmount');
            }
            $groupVerbal = $groupBlock;
            
            // Създаваме по един ред с името му, разпънат в цялата таблица
            $rowAttr = array('class' => ' group-by-field-row');

            $modifyBtn = new core_ET("");
            if(!Mode::isReadOnly()){

                if(store_ShipmentOrderTariffCodeSummary::haveRightFor('modify', (object)array('shipmentId' => $masterRec->id, 'tariffCode' => $tariffNumber))){
                    $modifyUrl = array('store_ShipmentOrderTariffCodeSummary', 'modify', 'shipmentId' => $masterRec->id, 'tariffCode' => $tariffNumber, 'ret_url' => true);
                    foreach (array('weight', 'netWeight', 'tareWeight', 'amount', 'transUnits') as $fld){
                        $modifyUrl[$fld] = $tariffObject->{$fld};
                    }
                    $modifyUrl['displayDescription'] = $tariffDescription;
                    $modifyBtn = ht::createBtn('Промяна', $modifyUrl, false, false, 'ef_icon=img/16/edit.png,title=Промяна на обобщения ред на митническия код');
                }
            }

            $element = ht::createElement('tr', $rowAttr, new ET("<td style='padding-top:9px;padding-left:5px;' colspan='{$columns}'>" . $groupVerbal . ' <span style="float:right">' . $modifyBtn->getContent() . '</span></td>'));
            $rows['|' . $tariffNumber] = $element;
            
            // За всички записи
            foreach ($data->rows as $id => $row) {
                $rec = $data->recs[$id];
                // Ако стойността на полето им за групиране е същата като текущото
                if ($rec->tariffNumber == $tariffNumber) {
                    if (is_object($data->rows[$id])) {
                        $rows[$id] = clone $data->rows[$id];
                        
                        // Веднъж групирано, премахваме записа от старите записи
                        unset($data->rows[$id]);
                    }
                }
            }
        }
        
        $data->rows = $rows;
    }
}