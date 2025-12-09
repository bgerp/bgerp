<?php


/**
 * Обработвач на шаблона за ЕН за SAF-T
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обработвач на шаблона за ЕН за SAF-T
 */
class store_tpl_SingleLayoutShipmentOrderSaft extends doc_TplScript
{
    /**
     * Към шаблоните на кой документ да може да се избира
     */
    public $addToClassTemplate = 'store_ShipmentOrders';


    /**
     * Метод който подава данните на детайла на мастъра, за обработка на скрипта
     *
     * @param core_Mvc $detail - Детайл на документа
     * @param stdClass $data   - данни
     *
     * @return void
     */
    /**
     * Непосредствено преди рендирането на лист таблицата
     *
     * @param core_Mvc $detail
     * @param stdClass $data
     */
    public function rightBeforeRenderListTable(core_Mvc $detail, &$data)
    {
        if (!countR($data->rows)) return;
        $tariffCodeId = cat_Params::fetchIdBySysId('customsTariffNumber');
        $countryOfOriginId = cat_Params::fetchIdBySysId('originCountry');

        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];

            // Ще се показват МТК и държавата на произход
            $values = array();
            foreach (array('tariffCode' => $tariffCodeId, 'countryOfOrigin' => $countryOfOriginId) as $fld => $paramId){
                // Ако има въведена стойност е тя, ако няма се търси продуктовия параметър
                $paramValue = !empty($rec->{$fld}) ? $rec->{$fld} : cat_Products::getParams($rec->productId, $paramId);
                if(empty($paramValue) && $fld == 'countryOfOrigin'){
                    $paramValue = drdata_Countries::getIdByName('Bulgaria');
                }
                $paramVerbal = cat_Params::toVerbal($paramId, 'cat_Products', $rec->productId, $paramValue);

                // Ако има стойност, украсява се да си личи, че е лайв
                if(isset($paramVerbal) && $paramVerbal != ''){
                    if(!Mode::isReadOnly()){
                        if($data->masterData->rec->state == 'draft'){
                            $paramVerbal = "<span style='color:blue'>{$paramVerbal}</span>";
                            $paramVerbal = ht::createHint($paramVerbal, 'Текущата стойност ще се запише към момента на активиране|*!');
                        }
                    }
                }

                $values[$fld] = $paramVerbal;
            }

            if(!empty($values['tariffCode'])){
                $values['tariffCode'] = "<div class='hsCode'>HS Code: {$values['tariffCode']}</div>";
            }

            if(!empty($values['countryOfOrigin'])){
                $values['countryOfOrigin'] = "<div class='hsCode'>Произход: {$values['countryOfOrigin']}</div>";
            }

            foreach ($values as $val){
                if($row->productId instanceof core_ET){
                    $row->productId->append("<div class='small'>{$val}</div>");
                } else {
                    $row->productId .= "<div class='small'>{$val}</div>";
                }
            }
        }
    }
}