<?php


/**
 * Клас закачащ се към шаблона за Packaging List за митница
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_tpl_SingleLayoutPackagingListGrouped extends doc_TplScript
{
    
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
        if(!count($data->recs)) {
            
            return;
        }
        
        // Извлича се тарифния номер на артикулите
        $length = store_Setup::get('TARIFF_NUMBER_LENGTH');
        foreach ($data->rows as $id => &$row){
            $rec = $data->recs[$id];
            
            $tariffNumber = cat_Products::getParams($rec->productId, 'customsTariffNumber', true);
            $tariffNumber = !empty($tariffNumber) ? substr($tariffNumber, 0, $length) : self::EMPTY_TARIFF_NUMBER;
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
        if(!count($data->recs)) {
            
            return;
        }
        $columns = count($data->listFields);
        
        // Извличане на всички уникални тарифни номера и сумиране на данните им
        $tarriffCodes = array();
        foreach ($data->recs as $rec1) {
            if(!array_key_exists($rec1->tariffNumber, $tarriffCodes)){
                $tarriffCodes[$rec1->tariffNumber] = (object)array('code' => $rec1->tariffNumber, 'weight' => null, 'transUnits' => array(), 'withoutWeightProducts' => array());
            }
            
            $transUnitId = (!empty($rec1->transUnitId)) ? $rec1->transUnitId : trans_TransportUnits::fetchIdByName('load');
            $transUnitQuantity = (isset($rec1->transUnitQuantity)) ? $rec1->transUnitQuantity : 1;
            if(!empty($transUnitQuantity)){
                $tarriffCodes[$rec1->tariffNumber]->transUnits[$transUnitId] += $transUnitQuantity;
            }
            
            $weight = $detail->getWeight($rec1->productId, $rec1->packagingId, $rec1->quantity, $rec1->weight);
            
            if(empty($weight)){
                $tarriffCodes[$rec1->tariffNumber]->withoutWeightProducts[] = cat_Products::getTitleById($rec1->productId);
            }
            $tarriffCodes[$rec1->tariffNumber]->weight += $weight;
        }
        
        ksort($tarriffCodes, SORT_STRING);
        $rows = array();
        
        // За всяко поле за групиране
        foreach ($tarriffCodes as $tariffNumber => $tariffObject) {
            
            $weight = core_Type::getByName('cat_type_Weight(decimals=2)')->toVerbal($tariffObject->weight);
            if(count($tariffObject->withoutWeightProducts) && !Mode::isReadOnly()){
                $imploded = implode(',', $tariffObject->withoutWeightProducts);
                $weight = ht::createHint($weight, "Следните артикули нямат транспортно тегло|*: {$imploded}", 'warning');
            }
            
            $code = ($tariffNumber != self::EMPTY_TARIFF_NUMBER) ? "HS Code / CTN {$tariffObject->code}" : tr('Без тарифен код');
            $transUnits = trans_Helper::displayTransUnits($tariffObject->transUnits);
            $groupVerbal = tr("|*<b>{$code}</b>, |Бруто|*: {$weight}, {$transUnits}");
            
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