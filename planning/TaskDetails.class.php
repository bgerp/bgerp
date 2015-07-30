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
     * Кой има право да чете?
     */
    public $canRead = 'ceo, planning';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planning';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'RowNumb=Пулт,code,operation,quantity,weight,employees,fixedAsset,modifiedOn,modifiedBy,message=@';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Задачи';

    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'weight,employees,fixedAsset,message';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD("taskId", 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Задача');
    	$this->FLD('operation', 'enum(start=Пускане,production=Произвеждане,waste=Отпадък,scrap=Бракуване,stop=Спиране)', 'silent,caption=Операция,mandatory,removeAndRefreshForm=code');
    	$this->FLD('code', 'varchar(16)', 'caption=Код,input=none');
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
    	if($Driver = planning_Tasks::getDriver($rec->taskId)){
    		$Driver->addDetailFields($form);
    	}
    	
    	// Оставяме само лицата, които са в група служители
    	$groupId = crm_Groups::fetchField("#sysId = 'employees'", 'id');
    	$employeesArr = cls::get('crm_Persons')->makeArray4Select('name', "#groupList LIKE '%|{$groupId}|%' AND #state != 'rejected'");
    	$form->setSuggestions('employees', $employeesArr);
    	
    	// Добавяме последните данни за дефолтни
    	if($lastRec = $mvc->fetch("#taskId = {$rec->taskId}")){
    		$form->setDefault('operation', $lastRec->operation);
    		$form->setDefault('employees', $lastRec->employees);
    		$form->setDefault('fixedAsset', $lastRec->fixedAsset);
    	}
    	
    	if($rec->operation == 'production'){
    		$form->setField('code', 'input');
    		//bp($rec);
    	} else {
    		//bp($rec);
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	// Подаваме формата на драйвера да я провери ако иска
    	if($Driver = planning_Tasks::getDriver($rec->taskId)){
    		$Driver->checkDetailForm($form);
    	}
    	
    	if($form->isSubmitted()){
    	
    		if(isset($rec->taskId)){
    	
    			// Опитваме се да намерим кои полета са дошли от драйвера
    			$formBefore = $mvc->getForm();
    			$fieldsBefore = arr::make(array_keys($formBefore->selectFields()), TRUE);
    			$Driver->addDetailFields($formBefore);
    			$fieldsAfter = arr::make(array_keys($formBefore->selectFields()), TRUE);
    			$params = array_diff_assoc($fieldsAfter, $fieldsBefore);
    			
    			// Ако има такива
    			if(count($params)){
    				$rec->data = new stdClass();
    				
    				// Записваме в блоб полето въведените стойностти на полетата добавени от драйвера
    				foreach ($params as $name => $value){
    					$rec->data->{$name} = $rec->{$name};
    				}
    			}
    		}
    		
    		if($rec->operation == 'production'){
    			if(empty($rec->code)){
    				$rec->code = $mvc->getDefaultCode();
    			}
    			
    			if(empty($rec->code) ){
    				$form->setError('code', 'Кода не може да се изчисли динамично. Въведете ръчно');
    			}
    		}
    	}
    }
    
    
    /**
     * Връща дефолтен код
     */
    private function getDefaultCode()
    {
    	$conf = core_Packs::getConfig('planning');
    	
    	$query = planning_TaskDetails::getQuery();
    	$query->where("#code IS NOT NULL");
    	$query->where("#operation = 'production'");
    	$query->show('code');
    	$query->orderBy('id', 'DESC');
    	$code = $query->fetch()->code;
    	
    	$conf->PLANNING_TASK_DETAIL_CODE_MIN += 1;
    	
    	$code = (str::increment($code)) ? str::increment($code) : "{$code}" . $conf->PLANNING_TASK_DETAIL_CODE_MIN;
    	while(self::fetch("#code = '{$code}'")){
    		$code = str::increment($code);
    	}
    	
    	//@TODO да проверява дали числото не е в максимума
    	
    	return $code;
    }
    
    
    /**
     * След подготовката на детайлите
     */
    public static function on_AfterPrepareDetail($mvc, &$res, &$data)
    {
    	if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId))){
    		
    		// Добавяме форма за добавяне на детайли
    		$data->addForm = $mvc->getAddForm($data);
    		
    		// Ако формата е събмитната
    		if($data->addForm->isSubmitted()){
    			$rec = $data->addForm->rec;
    			
    			// Записваме детайла
    			if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId))){
    				$mvc->save($rec);
    			}
    			
    			// Редирект
    			return Redirect(array($mvc->Master, 'single', $data->masterId), 'Записа е добавен успешно;');
    		}
    	}
    	
    	// Даваме възможност на драйвера да промени подготовката ако иска
    	if($Driver = planning_Tasks::getDriver($data->masterId)){
    		$Driver->prepareDetailData($data);
    	}
    }
    
    
    /**
     * Връща форма за добавяне в сингъла под таблицата на детайла
     */
    private function getAddForm($data)
    {
    	$form = $this->getForm();
    	$form->class = 'simpleForm';
    	$form->rec->taskId = $data->masterId;
    	
    	$form->input(NULL, 'silent');
    	$this->invoke('AfterPrepareEditForm', array((object)array('form' => &$form), (object)array('form' => &$form)));
    	$form->input();
    	$this->invoke('AfterInputEditForm', array(&$form));
    	
    	$form->toolbar->addSbBtn('Прогрес', 'save', 'id=save, ef_icon = img/16/progressbar.png', 'title=Добави прогрес');
    	
    	return $form;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    public function renderDetail_($data)
    {
    	// Даваме на драйвра възможността да рендира детайла
    	if($Driver = planning_Tasks::getDriver($data->masterId)){
    		$tpl = $Driver->renderDetailData($data);
    	}
    	
    	// Ако не иска да променя рендирането, правим стандартно
    	if(!isset($tpl)){
    		$tpl = parent::renderDetail_($data);
    	}
    	
    	if(isset($data->addForm)){
    		$tpl->replace($data->addForm->renderHtml(), 'ADD_FORM');
    	}
    	
    	// Връщаме рендирания детайл
    	return $tpl;
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
    	if(($action == 'add' || $action == 'reject') && isset($rec->taskId)){
    		
    		// Ако мастъра не е чернова не може детайлите му да се модифицират
    		$state = $mvc->Master->fetchField($rec->taskId, 'state');
    		if($state != 'active' && $state != 'pending'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$data->query->orWhere("#state = 'rejected'");
    }
}