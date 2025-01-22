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
    private function getVerbalRow(&$value, $type, $exValue)
    {
        // Показване на полето като лайв или ръчно въведеното;
        $isReadOnly = Mode::isReadOnly();
        $res = core_Type::getByName($type)->toVerbal($value);
        if(!empty($exValue)){
            $value = $exValue;
            $weightRecVerbal = core_Type::getByName($type)->toVerbal($exValue);
            if(!$isReadOnly){
                $res = ht::createHint($weightRecVerbal, "Изчислено от редовете|*: {$res}", 'noicon');
            } else {
                $res = $weightRecVerbal;
            }
        } else {
            if(!$isReadOnly && $res != self::EMPTY_TARIFF_NUMBER){
                $res = "<span style='color:blue'>{$res}</span>";
                $res = ht::createHint($res, "Изчислено от редовете с този МТК", 'noicon');
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

        // Скриване на колонките за нето/тара/бруто
        $masterRec = $data->masterData->rec;
        $firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
        $vatType = $firstDoc->isInstanceOf('sales_Sales') ? 'sales' : 'purchase';

        $columnCount = countR($data->listFields);
        $totalInPackListWithTariffCodeVal = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, 'totalInPackListWithTariffCode');
        $data->totalTareInPackListWithTariffCodeVal = cond_Parameters::getParameter($masterRec->contragentClassId, $masterRec->contragentId, 'tareInPackListWithTariffCode');

        // Извличане на всички уникални тарифни номера и сумиране на данните им
        $data->tariffCodes = array();

        foreach ($data->rows as $id => $row) {
            $rec1 = $data->recs[$id];
            if(!array_key_exists($rec1->tariffNumber, $data->tariffCodes)){
                $data->tariffCodes[$rec1->tariffNumber] = (object)array('code' => $rec1->tariffNumber, 'weight' => null, 'netWeight' => null, 'transUnits' => array());
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
                $data->tariffCodes[$rec1->tariffNumber]->transUnits[$transUnitId] += $transUnitQuantity;
            }

            $netWeight = $detail->getNetWeight($rec1->productId, $rec1->packagingId, $rec1->quantity, $rec1->netWeight);
            $weight = $detail->getWeight($rec1->productId, $rec1->packagingId, $rec1->quantity, $rec1->weight);

            if($totalInPackListWithTariffCodeVal == 'yes'){
                $amountR = $rec1->amount * (1 - $rec1->discount);
                if($masterRec->chargeVat == 'separate'){
                    $vat = cat_Products::getVat($rec1->productId, $masterRec->valior, $vatType);
                    $amountR += $amountR * $vat;
                }

                $data->tariffCodes[$rec1->tariffNumber]->amount += $amountR;
            }

            $data->tariffCodes[$rec1->tariffNumber]->weight += $weight;
            $data->tariffCodes[$rec1->tariffNumber]->netWeight += $netWeight;
        }

        // Подредба по МТК, като без МТК ще е най-накрая
        $emptyArr = $data->tariffCodes[static::EMPTY_TARIFF_NUMBER];
        unset($data->tariffCodes[static::EMPTY_TARIFF_NUMBER]);
        ksort($data->tariffCodes, SORT_STRING);
        if(is_object($emptyArr)){
            $data->tariffCodes += array(static::EMPTY_TARIFF_NUMBER => $emptyArr);
        }

        $rows = array();
        $isReadOnly = Mode::isReadOnly();
        $count = 0;

        // За всяко поле за групиране
        $unsetWeightCol = $unsetNetWeightCol = $unsetTareWeightCol = false;
        foreach ($data->tariffCodes as $tariffNumber => $tariffObject) {
            $tariffCodeRec = store_ShipmentOrderTariffCodeSummary::getRec($masterRec->id, $tariffNumber);
            $typeOfPacking = $tariffCodeRec->typeOfPacking;
            $typeOfPackingVerbal = null;
            if(empty($typeOfPacking)){
                $typeOfPackingDefault = store_Setup::get('SO_TYPE_OF_PACKING_DEFAULT');
                if(!empty($typeOfPackingDefault)){
                    $typeOfPacking = $typeOfPackingDefault;
                    $typeOfPackingVerbal = core_Type::getByName('varchar')->toVerbal($typeOfPacking);
                    if(!Mode::isReadOnly()){
                        $typeOfPackingVerbal = "<span style='color:blue'>{$typeOfPackingVerbal}</span>";
                        $typeOfPackingVerbal = ht::createHint($typeOfPackingVerbal, 'Дефолтна настройка за системата', 'noicon');
                    }
                }
            } else {
                $typeOfPackingVerbal = core_Type::getByName('varchar')->toVerbal($typeOfPacking);
            }

            $unsetWeightCol = (!empty($tariffObject->weight) && !empty($tariffCodeRec->weight) && $tariffObject->weight != $tariffCodeRec->weight);
            $weightVerbal = $this->getVerbalRow($tariffObject->weight, 'cat_type_Weight', $tariffCodeRec->weight);

            $unsetNetWeightCol = (!empty($tariffObject->netWeight) && !empty($tariffCodeRec->netWeight) && $tariffObject->netWeight != $tariffCodeRec->netWeight);
            $netWeightVerbal = $this->getVerbalRow($tariffObject->netWeight, 'cat_type_Weight', $tariffCodeRec->netWeight);
            $displayTariffCode = $this->getVerbalRow($tariffObject->code, 'varchar', $tariffCodeRec->displayTariffCode);

            if($displayTariffCode != self::EMPTY_TARIFF_NUMBER){
                $code = "<span class='quiet small'>{$this->tariffCodeCaption}</span> {$displayTariffCode}";
                $tariffDescription = cond_TariffCodes::getDescriptionByCode($tariffObject->code, $masterRec->tplLang);
                $tariffDescriptionVerbal = $this->getVerbalRow($tariffDescription, 'varchar', $tariffCodeRec->displayDescription);
            } else {
                $code = "<span class='small'>" . tr('Без тарифен код') . "</span>";
                $tariffDescriptionVerbal = $tariffDescription = null;
            }

            // Показване на полето като лайв или ръчно въведеното;
            $transUnitsVerbal = trans_Helper::displayTransUnits($tariffObject->transUnits);
            if(isset($tariffCodeRec->transUnits)){
                $transUnitsConverted = trans_Helper::convertTableToNormalArr($tariffCodeRec->transUnits);
                $transUnitsInputVerbal = trans_Helper::displayTransUnits($transUnitsConverted);
                $tariffObject->transUnits = $tariffCodeRec->transUnits;
                if(!$isReadOnly){
                    $transUnitsVerbal = ht::createHint($transUnitsInputVerbal, "Изчислено от редовете|*: {$transUnitsVerbal}", 'noicon');
                } else {
                    $transUnitsVerbal = $transUnitsInputVerbal;
                }
            } else {
                if(!$isReadOnly && !empty($transUnitsVerbal)){
                    $transUnitsVerbal = "<span style='color:blue;font-weight:bold'>{$transUnitsVerbal}</span>";
                    $transUnitsVerbal = ht::createHint($transUnitsVerbal, "Изчислено от редовете с този МТК", 'noicon');
                }
            }

            $groupBlock = getTplFromFile('store/tpl/HScodeBlock.shtml');
            $groupBlock->append($code, 'code');
            $groupBlock->append($weightVerbal, 'weight');
            $groupBlock->append($netWeightVerbal, 'netWeight');
            $groupBlock->append($tariffDescriptionVerbal, 'description');
            $groupBlock->append($typeOfPackingVerbal, 'typeOfPacking');

            $tariffObject->tareWeight = $tariffObject->weight - $tariffObject->netWeight;
            if($data->totalTareInPackListWithTariffCodeVal == 'yes'){
                if($tariffObject->tareWeight >= 0){
                    $unsetTareWeightCol = (!empty($tariffObject->tareWeight) && !empty($tariffCodeRec->tareWeight) && $tariffObject->tareWeight != $tariffCodeRec->tareWeight);
                    $tareWeightVerbal = $this->getVerbalRow($tariffObject->tareWeight, 'cat_type_Weight', $tariffCodeRec->tareWeight);
                    $groupBlock->append($tareWeightVerbal, 'tareWeight');
                }
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

            $customStyle = "";
            $modifyBtn = new core_ET("");
            if(!Mode::isReadOnly()){

                if(store_ShipmentOrderTariffCodeSummary::haveRightFor('modify', (object)array('shipmentId' => $masterRec->id, 'tariffCode' => $tariffNumber))){
                    $modifyUrl = array('store_ShipmentOrderTariffCodeSummary', 'modify', 'shipmentId' => $masterRec->id, 'tariffCode' => $tariffNumber, 'ret_url' => true);
                    foreach (array('weight', 'netWeight', 'tareWeight', 'amount', 'transUnits') as $fld){
                        $modifyUrl[$fld] = $tariffObject->{$fld};
                    }

                    $modifyUrl['typeOfPacking'] = $typeOfPacking;
                    $modifyUrl['displayDescription'] = $tariffDescription;

                    $customStyle = ' padding-right: 100px !important; ';
                    if(!Mode::is('selectRows2Delete')) {
                        core_Lg::pop();
                        $modifyBtn = ht::createBtn('Промяна', $modifyUrl, false, false, 'class=fright,ef_icon=img/16/edit.png,title=Промяна на обобщения ред на митническия код,style=position:absolute; right: 8px; top:8px;');
                        $detail->Master->pushTemplateLg($masterRec->template);
                    }
                }
            }
            $element = ht::createElement('tr', $rowAttr, new ET("<td style='position:relative;background: #eee;padding-top:9px;padding-left:5px; {$customStyle}' colspan='{$columnCount}'>" . $groupVerbal .  $modifyBtn->getContent() .'</td>'));
            $rows['|' . $tariffNumber] = $element;
            
            // За всички записи
            foreach ($data->rows as $id => $row) {
                $rec = $data->recs[$id];
                // Ако стойността на полето им за групиране е същата като текущото
                if ($rec->tariffNumber == $tariffNumber) {
                    if (is_object($data->rows[$id])) {
                        $count++;
                        $rows[$id] = clone $data->rows[$id];
                        $rows[$id]->RowNumb = $count;

                        // Веднъж групирано, премахваме записа от старите записи
                        unset($data->rows[$id]);
                    }
                }
            }
        }

        $data->rows = $rows;

        // Ънсетване на колонките ако има разминаване между общо по мтк и въведеното
        if($unsetWeightCol){
            unset($data->listFields['weight']);
        }
        if($unsetNetWeightCol){
            unset($data->listFields['netWeight']);
        }
        if($unsetTareWeightCol){
            unset($data->listFields['tareWeight']);
        }
    }


    /**
     * След рендиране на лист таблицата
     *
     * @param core_Mvc $detail
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    public function afterRenderListTable(core_Mvc $detail, &$tpl, &$data)
    {
        if(Mode::isReadOnly() || !is_array($data->tariffCodes)) return;

        $transUnitsByTariffCodes = array();
        $weightByTariffCodes = $netWeightByTariffCodes = $tareWeightByTariffCodes = 0;
        array_walk($data->tariffCodes, function($a) use (&$weightByTariffCodes, &$netWeightByTariffCodes, &$tareWeightByTariffCodes, &$transUnitsByTariffCodes) {
            $weightByTariffCodes += $a->weight;
            $netWeightByTariffCodes += $a->netWeight;
            $tareWeightByTariffCodes += $a->tareWeight;
            trans_Helper::sumTransUnits($transUnitsByTariffCodes, $a->transUnits);
        });

        $warnings = $forceArr = array();

        if(!empty($data->masterData->rec->weight)){
            $weightByTariffCodesDiff = abs(round($weightByTariffCodes, 2) - round($data->masterData->rec->weight, 2));
            if($weightByTariffCodesDiff > 0.05){
                $forceArr['forceWeight'] = $weightByTariffCodes;
                $weightByTariffCodesVerbal = core_Type::getByName('cat_type_Weight')->toVerbal($weightByTariffCodes);
                $warnings[] = tr("Общото бруто по документа е различно от сбора по МТК|*: <b>{$weightByTariffCodesVerbal}</b>");
            }
        }

        if(!empty($data->masterData->rec->netWeight)){
            $netWeightByTariffCodesDiff = abs(round($netWeightByTariffCodes, 2) - round($data->masterData->rec->netWeight, 2));
            if($netWeightByTariffCodesDiff > 0.05){
                $forceArr['forceNetWeight'] = $netWeightByTariffCodes;
                $netWeightByTariffCodesVerbal = core_Type::getByName('cat_type_Weight')->toVerbal($netWeightByTariffCodes);
                $warnings[] = tr("Общото нето по документа е различно от сбора по МТК|*: <b>{$netWeightByTariffCodesVerbal}</b>");
            }
        }

        if(!empty($data->masterData->rec->tareWeight) && $data->totalTareInPackListWithTariffCodeVal == 'yes'){
            $tareWeightByTariffCodesDiff = abs(round($tareWeightByTariffCodes, 2) - round($data->masterData->rec->tareWeight, 2));
            if($tareWeightByTariffCodesDiff > 0.05){
                $forceArr['forceTareWeight'] = $tareWeightByTariffCodes;
                $tareWeightByTariffCodesVerbal = core_Type::getByName('cat_type_Weight')->toVerbal($tareWeightByTariffCodes);
                $warnings[] = tr("Общата тара по документа е различна от сбора по МТК|*: <b>{$tareWeightByTariffCodesVerbal}</b>");
            }
        }

        $checkTransUnits = !empty($data->masterData->rec->transUnitsInput) ? $data->masterData->rec->transUnitsInput : $data->masterData->rec->transUnitsCalced;
        if(!empty($checkTransUnits) && !empty($transUnitsByTariffCodes)){
            $transUnitsByTariffCodesVerbal = trans_Helper::displayTransUnits($transUnitsByTariffCodes);
            $checkTransUnitsVerbal = trans_Helper::displayTransUnits($checkTransUnits);
            if($transUnitsByTariffCodesVerbal != $checkTransUnitsVerbal){
                $forceArr['forceTransUnits'] = $transUnitsByTariffCodes;
                $warnings[] = tr("Общо ЛЕ по документа са различни от сбора им по МТК|*: <b>{$transUnitsByTariffCodesVerbal}</b>");
            }
        }

        if(countR($warnings)){
            $blockTpl = new core_ET("<div class='invoiceNoteWarning' style='margin-bottom: 5px;margin-bottom: 5px;'>[#warnings#]<br>[#btnTransfer#]</div>");
            $blockTpl->append(implode('<br>', $warnings), 'warnings');

            if($detail->Master->haveRightFor('changeline', $data->masterId)){
                $changeLineUrl = array($detail->Master, 'changeline', $data->masterId, 'ret_url' => true) + $forceArr;
                $btnTransfer = ht::createBtn('Отразяване в общо за документа', $changeLineUrl, false, false, 'ef_icon=img/16/arrow_refresh.png,title=Отразяване на сумарните данни по МТК в общото за документа, style=margin-top:10px;');
                $blockTpl->append($btnTransfer, 'btnTransfer');
            }

            $tpl->prepend($blockTpl);
        }
    }
}
