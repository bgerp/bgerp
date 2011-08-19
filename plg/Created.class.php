<?php

/**
 * Клас 'plg_Created' - Поддръжка на createdOn и createdBy
 *
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class plg_Created extends core_Plugin
{
    
    
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$invoker)
    {
        // Добавяне на необходимите полета
        $invoker->FLD('createdOn', 'datetime', 'caption=Създаване->На, notNull, input=none');
        $invoker->FLD('createdBy', 'key(mvc=core_Users)', 'caption=Създаване->От, notNull, input=none');
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = NULL)
    {
        // Записваме полетата, ако запъсът е нов
        if (!$rec->id) {
            $fieldsArr = arr::make($fields, TRUE);
            
            // Определяме кой е създал продажбата
            if (!isset($rec->createdBy) || !$fieldsArr['createdBy']) {
                $rec->createdBy = Users::getCurrent();
                
                if (!$rec->createdBy) {
                    $rec->createdBy = 0;
                }
            }
            
            // Записваме момента на създаването
            if (!isset($rec->createdOn) || !$fieldsArr['createdOn']) {
                $rec->createdOn = dt::verbal2Mysql();
            }
        }
    }

    

    /**
     * Изпълнява се след подготовката на ролите, необходимо за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if (isset($rec->createdBy) && !isDebug()) {
	    	if (in_array($action, array('edit', 'delete', 'write')) && $rec->createdBy == -1) {
	            $requiredRoles = 'no_one';
	        }	
    	}
    }
}