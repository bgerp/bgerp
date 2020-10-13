<?php


/**
 * Плъгин добавящ артикулите от главния детайл на документа към ключовите му думи
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
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
        $rec = $mvc->fetchRec($rec);
        if (!isset($res)) {
            $res = plg_Search::getKeywords($mvc, $rec);
        }
        
        $Products = cls::get('cat_Products');
        $products = array();
        
        // Ако в мастъра има артикул
        if(isset($rec->productId)){
            $products[$rec->productId] = (object)array('productId' => $rec->productId, 'notes' => null);
        }
       
        // Гледа се в детайла на класа (ако има, кои артикули се използват)
        if ($rec->id) {
            if(isset($mvc->mainDetail)){
               
                // Намиране на детайлите на документа
                $Detail = cls::get($mvc->mainDetail);
                $dQuery = $Detail::getQuery();
                $dQuery->where("#{$Detail->masterKey} = '{$rec->id}'");
                
                if ($Detail->getField('state', false)) {
                    $dQuery->where("#state != 'rejected' AND #state != 'closed'");
                }
                
                setIfNot($Detail->productFld, 'productId');
                setIfNot($Detail->notesFld, 'notes');
                $dQuery->where("#{$Detail->productFld} IS NOT NULL");
                
                // Кои полета да се показват
                if ($Detail->getField($Detail->notesFld, false)) {
                    $dQuery->show("{$Detail->notesFld},{$Detail->productFld}");
                } else {
                    $dQuery->show($Detail->productFld);
                }
                
                // За всеки запис
                while ($dRec = $dQuery->fetch()) {
                    $products[$dRec->{$Detail->productFld}] = (object)array('productId' => $dRec->{$Detail->productFld}, 'notes' => $dRec->{$Detail->notesFld});
                }
            }
        }
        
        // Ако има артикули
        if(countR($products)){
            $detailsKeywords = '';
            foreach ($products as $obj){
                
                // Ключовите думи на артикулите се добавят към тези на мастъра
                $pRec = cat_Products::fetch($obj->productId);
                $productSearchKeywords = $Products->getSearchKeywords($pRec);
                $detailsKeywords .= ' ' . $productSearchKeywords;
                
                // Ако има забележки, и те се добавят към ключовите думи
                if (!empty($obj->productId)) {
                    $detailsKeywords .= ' ' . plg_Search::normalizeText($dRec->{$Detail->notesFld});
                }
            }
            
            // Ако има нови ключови думи, добавят се
            if (!empty($detailsKeywords)) {
                $res = ' ' . $res . ' ' . $detailsKeywords;
            }
        }
    }
}
