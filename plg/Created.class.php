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
        $invoker->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване->На, notNull, input=none');
        $invoker->FLD('createdBy', 'key(mvc=core_Users)', 'caption=Създаване->От, notNull, input=none');
    }
    
    
    /**
     *  Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = NULL)
    {
        // Записваме полетата, ако запъсът е нов и дали трябва да има createdOn и createdBy
        if (!$rec->id) {
            if($fields) {
                $fieldsArr = arr::make($fields, TRUE);
                $mustHaveCreatedBy = isset($fieldsArr['createdBy']);
                $mustHaveCreatedOn = isset($fieldsArr['createdOn']);
            } else {
                $mustHaveCreatedBy = TRUE;
                $mustHaveCreatedOn = TRUE;
            }
            
            // Определяме кой е създал продажбата
            if (!isset($rec->createdBy) && $mustHaveCreatedBy) {
                
                $rec->createdBy = Users::getCurrent();
                
                if (!$rec->createdBy) {
                    $rec->createdBy = 0;
                }
            }
            
            // Записваме момента на създаването
            if (!isset($rec->createdOn) && $mustHaveCreatedOn) {
                $rec->createdOn = dt::verbal2Mysql();
            }
        }
    }

    

    /**
     * Изпълнява се след подготовката на ролите, необходимо за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if ($rec->createdBy == -1) {
	    	if (in_array($action, array('edit', 'delete', 'write')) ) {
	            $requiredRoles = 'no_one';
	        }	
    	}
    }
}