<?php


/**
 * Плъгин добавящ артикулите от главния детайл на документа към ключовите му думи
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cat_plg_AddSearchKeywords extends core_Plugin
{
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        if ($rec->id) {
            $detailsKeywords = '';
            $Products = cls::get('cat_Products');
            
            // Намиране на детайлите на документа
            $Detail = cls::get($mvc->mainDetail);
            $dQuery = $Detail::getQuery();
            $dQuery->where("#{$Detail->masterKey} = '{$rec->id}'");
            setIfNot($Detail->productFld, 'productId');
            setIfNot($Detail->notesFld, 'notes');
            
            // Кои полета да се показват
            if ($Detail->getField($Detail->notesFld, false)) {
                $dQuery->show("{$Detail->notesFld},{$Detail->productFld}");
            } else {
                $dQuery->show($Detail->productFld);
            }
            
            // За всеки запис
            while ($dRec = $dQuery->fetch()) {
                if(!empty($dRec->{$Detail->productFld})){
                    
                    // Ключовите думи на артикулите се добавят към тези от продажбата
                    $pRec = cat_Products::fetch($dRec->{$Detail->productFld});
                    $productSearchKeywords = $Products->getSearchKeywords($pRec);
                    $detailsKeywords .= ' ' . $productSearchKeywords;
                    
                    // Ако има забележки, и те се добавят към ключовите думи
                    if (!empty($dRec->{$Detail->notesFld})) {
                        $detailsKeywords .= ' ' . plg_Search::normalizeText($dRec->{$Detail->notesFld});
                    }
                }
            }
            
            // Ако има нови ключови думи, добавят се
            if (!empty($detailsKeywords)) {
                $res = ' ' . $res . ' ' . $detailsKeywords;
            }
        }
    }
}
