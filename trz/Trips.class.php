<?php



/**
 * Мениджър на отпуски
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Командировки
 */
class trz_Trips extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Командировки';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Командировка";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, trz_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing';
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    /**
     * Какви детайли има този мастер
     */
    //var $details = 'trz_TripDetails';
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,trz';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,trz';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,trz';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "5.2|Човешки ресурси"; 
 
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, personId, startDate, toDate, purpose, amountRoad, amountDaily, amountHouse';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'personId';
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'trz/tpl/SingleLayoutTrips.shtml';
    
    /**
     * Абревиатура
     */
    var $abbr = "Trip";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител');
    	$this->FLD('startDate', 'date',     'caption=Считано->От');
		$this->FLD('toDate', 'date',     'caption=Считано->До');
        $this->FLD('place',    'richtext(rows=5)', 'caption=Място');
    	$this->FLD('purpose', 'richtext(rows=5)', 'caption=Цел');
    	$this->FLD('answerGSM', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Отговаря на моб. телефон, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('answerSystem', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Достъп до системата, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('alternatePerson', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=По време на отсъствието->Заместник');
    	$this->FLD('amountRoad', 'double', 'caption=Начисления->Пътни');
    	$this->FLD('amountDaily', 'double', 'caption=Начисления->Дневни');
    	$this->FLD('amountHouse', 'double', 'caption=Начисления->Квартирни');
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
    	$mvc->updateTripsToCalendar($rec->id);
    }
    
    
    /**
     * Прилага филтъра, така че да се показват записите за определение потребител
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	if($data->listFilter->rec->startDate) {
    		$data->query->where("#startDate = '{$data->listFilter->rec->startDate}'");
    	}elseif($data->listFilter->rec->toDate) {
    		$data->query->where("#toDate = '{$data->listFilter->rec->toDate}'");
    	}elseif($data->listFilter->rec->toDate && $data->listFilter->rec->startDate) {
    		$data->query->where("#startDate >= '{$data->listFilter->rec->startDate}'
    							 AND #toDate <= '{$data->listFilter->rec->toDate}'");
    	}
    	
        if($data->listFilter->rec->place) {
    		//$data->query->where("#paid = '{$data->listFilter->rec->plase}'");
    	}
    	
        // Филтриране по потребител/и
        if(!$data->listFilter->rec->selectedUsers) {
            $data->listFilter->rec->selectedUsers = '|' . core_Users::getCurrent() . '|';
        }

        if(($data->listFilter->rec->selectedUsers != 'all_users') && (strpos($data->listFilter->rec->selectedUsers, '|-1|') === FALSE)) {
            $data->query->where("'{$data->listFilter->rec->selectedUsers}' LIKE CONCAT('%|', #createdBy, '|%')");
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$cu = core_Users::getCurrent();

        // Добавяме поле във формата за търсене
       
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        $data->listFilter->setDefault('selectedUsers', 'all_users'); 
              
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'selectedUsers, startDate, toDate';
        
        $data->listFilter->input('selectedUsers, startDate, toDate', 'silent');
    }
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $rec = $data->form->rec;
        
        if ($rec->folderId) {
	        $data->form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
	        $data->form->setReadonly('personId');
        }
    }
    
    
    /**
     * Обновява информацията за задачата в календара
     */
    static function updateTripsToCalendar($id)
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
        $prefix = "TRIP-{$id}";

        $curDate = $rec->startDate;
    	
    	while($curDate < dt::addDays(1, $rec->toDate)){
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
	            $calRec->type  = 'working-travel';
	
	            $personName = crm_Persons::fetchField($rec->personId, 'name');
	            // Заглавие за записа в календара
	            $calRec->title = "Командировка:{$personName}";
	
	            $personProfile = crm_Profiles::fetch("#personId = '{$rec->personId}'");
	            $personId = array($personProfile->userId => 0);
	            $user = type_Keylist::fromArray($personId);
	           
	            // В чии календари да влезе?
	            $calRec->users = $user;
	            
	            // Статус на задачата
	            $calRec->state = $rec->state;
	            
	            // Url на задачата
	            $calRec->url = toUrl(array('trz_Trips', 'Single', $id), 'local'); 
	            
	            $events[] = $calRec;
	        }
	        $curDate = dt::addDays(1, $curDate);
    	}

        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
    }
    
    
    

    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = "Командировъчен лист  №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        //$row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка 
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
        
        if ('crm_Persons' != $coverClass) {
        	return FALSE;
        }
        
        $personId = doc_Folders::fetchCoverId($folderId);
        
        $personRec = crm_Persons::fetch($personId);
        $emplGroupId = crm_Groups::getIdFromSysId('employees');
        
        return type_Keylist::isIn($emplGroupId, $personRec->groupList);
    }
    

}