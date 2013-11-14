<?php


/**
 * Клас 'cal_Tasks' - Документ - задача
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Tasks extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, cal_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing, doc_SharablePlg, plg_Search, change_Plugin';
    

    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'timeStart';
    

    /**
     * Какви детайли има този мастер
     */
    var $details = 'cal_TaskProgresses';

    /**
     * Заглавие
     */
    var $title = "Задачи";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Задача";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, timeStart, timeEnd, timeDuration, progress, sharedUsers';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, description';
	
	
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    var $fetchFieldsBeforeDelete = 'id';
    
    
    /**
     * Кой има право да го чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да го променя?
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
     * Кой може да го разглежда?
     */
    var $canList = 'powerUser';
    
    
    /**
     * Кой има право да изтрива?
     */
    var $canDelete = 'no_one';


    /**
     * 
     */
    var $canSingle = 'powerUser';
    
    
    /**
     * Кой има право да приключва?
     */
    var $canChangeTaskState = 'powerUser';
    
    
    /**
     * Кой има право да затваря задачите?
     */
    var $canClose = 'powerUser';
    
    
    /**
     * Кой може да променя активирани записи
     */
    var $canChangerec = 'powerUser, admin, ceo';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/task-normal.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'cal/tpl/SingleLayoutTasks.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Tsk";
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "1.3|Общи"; 
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title',    'varchar(128)', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('priority', 'enum(low=Нисък,
                                    normal=Нормален,
                                    high=Висок,
                                    critical=Критичен)', 
            'caption=Приоритет,mandatory,maxRadio=4,columns=4,notNull,value=normal');
        $this->FLD('description',     'richtext(bucket=calTasks)', 'caption=Описание,mandatory');

        // Споделяне
        $this->FLD('sharedUsers', 'userList', 'caption=Отговорници,mandatory');
        
        // Начало на задачата
        $this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)', 
            'caption=Времена->Начало, silent, changable');
        
        // Продължителност на задачата
        $this->FLD('timeDuration', 'time', 'caption=Времена->Продължителност, changable');
        
        // Краен срок на задачата
        $this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)',     'caption=Времена->Край, changable');
        
        // Изпратена ли е нотификация?
        $this->FLD('notifySent', 'enum(no,yes)', 'caption=Изпратена нотификация,notNull,input=none');
        
        // Дали началото на задачата не е точно определено в рамките на деня?
        $this->FLD('allDay', 'enum(no,yes)',     'caption=Цял ден?,input=none');
        
        // Каква част от задачата е изпълнена?
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)',     'caption=Прогрес,input=none,notNull,value=0');
        
        // Колко време е отнело изпълнението?
        $this->FLD('workingTime', 'time',     'caption=Отработено време,input=none');
    }


    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$cu = core_Users::getCurrent();
        $data->form->setDefault('priority', 'normal');
        $data->form->setDefault('sharedUsers', "|".$cu."|");
	
        if(Mode::is('screenMode', 'narrow')){
        	$data->form->fields[priority]->maxRadio = 2;
        }

        $rec = $data->form->rec;
 
        if($rec->allDay == 'yes') {
            list($rec->timeStart,) = explode(' ', $rec->timeStart);
        }
        
    }


    /**
     * Подготвяне на вербалните стойности
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $blue = new color_Object("#2244cc");
        $grey = new color_Object("#bbb");

        $progressPx = min(100, round(100 * $rec->progress));
        $progressRemainPx = 100 - $progressPx;
        $row->progressBar = "<div style='display:inline-block;top:-5px;border-bottom:solid 10px {$blue}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'> </div>";
        
        if($rec->timeEnd && ($rec->state != 'closed' && $rec->state != 'rejected')) {
            $rec->remainingTime = round((dt::mysql2timestamp($rec->timeEnd) - time()) / 60) * 60;
            $typeTime = cls::get('type_Time');
            if($rec->remainingTime > 0) {
                $row->remainingTime = ' (' . tr('остават') . ' ' . $typeTime->toVerbal($rec->remainingTime) . ')';
            } else {
                 $row->remainingTime = ' (' . tr('просрочване с') . ' ' . $typeTime->toVerbal(-$rec->remainingTime) . ')';
            }
        }
 
        $grey->setGradient($blue, $rec->progress);
 
        $row->progress = "<span style='color:{$grey};'>{$row->progress}</span>";
        
        // Ако имаме само начална дата на задачата
        if($rec->timeStart && !$rec->timeEnd){
        	// я парвим хипервръзка към календара- дневен изглед
        	$row->timeStart = ht::createLink($row->timeStart, array('cal_Calendar', 'day', 'from' => $row->timeStart, 'Task' => 'true'), NULL, "ef_icon=img/16/calendar5.png");
          // Ако имаме само крайна дата на задачата 
        } elseif ($rec->timeEnd && !$rec->timeStart) {
        	// я правим хипервръзка към календара - дневен изглед
        	$row->timeEnd = ht::createLink($row->timeEnd, array('cal_Calendar', 'day', 'from' => $row->timeEnd, 'Task' => 'true'), NULL, "ef_icon=img/16/calendar5.png");
          // Ако задачата е с начало и край едновременно
        } elseif($rec->timeStart && $rec->timeEnd) {
        	// и двете ги правим хипервръзка към календара - дневен изглед 
        	$row->timeStart = ht::createLink($row->timeStart, array('cal_Calendar', 'day', 'from' => $row->timeStart, 'Task' => 'true'), NULL, "ef_icon=img/16/calendar5.png");
        	$row->timeEnd = ht::createLink($row->timeEnd, array('cal_Calendar', 'day', 'from' => $row->timeEnd, 'Task' => 'true'), NULL, "ef_icon=img/16/calendar5.png");
        }
    }


    /**
     * Показване на задачите в портала
     */
    static function renderPortal($userId = NULL)
    {
        
        if(empty($userId)) {
            $userId = core_Users::getCurrent();
        }
                
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Създаваме заявката
        $data->query = self::getQuery();
        
        // Подготвяме полетата за показване
        $data->listFields = 'timeStart,title,progress';

        $now = dt::verbal2mysql();
        
        if(Mode::is('listTasks', 'by')) {
            $data->query->where("#createdBy = $userId AND (#timeStart < '{$now}' || #timeStart IS NULL)");
        } else {
            $data->query->where("#sharedUsers LIKE '%|{$userId}|%' AND (#timeStart < '{$now}' || #timeStart IS NULL)");
        }

        $data->query->where("#state = 'active'");
        $data->query->orderBy("timeStart=DESC");
        
        // Подготвяме навигацията по страници
        self::prepareListPager($data);
        
        // Подготвяме филтър формата
        self::prepareListFilter($data);
        
        // Подготвяме записите за таблицата
        self::prepareListRecs($data);
 
        if (is_array($data->recs)) {
            foreach($data->recs  as   &$rec) {
                $rec->state = '';
            }    
        }

        $Tasks = cls::get('cal_Tasks');

        $Tasks->load('plg_GroupByDate');
        
        // Подготвяме редовете на таблицата
        self::prepareListRows($data);
        
        $tpl = new ET("
            [#PortalPagerTop#]
            [#PortalTable#]
          ");
        
        // Попълваме таблицата с редовете
        
    	if($data->listFilter && $data->pager->pagesCount > 1){
    		$formTpl = $data->listFilter->renderHtml();
    		$formTpl->removeBlocks();
    		$formTpl->removePlaces();
        	$tpl->append($formTpl, 'ListFilter');
        }
        
        $tpl->append(self::renderListTable($data), 'PortalTable');

        return $tpl;
    }


    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	$cu = core_Users::getCurrent();
        $rec = $form->rec;
  
        $rec->allDay = (strlen($rec->timeStart) == 10) ? 'yes' : 'no';

        if($rec->timeStart && $rec->timeEnd && ($rec->timeStart > $rec->timeEnd)) {
            $form->setError('timeEnd', 'Не може крайния срок да е преди началото на задачата');
        }
        
        // при активиране на задачата
        if($rec->state == 'active'){
        	
        	// проверява дали сме и задали начало и край
        	// или сме и задали начало и продължителност
        	if(($rec->timeStart && $rec->timeEnd) || ($rec->timeStart && $rec->timeDuration))
        	{
        		// ако имаме зададена продължителност
        		if($rec->timeDuration){
        			
        			// то изчисляваме края на задачата
        			// като към началото добавяме продължителността
        			$taskEnd = dt::timestamp2Mysql(dt::mysql2timestamp($rec->timeStart) + $rec->timeDuration);
        		} else {
        			$taskEnd = $rec->timeEnd;
        		}
        		
        		// правим заявка към базата
        		$query = self::getQuery();
        		
        		// търсим всички задачи, които са шернати на текущия потребител
        		// и имат някаква стойност за начало и край
        		// или за начало и продължителност
        		$query->likeKeylist('sharedUsers', $rec->sharedUsers);
        		
        		if($rec->id) {
        			$query->where("#id != {$rec->id}");
        		}
        		
        		$query->where("(#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$rec->timeStart}' AND #timeEnd >= '{$rec->timeStart}')
        		              OR
        		              (#timeStart IS NOT NULL AND #timeDuration IS NOT NULL  AND #timeStart <= '{$rec->timeStart}' AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) >= '{$rec->timeStart}')
        		              OR
        		              (#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$taskEnd}' AND #timeEnd >= '{$taskEnd}')
        		              OR
        		              (#timeStart IS NOT NULL AND #timeDuration IS NOT NULL AND #timeStart <= '{$taskEnd}' AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) >= '{$taskEnd}')");
        		
        		
        		$query->where("#state = 'active'");
        		
        		// за всяка една задача отговаряща на условията проверяваме 
        		if ($recTask = $query->fetch()){
        		 
        			// и изписваме предупреждение 
        		 	$form->setWarning('timeStart, timeDuration, timeEnd', 'Има колизия във времената на задачата');
        		}
        	}
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
    	$mvc->updateTaskToCalendar($rec->id);
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
        if($data->rec->state == 'active') {
            $data->toolbar->addBtn('Прогрес', array('cal_TaskProgresses', 'add', 'taskId' => $data->rec->id, 'ret_url' => array('cal_Tasks', 'single', $data->rec->id)), 'ef_icon=img/16/progressbar.png');
            $data->toolbar->addBtn('Напомняне', array('cal_Reminders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), 'ef_icon=img/16/bell_clock2.png, row=2');
        }
        
        // ако имаме зададена продължителност
    	if($data->rec->timeDuration){
        			
	        // то изчисляваме края на задачата
	        // като към началото добавяме продължителността
	        $taskEnd = dt::timestamp2Mysql(dt::mysql2timestamp($data->rec->timeStart) + $data->rec->timeDuration);
	    } else {
	        $taskEnd = $data->rec->timeEnd;
        }
        // изчислява продължителността в секунди
        $durations = dt::mysql2timestamp($taskEnd) - dt::mysql2timestamp($data->rec->timeStart);
        
        // ако имаме бутон "Активиране"
        if(isset($data->toolbar->buttons['Активиране'])) {
        	
        	// заявка към базата
        	$query = self::getQuery();
        	
        	// при следните условия
        	$query->likeKeylist('sharedUsers', $data->rec->sharedUsers);
        	$query->where("#timeEnd >= '{$data->rec->timeStart}' AND #timeStart <= '{$taskEnd}'");
        	$query->where("#timeDuration >= '{$durations}' AND #timeStart <= '{$taskEnd}'");
        	
        	// и намерим такъв запис
        	if($query->fetch()){ 
        		// променяме бутона "Активиране"
        		$data->toolbar->buttons['Активиране']->error = "Има колизия във времената на задачата";
        	}
        }
        
    }


    /**
     * След изтриване на запис
     */
    static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {        
        foreach($query->getDeletedRecs() as $id => $rec) {
 
            // изтриваме всички записи за тази задача в календара
            $mvc->updateTaskToCalendar($rec->id);
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
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId)
    {
    	if($action == 'postpone'){
	    	if ($rec->id) {
	        	if ($rec->state !== 'active' || (!$rec->timeStart) ) { 
	                $requiredRoles = 'no_one';
	            }  else {
	                if(!haveRole('ceo') || ($userId !== $rec->createdBy) &&
	                !keylist::isIn($userId, $rec->sharedUsers)) {
	                	
	                	$requiredRoles = 'no_one';
	                }
	            }
    	     }
         }
    }
    
    
    /**
     * Прилага филтъра, така че да се показват записите за определение потребител
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$data->query->orderBy("#timeStart=ASC,#state=DESC");
        
        if($data->action == 'list'){
            if($data->listFilter->rec->selectedUsers != 'all_users') {
	            $data->query->likeKeylist('sharedUsers', $data->listFilter->rec->selectedUsers);
            }
        }
    }
    
    
    /**
     * Игнорираме pager-а
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_BeforePrepareListPager($mvc, &$res, $data) {
    	// Ако искаме да видим графиката на структурата
    	// не ни е необходимо страницирване
    	if(Request::get('Chart')  == 'Gantt') {
    		// Задаваме броя на елементите в страница
            $mvc->listItemsPerPage = 1000000;
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
        $data->listFilter->FNC('Chart', 'varchar', 'caption=Таблица,input=hidden,silent', array('attr' => array('onchange' => 'this.form.submit();'), 'value' => Request::get('Chart')));
          
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->input('selectedUsers, Chart', 'silent');

        if(!$data->listFilter->rec->selectedUsers) {
            $data->listFilter->rec->selectedUsers = keylist::fromArray(arr::make(core_Users::getCurrent('id'), TRUE));
	  	}
        
	  	if($data->listFilter->rec->Chart == 'Gantt'){
	  		$data->listFilter->rec->selectedUsers = 'all_users';
	  	}
	  	
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        if($data->action === "list"){
        	$data->listFilter->showFields = 'search, selectedUsers';
        } else{
        	$data->listFilter->showFields = 'selectedUsers';
        }
    }

    
    /**
     * Ако няма записи не вади таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
        if(Mode::is('listTasks', 'by') || Mode::is('listTasks', 'to')) {
            
           // return FALSE;
        }
    }
    
	/**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_AfterRenderListTable($mvc, &$tpl, $data)
    {   
    	/*$chartType = Request::get('Chart');
    	
    	$tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
        
        $tabs->TAB('List', 'Таблица', array($mvc, 'list', 'Chart'=> 'List'));

        $tabs->TAB('Gantt', 'Гант', array($mvc, 'list', 'Chart'=> 'Gantt'));
       
        if($chartType == 'Gantt') {
        	
        	$tpl = static::getGantt($data);
        }
        
        $tpl = $tabs->renderHtml($tpl, $chartType);
               
        $mvc->currentTab = 'Задачи';*/
    	

    }
    
    
    /**
     * Обновява информацията за задачата в календара
     */
    static function updateTaskToCalendar($id)
    {
        $rec = static::fetch($id);
        
        $events = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);

        // Начална дата
        $fromDate = "{$cYear}-01-01";

        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
        
        // Префикс на клучовете за записите в календара от тази задача
        $prefix = "TSK-{$id}";

        // Подготвяме запис за началната дата
        if($rec->timeStart && $rec->timeStart >= $fromDate && $rec->timeStart <= $toDate && ($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'draft')) {
            
            $calRec = new stdClass();
                
            // Ключ на събитието
            $calRec->key = $prefix . '-Start';
            
            // Начало на задачата
            $calRec->time = $rec->timeStart;
            
            // Дали е цял ден?
            $calRec->allDay = $rec->allDay;
            
            // Икона на записа
            $calRec->type  = 'task';

            // Заглавие за записа в календара
            $calRec->title = "{$rec->title}";

            // В чии календари да влезе?
            $calRec->users = $rec->sharedUsers;
            
            // Статус на задачата
            $calRec->state = $rec->state;

            // Какъв да е приоритета в числово изражение
            $calRec->priority = self::getNumbPriority($rec);

            // Url на задачата
            $calRec->url = array('cal_Tasks', 'Single', $id); 
            
            $events[] = $calRec;
        }
        
        // Подготвяме запис за Крайния срок
        if($rec->timeEnd && $rec->timeEnd >= $fromDate && $rec->timeEnd <= $toDate && ($rec->state == 'active' || $rec->state == 'closed') ) {
            
            $calRec = new stdClass();
                
            // Ключ на събитието
            $calRec->key = $prefix . '-End';
            
            // Начало на задачата
            $calRec->time = $rec->timeEnd;
            
            // Дали е цял ден?
            $calRec->allDay = $rec->allDay;
            
            // Икона на записа
            $calRec->type  = 'end-date';

            // Заглавие за записа в календара
            $calRec->title = "Краен срок за \"{$rec->title}\"";

            // В чии календари да влезе?
            $calRec->users = $rec->sharedUsers;
            
            // Статус на задачата
            $calRec->state = $rec->state;
            
            // Какъв да е приоритета в числово изражение
            $calRec->priority = self::getNumbPriority($rec) - 1;

            // Url на задачата
            $calRec->url = array('cal_Tasks', 'Single', $id); 
            
            $events[] = $calRec;
        }
  
        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
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
     * Връща иконата на документа
     */
    function getIcon_($id)
    {
        $rec = self::fetch($id);

        return "img/16/task-" . $rec->priority . ".png";
    }

    
    /**
     * Изпращане на нотификации за започването на задачите
     */
    function cron_SendNotifications()
    {
        $query = $this->getQuery();
        $now = dt::verbal2mysql();
        $query->where("#state = 'active'  AND #notifySent = 'no' AND #timeStart <= '{$now}'");
        
        while($rec = $query->fetch()) {
            list($date, $time) = explode(' ', $rec->timeStart);  
            if($time != '00:00:00') {
                $subscribedArr = keylist::toArray($rec->sharedUsers); 
                if(count($subscribedArr)) { 
                    $message = "Стартирана е задачата \"" . $this->getVerbal($rec, 'title') . "\"";
                    $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                    $customUrl = array('cal_Tasks', 'single',  $rec->id);
                    $priority = 'normal';
                    foreach($subscribedArr as $userId) {  
                        if($userId > 0  &&  
                            doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
                            bgerp_Notifications::add($message, $url, $userId, $priority, $customUrl);
                        }
                    }
                }
            }

            $rec->notifySent = 'yes';

            $this->save($rec, 'notifySent');
        }
    }


    /**
     * Сменя задачите в сесията между 'поставените към', на 'поставените от' и обратно
     */
    function act_SwitchByTo()
    {
        if(Mode::is('listTasks', 'by')) {
            Mode::setPermanent('listTasks', 'to');
        } else {
            Mode::setPermanent('listTasks', 'by');
        }

        return new Redirect(array('Portal', 'Show', '#' => Mode::is('screenMode', 'narrow') ? 'switchTasks' : NULL));
    }


    /**
     * Изпълнява се след начално установяване
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $Cron = cls::get('core_Cron');
        
        $rec = new stdClass();
        $rec->systemId = "StartTasks";
        $rec->description = "Известява за стартирани задачи";
        $rec->controller = "cal_Tasks";
        $rec->action = "SendNotifications";
        $rec->period = 1;
        $rec->offset = 0;
        
        $Cron->addOnce($rec);
        
        $res .= "<li>Известяване за стартирани задачи по крон</li>";
        
        //Създаваме, кофа, където ще държим всички прикачени файлове в задачи
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('calTasks', 'Прикачени файлове в задачи', NULL, '104857600', 'user', 'user');
    }
 
}