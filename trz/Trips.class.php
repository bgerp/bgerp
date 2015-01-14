<?php



/**
 * Мениджър на отпуски
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Командировки
 */
class trz_Trips extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Командировки';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Командировка";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, trz_Wrapper, doc_DocumentPlg, acc_plg_DocumentSummary,
    				 doc_ActivatePlg, plg_Printing, doc_plg_BusinessDoc,
    				 plg_AutoFilter,bgerp_plg_Blank';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,trz';
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "5.4|Човешки ресурси"; 
 
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, personId, startDate, toDate, purpose, amountRoad, amountDaily, amountHouse';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'personId';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'trz/tpl/SingleLayoutTrips.shtml';
    
    
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateFrom = 'startDate';
    public $filterFieldDateTo = 'toDate';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Trip";
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees,allowEmpty=TRUE)', 'caption=Служител, autoFilter');
    	$this->FLD('startDate', 'date',     'caption=Считано->От');
		$this->FLD('toDate', 'date',     'caption=Считано->До');
        $this->FLD('place',    'richtext(rows=5, bucket=Notes)', 'caption=Място');
    	$this->FLD('purpose', 'richtext(rows=5, bucket=Notes)', 'caption=Цел');
    	$this->FLD('answerGSM', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Отговаря на моб. телефон, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('answerSystem', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Достъп до системата, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('alternatePerson', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=По време на отсъствието->Заместник');
    	$this->FLD('amountRoad', 'double(decimals=2)', 'caption=Начисления->Пътни');
    	$this->FLD('amountDaily', 'double(decimals=2)', 'caption=Начисления->Дневни');
    	$this->FLD('amountHouse', 'double(decimals=2)', 'caption=Начисления->Квартирни');
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
    	$mvc->updateTripsToCalendar($rec->id);
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
    	
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields .= ', personId';
        
        $data->listFilter->input('personId', 'silent');
        
    	if($filterRec = $data->listFilter->rec){
        	if($filterRec->personId){
        		$data->query->where(array("#personId = '[#1#]'", $filterRec->personId));
        	}
    	}
    }
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $rec = $data->form->rec;
        
        if ($rec->folderId) {
	        $rec->personId = doc_Folders::fetchCoverId($rec->folderId);
	        $data->form->setReadonly('personId');
        }
    }
    
    
    /**
     * Обновява информацията за задачата в календара
     */
    public static function updateTripsToCalendar($id)
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
	            $user = keylist::fromArray($personId);
	           
	            // В чии календари да влезе?
	            $calRec->users = $user;
	            
	            // Статус на задачата
	            $calRec->state = $rec->state;
	            
	            // Url на задачата
	            $calRec->url = array('trz_Trips', 'Single', $id); 
	            
	            $events[] = $calRec;
	        }
	        $curDate = dt::addDays(1, $curDate);
    	}

        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
    }
    
    
    /**
     * Подготовка за рендиране на единичния изглед
     * 
     *  
     * @param cal_Reminders $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingle($mvc, $data)
    {
    	$currencyId = "<span class='cCode'>" . acc_Periods::getBaseCurrencyCode($rec->startDate) . "</span>";
    	if(isset($data->rec->amountRoad)){
    		$data->row->roadCurrencyId = $currencyId;
    	}
    	if(isset($data->rec->amountDaily)){
    		$data->row->dailyCurrencyId = $currencyId;
    	}
    	if(isset($data->rec->amountHouse)){
    		$data->row->houseCurrencyId = $currencyId;
    	}
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
        $row->title = "Командировъчен лист  №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->title;
        
        return $row;
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
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
        
        if ('crm_Persons' != $coverClass) {
        	return FALSE;
        }
        
        $personId = doc_Folders::fetchCoverId($folderId);
        
        $personRec = crm_Persons::fetch($personId);
        $emplGroupId = crm_Groups::getIdFromSysId('employees');
        
        return keylist::isIn($emplGroupId, $personRec->groupList);
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
    	
    	if($coverClass instanceof crm_Persons){
    		
    		// Искаме да филтрираме само групата "Служители"
    		$sysId = crm_Groups::getIdFromSysId('employees');
    	
    		return $coverClass::makeArray4Select(NULL, "#state != 'rejected' AND #groupList LIKE '%|{$sysId}|%'");
    	}
    }
}