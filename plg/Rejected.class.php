<?php

/**
 * Клас 'plg_Rejected' - Поддръжка на rejectedOn и rejectedBy
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
class plg_Rejected extends core_Plugin
{
    
    
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$invoker)
    {
        // Добавяне на необходимите полета
        $invoker->FLD('rejectedOn', 'datetime', 'caption=Оттегляне->На,input=none');
        $invoker->FLD('rejectedBy', 'key(mvc=core_Users)', 'caption=Оттегляне->От,input=none');
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$invoker, &$id, &$rec)
    {
        // Записваме полетата, ако запъсът е нов
        if ($rec->id) {
            // Определяме кой е създал продажбата
            if ($rec->rejected === TRUE) {
                $rec->rejectedBy = Users::getCurrent();
                $rec->rejectedOn = dt::verbal2Mysql();
            }
        }
    }
}