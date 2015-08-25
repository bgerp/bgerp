<?php


/**
 * Клас 'tasks_TaskDetails'
 *
 * Детайли на задачите за производство
 *
 * @category  bgerp
 * @package   tasks
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
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
     * След дефиниране на полетата на модела
     */
    public static function on_AfterDescription(&$mvc)
    {
    	// Поле в което драйвера на мастъра ще записва данни
    	$mvc->FLD('data', "blob(1000000, serialize, compress)", "caption=Данни,input=none,column=none,single=none,forceField");
    	
    	if($mvc->getField('state', TRUE)){
    		$mvc->setFieldType('state', 'enum(active=Активирано,rejected=Оттеглено)');
    		$mvc->setField('state', 'value=active');
    	} else {
    		$mvc->FLD('state','enum(active=Активирано,rejected=Оттеглено)','caption=Състояние,column=none,input=none,notNull,value=active,forceField');
    	}
    }
    
    
    /**
     * Подготвя формата за редактиране
     */
    function prepareEditForm_($data)
    {
    	parent::prepareEditForm_($data);
    	
    	// Драйвера добавя полета към формата на детайла
    	if($Driver = $this->Master->getDriver($data->form->rec->{$this->masterKey})){
    		$Driver->addDetailFields($data->form);
    	}
    	
    	return $data;
    }
    
    
   /**
    * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
    */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'reject' || $action == 'restore') && isset($rec->{$mvc->masterKey})){
    		
    		// Може да се модифицират детайлите само ако състоянието е чакащо, активно или събудено
    		$state = $mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state');
    		if($state != 'active' && $state != 'pending' && $state != 'wakeup'){
    			$requiredRoles = 'no_one';
    		} else {
    			
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
    
    
    /**
     * Преди извличане на записите от БД
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	// Искаме да показваме и оттеглените детайли
    	$data->query->orWhere("#state = 'rejected'");
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
    	// Добавяме полетата от драйвера в списъка с полета за показване
    	if($Driver = $mvc->Master->getDriver($data->masterId)){
    		$fieldset = cls::get('core_Fieldset');
    		$Driver->addDetailFields($fieldset);
    		
    		foreach ($fieldset->fields as $name => $fld){
    			$data->listFields[$name] = $fld->caption;
    		}
    	}
    }
    
    
    /**
     * Добавяме полетата от драйвера, ако са указани
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
    	$me = cls::get(get_called_class());
    	$row = parent::recToVerbal_($rec, $fields);
    
    	// Разпънатите данни от драйвера ги вербализираме
    	$Driver = $me->Master->getDriver($rec->{$me->masterKey});
    	
    	if(is_array($fields) && $Driver){
    		$fieldset = cls::get('core_Fieldset');
    		$Driver->addDetailFields($fieldset);
    		
    		foreach($fieldset->fields as $name => $field) {
    			if(!isset($row->{$name}) && $fields[$name] && isset($rec->{$name})) {
    				$row->{$name} = $field->type->toVerbal($rec->{$name});
    			}
    		}
    	}
    	
    	return $row;
    }
    
    
    /**
     * Изпълнява се след извличане на запис чрез ->fetch()
     */
    public static function on_AfterRead($mvc, $rec)
    {
    	// Разпъваме данните от драйвера
    	if(isset($rec->{$mvc->masterKey})){
    		if($Driver = $mvc->Master->getDriver($rec->{$mvc->masterKey})){
    			$driverRec = $rec->data;
    		
    			if(is_array($driverRec)) {
    				foreach($driverRec as $field => $value) {
    					$rec->{$field} = $value;
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Преди запис в модела, компактираме полетата
     */
    public function save_(&$rec, $fields = NULL, $mode = NULL)
    {
    	// Компресираме данните от драйвера
    	if($Driver = $this->Master->getDriver($rec->{$this->masterKey})){
    		$form = cls::get('core_FieldSet');
    		$Driver->addDetailFields($form);
    		$addFields = $form->selectFields();
    		$driverRec = array();
    		
    		if(count($addFields)){
    			foreach ($addFields as $name => $caption){
    				$driverRec[$name] = $rec->{$name};
    			}
    		}
    		
    		$rec->data = $driverRec;
    	}
    	
    	return parent::save_($rec, $fields, $mode);
    }
    
    
    /**
     * Подменяне на входния метод за генериране на събития
     */
    function invoke($event, $args = array())
    {
    	$status = parent::invoke($event, $args);
    	
    	if($status !== FALSE) {
    		$masterId = NULL;
    		
    		// При постъпването на определени събития ще нотофицираме драйвера че са станали
    		switch(strtolower($event)) {
    			case 'afterprepareeditform':
    				$masterId = $args[0]->form->rec->{$this->masterKey};
    				$method = 'prepareeditformdetail';
    				break;
    			case 'afterinputeditform':
    				$masterId = $args[0]->rec->{$this->masterKey};
    				$method = 'inputeditformdetail';
    				break;
    			case 'afterrectoverbal':
    				$masterId = $args[1]->{$this->masterKey};
    				$method = 'rectoverbaldetail';
    				break;
    			case 'afterpreparelisttoolbar':
    				$masterId = $args[0]->masterId;
    				$method = 'preparelisttoolbardetail';
    				break;
    			case 'afterpreparedetail':
    				$masterId = $args[0]->masterId;
    				$method = 'preparedetail';
    				break;
    			case 'afterrenderdetail':
    				$masterId = $args[1]->masterId;
    				$method = 'renderdetail';
    				break;
    			case 'afterrenderdetaillayout':
    				$masterId = $args[1]->masterId;
    				$method = 'renderdetaillayout';
    				break;
    		}
    
    		// Ако е намерен мастър и той има драйвер
    		if(isset($masterId) && $Driver = $this->Master->getDriver($masterId)){
    			
    			// Викаме определения метод, който ще предаде данните за обработка на драйвера
    			call_user_func_array(array($Driver, $method),  $args);
    		}
    	}
    
    	return $status;
    }
}