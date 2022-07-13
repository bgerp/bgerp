<?php


/**
 * Плъгин за показване на акцизи и такси към изходяща фактура
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
class bgtaxes_plg_SaleInvoice extends core_Plugin
{


    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $rec = &$data->rec;
        $row = &$data->row;

        $isExciseLive = $isProductTaxLive = false;
        $exciseId = cat_Params::fetchIdBySysId('exciseBgn');

        $paramCache = array();
        $exciseAmount = $productTaxAmount = null;

        // Обикалят се детайлите
        $Detail = cls::get($mvc->mainDetail);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#invoiceId = {$rec->id}");
        $isForBg = ($rec->contragentCountryId == drdata_Countries::getIdByName('Bulgaria'));

        while($dRec = $dQuery->fetch()){
            if($rec->state == 'draft'){
                if(!array_key_exists($dRec->productId, $paramCache)){
                    $paramCache[$dRec->productId] = cat_Products::getParams($dRec->productId);
                }

                $excise = isset($dRec->exciseTax) ? $dRec->exciseTax : $paramCache[$dRec->productId][$exciseId];
                $isExciseLive = true;

                if($isForBg){
                    $productTax = isset($dRec->productTax) ? $dRec->productTax : bgtaxes_ProductTaxes::calcTax($dRec->productId, $rec->date, $paramCache[$dRec->productId]);
                    $isProductTaxLive = true;
                }
            } else {
                $excise = $dRec->exciseTax;
                if($isForBg){
                    $productTax = $dRec->productTax;
                }
            }

            if(isset($excise)){
                $exciseAmount += ($dRec->quantity * $dRec->quantityInPack) * $excise;
            }

            if(isset($productTax)){
                $productTaxAmount += ($dRec->quantity * $dRec->quantityInPack) * $productTax;
            }
        }

        // Показване на акциза
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->date);
        if(isset($exciseAmount)){
            $row->exciseCurrencyCode = $baseCurrencyCode;
            $row->totalExciseAmount = core_Type::getByName('double(decimals=2)')->toVerbal($exciseAmount);
            if($isExciseLive){
                $row->totalExciseAmount = ht::createHint("<span style='color:blue'>{$row->totalExciseAmount}</span>", 'Общата сума на акциза, ще се запише при активиране|*!', 'notice', false);
            }
        }

        // Показване на продуктовата такса
        if(isset($productTaxAmount)){
            $row->productTaxCurrencyCode = $baseCurrencyCode;
            $row->totalProductTax = core_Type::getByName('double(decimals=2)')->toVerbal($productTaxAmount);
            if($isProductTaxLive){
                $row->totalProductTax = ht::createHint("<span style='color:blue'>{$row->totalProductTax}</span>", 'Събраната екотакса, ще се запише при активиране|*!', 'notice', false);
            }
        }
    }
}