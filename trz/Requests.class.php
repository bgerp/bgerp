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
    public $loadList = 'plg_RowTools, trz_Wrapper, 
    				 doc_DocumentPlg, acc_plg_DocumentSummary, doc_ActivatePlg,
    				 plg_Printing, doc_plg_BusinessDoc,plg_AutoFilter,doc_SharablePlg,bgerp_plg_Blank';
    
    
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
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'powerUser';
    
    
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
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
  //  var $rowToolsField = 'id';

    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('docType', 'enum(request=Молба за отпуск, order=Заповед за отпуск)', 'caption=Документ, input=none,column=none');
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees,allowEmpty=TRUE)', 'caption=Служител, autoFilter');
    	$this->FLD('leaveFrom', 'date', 'caption=Считано->От, mandatory');
    	$this->FLD('leaveTo', 'date', 'caption=Считано->До, mandatory');
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
	        }else{
        	
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
    	$data->listFilter->fields['paid']->caption = 'Вид'; 
    	
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent,refreshForm');
        $data->listFilter->setDefault('selectedUsers', 'all_users'); 
                 
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields .= ', selectedUsers, personId, paid';
        
        $data->listFilter->input('selectedUsers, personId, paid', 'silent');
        
     	if($data->listFilter->rec->paid) {
    		$data->query->where("#paid = '{$data->listFilter->rec->paid}'");
    	}
    	
        // Филтриране по потребител/и
        if(!$data->listFilter->rec->selectedUsers) {
            $data->listFilter->rec->selectedUsers = '|' . core_Users::getCurrent() . '|';
        }

        if(($data->listFilter->rec->selectedUsers != 'all_users') && (strpos($data->listFilter->rec->selectedUsers, '|-1|') === FALSE)) {
            $data->query->where("'{$data->listFilter->rec->selectedUsers}' LIKE CONCAT('%|', #createdBy, '|%')");
        }
        
    	if($data->listFilter->rec->personId) {
    		$data->query->where("#personId = '{$data->listFilter->rec->personId}'");
    	}
    }

    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$nowYear = dt::mysql2Verbal(dt::now(),'Y');
    	for($i = 0; $i < 5; $i++){
    		$years[] = $nowYear - $i;
    	}
    	$data->form->setSuggestions('useDaysFromYear', $years);
    	$data->form->setDefault('useDaysFromYear', $years[0]);
    	
    	$rec = $data->form->rec;
        if($rec->folderId){
	        $rec->personId = doc_Folders::fetchCoverId($rec->folderId);
	        $data->form->setReadonly('personId');
        }
    }
    
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    {

    	$rec = $form->rec;

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
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId)
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
            $data->toolbar->addBtn('Заповед', array('trz_Orders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), 'ef_icon = img/16/btn-order.png, title=Създаване на заповед за отпуска');
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
            
    		return  Redirect(array('doc_Containers', 'list', 'threadId'=>$rec->threadId));
    	}
    }
    
    
    /**
     * Тестова функция
     */
    public static function act_Test()
    {
    	$p = 1;
    	$a = '2013-05-02';
    	$b = '2013-05-10';
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
	        if($curDate && $curDate >= $fromDate && $curDate <= $toDate && ($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'draft')) {
	            
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
     * посочената нишка
     *
     * @param $threadId int ид на нишката
     */
    public static function canAddToThread($threadId)
    {
        // Добавяме тези документи само в персонални папки
        $threadRec = doc_Threads::fetch($threadId);

        return self::canAddToFolder($threadRec->folderId);
    }


    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка 
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        // Името на класа
    	$coverClassName = strtolower(doc_Folders::fetchCoverClassName($folderId));
    	
    	// Ако не е папка проект или контрагент, не може да се добави
    	if ($coverClassName != 'crm_persons') return FALSE;
    	
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
        
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('crm_PersonAccRegIntf');
    }
    
    
    /**
     * Преди да се подготвят опциите на кориците, ако
     */
    public static function getCoverOptions($coverClass)
    {
    	$groups = array();
    	
    	if($coverClass instanceof crm_Persons){
    		
    		// Искаме да филтрираме само групата "Служители"
    		$sysIdEmployees = crm_Groups::getIdFromSysId('employees');
    		$sysIdManagers  = crm_Groups::getIdFromSysId('managers');
    		$sysIdUsers = crm_Groups::getIdFromSysId('users');
    		
    		$groups = array($sysIdEmployees=>$sysIdEmployees, $sysIdManagers=>$sysIdManagers, $sysIdUsers=>$sysIdUsers);
    		$groupList = keylist::fromArray($groups);
    		
    		$query = $coverClass->getQuery();
    		$query->where("#state != 'rejected'");
    		$query->likeKeylist('groupList', $groupList);
    		
    		while($rec = $query->fetch()){
    			$options[$rec->id] = $coverClass::getTitleById($rec->id);
    		}
    	
    		return $options;
    	}
    }
}
