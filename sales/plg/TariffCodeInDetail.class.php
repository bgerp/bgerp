<?php


/**
 * Плъгин 'sales_plg_TariffCodeInDetail' - плъгин за тарифен код в детайла на фактурите
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sales_plg_TariffCodeInDetail extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('tariffCode', 'varchar', 'caption=Допълнително->Митнически код,input=none');
    }


    /**
     * Кои продуктови параметри да се записват при активиране на фактурата
     *
     * @param $mvc
     * @param $res
     * @param $invoiceRec
     * @return void
     */
    public static function on_AfterGetFieldsToCalcOnActivation($mvc, &$res, $invoiceRec)
    {
        $res[] = 'tariffCode';
    }


    /**
     * Дали да се обнови записа при активиране
     */
    public static function on_AfterCalcFieldsOnActivation($mvc, &$res, &$rec, $masterRec, $params)
    {
        // Ако няма акциз се записва
        if (empty($rec->tariffCode)) {
            $tariffCodeId = cat_Params::fetchIdBySysId('customsTariffNumber');
            if (!empty($tariffCodeId)) {
                $rec->tariffCode = $params[$tariffCodeId];
                $res = true;
            }
        }
    }


    /**
     * След взимане на полетата, които да не се клонират
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $rec
     */
    public static function on_AfterGetFieldsNotToClone($mvc, &$res, $rec)
    {
        $fieldsNotToClone = arr::make($mvc->fieldsNotToClone, true);
        $fieldsNotToClone['tariffCode'] = 'tariffCode';

        if (!is_array($res)) {
            $res = $fieldsNotToClone;
        } else {
            $res += $fieldsNotToClone;
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        if (isset($rec->productId)) {
            $tariffCode = cat_Products::getParams($rec->productId, 'customsTariffNumber');
            $form->setField('tariffCode', "input,placeholder={$tariffCode}");
        }
    }
}
