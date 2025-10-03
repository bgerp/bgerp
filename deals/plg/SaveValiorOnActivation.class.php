<?php


/**
 * Плъгин записващ вальора при активиране
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deals_plg_SaveValiorOnActivation extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
        setIfNot($mvc->valiorFld, 'valior');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $valiorToBe = $mvc->getFieldType($mvc->valiorFld)->toVerbal(dt::today());
        $data->form->setField($mvc->valiorFld, "placeholder=|*{$valiorToBe}");
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $valiorToBe = $mvc->getFieldType($mvc->valiorFld)->toVerbal(dt::today());
        $row->{$mvc->valiorFld} = (isset($rec->{$mvc->valiorFld})) ? $row->{$mvc->valiorFld} : ((Mode::is('printing') || Mode::is('text', 'xhtml') || !in_array($rec->state, array('draft', 'pending'))) ? $valiorToBe : ht::createHint("<span style='color:blue'>{$valiorToBe}</span>", 'Вальорът ще бъде записан при контиране|*!'));
    }


    /**
     * След контиране на документа
     *
     * @param accda_Da $mvc
     * @param stdClass $rec
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        if(empty($rec->{$mvc->valiorFld})){
            $rec->{$mvc->valiorFld} = dt::today();
        }
    }
}