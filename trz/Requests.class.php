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
    public $loadList = 'plg_RowTools2, trz_Wrapper, doc_plg_TransferDoc,bgerp_plg_Blank,
    				 doc_DocumentPlg, acc_plg_DocumentSummary, doc_ActivatePlg,
    				 plg_Printing,doc_SharablePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,personId, leaveFrom, leaveTo, note, paid';
    
    
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
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canReject = 'ceo,trz';
	

	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canRestore = 'ceo,trz';
    
    
    /**
     * Кой може да го активира?
     */
    public $canActivate = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'powerUser';

    
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
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител, mandatory');
    	$this->FLD('leaveFrom', 'datetime', 'caption=Считано->От, mandatory');
    	$this->FLD('leaveTo', 'datetime(defaultTime=23:59:59)', 'caption=Считано->До, mandatory');
    	$this->FLD('leaveDays', 'int', 'caption=Считано->Дни, input=none');
    	$this->FLD('useDaysFromYear', 'int', 'caption=Информация->Ползване от,unit=година, input=none');
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
        
    }
    
    
    /**
     * Изпълнява се преди опаковане на съдаржанието от мениджъра
     *
     * @param core_Mvc $mvc
     * @param null|string|core_ET $res
     * @param string|core_ET $tpl
     * @param stdClass $data
     *
     * @return boolean
     */
    protected static function on_BeforeRenderSingleLayout($mvc, &$res, &$tpl = NULL, $data = NULL)
    {
        $curUrl = getCurrentUrl();
        
        if($curUrl['Order'] == 'yes') {
            $mvc->singleLayoutFile = 'trz/tpl/SingleLayoutOrders.shtml';
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
    	$data->listFilter->FLD('employeeId', 'key(mvc=crm_Persons,select=name,allowEmpty,group=employees)', 'caption=Служител,silent,before=paid');
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
    	for($i = 0; $i <= 1; $i++){
    		$years[$nowYear - $i] = $nowYear - $i;
    	} 
    	$form->setSuggestions('useDaysFromYear', $years);
    	//$form->setDefault('useDaysFromYear', $years[$nowYear]);

    	// Намират се всички служители
    	$employees = crm_Persons::getEmployeesOptions();
    	unset($employees[$rec->personId]);
   
    	if(count($employees)){
    		$form->setOptions('personId', $employees);
    		$form->setOptions('alternatePerson', $employees);
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
        $now = dt::now();
        // един месец назад
        $before30Days = dt::addMonths(-1, $now);
        $before30DaysVerbal = dt::mysql2verbal($before30Days,"d.m.Y");

        // една година напред
        $after1year = dt::addMonths(12, $now);
        $after1yearVerbal = dt::mysql2verbal($after1year,"d.m.Y");

        if ($form->isSubmitted()) {
            // Размяна, ако периодите са объркани
            if(isset($form->rec->leaveFrom) && isset($form->rec->leaveTo) && ($form->rec->leaveFrom > $form->rec->leaveTo)) { 
                $form->setError('leaveFrom, leaveTo', "Началната дата трябва да е по-малка от крайната");
            }
            
            if(isset($form->rec->leaveFrom) &&  ($form->rec->leaveFrom < $before30Days)) {
                $form->setError('leaveFrom', "Началната дата трябва да е след {$before30DaysVerbal}г.");
            }
            
            if(isset($form->rec->leaveFrom) && ($form->rec->leaveFrom > $after1year)) {
                $form->setError('leaveFrom', "Началната дата трябва да е преди {$after1yearVerbal}г.");
            }
            
            if(isset($form->rec->leaveTo) && ($form->rec->leaveTo > $after1year)) {
                $form->setError('leaveTo', "Крайната дата трябва да е преди {$after1yearVerbal}г.");
            }
            
            // изисляване на бр дни отпуска
            if($form->rec->leaveFrom &&  $form->rec->leaveTo){
                 
                $state = hr_EmployeeContracts::getQuery();
                $state->where("#personId='{$form->rec->personId}'");
                 
                if($employeeContractDetails = $state->fetch()){
            
                    $employeeContract = $employeeContractDetails->id;
                    $department = $employeeContractDetails->departmentId;
            
                    $schedule = hr_EmployeeContracts::getWorkingSchedule($employeeContract);
                    if($schedule == FALSE){
                        $days = hr_WorkingCycles::calcLeaveDaysBySchedule($schedule, $department, $form->rec->leaveFrom, $form->rec->leaveTo);
                    } else {
                        $days = cal_Calendar::calcLeaveDays($form->rec->leaveFrom, $form->rec->leaveTo);
                    }
                } else {
                     
                    $days = cal_Calendar::calcLeaveDays($form->rec->leaveFrom, $form->rec->leaveTo);
                }
            
                $form->rec->leaveDays = $days->workDays;
            }
          
            // ако не са изчислени дните за отпуска или са по-малко от 1, даваме грешка
            if(!$form->rec->leaveDays || isset($form->rec->leaveDays) < 1) {
                $form->setError('leaveDays', "Броят  неприсъствени дни е 0");
            }
            
            // правим заявка към базата
            $query = self::getQuery();
            
            // търсим всички молби, които са за текущия потребител
            $query->where("#personId='{$form->rec->personId}'");
   
            if ($form->rec->id) { 
                $query->where("#id != {$form->rec->id}");
            }
            
            // търсим времево засичане
            $query->where("(#leaveFrom <= '{$form->rec->leaveFrom}' AND #leaveTo >= '{$form->rec->leaveFrom}')
            OR
            (#leaveFrom <= '{$form->rec->leaveTo}' AND #leaveTo >= '{$form->rec->leaveTo}')");
            
            $query->where("#state = 'active'");
            
            // за всяка една молба отговаряща на условията проверяваме
            if ($recReq = $query->fetch()) {
            
                $link = ht::createLink("Молба за отпуска №{$recReq->id}", array('trz_Requests', 'single', $recReq->id, 'ret_url' => TRUE, ''), NULL, "ef_icon=img/16/leaves.png");
                // и изписваме предупреждение
            	$form->setError('leaveFrom, leaveTo', "|Засичане по време с |*{$link}");
            
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

	     if ($rec->id) {
	         $oRec = $mvc->fetch($rec->id);
	        
	         if ($action == 'order') {
	             // и нямаме нужните права
	             if(!Users::haveRole('ceo') || !Users::haveRole('trz') ) {
	                 // то не може да я направим
	                 $requiredRoles = 'no_one';
	             }
	         }
	     }
	     
	     if ($action == 'add' || $action == 'reject') { 
	         if ($rec->folderId) {
    	         $folderClass = doc_Folders::fetchCoverClassName($rec->folderId);
    	        
    	         if($rec->folderId && $folderClass == 'crm_Persons') { 
    	             $personId = doc_Folders::fetchCoverId($rec->folderId);
    	             $inCharge = crm_Profiles::fetchField("#personId = '{$personId}'", 'userId');
    	             //$inCharge = doc_Folders::fetchField($rec->folderId, 'inCharge');
    	       
    	             if($inCharge != $userId) { 
                        if(!Users::haveRole('ceo') && !Users::haveRole('trz')){
    	                 // то не може да я направим
    	                 $requiredRoles = 'no_one';
                        }
    	             }
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
            //$data->toolbar->addBtn('Заповед', array('trz_Requests', 'Print', 'id' => $data->rec->id, 'Printing' => 'yes'), 'ef_icon = img/16/btn-order.png, title=Създаване на заповед за отпуска');
            $data->toolbar->addBtn('Заповед', array('trz_Requests', 'single', 'id' => $data->rec->id, 'Printing' => 'yes', 'Order'=>'yes'),
            'ef_icon = img/16/btn-order.png, title=Създаване на заповед за отпуска', array('target' => '_blank'), array('class' => 'print'));
        
        }
        
        // Ако имаме права да създадем заповед за отпуск
        if(haveRole('trz, ceo') && $data->rec->state == 'active') {
        
            // Добавяме бутон
            //$data->toolbar->addBtn('Заповед', array('trz_Requests', 'Print', 'id' => $data->rec->id, 'Printing' => 'yes'), 'ef_icon = img/16/btn-order.png, title=Създаване на заповед за отпуска');
            $data->toolbar->addBtn('Заповед', array('trz_Requests', 'single', 'id' => $data->rec->id, 'Printing' => 'yes', 'Order'=>'yes'),
                'ef_icon = img/16/btn-order.png, title=Създаване на заповед за отпуска', array('target' => '_blank'), array('class' => 'print'));
        
        }
        
        // Ако нямаме права за писане в треда
    	if(doc_Threads::haveRightFor('single', $data->rec->threadId) == FALSE){
    		
    		// Премахваме бутона за коментар
	    	$data->toolbar->removeBtn('Коментар');
	    }
        
    }
    
    
    /**
     * Функция, която прихваща след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        //
        $rec = $mvc->fetchRec($rec);
        $subscribedArr = keylist::toArray($rec->sharedUsers);
        
        if(isset($rec->alternatePerson)) { 
            $alternatePersonId = crm_Profiles::fetchField("#personId = '{$rec->alternatePerson}'", 'userId');
            $subscribedArr[$alternatePersonId] = $alternatePersonId;
        }

    	if(count($subscribedArr)) {
   	        foreach($subscribedArr as $userId) {
    	        if($userId > 0  && doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
    	            $rec->message  = "|Активирана е |* \"" . self::getRecTitle($rec) . "\"";
    	            $rec->url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
    	            $rec->customUrl = array('trz_Requests', 'single',  $rec->id);
    	            $rec->priority = 0;
    	
    	            bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
    	        }
    	    }
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
    	
    	while($curDate < $rec->leaveTo){
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
}