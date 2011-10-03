<?php

/**
 * Клас 'plg_Selected' - Избира id на запис от модела
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
class plg_Selected extends core_Plugin
{
    /**
     * Връща id-то, което е в сесия за мениджъра, с който работим
     * 
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @return FALSE
     */
	function on_BeforeGetCurrent($mvc, &$res)
    {
        $res = Mode::get('selectedPlg_' . $mvc->className);
     	
    	return FALSE;
    }
    
    
    /**
     * Слага id-to на даден мениджър в сесия
     * 
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param string $action
     * @return boolean
     */
    function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action == 'setcurrent') {
	        
            $id = Request::get('id'); 
	        
            $mvc->requireRightFor('edit', $mvc->fetch($id));
	        
	        Mode::setPermanent('selectedPlg_' . $mvc->className, $id);
	        
	        $res = new Redirect(array($mvc, 'list'));
	
	        return FALSE;
        }

    }
    
    
    /**
     * Добавя функционално поле 'selected'
     * 
     * @param $mvc
     */    
    function on_AfterPrepareListFields($mvc, $res, $data)
    {
        $data->listFields['selectedPlg'] = "Текущ";
    }    


    /**
     * Слага съдържание на полето 'selected'
     * 
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {    	
        $selectedId = $mvc->getCurrent();
    	
    	if ($rec->id == $selectedId) {
    	   $row->selectedPlg = ht::createElement('img', array('src' => sbf('img/16/accept.png', ''), 'style' => 'margin-left:20px;', 'width' => '22px', 'height' => '22px'));
    	   $row->ROW_ATTR .= ' class="state-active"';
    	} elseif($mvc->haveRightFor('write', $rec)) {
           $row->selectedPlg = ht::createBtn('Избор', array($mvc, 'SetCurrent', $rec->id), NULL, NULL, array('class' => 'btn-select'));    		
    	   $row->ROW_ATTR .= ' class="state-closed"';
    	}
    	
    }
    
}