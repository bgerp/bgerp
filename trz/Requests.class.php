<?php



/**
 * Мениджър на отпуски
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Молби за отпуски
 */
class trz_Requests extends core_Master
{
    
	
	/**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    /**
     * Заглавие
     */
    public $title = 'Молби';
    
     /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Молба за отпуск";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trz_Wrapper, doc_plg_TransferDoc,
    				 doc_DocumentPlg, acc_plg_DocumentSummary, doc_ActivatePlg,
    				 plg_Printing,doc_SharablePlg,bgerp_plg_Blank';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,personId, leaveFrom, leaveTo, note, useDaysFromYear, paid';
    
    
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateFrom = 'leaveFrom';
    public $filterFieldDateTo = 'leaveTo';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'personId';
    
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'powerUser';

    
    /**
     * Икона за единичния изглед
     */
    //var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/leaves.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'trz/tpl/SingleLayoutRequests.shtml';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Req";
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "5.2|Човешки ресурси"; 

    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'powerUser';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * По кое поле ще се премества документа
     */
    public $transferFolderField = 'personId';
    
    static public $map = array('paid' => 'платен', 'unpaid' => 'неплатен');
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('docType', 'enum(request=Молба за отпуск, order=Заповед за отпуск)', 'caption=Документ, input=none,column=none');
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител');
    	$this->FLD('leaveFrom', 'datetime', 'caption=Считано->От, mandatory');
    	$this->FLD('leaveTo', 'datetime(defaultTime=23:59:59)', 'caption=Считано->До, mandatory');
    	$this->FLD('leaveDays', 'int', 'caption=Считано->Дни, input=none');
    	$this->FLD('useDaysFromYear', 'int', 'caption=Информация->Ползване от,unit=година');
    	$this->FLD('paid', 'enum(paid=платен, unpaid=неплатен)', 'caption=Информация->Вид, maxRadio=2,columns=2,notNull,value=paid');
    	$this->FLD('note', 'richtext(rows=5, bucket=Notes, shareUsersRoles=trz|ceo)', 'caption=Информация->Бележки');
    	$this->FLD('answerGSM', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Отговаря на моб. телефон, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('answerSystem', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Достъп до системата, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('alternatePerson', 'key(mvc=crm_Persons,select=name,group=employees, allowEmpty=true)', 'caption=По време на отсъствието->Заместник');
    	
    	// Споделени потребители
        $this->FLD('sharedUsers', 'userList(roles=trz|ceo)', 'caption=Споделяне->Потребители');
    }

    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_BeforeSave($mvc, &$id, $rec)
    {
        if($rec->leaveFrom &&  $rec->leaveTo){
        	
        	$state = hr_EmployeeContracts::getQuery();
	        $state->where("#personId='{$rec->personId}'");
	        
	        if($employeeContractDetails = $state->fetch()){
	           
	        	$employeeContract = $employeeContractDetails->id;
	        	$department = $employeeContractDetails->departmentId;
	        	
	        	$schedule = hr_EmployeeContracts::getWorkingSchedule($employeeContract);
	        	if($schedule == FALSE){ 
	        		$days = hr_WorkingCycles::calcLeaveDaysBySchedule($schedule, $department, $rec->leaveFrom, $rec->leaveTo);
	        	} else {
	        		$days = cal_Calendar::calcLeaveDays($rec->leaveFrom, $rec->leaveTo);
	        	}
	        } else {
        	
	    		$days = cal_Calendar::calcLeaveDays($rec->leaveFrom, $rec->leaveTo);
	        }
	    	$rec->leaveDays = $days->workDays;
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
    	$mvc->updateRequestsToCalendar($rec->id);
    }
 
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->listFilter->FLD('employeeId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител,silent,before=paid');
    	$data->listFilter->showFields = $data->listFilter->showFields . ',employeeId';
    	$data->listFilter->input('employeeId', 'silent');
    	
    	$data->listFilter->fields['paid']->caption = 'Вид'; 

        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields .= ', employeeId, paid';
        
        $data->listFilter->input('employeeId, paid', 'silent');
        
     	if($data->listFilter->rec->paid) {
    		$data->query->where("#paid = '{$data->listFilter->rec->paid}'");
    	}

    	if($data->listFilter->rec->employeeId) {
    		$data->query->where("#personId = '{$data->listFilter->rec->employeeId}'");
    	}
    }

    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$nowYear = dt::mysql2Verbal(dt::now(),'Y');
    	for($i = 0; $i < 5; $i++){
    		$years[$nowYear - $i] = $nowYear - $i;
    	} 
    	$form->setSuggestions('useDaysFromYear', $years);
    	$form->setDefault('useDaysFromYear', $years[$nowYear]);
    	

    	// Намират се всички служители
    	$employees = crm_Persons::getEmployeesOptions();
    	if(count($employees)){
    		$form->setOptions('personId', crm_Persons::getEmployeesOptions());
    	} else {
    		redirect(array('crm_Persons', 'list'), FALSE, "|Липсва избор за служители|*");
    	}
    	
    	$folderClass = doc_Folders::fetchCoverClassName($rec->folderId);

        if($rec->folderId && $folderClass == 'crm_Persons') {
        	$form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
	        $form->setReadonly('personId');

	        if(!haveRole('ceo,trz,hr')) {
	        	$form->setField('sharedUsers', 'mandatory');
	        }
        }
    }

    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {

        if ($form->isSubmitted()) { 
            // Размяна, ако периодите са объркани
            if(isset($form->rec->leaveFrom) && isset($form->rec->leaveTo) && ($form->rec->leaveFrom > $form->rec->leaveTo)) { 
                $form->setError('startDate, toDate', "Началната дата трябва да е по-малка от крайната");
            }
        }
    }
 
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId = NULL)
    {
    	// Ако се опитваме да направим заповед за отпуска
	    if($action == 'order'){ 
			if ($rec->id) {
				    // и нямаме нужните права
					if(!Users::haveRole('ceo') || !Users::haveRole('trz') ) {
				        // то не може да я направим
						$requiredRoles = 'no_one';
				}
		    }
	    }
     }

    
	/**
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
    	
    	// Ако имаме права да създадем заповед за отпуск
        if(haveRole('trz, ceo') && $data->rec->state == 'active') {
            
        	// Добавяме бутон
            $data->toolbar->addBtn('Заповед', array('trz_Requests', 'Print', 'id' => $data->rec->id, 'Printing' => 'yes'), 'ef_icon = img/16/btn-order.png, title=Създаване на заповед за отпуска');
        }
        
        // Ако нямаме права за писане в треда
    	if(doc_Threads::haveRightFor('single', $data->rec->threadId) == FALSE){
    		
    		// Премахваме бутона за коментар
	    	$data->toolbar->removeBtn('Коментар');
	    }
        
    }
    
    
    /**
     * Извиква се след изпълняването на екшън
     */
    public static function on_AfterAction(&$invoker, &$tpl, $act)
    {
    	if (strtolower($act) == 'single' && haveRole('trz,ceo') && !Mode::is('printing')) {
    		
    		// Взимаме ид-то на молбата
    		$id = Request::get('id', 'int');
    		
    		// намираме, кой е текущия потребител
    		$cu =  core_Users::getCurrent();
    		
    		// взимаме записа от модела
    		$rec = self::fetch($id);
    		
    		// превръщаме кей листа на споделените потребители в масив
    		$sharedUsers = type_Keylist::toArray($rec->sahredUsers);
    		
    		// добавяме текущия потребител
    		$sharedUsers[$cu] = $cu;
    		
    		// връщаме в кей лист масива
    		$rec->sharedUsers =  keylist::fromArray($sharedUsers);
    		    		
    		self::save($rec, 'sharedUsers');
    		
            doc_ThreadUsers::removeContainer($rec->containerId);
            doc_Threads::updateThread($rec->threadId);
            
    		redirect(array('doc_Containers', 'list', 'threadId'=>$rec->threadId));
    	}
    }

    
    /**
     * Обновява информацията за молбите в календара
     */
    public static function updateRequestsToCalendar($id)
    {
        $rec = static::fetch($id);
        
        $events = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);

        // Начална дата
        $fromDate = "{$cYear}-01-01";

        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
        
        // Префикс на ключовете за записите в календара от тази задача
        $prefix = "REQ-{$id}";

        $curDate = $rec->leaveFrom;
    	
    	while($curDate < dt::addDays(1, $rec->leaveTo)){
        // Подготвяме запис за началната дата
	        if($curDate && $curDate >= $fromDate && $curDate <= $toDate && $rec->state == 'active') {
	            
	            $calRec = new stdClass();
	                
	            // Ключ на събитието
	            $calRec->key = $prefix . "-{$curDate}";
	            
	            // Начало на отпуската
	            $calRec->time = $curDate;
	            
	            // Дали е цял ден?
	            $calRec->allDay = 'yes';
	            
	            // Икона на записа
	            $calRec->type  = 'leaves';
	
	            $personName = crm_Persons::fetchField($rec->personId, 'name');
	            // Заглавие за записа в календара
	            $calRec->title = "Отпуск:{$personName}";
	            
	            $personProfile = crm_Profiles::fetch("#personId = '{$rec->personId}'");
	            $personId = array($personProfile->userId => 0);
	            $user = keylist::fromArray($personId);
	
	            // В чии календари да влезе?
	            $calRec->users = $user;
	            
	            // Статус на задачата
	            $calRec->state = $rec->state;
	            
	            // Url на задачата
	            $calRec->url = array('trz_Requests', 'Single', $id); 
	            
	            $events[] = $calRec;
	        }
	        $curDate = dt::addDays(1, $curDate);
    	}

        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
    }

    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка 
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $Cover = doc_Folders::getCover($folderId);
        
        // Трябва да е в папка на лице или на проект
        if ($Cover->className != 'crm_Persons' && $Cover->className != 'doc_UnsortedFolders') return FALSE;
        
        // Ако е в папка на лице, лицето трябва да е в група служители
        if($Cover->className == 'crm_Persons'){
        	$emplGroupId = crm_Groups::getIdFromSysId('employees');
        	$personGroups = $Cover->fetchField('groupList');
        	if(!keylist::isIn($emplGroupId, $personGroups)) return FALSE;
        }
        
        if($Cover->className == 'doc_UnsortedFolders') {
            $cu = core_Users::getCurrent();
            if(!haveRole('ceo,trz', $cu)) return FALSE;
        }
        
        return TRUE;
    }

    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = "Молба за отпуск  №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $this->getRecTitle($rec, FALSE);
        
        return $row;
    }

    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
        $me = cls::get(get_called_class());
         
        $title = tr('Молба за отпуска  №|*'. $rec->id . ' на|* ') . $me->getVerbal($rec, 'personId');
         
        return $title;
    }

    
    /**
     * Разпечатва заповед
     */
    public function act_Print()
    {
        $id = Request::get('id');
        $recs = array();
        $recs[] = self::fetch($id);

        $tpl = self::printOrder($recs);

        return  $this->renderWrapping($tpl);
    }
    
    
    /**
     * Подготвя заповед за разпечатване
     *
     * @param array $res - Масив от записи за показване
     * @return core_ET $tpl - Шаблон на обобщението
     */
    private static function printOrder($res)
    {
        // Зареждаме и подготвяме шаблона
        $tpl = new ET(tr('|*' . getFileContent("trz/tpl/SingleLayoutOrders.shtml")));
        
        $Int = cls::get('type_Int');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $Text = cls::get(type_Text);
        $Date = cls::get(type_Date); 
        $Datetime = cls::get(type_Datetime);
        $Datetime->params['defaultTime'] = "23:59:59";
        $me = cls::get(get_called_class());
        
        if(count($res)) {
            foreach($res as $rec) { 
                $row = new stdClass();
                $row->id = $Int->toVerbal($rec->id);
                $row->createdDate =  $Date->toVerbal($rec->createdOn);
                $row->isPaid = static::$map[$rec->isPaid];
                $row->personId = $me->getVerbal($rec, 'personId');
                $row->leaveDays = $Int->toVerbal($rec->leaveDays);
                $row->useDaysFromYear = $Int->toVerbal($rec->useDaysFromYear);
                $row->leaveFrom = $Datetime->toVerbal($rec->leaveFrom);
                $row->leaveTo =  $Datetime->toVerbal($rec->leaveTo);
                $row->amount = $Double->toVerbal($rec->amount);
                $row->note = $Text->toVerbal($rec->note);
                $row->baseCurrencyId = $rec->baseCurrencyId;
            }

            $tpl->placeObject($row);
            $tpl->removeBlocks();
            $tpl->append2master();
        } 

        return $tpl;
    }
}