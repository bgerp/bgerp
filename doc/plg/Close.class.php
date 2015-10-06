<?php


/**
 * Клас 'doc_plg_Close' - Плъгин за затваряне на мениджъри
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_Close extends core_Plugin
{
    
    
	/**
     * Извиква се след описанието на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
    	// Ако липсва, добавяме поле за състояние
    	if (!$mvc->getField('state', FALSE)) {
    		plg_State::setStateField($mvc);
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($mvc->haveRightFor('close', $data->rec)){
    		$singleTitle = mb_strtolower($mvc->singleTitle);
    		
    		if($data->rec->state == 'closed'){
    			$data->toolbar->addBtn("Активиране", array($mvc, 'changeState', $data->rec->id, 'ret_url' => TRUE), "id=btnActivate,row=2,ef_icon = img/16/lightbulb.png,title=Активиранe на {$singleTitle}");
    			$data->toolbar->setWarning('btnActivate', 'Сигурни ли сте, че искате да активирате документа?');
    		
    		} elseif($data->rec->state == 'active'){
    			$data->toolbar->addBtn("Затваряне", array($mvc, 'changeState', $data->rec->id, 'ret_url' => TRUE), "id=btnClose,row=2,ef_icon = img/16/lightbulb_off.png,title=Затваряне на {$singleTitle}");
    			$data->toolbar->setWarning('btnClose', 'Сигурни ли сте, че искате да затворите документа?');
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'close' && isset($rec)){
    		if($rec->threadId){
    			if(!doc_Threads::haveRightFor('single', $rec->threadId)){
    				$res = 'no_one';
    			}
    		} else {
    			if(!$mvc->haveRightFor('single', $rec)){
    				$res = 'no_one';
    			}
    		}
    		
    		if($rec->state == 'draft' || $rec->state == 'rejected'){
    			$res = 'no_one';
    		}
    		
    		if($res != 'no_one'){
    			
    			// Ако мениджъра е корица
    			if(cls::haveInterface('doc_FolderIntf', $mvc)){
    				
    				// И има папка без документи или няма папка, няма смиссъл да се затваря (защото може да се оттегли)
    				if(isset($rec->folderId)){
    					$threadsCount = doc_Folders::fetchField($rec->folderId, 'allThreadsCnt');
    					if($threadsCount == 0){
    						$res = 'no_one';
    					}
    				} else {
    					$res = 'no_one';
    				}
    				
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param string $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
    	if($action != 'changestate') return;
    	
    	$mvc->requireRightFor('close');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $mvc->fetch($id));
    	$mvc->requireRightFor('close', $rec);
    	 
    	$state = ($rec->state == 'closed') ? 'active' : 'closed';
    	$action = ($state == 'closed') ? 'Приключване' : 'Активиране';
    	
    	$rec->exState = $rec->state;
    	$rec->state = $state;
    	
    	$mvc->save($rec, 'state');
    	$mvc->logInfo($action, $rec->id);
    	
    	return Redirect(array($mvc, 'single', $rec->id));
    }
}