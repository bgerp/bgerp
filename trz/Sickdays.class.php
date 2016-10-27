<?php



/**
 * Мениджър на болнични
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Болнични листи
 */
class trz_Sickdays extends core_Master
{
    
	
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
    public $loadList = 'plg_RowTools2, trz_Wrapper, doc_DocumentPlg,acc_plg_DocumentSummary,doc_plg_TransferDoc, 
    				 doc_ActivatePlg, plg_Printing, doc_plg_BusinessDoc,doc_SharablePlg,bgerp_plg_Blank,change_Plugin';
    
    
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
    public $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
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
    public $canChangerec = 'ceo,trz';

    
    public $canEdited = 'ceo,trz';

    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'trz/tpl/SingleLayoutSickdays.shtml';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Sick";
    
    
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
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees,allowEmpty=TRUE)', 'caption=Служител,readonly');
    	$this->FLD('startDate', 'date', 'caption=Отсъствие->От, mandatory');
    	$this->FLD('toDate', 'date', 'caption=Отсъствие->До, mandatory');
    	$this->FLD('fitNoteNum', 'varchar', 'caption=Болничен лист->Номер, hint=Номер/Серия/Година, input=none, changable');
    	$this->FLD('fitNoteDate', 'date', 'caption=Болничен лист->Издаден на, input=none, changable');
    	$this->FLD('fitNoteFile', 'fileman_FileType(bucket=trzSickdays)', 'caption=Болничен лист->Файл');
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
    	
    	$this->FLD('sharedUsers', 'userList(roles=trz|ceo)', 'caption=Споделяне->Потребители');
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
        $data->listFilter->showFields .= ',personId';
        
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
    	$data->form->setDefault('reason', 3);
        
        $rec = $data->form->rec;
        
        $folderClass = doc_Folders::fetchCoverClassName($rec->folderId);

        if ($rec->folderId && $folderClass == 'crm_Persons') {
	        $rec->personId = doc_Folders::fetchCoverId($rec->folderId);
	        $data->form->setReadonly('personId');
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
                $form->setError('startDate', "Началната дата трябва да е преди ". $now);
        	}
        	if($form->rec->toDate < $form->rec->startDate){
        		$form->setError('toDate', "Крайната дата трябва да е след ". $form->rec->startDate);
        	}
        }
        
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
     * Изпълнява се след начално установяване
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        //Създаваме, кофа, където ще държим всички прикачени файлове на болничните листи
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('trzSickdays', 'Прикачени файлове в болнични листи', NULL, '104857600', 'user', 'user');
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
	            $calRec->url = array('trz_Sickdays', 'Single', $id); 
	            
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
                $customRec->docClass = core_Classes::getId("trz_Sickdays");

                $events[] = $customRec;
            }
            
            $curDate = dt::addDays(1, $curDate);
        }
    
        return hr_CustomSchedules::updateEvents($events, $fromDate, $toDate, $prefix);
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
    	if ($coverClassName != 'crm_persons' && $coverClassName != 'doc_unsortedfolders') return FALSE;
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
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('crm_PersonAccRegIntf', 'folderClass' => 'doc_UnsortedFolders');
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
         
        $title = tr('Болничен лист №|*'. $rec->id . ' на|* ') . $me->getVerbal($rec, 'personId');
         
        return $title;
    }
}