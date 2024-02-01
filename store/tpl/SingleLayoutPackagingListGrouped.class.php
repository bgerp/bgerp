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
    const EMPTY_TARIFF_NUMBER = ' ';
    
    
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
        $getLiveTariffCode = ($data->masterData->rec->state == 'draft');

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
        foreach ($data->recs as $rec1) {
            if(!array_key_exists($rec1->tariffNumber, $tariffCodes)){
                $tariffCodes[$rec1->tariffNumber] = (object)array('code' => $rec1->tariffNumber, 'weight' => null, 'netWeight' => null, 'transUnits' => array(), 'withoutWeightProducts' => array());
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
            if(empty($weight)){
                $tariffCodes[$rec1->tariffNumber]->withoutWeightProducts[] = cat_Products::getTitleById($rec1->productId);
            }


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

        // За всяко поле за групиране
        foreach ($tariffCodes as $tariffNumber => $tariffObject) {
            $weight = core_Type::getByName('cat_type_Weight(decimals=2)')->toVerbal($tariffObject->weight);
            $netWeight = core_Type::getByName('cat_type_Weight(decimals=2)')->toVerbal($tariffObject->netWeight);

            if(countR($tariffObject->withoutWeightProducts) && !Mode::isReadOnly()){
                $imploded = implode(',', $tariffObject->withoutWeightProducts);
                $weight = ht::createHint($weight, "Следните артикули нямат транспортно тегло|*: {$imploded}", 'warning');
            }

            if($tariffNumber != self::EMPTY_TARIFF_NUMBER){
                $code = "HS Code / CTN {$tariffObject->code}";
                $tariffDescription = cond_TariffCodes::getDescriptionByCode($tariffObject->code, $masterRec->tplLang);
            } else {
                $code = tr('Без тарифен код');
                $tariffDescription = null;
            }

            $transUnits = trans_Helper::displayTransUnits($tariffObject->transUnits);
            $groupBlock = getTplFromFile('store/tpl/HScodeBlock.shtml');
            $groupBlock->append($code, 'code');
            $groupBlock->append($weight, 'weight');
            $groupBlock->append($tariffDescription, 'description');
            if($totalTareInPackListWithTariffCodeVal == 'yes'){
                $tareWeight = core_Type::getByName('cat_type_Weight(decimals=2)')->toVerbal($tariffObject->tareWeight);
                $groupBlock->append($tareWeight, 'tareWeight');
            }

            $groupBlock->append($netWeight, 'netWeight');
            $groupBlock->append($transUnits, 'transUnits');
            if($totalInPackListWithTariffCodeVal == 'yes'){
                $groupAmount = core_Type::getByName('double(decimals=2)')->toVerbal($tariffObject->amount);
                $groupAmount .= "<span style='font-weight:normal;'> {$masterRec->currencyId}, " . (($masterRec->chargeVat == 'yes' || $masterRec->chargeVat == 'separate') ? tr('|с ДДС|*') : tr('|без ДДС|*')) . "</span>";
                $groupBlock->append($groupAmount, 'groupAmount');
            }
            $groupVerbal = $groupBlock;
            
            // Създаваме по един ред с името му, разпънат в цялата таблица
            $rowAttr = array('class' => ' group-by-field-row');
            
            $element = ht::createElement('tr', $rowAttr, new ET("<td style='padding-top:9px;padding-left:5px;' colspan='{$columns}'>" . $groupVerbal . '</td>'));
            $rows['|' . $tariffNumber] = $element;
            
            // За всички записи
            foreach ($data->recs as $id => $rec) {
                
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