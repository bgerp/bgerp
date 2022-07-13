<?php


/**
 * Плъгин за показване на такси към редове от изходяща фактура
 *
 *
 * @category  bgerp
 * @package   bgtaxes
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgtaxes_plg_SaleInvoiceDetail extends core_Plugin
{

    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->FLD('productTax', 'double(min=0)', 'caption=Допълнително->Екотакса,after=discount,input=none');
        $mvc->FLD('exciseTax', 'double(min=0)', 'caption=Допълнително->Акциз,after=exciseTax');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        if(isset($rec->productId)){
            $baseCurrencyId = acc_Periods::getBaseCurrencyCode($data->masterRec->date);
            $measureName = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
            $fieldUnit = "|*{$baseCurrencyId}, |за|* 1 {$measureName}";

            // Показване на подсказка с текущия акциз
            $exciseId = cat_Params::fetchIdBySysId('exciseBgn');
            $params = cat_Products::getParams($rec->productId);
            $form->setField('exciseTax', array('placeholder' => $params[$exciseId], 'unit' => $fieldUnit));

            // Показване на подсказка с текущата продуктова такса
            if($data->masterRec->contragentCountryId == drdata_Countries::getIdByName('Bulgaria')){
                $productTax = bgtaxes_ProductTaxes::calcTax($rec->productId, $data->masterRec->date, $params);
                $form->setField('productTax', array('placeholder' => $productTax, 'unit' => $fieldUnit, 'input' => 'input'));
            }
        }
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
        $res[] = 'exciseTax';
        $res[] = 'productTax';
    }


    /**
     * Дали да се обнови записа при активиране
     */
    public static function on_AfterCalcFieldsOnActivation($mvc, &$res, &$rec, $masterRec, $params)
    {
        $exciseId = cat_Params::fetchIdBySysId('exciseBgn');
        if(!isset($rec->exciseTax)){
            $rec->exciseTax = $params[$exciseId];
            $res = true;
        }

        if(!isset($rec->productTax)){
            $rec->productTax = bgtaxes_ProductTaxes::calcTax($rec->productId, $masterRec->date, $params);
            $res = true;
        }
    }
}