<?php


/**
 * Клас 'planning_TaskDetails'
 *
 * Детайли на задачите за производство
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_TaskDetails extends doc_Detail
{
    
	
    /**
     * Заглавие
     */
    public $title = 'Детайли на задачите за производство';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Прогрес';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, planning_Wrapper, plg_RowNumbering, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created';
    
    
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'RowNumb=Пулт,code,operation,quantity,weight,employees,fixedAsset,modifiedOn,modifiedBy,message=@';
    

    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'weight,employees,fixedAsset,message';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD("taskId", 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Задача');
    	$this->FLD('code', 'bigint', 'caption=Код,input=none');
    	$this->FLD('operation', 'varchar', 'silent,caption=Операция,input=none,removeAndRefreshForm=code');
    	$this->FLD('quantity', 'double', 'caption=Количество,mandatory');
    	$this->FLD('weight', 'cat_type_Weight', 'caption=Тегло');
    	$this->FLD('employees', 'keylist(mvc=crm_Persons,select=name,makeLinks=short)', 'caption=Работници,tdClass=rightCol');
    	$this->FLD('fixedAsset', 'key(mvc=cat_Products,select=name)', 'caption=Машина,input=none,tdClass=rightCol');
    	$this->FLD('message',    'richtext(rows=2)', 'caption=Съобщение');
    	
    	// Поле в което драйвера на мастъра ще записва данни
    	$this->FLD('data', "blob(1000000, serialize, compress)", "caption=Данни,input=none,column=none,single=none");
    	
    	$this->FLD('state',
    			'enum(active=Активирано,rejected=Оттеглено)',
    			'caption=Състояние,column=none,input=none,notNull,value=active');
    	
    	$this->setDbUnique('code');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	// Драйвера добавя полета към формата на детайла
    	if($Driver = $mvc->Master->getDriver($rec->taskId)){
    		$Driver->addDetailFields($form);
    	}
    	
    	// Оставяме само лицата, които са в група служители
    	$groupId = crm_Groups::fetchField("#sysId = 'employees'", 'id');
    	$employeesArr = cls::get('crm_Persons')->makeArray4Select('name', "#groupList LIKE '%|{$groupId}|%' AND #state != 'rejected'");
    	$form->setSuggestions('employees', $employeesArr);
    	
    	// Добавяме последните данни за дефолтни
    	$query = $mvc->getQuery();
    	$query->where("#taskId = {$rec->taskId}");
    	$query->orderBy('id', 'DESC');
    	
    	if($lastRec = $query->fetch()){
    		$form->setDefault('operation', $lastRec->operation);
    		$form->setDefault('employees', $lastRec->employees);
    		$form->setDefault('fixedAsset', $lastRec->fixedAsset);
    	}
    	
    	// Показваме полето за въвеждане на код само при операция "произвеждане"
    	if($rec->operation == 'production'){
    		$form->setField('code', 'input');
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if($form->isSubmitted()){
    		if($rec->operation == 'production'){
    			if(empty($rec->code)){
    				$rec->code = $mvc->getDefaultCode();
    			}
    		}
    	}
    }
    
    
    /**
     * Връща следващия най-голям свободен код
     * 
     * @return int $code - код
     */
    private function getDefaultCode()
    {
    	$conf = core_Packs::getConfig('planning');
    	
    	// Намираме последния въведен код
    	$query = planning_TaskDetails::getQuery();
    	$query->XPR('maxCode', 'int', 'MAX(#code)');
    	$code = $query->fetch()->maxCode;
    	
    	// Инкрементираме кода, докато достигнем свободен код
    	$code++;
    	while(self::fetch("#code = '{$code}'")){
    		$code++;
    	}
    	
    	return $code;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if($rec->fixedAsset){
    		$row->fixedAsset = cat_Products::getShortHyperlink($rec->fixedAsset);
    		$row->fixedAsset = "<span style='font-size:0.9em'>{$row->fixedAsset}</span>";
    	}
    	
    	if($rec->code){
    		$row->code = "<b>{$row->code}</b>";
    	}
    	
    	$row->ROW_ATTR['class'] .= " state-{$rec->state}";
    	if($rec->state == 'rejected'){
    		$row->ROW_ATTR['title'] = tr('Оттеглено от') . " " . core_Users::getVerbal($rec->modifiedBy, 'nick');
    	}
    }
    
    
   /**
    * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
    */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'reject' || $action == 'restore') && isset($rec->taskId)){
    		
    		// Може да се модифицират детайлите само ако състоянието е чакащо, активно или събудено
    		$state = $mvc->Master->fetchField($rec->taskId, 'state');
    		if($state != 'active' && $state != 'pending' && $state != 'wakeup'){
    			$requiredRoles = 'no_one';
    		} else {
    			
    			// Ако не може да бъде избран драйвера от потребителя, не може да добавя прогрес
    			if($Driver = $mvc->Master->getDriver($rec->taskId)){
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
    	if($Driver = cls::get('planning_Tasks')->getDriver($data->masterId)){
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
    	$row = parent::recToVerbal_($rec, $fields);
    
    	// Разпънатите данни от драйвера ги вербализираме
    	$Driver = cls::get('planning_Tasks')->getDriver($rec->taskId);
    	
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
    	if(isset($rec->taskId)){
    		if($Driver = $mvc->Master->getDriver($rec->taskId)){
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
    	if($Driver = $this->Master->getDriver($rec->taskId)){
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
    				$masterId = $args[0]->form->rec->taskId;
    				$newEvent = 'prepareeditformdetail';
    				break;
    			case 'afterinputeditform':
    				$masterId = $args[0]->rec->taskId;
    				$newEvent = 'inputeditformdetail';
    				break;
    			case 'afterrectoverbal':
    				$masterId = $args[1]->taskId;
    				$newEvent = 'rectoverbaldetail';
    				break;
    			case 'afterpreparelisttoolbar':
    				$masterId = $args[0]->masterId;
    				$newEvent = 'preparelisttoolbardetail';
    				break;
    			case 'afterpreparedetail':
    				$masterId = $args[0]->masterId;
    				$newEvent = 'preparedetail';
    				break;
    			case 'afterrenderdetail':
    				$masterId = $args[1]->masterId;
    				$newEvent = 'renderdetail';
    				break;
    			case 'afterrenderdetaillayout':
    				$masterId = $args[1]->masterId;
    				$newEvent = 'renderdetaillayout';
    				break;
    		}
    
    		// Ако е намерен мастър и той има драйвер
    		if(isset($masterId) && $Driver = $this->Master->getDriver($masterId)){
    			
    			// Викаме определения метод, който ще предаде данните за обработка на драйвера
    			call_user_func_array(array($Driver, $newEvent),  $args);
    		}
    	}
    
    	return $status;
    }
}