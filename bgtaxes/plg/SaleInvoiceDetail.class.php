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
        $mvc->FLD('productTax', 'double(min=0)', 'caption=Допълнително->Екотакса,after=discount');
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
            $measureName = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
            $fieldUnit = "|*{$data->masterRec->currencyId}, |за|* 1 {$measureName}";
            $rate = ($data->masterRec->displayRate) ? $data->masterRec->displayRate : $data->masterRec->rate;

            // Показване на подсказка с текущия акциз
            $exciseId = cat_Params::fetchIdBySysId('exciseBgn');
            $params = cat_Products::getParams($rec->productId);
            $form->setField('exciseTax', array('placeholder' => round($params[$exciseId] / $rate, 4), 'unit' => $fieldUnit));

            // Показване на подсказка с текущата продуктова такса
            $productTax = bgtaxes_ProductTaxes::calcTax($rec->productId, $data->masterRec->date, $params);
            $form->setField('productTax', array('placeholder' => round($productTax / $rate, 4), 'unit' => $fieldUnit));

            if(isset($rec->productTax)){
                $rec->productTax = deals_Helper::getDisplayPrice($rec->productTax, 0, $rate, 'no');
            }

            if(isset($rec->exciseTax)){
                $rec->exciseTax = deals_Helper::getDisplayPrice($rec->exciseTax, 0, $rate, 'no');
            }
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
        $rec = &$form->rec;

        if ($form->isSubmitted()) {
            $masterRec = sales_Invoices::fetch($rec->invoiceId);
            $rate = ($masterRec->displayRate) ? $masterRec->displayRate : $masterRec->rate;

            if(isset($rec->productTax)){
                $rec->productTax = deals_Helper::getPurePrice($rec->productTax, 0, $rate, 'no');
            }

            if(isset($rec->exciseTax)){
                $rec->exciseTax = deals_Helper::getPurePrice($rec->exciseTax, 0, $rate, 'no');
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
    public static function on_AfterCalcFieldsOnActivation($mvc, $res, &$rec, $masterRec, $params)
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