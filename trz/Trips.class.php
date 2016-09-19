<?php



/**
 * Мениджър на отпуски
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
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
    public $loadList = 'plg_RowTools2, trz_Wrapper, doc_DocumentPlg, acc_plg_DocumentSummary,
    				 doc_ActivatePlg, plg_Printing, doc_plg_BusinessDoc,doc_SharablePlg,bgerp_plg_Blank,change_Plugin';
    
    
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
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,trz';
    
    
    /**
     * Кой има право да прави начисления
     */
    public $canChange = 'ceo,trz';
    
    
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
     * Единична икона
     */
    public $singleIcon = 'img/16/working-travel.png';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees,allowEmpty=TRUE)', 'caption=Служител, autoFilter');
    	$this->FLD('startDate', 'datetime(format=smartTime)',     'caption=Считано->От');
		$this->FLD('toDate', 'datetime(format=smartTime)',     'caption=Считано->До');
        $this->FLD('place',    'richtext(rows=5, bucket=Notes)', 'caption=Място');
    	$this->FLD('purpose', 'richtext(rows=5, bucket=Notes)', 'caption=Цел');
    	$this->FLD('answerGSM', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Отговаря на моб. телефон, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('answerSystem', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Достъп до системата, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('alternatePerson', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=По време на отсъствието->Заместник');
    	$this->FLD('amountRoad', 'double(decimals=2)', 'caption=Начисления->Пътни,input=none, changable');
    	$this->FLD('amountDaily', 'double(decimals=2)', 'caption=Начисления->Дневни,input=none, changable');
    	$this->FLD('amountHouse', 'double(decimals=2)', 'caption=Начисления->Квартирни,input=none, changable');
    	
    	$this->FLD('sharedUsers', 'userList(roles=trz|ceo)', 'caption=Споделяне->Потребители,mandatory');
    }

    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
    	$mvc->updateTripsToCalendar($rec->id);
    	$mvc->updateTripsToCustomSchedules($rec->id);
    	
    	$subscribedArr = keylist::toArray($rec->sharedUsers);
    	if(count($subscribedArr)) {
    	    foreach($subscribedArr as $userId) {
    	        if($userId > 0  && doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
    	            $rec->message  = self::getVerbal($rec, 'personId'). "| добави |* \"" . self::getRecTitle($rec) . "\"";
    	            $rec->url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
    	            $rec->customUrl = array('trz_Trips', 'single',  $rec->id);
    	            $rec->priority = 0;
    	             
    	            bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
    	        }
    	    }
    	}
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
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
        
        $row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->from);
        
        $row->amountRoad = $Double->toVerbal($rec->amountRoad);
        $row->amountRoad .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
        
        $row->amountDaily = $Double->toVerbal($rec->amountDaily);
        $row->amountDaily .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
        
        $row->amountHouse = $Double->toVerbal($rec->amountHouse);
        $row->amountHouse .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
        
        if(isset($rec->alternatePerson)) {
            // Ако имаме права да видим визитката
            if(crm_Persons::haveRightFor('single', $rec->alternatePerson)){
                $name = crm_Persons::fetchField("#id = '{$rec->alternatePerson}'", 'name');
                $row->alternatePerson = ht::createLink($name, array ('crm_Persons', 'single', 'id' => $rec->alternatePerson), NULL, 'ef_icon = img/16/vcard.png');
            }
        } 
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingleLayout($mvc, $tpl, $data)
    {
        if(!isset($data->rec->amountRoad) || !isset($data->rec->amountDaily) || !isset($data->rec->amountHouse)  ) {
    
            $tpl->removeBlock('compensation');
             
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
	        if($curDate && $curDate >= $fromDate && $curDate <= $toDate && $rec->state == 'active') {
	            
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
     * Обновява информацията за командировките в Персонални работни графици
     */
    public static function updateTripsToCustomSchedules($id)
    {
        $rec = static::fetch($id);
    
        $events = array();
    
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);
    
        // Начална дата
        $fromDate = "{$cYear}-01-01";
    
        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
    
        // Префикс на ключовете за записите персонални работни цикли
        $prefix = "TRIP-{$id}";
    
        $curDate = $rec->startDate;
         
        while($curDate < dt::addDays(1, $rec->toDate)){
            // Подготвяме запис за началната дата
            if($curDate && $curDate >= $fromDate && $curDate <= $toDate && $rec->state == 'active') {
                 
                $customRec = new stdClass();
                 
                // Ключ на събитието
                $customRec->key = $prefix . "-{$curDate}";
                 
                // Дата на събитието
                $customRec->date = $curDate;
    
                // За човек или департамент е
                $customRec->strukture  = 'personId';
    
                // Тип на събитието
                $customRec->typePerson = 'traveling';
    
                // За кого се отнася
                $customRec->personId = $rec->personId;
    
                // Документа
                $customRec->docId = $rec->id;
    
                // Класа ан документа
                $customRec->docClass = core_Classes::getId("trz_Trips");
    
                $events[] = $customRec;
            }
    
            $curDate = dt::addDays(1, $curDate);
        }
    
        return hr_CustomSchedules::updateEvents($events, $fromDate, $toDate, $prefix);
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
        
        $row->recTitle = $this->getRecTitle($rec, FALSE);
        
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
        // Името на класа
    	$coverClassName = strtolower(doc_Folders::fetchCoverClassName($folderId));
    	
    	// Ако не е папка проект или контрагент, не може да се добави
    	if ($coverClassName != 'crm_persons') return FALSE;
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
     * Метод филтриращ заявка към doc_Folders
     * Добавя условия в заявката, така, че да останат само тези папки, 
     * в които може да бъде добавен документ от типа на $mvc
     * 
     * @param core_Query $query   Заявка към doc_Folders
     */
    function restrictQueryOnlyFolderForDocuments($query)
    {
    	$pQuery = crm_Persons::getQuery();
        
        // Искаме да филтрираме само групата "Служители"
        $employeesId = crm_Groups::getIdFromSysId('employees');
        
        if($employees = $pQuery->fetchAll("#groupList LIKE '%|$employeesId|%'", 'id')) {
            $list = implode(',', array_keys($employees));
            $query->where("#coverId IN ({$list})");
        } else {
            $query->where("#coverId = -2");
        }
    }
    
    
    /**
     * Преди да се подготвят опциите на кориците, ако
     */
    public static function getCoverOptions($coverClass)
    {
         
        if($coverClass instanceof crm_Persons){
    
            // Искаме да филтрираме само групата "Служители"
            $sysId = crm_Groups::getIdFromSysId('employees');
             
            $query->where("#groupList LIKE '%|{$sysId}|%'");
        }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
        $me = cls::get(get_called_class());
         
        $title = tr('Командировъчен лист  №|*'. $rec->id . ' на|* ') . $me->getVerbal($rec, 'personId');
         
        return $title;
    }
}