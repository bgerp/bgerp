<?php



/**
 * Мениджър на отпуски
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Командировки
 */
class hr_Trips extends core_Master
{
    /**
     * Старото име на класа
     */
    public $oldClassName = 'trz_Trips';


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
    public $loadList = 'plg_RowTools2, doc_DocumentPlg,doc_plg_TransferDoc, acc_plg_DocumentSummary,
    				 doc_ActivatePlg, plg_Printing,doc_SharablePlg,bgerp_plg_Blank,change_Plugin, hr_Wrapper';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hr';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,hr';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,hr';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го активира?
     */
    public $canActivate = 'ceo,hr';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,hr';
    
    
    /**
     * Кой има право да прави начисления
     */
    public $canChangerec = 'ceo,hr';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'powerUser';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "5.4|Човешки ресурси"; 
 
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, personId, startDate, toDate, purpose, amountRoad, amountDaily, amountHouse';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'personId,startDate, toDate,purpose,title';
    
    
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
    public $singleLayoutFile = 'hr/tpl/SingleLayoutTrips.shtml';
    
    
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateFrom = 'startDate';
    public $filterFieldDateTo = 'toDate';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Trp";
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/working-travel.png';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * По кое поле ще се премества документа
     */
    public $transferFolderField = 'personId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител, mandatory');
    	$this->FLD('startDate', 'datetime',     'caption=Считано->От, mandatory');
		$this->FLD('toDate', 'datetime(defaultTime=23:59:59)',     'caption=Считано->До, mandatory');
        $this->FLD('place',    'richtext(rows=5, bucket=Notes)', 'caption=Място');
    	$this->FLD('purpose', 'richtext(rows=5, bucket=Notes)', 'caption=Цел');
    	$this->FLD('answerGSM', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Отговаря на моб. телефон, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('answerSystem', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Достъп до системата, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('alternatePerson', 'key(mvc=crm_Persons,select=name,group=employees, allowEmpty)', 'caption=По време на отсъствието->Заместник');
    	$this->FLD('amountRoad', 'double(decimals=2)', 'caption=Начисления->Пътни,input=none, changable');
    	$this->FLD('amountDaily', 'double(decimals=2)', 'caption=Начисления->Дневни,input=none, changable');
    	$this->FLD('amountHouse', 'double(decimals=2)', 'caption=Начисления->Квартирни,input=none, changable');
    	$this->FNC('title', 'varchar', 'column=none');
    	
    	$this->FLD('sharedUsers', 'userList(roles=hr|ceo)', 'caption=Споделяне->Потребители');
    }

    
    /**
     * Изчисление на title
     */
    protected static function on_CalcTitle($mvc, $rec)
    {
        $rec->title = "Командировъчен лист  №{$rec->id}";
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
    	$data->listFilter->FLD('employeeId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител,silent,before=selectPeriod');
        $data->listFilter->showFields = $data->listFilter->showFields . ',employeeId';
        $data->listFilter->input('employeeId', 'silent');
        
    	if($filterRec = $data->listFilter->rec){
        	if($filterRec->employeeId){
        		$data->query->where(array("#personId = '[#1#]'", $filterRec->employeeId));
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
            if(isset($form->rec->startDate) && isset($form->rec->toDate) && ($form->rec->startDate > $form->rec->toDate)) {
                $form->setError('startDate, toDate', "Началната дата трябва да е по-малка от крайната");
            }
        } 
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$form = &$data->form;
    	$rec = $form->rec;

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
        
        if ($rec->folderId && $folderClass == 'crm_Persons') { 
	        $form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
	        $form->setReadonly('personId');

	        if(!haveRole('ceo,hr')) {
	        	$form->setField('sharedUsers', 'mandatory');
	        }
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
        
        if ($rec->amountRoad) {
            $row->amountRoad = $Double->toVerbal($rec->amountRoad);
            $row->amountRoad .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
        }
        
        if ($rec->amountDaily) {
            $row->amountDaily = $Double->toVerbal($rec->amountDaily);
            $row->amountDaily .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
        }
        
        if ($rec->amountHouse) {
            $row->amountHouse = $Double->toVerbal($rec->amountHouse);
            $row->amountHouse .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
        }
        
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
        if(!isset($data->rec->amountRoad) && !isset($data->rec->amountDaily) && !isset($data->rec->amountHouse)  ) {
    
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
    	
    	while($curDate < $rec->toDate){
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
	            $calRec->title = "Командировка: {$personName}";
	
	            $personProfile = crm_Profiles::fetch("#personId = '{$rec->personId}'");
	            $personId = array($personProfile->userId => 0);
	            $user = keylist::fromArray($personId);
	           
	            // В чии календари да влезе?
	            $calRec->users = $user;
	            
	            // Статус на задачата
	            $calRec->state = $rec->state;
	            
	            // Url на задачата
	            $calRec->url = array('hr_Trips', 'Single', $id); 
	            
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
            if(!haveRole('ceo,hr', $cu)) return FALSE;
        }
        
        return TRUE;
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