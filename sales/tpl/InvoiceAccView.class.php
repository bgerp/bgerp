<?php


/**
 * Обработвач на шаблона за счетоводна фактура
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обработвач на шаблона за счетоводна фактура
 */
class sales_tpl_InvoiceAccView extends doc_TplScript
{
    /**
     * Към шаблоните на кой документ да може да се избира
     */
    public $addToClassTemplate = 'sales_Invoices';


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
        if (!countR($data->rows)) return;
        $exportParamId = acc_Setup::get('INVOICE_MANDATORY_EXPORT_PARAM');
        if (!$exportParamId) return;

        $exportParamName = cat_Params::getTitleById($exportParamId);
        foreach ($data->rows as $id => $row) {
            $rec = $data->recs[$id];

            // Ако има кеширан параметър показва се неговата стойност
            if(isset($rec->exportParamValue)){
                $displayValue = cat_Params::toVerbal($exportParamId, 'cat_Products', $rec->productId, $rec->exportParamValue);
            } else {

                // Ако няма прави се опит да се извлече лайв
                $displayValue = cat_Products::getParams($rec->productId, $exportParamId, true);

                // Ако има стойност, украсява се да си личи, че е лайв
                if(isset($displayValue) && $displayValue != ''){
                    $displayValue = "<span style='color:blue'>{$displayValue}</span>";
                    if($data->masterData->rec->state == 'draft'){
                        $displayValue = ht::createHint($displayValue, 'Текущата стойност ще се запише към момента на активиране');
                    }
                }
            }

            // Ако има стойност показва се на мястото на артикула
            if(isset($displayValue) && $displayValue != ''){
                $oldProductId = (is_object($row->productId)) ? $row->productId->getContent() : $row->productId;
                $displayValue = (is_object($displayValue)) ? $displayValue->getContent() : $displayValue;
                $oldProductId = strip_tags($oldProductId);
                $row->productId = (!Mode::isReadOnly()) ? ht::createLinkRef($displayValue, cat_Products::getSingleUrlArray($rec->productId)) : strip_tags($displayValue);
                $row->productId .= "<div class='small'>{$oldProductId}</div>";
            } elseif (!Mode::isReadOnly()) {
                $row->productId = ht::createHint($row->productId, "Артикулът няма парамертър|* '{$exportParamName}'", 'warning', false);
            }
        }
    }
}
