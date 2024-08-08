<?php
/**
 * Клас 'dec_plg_Source' - плъгин за документи източник на декларация
 *
 * @category  bgerp
 * @package   dec
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class dec_plg_Source extends core_Plugin
{
    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        $mvc->declareInterface('dec_SourceIntf');
    }


    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        if (dec_Declarations::haveRightFor('add', (object)array('originId' => $rec->containerId, 'threadId' => $rec->threadId))) {
            $data->toolbar->addBtn('Декларация', array('dec_Declarations', 'add', 'originId' => $rec->containerId, 'ret_url' => true), 'ef_icon=img/16/declarations.png, row=2, title=Създаване на декларация за съответсвие');
        }
    }


    /**
     * Метод по подразбиране на Помощна ф-я връщаща артикулите за избор в декларацията от източника
     *
     * @see dec_SourceIntf
     * @param stdClass $rec
     * @return void
     */
    public static function on_AfterGetProducts4Declaration($mvc, &$res, $rec)
    {
        if(isset($res)) return;
        if(!isset($mvc->mainDetail)) return;

        $res = array();
        $Detail = cls::get($mvc->mainDetail);
        $dQuery = $Detail->getQuery();
        $dQuery->where("#{$Detail->masterKey} = {$rec->id}");
        while($dRec = $dQuery->fetch()){
            $res[$dRec->{$Detail->productFld}] = (object)array('productId' => $dRec->{$Detail->productFld}, 'batches' => $dRec->batches);
        }

        return $res;
    }
}