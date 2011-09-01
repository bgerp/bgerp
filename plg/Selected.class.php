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
    	if (Mode::get('selectedIds')) {
	        $selectedIds = Mode::get('selectedIds');
	        $invokerClassName = $mvc->className;
	        $selectedIds = Mode::get('selectedIds');
	        
	        if($selectedIds[$invokerClassName]) {
	        	$res =  $selectedIds[$invokerClassName];
	        } else {
	        	$res = NULL;
	        }
    	} else {
    		$res = NULL;
    	}
    	
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
        if (strtolower($action) == 'setcurrent') {
	        $id = Request::get('id'); 
	        $invokerClassName = Request::get('className');
	        
	        $selectedIds[$invokerClassName] = $id;
	        Mode::setPermanent('selectedIds', $selectedIds);
	        
	        $res = new Redirect(array($invokerClassName, 'list'));
	
	        return FALSE;
        }

    }
    
    
    /**
     * Добавя функционално поле 'selected'
     * 
     * @param $mvc
     */    
    function on_AfterDescrition($mvc)
    {
        $this->FNC('selected',    'varchar(255)', 'caption=Избор');
    }    

    
    /**
     * Добавя полето 'selected' във view-то 
     * 
     * @param $mvc
     * @param $data
     */
    function on_BeforePrepareListFields($mvc, &$data)
    {
    	$mvc->listFields .= ', selected=Избор';
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
    	$invokerClassName = $mvc->className;
    	$selectedIds = Mode::get('selectedIds');
    	$selectedId = $selectedIds[$invokerClassName]; 
    	
    	if ($rec->id == $selectedId) {
    	   $row->selected = 'Избран';
    	   $row->ROW_ATTR .= new ET(' style="background-color: #ddffdd;"');
    	} elseif($mvc->haveRightFor('doselect', $rec)) {
    	   $row->selected = Ht::createLink('Избери', array($mvc, 'SetCurrent', $rec->id, 'className' => $invokerClassName));
    	   $row->ROW_ATTR .= new ET(' style="background-color: #ffdddd;"');
    	}
    	
    }
    
}