<?php

/**
 * Клас 'plg_Modified' - Поддръжка на modifiedOn и modifiedBy
 *
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class plg_Modified extends core_Plugin
{
    
    
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$invoker)
    {
        // Добавяне на необходимите полета
        $invoker->FLD('modifiedOn', 'datetime', 'caption=Модифициране->На,input=none');
        $invoker->FLD('modifiedBy', 'key(mvc=core_Users)', 'caption=Модифициране->От,input=none');
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = NULL)
    {
        $fieldsArr = arr::make($fields, TRUE);
        
        // Определяме кой е модифицирал записа
        if (!isset($rec->modifieddBy) || !$fieldsArr['modifiedBy']) {
            $rec->modifiedBy = Users::getCurrent();
            
            if (!$rec->modifiedBy) {
                $rec->modifiedBy = 0;
            }
        }
        
        // Записваме момента на създаването
        if (!isset($rec->modifiedOn) || !$fieldsArr['modifiedOn']) {
            $rec->modifiedOn = dt::verbal2Mysql();
        }
    }
}