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
        $hint = $mvc->hasPlugin('acc_plg_Contable') ? 'Вальорът ще бъде записан при контиране|*!' : 'Вальорът ще бъде записан при активиране|*!';
        $valiorToBe = $mvc->getFieldType($mvc->valiorFld)->toVerbal(dt::today());
        $row->{$mvc->valiorFld} = (isset($rec->{$mvc->valiorFld})) ? $row->{$mvc->valiorFld} : ((Mode::is('printing') || Mode::is('text', 'xhtml')) ? $valiorToBe : ht::createHint("<span style='color:blue'>{$valiorToBe}</span>", $hint));
    }


    /**
     * Изпълнява се преди запис
     */
    public static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $valior = !empty($rec->{$mvc->valiorFld}) ? $rec->{$mvc->valiorFld} : (isset($rec->id) ? $mvc->fetchField($rec->id, $mvc->valiorFld, '*') : null);

        if($rec->state == 'active' && empty($valior)){
            $rec->{$mvc->valiorFld} = dt::today();
        }
    }
}