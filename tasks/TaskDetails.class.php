<?php


/**
 * Клас 'tasks_TaskDetails'
 *
 * Детайли на задачите за производство
 *
 * @category  bgerp
 * @package   tasks
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class tasks_TaskDetails extends doc_Detail
{
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Прогрес';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_RowNumbering, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created';
    
    
    /**
     * Кой има право да оттегля?
     */
    public $canReject = 'powerUser';
    
    
    /**
     * Кой има право да възстановява?
     */
    public $canRestore = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой е мастър класа
     */
    public function getMasterMvc($rec)
    {
    	$masterMvc = cls::get(tasks_Tasks::fetchField($rec->{$this->masterKey}, 'classId'));
    		
    	return $masterMvc;
    }
    
    
   /**
    * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
    */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	// Ако нямаме права не правим нищо
    	if($requiredRoles == 'no_one') return;
    	
    	if(($action == 'add' || $action == 'reject' || $action == 'restore' || $action == 'edit' || $action == 'delete') && isset($rec->{$mvc->masterKey})){
    		
    		// Ако не може да бъде избран драйвера от потребителя, не може да добавя прогрес
    		if($Driver = $mvc->Master->getDriver($rec->{$mvc->masterKey})){
    			if(!$Driver->canSelectDriver($userId)){
    				$requiredRoles = 'no_one';
    			}
    		} else {
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}