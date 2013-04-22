<?php



/**
 * Мениджър на отпуски
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Отпуски
 */
class trz_Requests extends core_Master
{
    
	
	/**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    /**
     * Заглавие
     */
    var $title = 'Молби';
    
     /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Молба за отпуск";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, trz_Wrapper, trz_LeavesWrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,personId, leaveFrom, leaveTo, note, useDaysFromYear, paid';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    //var $searchFields = 'description';

    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'personId';
    
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'powerUser';

    
    /**
     * Икона за единичния изглед
     */
    //var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'trz/tpl/SingleLayoutRequests.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Req";
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "5.2|Човешки ресурси"; 
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
  //  var $rowToolsField = 'id';

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('docType', 'enum(request=Молба за отпуск, order=Заповед за отпуск)', 'caption=Документ, input=none,column=none');
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител');
    	$this->FLD('leaveFrom', 'date', 'caption=Считано->От, mandatory');
    	$this->FLD('leaveTo', 'date', 'caption=Считано->До, mandatory');
    	$this->FLD('leaveDays', 'int', 'caption=Считано->Дни, input=none');
    	$this->FLD('useDaysFromYear', 'int', 'caption=Информация->Ползване от,unit=Година');
    	$this->FLD('paid', 'enum(paid=платен, unpaid=неплатен)', 'caption=Информация->Вид, maxRadio=2,columns=2,notNull,value=paid');
    	$this->FLD('note', 'richtext(rows=5)', 'caption=Информация->Бележки');
    	$this->FLD('answerGSM', 'enum(yes=да, no=не, partially=частично)', 'caption=Ще отговаря->на GSM, maxRadio=3,columns=3,notNull,value=yes');
    	$this->FLD('answerSystem', 'enum(yes=да, no=не, partially=частично)', 'caption=Ще отговаря->в системата, maxRadio=3,columns=3,notNull,value=yes');
    	
    	
    }
    
    
    /**
     * Прилага филтъра, така че да се показват записите за определение потребител
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	if($data->listFilter->rec->leaveFrom) {
    		$data->query->where("#leaveFrom = '{$data->listFilter->rec->leaveFrom}'");
    	}elseif($data->listFilter->rec->leaveTo) {
    		$data->query->where("#leaveTo = '{$data->listFilter->rec->leaveTo}'");
    	}elseif($data->listFilter->rec->leaveTo && $data->listFilter->rec->leaveFrom) {
    		$data->query->where("#leaveFrom >= '{$data->listFilter->rec->leaveFrom}'
    							 AND #leaveTo <= '{$data->listFilter->rec->leaveTo}'");
    	}
    	
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
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if($rec->leaveFrom &&  $rec->leaveTo){
	    	$days = cal_Calendar::calcLeaveDays($rec->leaveFrom, $rec->leaveTo);
	    	$rec->leaveDays = $days->workDays;
        }

    }
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
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
        $data->listFilter->showFields = 'selectedUsers, leaveFrom, leaveTo, paid';
        
        $data->listFilter->input('selectedUsers, leaveFrom, leaveTo, paid', 'silent');
    }

    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	//bp($data->form->fields[personId]);
    	$nowYear = dt::mysql2Verbal(dt::now(),'Y');
    	for($i = 0; $i < 5; $i++){
    		$years[] = $nowYear - $i;
    	}
    	$data->form->setSuggestions('useDaysFromYear', $years);
    	$data->form->setDefault('useDaysFromYear', $years[0]);
    	
    	$cu = core_Users::getCurrent();
        $data->form->setDefault('personId', $cu);
        
         $rec = $data->form->rec;
    }
    
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {

    	$rec = $form->rec;

    }
    
    
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId)
    {
	    if($action == 'order'){
			if ($rec->id) {
				
					if(!haveRole('ceo') || !haveRole('trz')) {
				
						$requiredRoles = 'no_one';
				}
		    }
	    }
    }

    
    /**
     *
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if($mvc->haveRightFor('orders') && $data->rec->state == 'active') {
            
            $data->toolbar->addBtn('Заповед', array('trz_Orders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''),'class=btn-order');
        }
        
    }
    
    static public function act_Test()
    {
    	$p = 1;
    	$a = '2013-05-02';
    	$b = '2013-05-10';
    	bp(cal_Calendar::calcLeaveDays($a,$b));
    }
    
    /**
     * Обновява информацията за задачата в календара
     */
    static function updateRequestsToCalendar($id)
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
	            $calRec->type  = 'leave';
	
	            $personName = crm_Persons::fetchField($rec->personId, 'name');
	            // Заглавие за записа в календара
	            $calRec->title = "Отпуск:{$personName}";
	            
	            $personProfile = crm_Profiles::fetch("#personId = '{$rec->personId}'");
	            $personId = array($personProfile->userId => 0);
	            $user = type_Keylist::fromArray($personId);
	
	            // В чии календари да влезе?
	            $calRec->users = $user;
	            
	            // Статус на задачата
	            $calRec->state = $rec->state;
	            
	            // Url на задачата
	            $calRec->url = toUrl(array('trz_Requests', 'Single', $id), 'local'); 
	            
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
        $row->title = "Молба за отпуск  №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        //$row->recTitle = $rec->title;
        
        return $row;
    }

}