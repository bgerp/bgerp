<?php



/**
 * Мениджър на болнични
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Болнични листи
 */
class hr_Sickdays extends core_Master
{
    /**
     * Старо име на класа
     */
	public $oldClassName = 'trz_Sickdays';


	/**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    

    /**
     * Заглавие
     */
    public $title = 'Болнични листи';
    
     /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Болничен лист";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, doc_DocumentPlg,acc_plg_DocumentSummary,doc_plg_TransferDoc, 
    				 doc_ActivatePlg, plg_Printing,doc_SharablePlg,bgerp_plg_Blank,change_Plugin, hr_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,personId, fitNoteNum, fitNoteDate, fitNoteFile, startDate, toDate, reason, note, icdCode';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    //public $searchFields = 'description';

    
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateFrom = 'startDate';
    public $filterFieldDateTo = 'toDate';
    
    
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
    public $canRead = 'ceo,hr';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,hr';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,hr';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,hr';
    
    
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

    
    public $canEdited = 'powerUser';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'powerUser';

    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'hr/tpl/SingleLayoutSickdays.shtml';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Skd";
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "5.4|Човешки ресурси"; 
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/sick.png';
    
    
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
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител,mandatory');
    	$this->FLD('startDate', 'date', 'caption=Отсъствие->От, mandatory');
    	$this->FLD('toDate', 'date', 'caption=Отсъствие->До, mandatory');
    	$this->FLD('fitNoteNum', 'varchar', 'caption=Болничен лист->Номер, hint=Номер/Серия/Година, input=none, changable');
    	$this->FLD('fitNoteDate', 'date', 'caption=Болничен лист->Издаден на, input=none, changable');
    	$this->FLD('fitNoteFile', 'fileman_FileType(bucket=humanResources)', 'caption=Болничен лист->Файл');
    	$this->FLD('reason', 'enum(1=Майчинство до 15 дни,
								   2=Майчинство до 410 дни,
								   3=Заболяване,
								   4=Трудова злополука,
								   5=Битова злополука,
								   6=Гледане на болен член от семейството,
								   7=Професионално заболяване,
								   8=Бащинство до 15 дни,
								   9=Бащинство до 410 дни,
								   10=Гледа дете до 18 години)', 'caption=Информация->Причина');
    	$this->FLD('note', 'richtext(rows=5)', 'caption=Информация->Бележки');
    	$this->FLD('icdCode', 'varchar(5)', 'caption=Информация->MKB код, hint=Международна класификация на болестите');
    	$this->FLD('answerGSM', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Отговаря на моб. телефон, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('answerSystem', 'enum(yes=да, no=не, partially=частично)', 'caption=По време на отсъствието->Достъп до системата, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('alternatePerson', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=По време на отсъствието->Заместник');
    	$this->FLD('paidByEmployer', 'double(Min=0)', 'caption=Заплащане->Работодател, input=hidden, changable');
    	$this->FLD('paidByHI', 'double(Min=0)', 'caption=Заплащане->НЗК, input=hidden,changable');
    	
    	$this->FLD('sharedUsers', 'userList(roles=hr|ceo)', 'caption=Споделяне->Потребители');
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
    	
    	$form->setDefault('reason', 3);
        $folderClass = doc_Folders::fetchCoverClassName($rec->folderId);

        if ($rec->folderId && $folderClass == 'crm_Persons') {
	        $form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
	        $form->setReadonly('personId');
	        
	        if(!haveRole('ceo,hr')) {
	           $data->form->fields['sharedUsers']->mandatory = 'mandatory';
	        }
        } 
    }
    
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    public static function on_AfterInputEditForm($mvc, $form)
    { 
    	$now = dt::now(FALSE);
    	
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
        	if($form->rec->startDate > $now){
        		
        		// Добавяме съобщение за грешка
                $form->setError('startDate', "Началната дата трябва да е преди|* <b>{$now}</b>");
        	}
        	
        	if($form->rec->toDate < $form->rec->startDate){
        		$form->setError('toDate', "Крайната дата трябва да е след|*  <b>{$form->rec->startDate}</b>");
        	}
        	
        	// Размяна, ако периодите са объркани
        	if(isset($form->rec->startDate) && isset($form->rec->toDate) && ($form->rec->startDate > $form->rec->toDate)) {
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

    }

    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
    	$mvc->updateSickdaysToCalendar($rec->id);
    	$mvc->updateSickdaysToCustomSchedules($rec->id);	
    }
            
    
    /**
     * Добавя съответните бутони в лентата с инструменти, в зависимост от състоянието
     *
     * @param blast_Emails $mvc
     * @param object $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {

    }
    
    
    /**
     * Извиква се след изпълняването на екшън
     */
    public static function on_AfterAction(&$invoker, &$tpl, $act)
    { 
        if (strtolower($act) == 'single' && haveRole('hr,ceo') && !Mode::is('printing')) {
    
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
        
        $row->paidByEmployer = $Double->toVerbal($rec->paidByEmployer);
        $row->paidByEmployer .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
        
        $row->paidByHI = $Double->toVerbal($rec->paidByHI);
        $row->paidByHI .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
        
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
        if(!isset($data->rec->paidByEmployer)) {
        
            $tpl->removeBlock('compensationEmployer');
       
        }
        
        if(!isset($data->rec->paidByHI)) {
        
            $tpl->removeBlock('compensationHI');
             
        }
    }
    
    
    /**
     * Обновява информацията за болничните в календара
     */
    public static function updateSickdaysToCalendar($id)
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
        $prefix = "Sick-{$id}";

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
	            $calRec->type  = 'sick';
	
	            $personName = crm_Persons::fetchField($rec->personId, 'name');
	            
	            // Заглавие за записа в календара
	            $calRec->title = "Болничен:{$personName}";
	
	            $personProfile = crm_Profiles::fetch("#personId = '{$rec->personId}'");
	            $personId = array($personProfile->userId => 0);
	            $user = keylist::fromArray($personId);
	            
	            // В чии календари да влезе?
	            $calRec->users = $user;
	            
	            // Статус на задачата
	            $calRec->state = $rec->state;
	            
	            // Url на задачата
	            $calRec->url = array('hr_Sickdays', 'Single', $id); 
	            
	            $events[] = $calRec;
	        }
	        $curDate = dt::addDays(1, $curDate);
    	}

        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
    }
    
    
    /**
     * Обновява информацията за болничните в Персонални работни графици
     */
    public static function updateSickdaysToCustomSchedules($id)
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
        $prefix = "Sick-{$id}";
    
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
                $customRec->typePerson = 'sicDay';
    
                // За кого се отнася
                $customRec->personId = $rec->personId;

                // Документа
                $customRec->docId = $rec->id;
                
                // Класа ан документа
                $customRec->docClass = core_Classes::getId("hr_Sickdays");

                $events[] = $customRec;
            }
            
            $curDate = dt::addDays(1, $curDate);
        }
    
        return hr_CustomSchedules::updateEvents($events, $fromDate, $toDate, $prefix);
    }

    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената папка 
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
        $row->title = "Болничен лист №{$rec->id}";

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
         
        $title = tr('Болничен лист №|*'. $rec->id . ' на|* ') . $me->getVerbal($rec, 'personId');
         
        return $title;
    }
}