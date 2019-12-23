<?php


/**
 * Клас 'store_plg_Requests' за записване на заявените количества
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_plg_Request extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->storeFieldName, 'storeId');
    }
    
    
    /**
     * След подготовка на тулбара за единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (!$mvc->hasPlugin('deals_plg_EditClonedDetails')) {
            
            return;
        }
        
        // Подмяна на бутона за клониране към такъв с остатък
        $rec = $data->rec;
        if ($data->toolbar->haveButton("clone{$rec->containerId}") && $rec->state == 'active') {
            $Detail = cls::get($mvc->mainDetail);
            $undelivered = $Detail->getUndeliveredDetails($rec->id);
            
            if (count($undelivered)) {
                core_Request::setProtected('showDiff');
                $data->toolbar->addBtn('Остатък', toUrl(array($mvc, 'cloneFields', $data->rec->id, 'showDiff' => true,'ret_url' => array($mvc, 'single', $data->rec->id))), 'ef_icon=img/16/clone.png,title=Остатък от заявеното,row=1, order=19.1');
                core_Request::removeProtected('showDiff');
            }
        }
    }
    
    
    /**
     * Кои детайли да се клонират с промяна
     *
     * @param stdClass $rec
     * @param mixed    $Detail
     *
     * @return array
     */
    public static function on_BeforeGetDetailsToCloneAndChange($mvc, &$res, $rec, &$Detail = null)
    {
        if (!$rec->clonedFromId) {
            
            return;
        }
        if ($rec->state != 'active') {
            
            return;
        }
        core_Request::setProtected('showDiff');
        $showDiff = Request::get('showDiff', 'int');
        if (empty($showDiff)) {
            
            return;
        }
        
        $Detail = cls::get($mvc->mainDetail);
        $arr = $Detail->getUndeliveredDetails($rec->clonedFromId);
        
        if (count($arr)) {
            $res = $arr;
        }
    }
}
