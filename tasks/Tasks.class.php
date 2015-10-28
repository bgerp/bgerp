<?php



/**
 * Мениджър на задачи
 *
 *
 * @category  bgerp
 * @package   tasks
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задачи за производство
 */
class tasks_Tasks extends embed_Manager
{
    
    
    /**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'tasks_DriverIntf';
	
	
    /**
     * Заглавие
     */
    public $title = 'Задачи';
    
    
    /**
     * Еденично заглавие
     */
    public $singleTitle = 'Задача';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, doc_DocumentPlg, planning_plg_StateManager, acc_plg_DocumentSummary, plg_Search, change_Plugin, plg_Clone, plg_Sorting';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
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
	public $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'powerUser';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, name = Документ, originId=Задание, title, expectedTimeStart,timeStart, timeDuration, timeEnd, progress, state';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'originId';
    
    
    /**
     * Детайли
     */
    public $details = 'tasks_TaskConditions';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'tasks/tpl/SingleLayoutTask.shtml';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Поле за начало на търсенето
     */
    public $filterFieldDateFrom = 'timeStart';
    
    
    /**
     * Поле за крайна дата на търсене
     */
    public $filterFieldDateTo = 'timeEnd';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title';
    
    
    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'powerUser';
    
    
    /**
     * Кой може да променя записа?
     */
    public $canChangerec = 'powerUser';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/task-normal.png';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar(128)', 'caption=Заглавие,width=100%,changable,silent');
    	
    	$this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)',
    			'caption=Времена->Начало, silent, changable, tdClass=leftColImportant,formOrder=101');
    	$this->FLD('timeDuration', 'time', 'caption=Времена->Продължителност,changable,formOrder=102');
    	$this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)', 'caption=Времена->Край,changable, tdClass=leftColImportant,formOrder=103');
    	$this->FLD('progress', 'percent', 'caption=Прогрес,input=none,notNull,value=0');
    	$this->FLD('systemId', 'int', 'silent,input=hidden');
    	$this->FLD('expectedTimeStart', 'datetime', 'silent,input=hidden,caption=Очаквано начало');
    	
    	$this->FLD('classId', 'key(mvc=core_Classes)', 'input=hidden,notNull');
    	
    	$this->setDbIndex('classId');
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(is_object($rec)){
    		static::fillGapsInRec($rec);
    	}
    }
    
    
    /**
     * Подготвяне на вербалните стойности
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	
    	$red = new color_Object("#FF0000");
    	$blue = new color_Object("green");
    	$grey = new color_Object("#bbb");
    	
    	$progressPx = min(100, round(100 * $rec->progress));
    	$progressRemainPx = 100 - $progressPx;
    	
    	$color = ($rec->progress <= 1) ? $blue : $red;
    	$row->progressBar = "<div style='white-space: nowrap; display: inline-block;'><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$color}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'></div></div>";
    
    	$grey->setGradient($color, $rec->progress);
    	$row->progress = "<span style='color:{$grey};'>{$row->progress}</span>";
    	
    	$row->name = $mvc->getLink($rec->id, 0);
    	
    	if ($rec->timeEnd && ($rec->state != 'closed' && $rec->state != 'rejected')) {
    		$remainingTime = dt::mysql2timestamp($rec->timeEnd) - time();
    		$rec->remainingTime = cal_Tasks::roundTime($remainingTime);
    	
    		$typeTime = cls::get('type_Time');
    		if ($rec->remainingTime > 0) {
    			$row->remainingTime = ' (' . tr('остават') . ' ' . $typeTime->toVerbal($rec->remainingTime) . ')';
    		} else {
    	
    			$row->remainingTime = ' (' . tr('просрочване с') . ' ' . $typeTime->toVerbal(-$rec->remainingTime) . ')';
    		}
    	}
    	
    	$row->timeStart = str_replace('00:00', '', $row->timeStart);
    	$row->timeEnd = str_replace('00:00', '', $row->timeEnd);
    	
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    		// Ако имаме само начална дата на задачата
    		if ($rec->timeStart && !$rec->timeEnd) {
    			// я парвим хипервръзка към календара- дневен изглед
    			$row->timeStart = ht::createLink(dt::mysql2verbal($rec->timeStart, 'smartTime'), array('cal_Calendar', 'day', 'from' => $row->timeStart, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
    			// Ако имаме само крайна дата на задачата
    		} elseif ($rec->timeEnd && !$rec->timeStart) {
    			// я правим хипервръзка към календара - дневен изглед
    			$row->timeEnd = ht::createLink(dt::mysql2verbal($rec->timeEnd, 'smartTime'), array('cal_Calendar', 'day', 'from' => $row->timeEnd, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
    			// Ако задачата е с начало и край едновременно
    		} elseif ($rec->timeStart && $rec->timeEnd) {
    			// и двете ги правим хипервръзка към календара - дневен изглед
    			$row->timeStart = ht::createLink(dt::mysql2verbal($rec->timeStart, 'smartTime'), array('cal_Calendar', 'day', 'from' => $row->timeStart, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
    			$row->timeEnd = ht::createLink(dt::mysql2verbal($rec->timeEnd, 'smartTime'), array('cal_Calendar', 'day', 'from' => $row->timeEnd, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
    		}
    	}
    	
    	
    	// Ако е изчислено очакваното начало и има продължителност, изчисляваме очаквания край
    	if(isset($rec->expectedTimeStart) && isset($rec->timeDuration)){
    		$expectedTimeEnd = dt::addSecs($rec->timeDuration, $rec->expectedTimeStart);
    		$row->expectedTimeEnd = $mvc->getFieldType('expectedTimeStart')->toVerbal($expectedTimeEnd);
    	}
    	
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    		if (isset($rec->expectedTimeStart)) {
    			$row->expectedTimeStart = ht::createLink(dt::mysql2verbal($rec->expectedTimeStart, 'smartTime'), array('cal_Calendar', 'day', 'from' => $row->expectedTimeStart, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
    		}
    		 
    		if (isset($expectedTimeEnd)) {
    			$row->expectedTimeEnd = ht::createLink(dt::mysql2verbal($expectedTimeEnd, 'smartTime'), array('cal_Calendar', 'day', 'from' => $row->expectedTimeEnd, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
    		}
    	}
    	
    	if($rec->originId){
    		$origin = doc_Containers::getDocument($rec->originId);
    		$originId = (!Mode::is('text', 'xhtml') && !Mode::is('printing')) ? $origin->getLink(0) : "#" . $origin->getHandle();
    		$row->originId = $originId;
    	}
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    
    	$row->title    = self::getRecTitle($rec);
    	$row->authorId = $rec->createdBy;
    	$row->author   = $this->getVerbal($rec, 'createdBy');
    	$row->recTitle = $row->title;
    	$row->state    = $rec->state;
    	$row->subTitle = self::getVerbal($rec, 'title');
    	
    	return $row;
    }
    
    
    /**
     * Прави заглавие на МО от данните в записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$me = cls::get(get_called_class());
    	
    	return $me->singleTitle . " №{$rec->id}";
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if(isset($rec->originId) && isset($rec->innerClass)){
    		$form->setReadOnly('innerClass');
    	}
    	
    	if($form->isSubmitted()){
    		
    		if(empty($rec->title)){
				if(cls::load($rec->driverClass, TRUE)){
    				if($Driver = cls::get($rec->driverClass)){
    					$rec->title = $Driver->getDefaultTitle();
    				}
    			}
    		}
    		
    		if ($rec->timeStart && $rec->timeEnd && ($rec->timeStart > $rec->timeEnd)) {
    			$form->setError('timeEnd', 'Крайния срок трябва да е преди началото на задачата');
    		}
    		
    		if(!empty($rec->timeStart) && !empty($rec->timeDuration) && !empty($rec->timeEnd)){
    			if(strtotime(dt::addSecs($rec->timeDuration, $rec->timeStart)) != strtotime($rec->timeEnd)){
    				$form->setWarning('timeStart,timeDuration,timeEnd', 'Въведеното начало плюс продължителноста не отговарят на въведената крайната дата');
    			}
    		}
    	}
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
    	$rec = $this->fetch($id);
    	
    	// Даваме възможност на драйвера да обнови мастъра ако иска
    	if($Driver = $this->getDriver($id)){
    		$Driver->updateEmbedder($rec);
    	}
    	
    	$rec->expectedTimeStart = $this->getExpectedTimeStart($rec);
    	
    	return $this->save($rec);
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$data->query->where("#classId = {$mvc->getClassId()}");
    	
    	// Добавяме поле за търсене по състояние
    	if(!Request::get('Rejected', 'int')){
    		$data->listFilter->setOptions('state', array('' => '') + arr::make('draft=Чернова, active=Активно, pending=Чакащо, pendingandactive=Активно+Чакащо,closed=Приключено, stopped=Спряно, wakeup=Събудено', TRUE));
    		$data->listFilter->setField('state', 'placeholder=Всички');
    		$data->listFilter->showFields .= ',state';
    		$data->listFilter->input('state');
    		 
    		if($state = $data->listFilter->rec->state){
    			if($state != 'pendingandactive'){
    				$data->query->where("#state = '{$state}'");
    			} else {
    				$data->query->where("#state = 'active' || #state = 'pending'");
    			}
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	// Може да се променя само ако състоянието на задачата е активно или чакащо
    	if($action == 'changerec' && isset($rec)){
    		if($rec->state != 'pending' && $rec->state != 'active'){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($action == 'add'){
    		if(isset($rec->originId)){
    			
    			// Може да се добавя само към активно задание
    			$origin = doc_Containers::getDocument($rec->originId);
    			
    			if(!$origin->isInstanceOf('planning_Jobs')){
    				$requiredRoles = 'no_one';
    			} else {
    				if($origin->fetchField('state') != 'active'){
    					$requiredRoles = 'no_one';
    				}
    			}
    		}
    		
    		// Ако потребителя не може да избере поне една опция, той не може да добави задача
    		$interfaces = $mvc::getAvailableDriverOptions($userId);
    		if(!count($interfaces)){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// Ако нямаме права за драйвера на документа, не можем да правим нищо с него
    	if($action == 'clonerec' || $action == 'add' || $action == 'edit' || $action == 'reject' || $action == 'restore' || $action == 'changestate' || $action == 'changerec'){
    		
    		if(isset($rec->driverClass)){
    			if(cls::load($rec->driverClass, TRUE)){
    				$Driver = cls::get($rec->driverClass);
    				if(!cls::haveInterface('tasks_DriverIntf', $Driver)){
    					$requiredRoles = 'no_one';
    				} else {
    					if(!$Driver->canSelectDriver()){
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
    protected static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
    	if($data->rec->state == 'active' || $data->rec->state == 'pending'){
    		if(cal_Reminders::haveRightFor('add', (object)array('originId' => $data->rec->containerId))){
    			$data->toolbar->addBtn('Напомняне', array('cal_Reminders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), 'ef_icon=img/16/rem-plus.png, row=2', 'title=Създаване на ново напомняне');
    		}
    	}
    	
    	if(tasks_TaskConditions::haveRightFor('add', (object)array('taskId' => $data->rec->id))){
    		$data->toolbar->addBtn('Условие', array('tasks_TaskConditions', 'add', 'taskId' => $data->rec->id, 'ret_url' => TRUE), 'ef_icon=img/16/task-option.png', 'title=Добавяне на условие за стартиране');
    	}
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$cu = core_Users::getCurrent();
    	$form->setDefault('inCharge', keylist::addKey('', $cu));
    	$form->setDefault('classId', $mvc->getClassId());
    	
    	if(isset($rec->originId)){
    		$origin = doc_Containers::getDocument($rec->originId);
    		
    		// Ако задачата идва от дефолт задача на продуктов драйвер
    		if(isset($rec->systemId)){
    			$productId = $origin->fetchField('productId');
    			$ProductDriver = cat_Products::getDriver($productId);
    			
    			// Намираме препоръчителните задачи за драйвера
    			$taskInfoArray = $ProductDriver->getDefaultJobTasks();
    			
    			// Задаваме дефолтите на задачата
    			if(isset($taskInfoArray[$rec->systemId])){
    				$params = (array)$taskInfoArray[$rec->systemId];
    				if(is_array($params)){
    					foreach ($params as $key => $value){
    						$form->setDefault($key, $value);
    					}
    				}
    			}
    			
    			// Щом задачата е от препоръчителните на продуктовия драйвера, 
    			// не може да се сменя класа на драйвера на задачата
    			$form->setReadOnly('driverClass');
    		}
    	}
    }
    
    
    /**
     * Ако са въведени две от времената (начало, продължителност, край) а третото е празно, изчисляваме го.
     * ако е въведено само едно време или всички не правим нищо
     * 
     * @param stdClass $rec - записа който ще попълним
     * @return void
     */
	private static function fillGapsInRec(&$rec)
	{
		if(isset($rec->timeStart) && isset($rec->timeDuration) && empty($rec->timeEnd)){
			
			// Ако има начало и продължителност, изчисляваме края
			$rec->timeEnd = dt::addSecs($rec->timeDuration, $rec->timeStart);
		} elseif(isset($rec->timeStart) && isset($rec->timeEnd) && empty($rec->timeDuration)) {
			
			// Ако има начало и край, изчисляваме продължителността
			$rec->timeDuration = $diff = strtotime($rec->timeEnd) - strtotime($rec->timeStart);
		} elseif(isset($rec->timeDuration) && isset($rec->timeEnd) && empty($rec->timeStart)) {
			
			// Ако има продължителност и край, изчисляваме началото
			$rec->timeStart = dt::addSecs(-1 * $rec->timeDuration, $rec->timeEnd);
		}
	}

	
	/**
	 * Дали задачата може да се активира
	 * Ако задачата има прогрес или очакваното и начало е <= текущото време, тя е готова за активация
	 * 
	 * @param stdClass $rec - запис на задачата
	 * @return boolean - можели да се активира или не
	 */
	public function activateNow_($rec)
	{
		// Ако задачата има прогрес или очакваното и начало е <= текущото време, тя е готова за активация
		$res = ($rec->progress || $rec->expectedTimeStart <= dt::now());
		
		return $res;
	}
	
	
	/**
	 * По разписание променя състоянията на задачите, взависимост
	 * от зададените им условия за активиране
	 * 
	 * Вземат се активните и чакащите задачи от най-старите към най-новите(*).
	 * Всички задачи, които за които са въведени 2 от параметрите Начало, Край и Продължителност, третия параметър
	 * се изчислява на базата на другите два. За всички редове от детайла на всяка задача, се калкулира
	 * последното поле (calcTime) като функция calcTime = getExpectedTime($offset, $progress, dependsOn->Очаквано начало, dependsOn->Продължителност, $dependsOn->текущ прогрес)
	 * На всички тях се определя [Начало (изчислено)] = Max(Начало, и calcTimes на всичките редове от детайла на задачата).
	 * Ако при едно от присвояванията от предходния ред, има промяна на стойност, то целия цикъл по всички задачи.
	 * 
	 * След като цикъла мине без повече да има нови присвоявания на различни стойности, задачите се поставят в състояние, според Очаквано начало:
	 *  - активно - всички при които този параметър не е изчислен или е по-малък или равен на текущото време, или имат прогрес > 0
	 *  - чакащо - всички останали
	 *  
	 *  @return void
	 */
    public function cron_UpdateTasksStates()
    {
    	// Намираме чакащите и активните задачи от най-старата към най-новата
    	$query = self::getQuery();
    	$query->where("#state = 'active' || #state = 'pending' || #state = 'stopped'");
    	$query->orderBy('id', 'ASC');
    	
    	$recs = $query->fetchAll();
    	
    	// Ако няма записи не правим нищо
    	if(!count($recs)) return;
    	
    	$count = 0;
    	$expectedTimes = array();
    	$calcedTimes = array();
    	
    	// Първоначално попълване на очакваните начални времена
    	foreach ($recs as $rec2){
    		$expectedTimes[$rec2->id] = $rec2->expectedTimeStart;
    	}
    	
    	do{
    		// Нулираме флага да сме сигурни, че няма да влезем във вечен цикъл
    		$repeat = FALSE;
    		$count++;
    		
    		// За всяка задача
    		foreach ($recs as &$rec){
    			 
    			// Изчисляваме очакваното начало за задачата наново
    			$max = $this->getExpectedTimeStart($rec, $expectedTimes, $calcedTimes);
    			
    			// Ако предишно изчисленото очаквано начало е различно от текущото
    			if($expectedTimes[$rec->id] !== $max){
    				//echo "<li><b>{$rec->id}</b> old: '{$expectedTimes[$rec->id]}' new: '{$max}'";
    		
    				// Записваме новото време
    				$expectedTimes[$rec->id] = $max;
    		
    				// Имало е промяна, дигаме флага за преизчисляване
    				$repeat = TRUE;
    			}
    		}
    		
    		if($repeat){
    			//echo "<li>REPEAT";
    		} 
    		
    	// Докато флага е сетнат преизчисляваме очакваното начало на задачите
    	// Докато спрат да се присвояват нови времена
    	} while ($repeat);
    	
    	// Обновяваме изчисленото време на условията
    	if(count($calcedTimes)){
    		$timesToSave = array();
    		foreach ($calcedTimes as $conditionId => $calcTime){
    			$timesToSave[] = (object)array('id' => $conditionId, 'calcTime' => $calcTime);
    		}
    		
    		// Записваме изчисленото време
    		cls::get('tasks_TaskConditions')->saveArray($timesToSave, 'id,calcTime');
    	}
    	
    	// Обновяваме състоянието и очакваното начало на задачите
    	foreach ($recs as &$rec1){
    		$rec1->expectedTimeStart = $expectedTimes[$rec1->id];
    		
    		// Ако условията са изпълнени за активиране, активираме задачата иначе я правим чакаща
    		$rec1->state = ($this->activateNow($rec1)) ? 'active' : 'pending';
    	}
    	
    	$this->saveArray($recs);
    }
    
    
    /**
     * Намира очакваното време за изпълнение спрямо условията.
     * Това е максимума на началото на задачата и максимума на изчислените
     * времена на условията към нея
     * 
     * @param stdClass $taskRec - запис на задача
     * @param array $expectedTimes - масив с изчислени времена за стартиране
     * @param array $calcedTimes - изчислените времена на условията
     * @return datetime - датата на очакваното начало на задачата
     */
    private function getExpectedTimeStart($taskRec, $expectedTimes = array(), &$calcedTimes = array())
    {
    	// Допълваме и времената, ако има две въведени а третото го няма изчисляваме го
    	$rec = clone $taskRec;
    	self::fillGapsInRec($rec);
    	
    	// Намираме условията за стартиране свързани със задачата
    	$condTimes = array();
    	$condQuery = tasks_TaskConditions::getQuery();
    	$condQuery->where("#taskId = {$rec->id}");
    	
    	while($dRec = $condQuery->fetch()){
    		$dependsOnRec = self::fetch($dRec->dependsOn, 'expectedTimeStart,timeDuration,timeEnd,progress,state');
    	
    		// Ако е чернова или оттеглена я пренебрегваме
    		if($dependsOnRec->state == 'draft' || $dependsOnRec->state == 'rejected') continue;
    	
    		// Допълваме и времената за всеки случай
    		self::fillGapsInRec($dependsOnRec);
    			
    		// Ако има изчислено ново текущо време за нея, взимаме него а не старото
    		if(isset($expectedTimes[$dependsOnRec->id])){
    			$dependsOnRec->expectedTimeStart = $expectedTimes[$dependsOnRec->id];
    		}
    			
    		// За условието изчисляваме времето му на стартиране спрямо подадените данни
    		$dRec->calcTime = tasks_TaskConditions::getExpectedTime($dRec->offset, $dRec->progress, $dependsOnRec->expectedTimeStart, $dependsOnRec->timeDuration, $dependsOnRec->progress);
    		$calcedTimes[$dRec->id] = $dRec->calcTime;
    		$condTimes[] = $dRec->calcTime;
    	}
    	 
    	// Сортираме изчислените времена на условията във низходящ ред
    	rsort($condTimes);
    	
    	// Изчисляваме максималното време от началото на задачата и максималното начало на условие
    	$max = max($rec->timeStart, $condTimes[0]);
    	
    	// Връщаме изчисленото време
    	return $max;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	$Cover = doc_Folders::getCover($folderId);
    	
    	// Може да се добавя само в папка на 'Проект'
    	return ($Cover->isInstanceOf('doc_UnsortedFolders'));
    }


    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	// Може да се добавя само към нишка с начало задание
    	return $firstDoc->isInstanceOf('planning_Jobs');
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	if($rec->classId && cls::load($rec->classId, TRUE)){
    		$self = cls::get($rec->classId);
    		
    		return $self->abbr . $rec->id;
    	}
    	
    	return $id;
    }
    
    
    /**
     * Премахва от резултатите скритите от менютата за избор
     */
    public static function on_AfterMakeArray4Select($mvc, &$res, $fields = NULL, &$where = "", $index = 'id'  )
    {
    	if(is_array($res)){
    		foreach ($res as $id => &$title){
    			$title =  " {$title}" . " (#" . $mvc->getHandle($id) . ")";
    		}
    	}
    }
    
    
    /**
     * Връща позволените за избор драйвери според класа и потребителя
     *
     * @param mixed $userId - ид на потребител
     * @return array $interfaces - възможните за избор опции на класове
     */
    public static function getAvailableDriverOptions($userId = NULL)
    {
    	$me = get_called_class();
    	$options = parent::getAvailableDriverOptions($userId);
    	foreach ($options as $id => $title){
    		if(!cls::load($id, TRUE)) continue;
    		
    		// Ако драйвера не може да бъде добавен към ибзрания клас, махаме го
    		$Driver = cls::get($id);
    		$availableClasses = arr::make($Driver->availableClasses, TRUE);
    		if(!isset($availableClasses[$me])){
    			unset($options[$id]);
    		}
    	}
    	
    	return $options;
    }
    

    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    function prepareSingle_($data)
    {
    	$rec = $data->rec;
    	if($Driver = $this->getDriver($rec->id)){
    		$data->details = array_merge(arr::make($Driver->getDetail(), TRUE), arr::make($this->details, TRUE));
    	}
    		
    	parent::prepareSingle_($data);
    	
    	return $data;
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
        
        if(cls::load($rec->driverClass, TRUE)){
			if($Driver = cls::get($rec->driverClass)){
				if (method_exists($Driver, 'prepareFieldLetterHeaded')) {
				    $fieldArr = $Driver->prepareFieldLetterHeaded($rec, $row);
				    $resArr = array_merge($resArr, $fieldArr);
				}
			}
		}
    }
    
    
    /**
     * Преди клонирането на запис
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     * @param stdClass $nRec
     */
    public static function on_BeforeSaveCloneRec($mvc, $rec, &$nRec)
    {
    	unset($nRec->progress);
    }
}