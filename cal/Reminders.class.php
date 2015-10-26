<?php


/**
 * Клас 'cal_Reminders' - Документ - напомняне
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Reminders extends core_Master
{
	

	/**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    var $defaultFolder = FALSE;
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = ' cal_Wrapper, doc_DocumentPlg, plg_RowTools, plg_Printing, doc_ActivatePlg, doc_SharablePlg, 
    				  bgerp_plg_Blank, plg_Sorting, plg_State, change_Plugin';
    

    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'timeStart';


    /**
     * Заглавие
     */
    var $title = "Напомняния";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Напомняне";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, timeStart, timePreviously, repetition=Повторение, action, nextStartTime, sharedUsers';
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'description';

    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'title';
 
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'powerUser';

    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'powerUser';
    
    
    /**
     * Кой има право да приключва?
     */
    var $canChangeTaskState = 'powerUser';
    
    
    /**
     * Кой има право да затваря задачите?
     */
    var $canClose = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
    
	
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/alarm_clock.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'cal/tpl/SingleLayoutReminders.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Rem";
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "1.4|Общи"; 
    
    
    /**
     * 
     */
    //var $cloneFields = 'title, priority, ';
    
    
    /**
     * 
     */
    static $suggestions = array("", 1, 2, 3, 4, 5, 6, 7, 8, 9 , 10, 11, 12);
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title',    'varchar(128)', 'caption=Заглавие,mandatory,width=100%, changable');
        $this->FLD('priority', 'enum(low=Нисък,
                                     normal=Нормален,
                                     high=Висок,
                                     critical=Критичен)', 
            'caption=Приоритет,mandatory,maxRadio=4,columns=4,notNull,value=normal,changable');
        
        $this->FLD('description', 'richtext(bucket=calReminders)', 'caption=Описание,changable');

        // Споделяне
        $this->FLD('sharedUsers', 'userList', 'caption=Споделяне,mandatory,changable');
        
        // Какво ще е действието на известието?
        $this->FLD('action', 'enum(threadOpen=Отваряне на нишката,
        						   notify=Нотификация,
        						   replicateDraft=Чернова-копие на темата,
        						   replicate=Копие на темата)', 'caption=Действие, mandatory,maxRadio=5,columns=1,notNull,value=notify,changable');
        
        // Начало на напомнянето
        $this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00, format=smartTime)', 'caption=Време->Начало, silent,changable');
        
        // Предварително напомняне
        $this->FLD('timePreviously', 'time', 'caption=Време->Предварително,changable');
        
        // Колко пъти ще се повтаря напомнянето? 
        $this->FLD('repetitionEach', 'int(Min=0)',     'caption=Повторение->Всеки,changable');
        
        // По какво ще се повтаря напомненето - дни, седмици, месеци, години
        $this->FLD('repetitionType', 'enum(   days=дена,
			                                  weeks=седмици,
			                                  months=месецa,
			                                  weekDay=месецa-ден от началото на седмицата,
			                                  monthDay=месецa-ден от началото на месеца)',  
           'caption=Повторение->Мярка, maxRadio=5,columns=1,notNull,value=days,changable');
        
        // За кой път се среща деня
        $this->FLD('monthsWeek',    'varchar(12)', 'caption=Срещане,notNull,input=none');
        
        // Кой ден от седмицата е
        $this->FLD('weekDayNames', 'varchar(12)', 'caption=Име на деня,notNull,input=none');
        
        // Кога е следващото стартирване на напомнянето?
        $this->FLD('nextStartTime', 'datetime(format=smartTime)', 'caption=Следващо напомняне,input=none');
        
        // Изпратена ли е нотификация?
        $this->FLD('notifySent', 'enum(no,yes)', 'caption=Изпратена нотификация,notNull,input=none');

    }


    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$cu = core_Users::getCurrent();
    	$currUrl = getCurrentUrl();
    	$now = dt::now();
        $data->form->setDefault('priority', 'normal');
        $data->form->setDefault('sharedUsers', "|".$cu."|");
        
        $folderList = cls::get('doc_Folders');
		$folderId = $data->form->rec->folderId;
		$folderClass = $folderList->fetchField("#id = '{$folderId}'", 'coverClass');
							
		// Проверка дали папката е фирмена или лична
		$companies = 'crm_Companies';
		$idCompanies = core_Classes::getId($companies);
							
		$persons = 'crm_Persons';
		$idPersons = core_Classes::getId($persons);
							
		if($folderClass == $idCompanies || $folderClass == $idPersons){

			$mvc->getFieldType(action)->options[notifyNoAns] = tr("Нотификация-ако няма отговор");
		}

		$data->form->setSuggestions('repetitionEach', static::$suggestions);
        
		if($data->form->rec->originId){
			// Ако напомнянето е по  документ задача намираме кой е той
    		$doc = doc_Containers::getDocument($data->form->rec->originId);
    		$class = $doc->className;
    		$dId = $doc->that;
    		$rec = $class::fetch($dId);
    		
    		// Извличаме каквато информация можем от оригиналния документ
    		if($rec->timeStart){
    			$data->form->setDefault('title', tr("Начало на задача "). "\"".$rec->title. "\"");
    		} elseif($rec->timeEnd){
    			$data->form->setDefault('title', tr("Изтичаща задача "). "\"".$rec->title. "\"");
    		}
    		$data->form->setDefault('priority', $rec->priority);
    		$data->form->setDefault('sharedUsers', $rec->sharedUsers);
    		if($rec->timeStart){ 
    			$data->form->setDefault('timeStart', $rec->timeStart);
    		} elseif($rec->timeEnd) {
    			$data->form->setDefault('timeStart', $rec->timeEnd);
    		}
    		$data->form->setDefault('timePreviously', tr("15 мин."));
    	    $data->form->setDefault('repetitionEach', " ");

		}
        
		if(Mode::is('screenMode', 'narrow')){
			$data->form->fields[priority]->maxRadio = 2;
		}
		
		// Ако правим промянана напомнянето. Слагаме началната дата да е следващото напомняне
		if ($currUrl['Act'] == 'changeFields') {
			if ($data->form->rec->id) {
				
				$nextStartTime = self::fetchField($data->form->rec->id, 'nextStartTime');

				if ($nextStartTime > $now) { 
					$data->form->rec->timeStart = $nextStartTime;
				}
			}
		}
		
    }


    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {  
    	if ($form->isSubmitted()) {
    	    
    	    $now = dt::now();
    	    
        	if($form->rec->timeStart < $now){
        		// Добавяме съобщение за грешка
                $form->setError('timeStart', tr("Датата за напомняне трябва да е след "). dt::mysql2verbal($now, 'smartTime'));
        	}
        	
    		if($form->rec->id){
    			
    			$exState = self::fetchField($form->rec->id, 'state');
    			
    			if($form->rec->timeStart < $now && ($form->rec->state != $exState && $form->rec->state != 'rejected')){
    				// Добавяме съобщение за грешка
                	$form->setError('timeStart', tr("Не може да се направи напомняне в миналото"). dt::mysql2verbal($now, 'smartTime'));
    			}
    		}
    		
        	if (!$form->gotErrors()){
        		$form->rec->nextStartTime = $mvc->calcNextStartTime($form->rec);
        	}
        } 
    	$rec = $form->rec;

    }
    

    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
    	$now = dt::now(); 
    	
    	if($rec->id){
    		$exState = self::fetchField($rec->id, 'state');
    		$timeStart = $rec->timeStart;
    		if(!$timeStart){
    			$timeStart = self::fetchField($rec->id, 'timeStart');
    		} 
    	}
    }

    
    /**
     * Подрежда по state, за да могат затворените да са отзад
     */
    public static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
    	$data->query->orderBy("#state=ASC, #nextStartTime=DESC");
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
       
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent,refreshForm');
                
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'selectedUsers';
        
        $data->listFilter->input('selectedUsers', 'silent');
                        
        if(!$data->listFilter->rec->selectedUsers) {
            $data->listFilter->rec->selectedUsers = keylist::fromArray(arr::make(core_Users::getCurrent('id'), TRUE));
	  	}
                        
        if($data->listFilter->rec->selectedUsers) {
	           
	         if($data->listFilter->rec->selectedUsers != 'all_users') {
	                $data->query->likeKeylist('sharedUsers', $data->listFilter->rec->selectedUsers);
	               
	         }
        }
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
    	if($data->rec->repetitionType == 'days' ) {
    		if($data->rec->repetitionEach == '1'){
    			$data->row->each = 'всеки';
    			$data->row->repetitionEach = '';
    			$data->row->repetitionType = 'ден';
    		}elseif ($data->rec->repetitionEach == NULL) {
    			$data->row->rem = $data->row->nextStartTime;
    			$data->row->nextStartTime = NULL;
    		}else {
    			$data->row->each = 'на всеки';
    		}
    	} elseif($data->rec->repetitionType == 'months'){
    		if($data->rec->repetitionEach == '1'){
    			$data->row->each = 'всеки';
    			$data->row->repetitionEach = '';
    			$data->row->repetitionType = 'месец';
    		} elseif ($data->rec->repetitionEach == NULL) {
    			$data->row->rem = $data->row->nextStartTime;
    			$data->row->nextStartTime = NULL;
    		} else {
    			$data->row->each = 'на всеки';
    		}
    		
    	} elseif($data->rec->repetitionType == 'weeks') {
    		if($data->rec->repetitionEach == '1'){
    			$data->row->each = 'всяка';
    			$data->row->repetitionEach = '';
    			$data->row->repetitionType = 'седмица';
    		} elseif ($data->rec->repetitionEach == NULL) {
    			$data->row->rem = $data->row->nextStartTime;
    			$data->row->nextStartTime = NULL;
    		} else {
    			$data->row->each = 'на всеки';
    		}
    	} elseif($data->rec->repetitionType == 'weekDay'){
    		if($data->rec->repetitionEach == '1'){
    			$data->row->each = 'всеки';
    			$data->row->repetitionEach = '';
    			$data->row->repetitionType = 'месец';
    			$data->row->repetitionTypeMonth = tr($data->rec->monthsWeek. " " .$data->rec->weekDayNames). " от месеца";
    		} elseif ($data->rec->repetitionEach == NULL) {
    			$data->row->rem = $data->row->nextStartTime;
    			$data->row->nextStartTime = NULL;
    		} else{
	    		$data->row->each = 'на всеки';
	    		$data->row->repetitionType = 'месеца';
	    		$data->row->repetitionTypeMonth = tr($data->rec->monthsWeek. " " .$data->rec->weekDayNames). " от месеца";
    		}
    	} elseif($data->rec->repetitionType == 'monthDay') {
    		if($data->rec->repetitionEach == '1'){
    			$data->row->each = 'всеки';
    			$data->row->repetitionEach = '';
    			$data->row->repetitionType = 'месец';
    			$data->row->repetitionTypeMonth = 'точния ден от месеца';
    		} elseif ($data->rec->repetitionEach == NULL) {
    			$data->row->rem = $data->row->nextStartTime;
    			$data->row->nextStartTime = NULL;
    		} else {
	    		$data->row->each = 'на всеки';
	    		$data->row->repetitionType = 'месеца';
	    		$data->row->repetitionTypeMonth = 'точния ден от месеца';
    		}
    		
    	}
    	
    	if($data->rec->action === 'notifyNoAns') $data->row->action = 'Нотификация-ако няма отговор';

    	if($data->rec->repetitionEach === NULL){
    		$data->row->each = '';
	    	$data->row->repetitionType = '';
	    	$data->row->repetitionTypeMonth = '';
    	}
    }
    
    
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	if ($data->recs) {
        	foreach((array)$data->recs as $id => $rec){
    		    $row = $mvc->recToVerbal($rec);
    		    
    		    if ($rec->repetitionEach != NULL) {
    				$data->rows[$id]->repetition = $row->repetitionEach . " " . $row->repetitionType;
    		    } else {
    		    	$data->rows[$id]->repetition = " ";
    		    }
    		}
    	}
    }
    

    /**
     *
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {  
   
     	if ($mvc->haveRightFor('stop', $data->rec)) { 
	            $data->toolbar->addBtn('Затваряне', array(
	                    $mvc,
	                    'Stop',
	                    $data->rec->id
	                ),
	                array('ef_icon'=>'img/16/gray-close.png',
	                	'title'=>'Спиране на напомнянето'
	                ));
	                
	                
	     }
	       
	     if ($data->rec->state == 'closed' || $data->rec->state == 'active') {
	     	$data->toolbar->removeBtn('btnActivate');
	     }
    }
    
    
    static function on_AfterInputChanges($mvc, &$oldRec, $newRec) 
    {
    	// Ако не е обект, а е подаден id
        if (!is_object($newRec)) {
            
            // Опитваме се да извлечем данните
            $rec = cal_Reminders::fetch($newRec);
        }
        
        // Очакваме да има такъв запис
        expect($newRec, 'Няма такъв запис');
    	
    	if ($newRec->state === 'closed') {
    		$newRec->state = 'active';
    	}
    	
    	if ($newRec->notifySent === 'yes') {
    		$newRec->notifySent = 'no';
    	}
    }
 
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява изтриването на вече използвани сметки
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	$now = dt::now();
      
    	if ($rec->id) {
    		$oRec = $mvc->fetch($rec->id);
    	    		
    		if ($action == 'stop') {
                if (doc_Threads::haveRightFor('single', $oRec->threadId, $userId)) { 
                    if($rec->state !== 'active') { 
                    	$requiredRoles = 'no_one';
                    } 
                }
    		}
    	}
    }
    
    
	/**
     * Проверява дали може да се променя записа в зависимост от състоянието на документа
     * 
     * @param core_Mvc $mvc
     * @param boolean $res
     * @param string $state
     */
    function on_AfterCanChangeRec($mvc, &$res, $rec)
    {
        $res = TRUE;
        
        // Чернова документи не могат да се променят
        if ($rec->state == 'draft') {
            
            $res = FALSE;
        } 
    }
    
    
    /**
     * Екшън за спиране
     */
    function act_Stop()
    {
        //Права за работа с екшън-а
        requireRole('powerUser');
       
        //Очакваме да има такъв запис
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        //Очакваме потребителя да има права за спиране
        $this->haveRightFor('stop', $rec);
         
        $link = array('cal_Reminders', 'single', $rec->id);
        
        //Променяме статуса на спрян
        $recUpd = new stdClass(); 
        $recUpd->id = $rec->id;
        $recUpd->state = 'closed';
        
       	cal_Reminders::save($recUpd);
       
        // Добавяме съобщение в статуса
        status_Messages::newStatus(tr("Успешно спряхте напомнянето"));
        
        // Редиректваме
        return redirect($link);
    }


    /**
     * Връща приоритета на задачата за отразяване в календара
     */
    static function getNumbPriority($rec)
    {
        if($rec->state == 'active') {

            switch($rec->priority) {
                case 'low':
                    $res = 100;
                    break;
                case 'normal':
                    $res = 200;
                    break;
                case 'high':
                    $res = 300;
                    break;
                case 'critical':
                    $res = 400;
                    break;
            }
        } else {

            $res = 0;
        }

        return $res;
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
        $row->title = $this->getVerbal($rec, 'title');
        
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
     * Изпращане на нотификации за започването на задачите
     */
    function cron_SendNotifications()
    {
    	
        $now = dt::verbal2mysql();
       
        $this->doReminderingForActiveRecs();

    }

    public function doReminderingForActiveRecs()
    {
    	 $now = dt::verbal2mysql();
    	 $query = self::getQuery();
    	 $query->where("#state = 'active' AND #nextStartTime <= '{$now}' AND (#notifySent = 'no' OR #notifySent = NULL)");
    	     	 
    	 while($rec = $query->fetch()){
             
    	 	 $rec->message  = "|Напомняне|* \"" . self::getVerbal($rec, 'title') . "\"";
    	 	 $rec->url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
    	 	 $rec->customUrl = array('cal_Reminders', 'single',  $rec->id);
    	 	 
    	 	 self:: doUsefullyPerformance($rec);
    	 	
    	 	 if($rec->repetitionEach == 0){
    	 	 	$rec->notifySent = 'yes';
    	 	 	$rec->state = 'closed';
    	 	 }
    	 	 $rec->nextStartTime = $this->calcNextStartTime($rec);
    	 	 
    	 	 self::save($rec);
    	 }

    }
    
    
    static public function doUsefullyPerformance($rec)
    {   
    	$subscribedArr = keylist::toArray($rec->sharedUsers); 
		if(count($subscribedArr)) { 
			foreach($subscribedArr as $userId) {  
				if($userId > 0  && doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
					switch($rec->action){
						case 'notify':
							bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
						break;
						
						case 'threadOpen':
							doc_Threads::save((object)array('id'=>$rec->threadId, 'state'=>'opened'), 'state');
							bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
						break;
						
						case 'notifyNoAns':
							// Търсим дали има пристигнало писмо
	            			$emailIncomings = 'email_Incomings';
	            			$idEmailIncomings = core_Classes::getId($emailIncomings);
	            				
							if(doc_Containers::fetch("#threadId = '{$rec->threadId}' AND 
													  #docClass = '{$idEmailIncomings}' AND
													  #createdOn > '{$rec->modifiedOn}'") == FALSE){
								bgerp_Notifications::add($rec->message, $rec->url, $userId, $rec->priority, $rec->customUrl);
							}
							
						break;
						
						case 'replicateDraft':
						break;
						
						case 'replicate':
						break;
					}
				}
			}
		}

    }
    
    
    /**
     * За тестове
     */
    static public function act_Test()
    {
    	$rec = new stdClass();
    	$rec->timeStart = '2013-03-30 18:10';
    	$rec->repetitionEach = 1;
    	$rec->repetitionType = 'months';
    	$rec->repetitionAbidance = 'weekDay';
   	
    }
    
    
    /**
     *  Изчислява времето за следващото стартиране на напомнянето. Винаги е дата > от текущата
     */
    static public function calcNextStartTime($rec)
    {
    	$now = dt::verbal2mysql();
    	// Секундите на днешната дата
    	$nowTs = dt::mysql2timestamp($now) + $rec->timePreviously;
    	
    	// Секундите на началната дата
        $startTs = dt::mysql2timestamp($rec->timeStart);
        
        // Ако искаме напомнянето да се изпълни само един път
        if($rec->repetitionEach == NULL && $rec->timePreviously !== NULL) {
        	$nextStartTimeTs = $startTs - $rec->timePreviously ;
        	$nextStartTime = date("Y-m-d H:i:s", $nextStartTimeTs);
        	return $nextStartTime;
        	
        } elseif($rec->repetitionEach == NULL && $rec->timePreviously == NULL){
        	$nextStartTime = $rec->timeStart;
        	return $nextStartTime;
        	
        }
        
        if($rec->repetitionEach !== NULL ) {
	        // Типа на повторението е ден или седмица
	        if($rec->repetitionType == 'days' || $rec->repetitionType == 'weeks'){
	        	
	        	if($startTs > $nowTs) $nextStartTime = $startTs; 
	        	// Намираме интервала в секинди
		    	$intervalTs = self::getSecOfInterval($rec->repetitionEach, $rec->repetitionType);
		  
		    	// Изчисляваме следващата дата в секунди
		    	$nextStartTimeTs = (floor(($nowTs-$startTs)/$intervalTs) + 1)*$intervalTs;
		    	
		    	// Правим mySQL формат на новата дата
			    $nextStartTime = date("Y-m-d H:i:s", $startTs + $nextStartTimeTs);
		    	
		    	if($rec->timePreviously !== NULL){
		    		$nextStartTimePrev = $nextStartTimeTs - $rec->timePreviously;
		    		$nextStartTime = date("Y-m-d H:i:s", $startTs + $nextStartTimePrev);
		    		
		    		return $nextStartTime;
		    	}

		    	return $nextStartTime;
	
		    	
	        }
	        // Типа на повторението е месец
	        for ($i = 1; $i <= 10000; $i++){
	        		
	        // Масив с час, сек, мин, ден, месец, год ... на Началната дата
	        $data = getdate($startTs);
	        	
	        // Новия месец който търсим е стария месец + ($i * повторението ни)
	        $newMonth = $data[mon] + ($i * $rec->repetitionEach);
	        		
	        // Секундите на новия месец
	        $newMonthTs = mktime(0, 0, 0, $newMonth, 1, $data[year]);
	        		
		        // Търсим съответствие по ден от месеца:
			    if($rec->repetitionType == 'monthDay' || $rec->repetitionType == 'months'){
			        		
				    // НАчалния ни ден
				    $day = $data[mday];
				        		
				    // Новия ни ден
				    $newDay = 1 + ($day - 1);
				        		
				    // Правим mySQL формат на датата от началните час, мин, сек и новия месец, новия ден и началната година
				    $nextStartTime = date("Y-m-d H:i:s", mktime($data[hours], $data[minutes], $data[seconds], $newMonth, $newDay, $data[year]));
				        		
				    // Проверяваме броя на дните в новия месец
				    $numbMonthDay = date('t', $newMonthTs);
				        		
				    // Ако новия ден не присъства в новия месец, то взимаме последния ден от новия месец
				    if($newDay >= $numbMonthDay) $nextStartTime = date("Y-m-d H:i:s", mktime($data[hours], $data[minutes], $data[seconds], $newMonth, $numbMonthDay, $data[year]));

				    if(dt::mysql2timestamp($nextStartTime) < $nowTs) continue;
				    
				    if($rec->timePreviously !== NULL){
				    	$nextStartTime = date("Y-m-d H:i:s", mktime($data[hours], $data[minutes], $data[seconds] - $rec->timePreviously, $newMonth, $newDay, $data[year]));
				    	return $nextStartTime;
				    }
				    
				    
				    
				    return $nextStartTime;
				        		
				} elseif($rec->repetitionType == 'weekDay'){
				        		
					// Масив с дните от седмицата
					$weekDayNames = array(
							            1 => 'monday',
							            2 => 'tuesday',
							            3 => 'wednesday',
							            4 => 'thursday',
							            5 => 'friday',
							            6 => 'saturday',
							            0 => 'sunday');
							            
					// Броя на дните в месеца	
					$numbMonthDay = date('t', $startTs);
					        	    
					// Проверки за поредността на деня - 
					// един ден от седмицата (напр. понеделник) може да има най-много 5 срещания
					// в дадения месец
					if ($data[mday] - 7 >= -6 && $data[mday] - 7 <= 0) $monthsWeek = 'first';
					elseif($data[mday] - 14 >= -6 && $data[mday] - 14 <= 0) $monthsWeek = 'second'; 
					elseif($data[mday] - 21 >= -6 && $data[mday] - 21 <= 0) $monthsWeek = 'third'; 
					        		
					// Ако един ден е намерен за 3 път, проверяваме дали той не е и предпоследен
					if($data[mday] + 14 > $numbMonthDay && $monthsWeek = 'third') $monthsWeek = 'penultimate'; 
					        		
					// Ако един ден е намерен за предпоследен път, проверяваме дали той не е и последен
					if($data[mday] + 7 > $numbMonthDay && $monthsWeek == 'penultimate') $monthsWeek = 'last'; 
					        	
					// Вербалното име на деня, напр. first-monday, penultimate-wednesday
					$nextStartTimeName = $monthsWeek."-".$weekDayNames[$data[wday]];
					$nextStartTimeMonth = $newMonth;
					        		
					$rec->monthsWeek = $monthsWeek;
					$rec->weekDayNames = $weekDayNames[$data[wday]];
					
					$nextStartTime = date("Y-m-d {$data[hours]}:{$data[minutes]}:{$data[seconds]}", dt::firstDayOfMounthTms($nextStartTimeMonth, $data[year], $nextStartTimeName));
					        		
					if(dt::mysql2timestamp($nextStartTime) < $nowTs) continue;
					
					if($rec->timePreviously !== NULL){
						$nextStartTimeD = date("d", dt::firstDayOfMounthTms($nextStartTimeMonth, $data[year], $nextStartTimeName));
						$nextStartTimeM = date("m", dt::firstDayOfMounthTms($nextStartTimeMonth, $data[year], $nextStartTimeName));
						$nextStartTimeG = date("Y", dt::firstDayOfMounthTms($nextStartTimeMonth, $data[year], $nextStartTimeName));
				    	$nextStartTime = date("Y-m-d H:i:s", mktime($data[hours], $data[minutes], $data[seconds] - $rec->timePreviously, $nextStartTimeM, $nextStartTimeD, $nextStartTimeG));
				    	
				    	return $nextStartTime;
					}
					        		
					return $nextStartTime;
				        		
				}
 	
		    }

        }

    }
    
    
    /**
     * По зададен брой пъти и тип (ден или сецмица) изчислява интервала в секунди
     * @param int $each
     * @param string $type = days/weeks
     */
    static public function getSecOfInterval($each, $type)
    {
    	if ($type !== 'days' || $type !== 'weeks') $intervalTs;
    	if ($type == 'days') $intervalTs = $each * 24 * 60 *60;
    	else $intervalTs = $each * 7 * 24 * 60 *60;
    	
    	return $intervalTs;
    }

    
    /**
     * Изпълнява се след начално установяване
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Нагласяне на Крон
        $rec = new stdClass();
        $rec->systemId = "StartReminders";
        $rec->description = "Известяване за стартирани напомняния";
        $rec->controller = "cal_Reminders";
        $rec->action = "SendNotifications";
        $rec->period = 1;
        $rec->offset = 0;
        $res .= core_Cron::addOnce($rec);
           
        //Създаваме, кофа, където ще държим всички прикачени файлове на напомнянията
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('calReminders', 'Прикачени файлове в напомнянията', NULL, '104857600', 'user', 'user');
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     * 
     * @param core_Master $mvc
     * @param NULL|array $res
     * @param object $rec
     * @param object $row
     */
    public static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);
        
        $allFieldsArr = array('priority' => 'Приоритет',
        						'timeStart' => 'Начало',
        						'action' => 'Действие',
        						'timePreviously' => 'Предварително',
        						'nextStartTime' => 'Следващо напомняне',
        						'rem' => 'Напомняне',
        						'repetitionTypeMonth' => 'Съблюдаване на',
                            );
        foreach ($allFieldsArr as $fieldName => $val) {
            if ($row->{$fieldName}) {
                $resArr[$fieldName] =  array('name' => tr($val), 'val' =>"[#{$fieldName}#]");
            }
        }
        
        if ($row->repetitionEach){
            $resArr['each'] =  array('name' => tr('Повторение'), 'val' =>"[#each#]<!--ET_BEGIN repetitionEach--> [#repetitionEach#]<!--ET_END repetitionEach--><!--ET_BEGIN repetitionType--> [#repetitionType#]<!--ET_END repetitionType-->");
        }
    }
}