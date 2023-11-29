<?php


/**
 * Клас 'n18_plg_SaleDocument' - за добавяне на функционалност от наредба 18 към ПОС бележките към ПКО-та и РКО-та
 *
 *
 * @category  bgplus
 * @package   n18
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class n18_plg_SaleDocument extends core_Plugin
{
    

    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->notesFld, 'notes');
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $rec = &$data->rec;
        $row = &$data->row;
        if (!n18_plg_CashDocument::isApplicable($rec->threadId)) {
            
            return;
        }
        
        // Показване на УНП-то на първия документ в нишката
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        if ($cashReg = n18_Register::getRec($firstDoc->getInstance(), $firstDoc->that)) {
            $urn = n18_Register::getUrlLink($cashReg->urn);
            $row->{$mvc->notesFld} = phptr("|*<div><span class='quiet'>|УНП|*</span>: {$urn}</div>") . $row->{$mvc->notesFld};
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        if(isset($rec->threadId)){
            if(n18_plg_CashDocument::isApplicable($rec->threadId)){
                
                // Добавяне на УНП-то на основния документ
                $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
                if ($urn = n18_Register::getRec($firstDoc->getInstance(), $firstDoc->that)->urn) {
                    $res .= ' ' . plg_Search::normalizeText($urn);
                }
            }
        }
    }
}