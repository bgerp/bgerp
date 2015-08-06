<?php



/**
 * Мениджър на задачи за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задачи за производство
 */
class planning_Tasks extends embed_Manager
{
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'planning_TaskDetailIntf';
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_Tasks';
	
	
    /**
     * Заглавие
     */
    public $title = 'Задачи за производство';
    
    
    /**
     * Еденично заглавие
     */
    public $singleTitle = 'Задача за производство';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, doc_SharablePlg, doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, change_Plugin, plg_Clone, plg_Sorting';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,planning';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,planning';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, name = Документ, jobId, title, expectedTimeStart,timeStart, timeDuration, timeEnd, inCharge, progress, state';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'jobId';
    
    
    /**
     * Детайли
     */
    public $details = 'planning_TaskDetails, planning_TaskConditions';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutTask.shtml';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Pts';
    
    
    /**
     * Опашка за обновяване
     */
    protected $updated = array();
    
    
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
    public $searchFields = 'title,description';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "3.8|Производство";
    
    
    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'ceo, planning';
    
    
    /**
     * Кой може да променя записа?
     */
    public $canChangerec = 'ceo, planning';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/task-normal.png';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar(128)', 'caption=Заглавие,mandatory,width=100%,changable,silent');
    	
    	$this->FLD('priority', 'enum(low=Нисък,
                                    normal=Нормален,
                                    high=Висок,
                                    critical=Критичен)',
    			'caption=Приоритет,mandatory,maxRadio=4,columns=4,notNull,value=normal,silent');
    	$this->FLD('inCharge' , 'userList(roles=planning|ceo)', 'caption=Отговорници,mandatory,changable');
    	$this->FLD('description', 'richtext(bucket=calTasks,rows=3)', 'caption=Описание,changable');
    	
    	$this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)',
    			'caption=Времена->Начало, silent, changable, tdClass=leftColImportant,formOrder=101');
    	$this->FLD('timeDuration', 'time', 'caption=Времена->Продължителност,changable,formOrder=102');
    	$this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)', 'caption=Времена->Край,changable, tdClass=leftColImportant,formOrder=103');
    	$this->FLD('sharedUsers', 'userList', 'caption=Допълнително->Споделени,changable,formOrder=104');
    	$this->FLD('progress', 'percent', 'caption=Прогрес,input=none,notNull,value=0');
    	$this->FLD('jobId', 'key(mvc=planning_Jobs)', 'input=none,caption=По задание');
    	$this->FLD('fromProductArrayId', 'int', 'silent,input=hidden');
    	$this->FLD('expectedTimeStart', 'datetime(format=smartTime)', 'silent,input=hidden,caption=Очаквано начало');
    	
    	$this->setDbIndex('jobId');
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
    	$blue = new color_Object("#2244cc");
    	$grey = new color_Object("#bbb");
    	
    	$progressPx = min(100, round(100 * $rec->progress));
    	$progressRemainPx = 100 - $progressPx;
    	
    	$row->progressBar = "<div style='white-space: nowrap; display: inline-block;'><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$blue}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'></div></div>";
    
    	$grey->setGradient($blue, $rec->progress);
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
    	
    	$grey->setGradient($blue, $rec->progress);
    	
    	$row->progress = "<span style='color:{$grey};'>{$row->progress}</span>";
    	
    	$row->timeStart = str_replace('00:00', '', $row->timeStart);
    	$row->timeEnd = str_replace('00:00', '', $row->timeEnd);
    	
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
    	
    	// Ако е изчислено очакваното начало и има продължителност, изчисляваме очаквания край
    	if(isset($rec->expectedTimeStart) && isset($rec->timeDuration)){
    		$expectedTimeEnd = dt::addSecs($rec->timeDuration, $rec->expectedTimeStart);
    		$row->expectedTimeEnd = $mvc->getFieldType('expectedTimeStart')->toVerbal($expectedTimeEnd);
    	}
    	
    	if($rec->jobId){
    		$row->jobId = planning_Jobs::getLink($rec->jobId, 0);
    	}
    	
    	if($row->timeStart === ''){
    		unset($row->timeStart);
    	}
    	
    	if($row->timeEnd === ''){
    		unset($row->timeEnd);
    	}
    }
    
    
    /**
     * Подготвя задачите към заданията
     */
    public function prepareTasks($data)
    {
    	$data->recs = $data->rows = array();
    	
    	// Дали според продуктовия драйвер на артикула в заданието има дефолтни задачи
    	$ProductDriver = cat_Products::getDriver($data->masterData->rec->productId);
    	$defaultTasks = $ProductDriver->getDefaultJobTasks();
    	
    	// Намираме всички задачи към задание
    	$query = $this->getQuery();
    	$query->where("#state != 'rejected'");
    	$query->where("#jobId = {$data->masterId}");
    	$query->XPR('orderByState', 'int', "(CASE #state WHEN 'wakeup' THEN 1 WHEN 'active' THEN 2 WHEN 'stopped' THEN 3 WHEN 'closed' THEN 4 WHEN 'pending' THEN 5 ELSE 6 END)");
    	$query->orderBy('#orderByState=ASC');
    	
    	// Подготвяме данните
    	while($rec = $query->fetch()){
    		$data->recs[$rec->id] = $rec;
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    		
    		// Премахваме от масива с дефолтни задачи, тези с чието име има сега създадена задача
    		$title = $data->rows[$rec->id]->title;
    		if(isset($rec->fromProductArrayId)){
    			unset($defaultTasks[$rec->fromProductArrayId]);
    		}
    	}
    	
    	// Ако има дефолтни задачи, показваме ги визуално в $data->rows за по-лесно добавяне
    	if(count($defaultTasks)){
    		foreach ($defaultTasks as $index => $taskInfo){
    				
    				// Ако не може да бъде доабвена задача не показваме реда
    				if(!planning_Tasks::haveRightFor('add', (object)array('originId' => $data->masterData->rec->containerId, 'innerClass' => $taskInfo->driver))) continue;
    			
    				$url = array('planning_Tasks', 'add', 'originId' => $data->masterData->rec->containerId, 'driverClass' => $taskInfo->driverClass, 'fromProductArrayId' => $index, 'ret_url' => TRUE);
    				$row = new stdClass();
    				$row->title = $taskInfo->title;
    				$row->tools = ht::createLink('', $url, FALSE, 'ef_icon=img/16/add.png,title=Добавяне на нова задача');
    				$row->ROW_ATTR['style'] .= 'background-color:#f8f8f8;color:#777';
    				
    				$data->rows[] = $row;
    		}
    	}
    	
    	// Бутон за нова задача ако има права
    	if(planning_Tasks::haveRightFor('add', (object)array('originId' => $data->masterData->rec->containerId))){
    		$data->addUrl = array('planning_Tasks', 'add', 'originId' => $data->masterData->rec->containerId, 'ret_url' => TRUE);
    	}
    }
    
    
    /**
     * Рендира задачите на заданията
     */
    public function renderTasks($data)
    {
    	$tpl = new ET("");
    	
    	// Ако няма намерени записи, не се реднира нищо
    	// Рендираме таблицата с намерените задачи
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	$table->setFieldsToHideIfEmptyColumn('timeStart,timeDuration,timeEnd');
    	$tpl = $table->get($data->rows, 'tools=Пулт,progress=Прогрес,name=Документ,title=Заглавие,timeStart=Начало, timeDuration=Продължителност, timeEnd=Край, inCharge=Отговорник');
    		 
    	// Рендира броя на задачите
    	$count = count($data->recs);
    	$tpl->append("<small>($count)</small>", 'TASK_COUNT');
    	
    	// Добавя бутон за създаване на нова задача
    	if(isset($data->addUrl)){
    		$addBtn = ht::createBtn('Задача', $data->addUrl, FALSE, FALSE, 'title=Създаване на задача по заданието,ef_icon=img/16/task-normal.png');
    		$tpl->append("<div style='margin-top:8px'>{$addBtn}</div>");
    	}
    	
    	// Връщаме шаблона
    	return $tpl;
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
    		
    		// Запомняне кои документи трябва да се обновят
    		if($rec->id){
    			$mvc->updated[$rec->id] = $rec->id;
    		}
    		
    		if ($rec->timeStart && $rec->timeEnd && ($rec->timeStart > $rec->timeEnd)) {
    			$form->setError('timeEnd', 'Не може крайния срок да е преди началото на задачата');
    		}
    	}
    }
    
    
    /**
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
    	// Запомняне кои документи трябва да се обновят
    	$mvc->updated[$id] = $id;
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
    	if(count($mvc->updated)){
    		foreach ($mvc->updated as $id) {
    			$rec = $mvc->fetch($id);
    			
    			// Даваме възможност на драйвера да обнови мастъра ако иска
    			if($Driver = $mvc->getDriver($id)){
    				$Driver->updateEmbedder($rec);
    			}
    			
    			$rec->expectedTimeStart = $mvc->getExpectedTimeStart($rec);
    			$mvc->save($rec);
    		}
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	// Добавяме поле за търсене по състояние
    	if(!Request::get('Rejected', 'int')){
    		$data->listFilter->setOptions('state', array('' => '') + arr::make('draft=Чернова, active=Активно, pending=Чакащо, pandact=Активно+Чакащо,closed=Приключено, stopped=Спряно, wakeup=Събудено', TRUE));
    		$data->listFilter->setField('state', 'placeholder=Всички');
    		$data->listFilter->showFields .= ',state';
    		$data->listFilter->input('state');
    		 
    		if($state = $data->listFilter->rec->state){
    			if($state != 'pandact'){
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
    			if(!($origin->getInstance() instanceof planning_Jobs)){
    				$requiredRoles = 'no_one';
    			} else {
    				if($origin->fetchField('state') != 'active'){
    					$requiredRoles = 'no_one';
    				}
    			}
    			
    			if(isset($rec->innerClass)){
    				$Driver = cls::get($rec->innerClass);
    				if(!cls::haveInterface('planning_TaskDetailIntf', $Driver)){
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
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	if($rec->id){
    		$dQuery = planning_TaskDetails::getQuery();
    		$dQuery->where("#taskId = {$rec->id}");
    		
    		$detailsKeywords = '';
    		while($dRec = $dQuery->fetch()){
    			
    			// Добавяме данните от детайла към ключовите думи
    			$dRow = planning_TaskDetails::recToVerbal($dRec);
    			$detailsKeywords .= " " . plg_Search::normalizeText($dRow->operation);
    			if($dRec->code){
    				$detailsKeywords .= " " . plg_Search::normalizeText($dRow->code);
    			}
    			
    			if($dRec->fixedAsset){
    				$detailsKeywords .= " " . plg_Search::normalizeText($dRow->fixedAsset);
    			}
    		}
    		
    		// Добавяме новите ключови думи към старите
    		$res = " " . $res . " " . $detailsKeywords;
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
    	
    	if(planning_TaskConditions::haveRightFor('add', (object)array('taskId' => $data->rec->id))){
    		$data->toolbar->addBtn('Условие', array('planning_TaskConditions', 'add', 'taskId' => $data->rec->id, 'ret_url' => TRUE), 'ef_icon=img/16/task-option.png', 'title=Добавяне на условие за стартиране');
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
    	
    	if(isset($rec->originId)){
    		$origin = doc_Containers::getDocument($rec->originId);
    		$form->setDefault('jobId', $origin->that);
    		
    		// Ако задачата идва от дефолт задача на продуктов драйвер
    		if(isset($rec->fromProductArrayId)){
    			$productId = $origin->fetchField('productId');
    			$ProductDriver = cat_Products::getDriver($productId);
    			
    			// Намираме препоръчителните задачи за драйвера
    			$taskInfoArray = $ProductDriver->getDefaultJobTasks();
    			
    			// Задаваме дефолтите на задачата
    			if(isset($taskInfoArray[$rec->fromProductArrayId])){
    				$params = (array)$taskInfoArray[$rec->fromProductArrayId];
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
     * Преди запис на документ
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	// Добавяме отговорниците към споделените
    	$rec->sharedUsers = keylist::merge($rec->sharedUsers, $rec->inCharge);
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
	 *  от зададените им условия за активиране
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
    public function cron_CheckTasks()
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
    		cls::get('planning_TaskConditions')->saveArray($timesToSave, 'id,calcTime');
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
     * @param stdClass $rec - запис на задача
     * @param array $expectedTimes - масив с изчислени времена за стартиране
     * @param array $calcedTimes - изчислените времена на условията
     * @return datetime - датата на очакваното начало на задачата
     */
    private function getExpectedTimeStart($rec, $expectedTimes = array(), &$calcedTimes = array())
    {
    	// Допълваме и времената, ако има две въведени а третото го няма изчисляваме го
    	self::fillGapsInRec($rec);
    	
    	// Намираме условията за стартиране свързани със задачата
    	$condTimes = array();
    	$condQuery = planning_TaskConditions::getQuery();
    	$condQuery->where("#taskId = {$rec->id}");
    	
    	while($dRec = $condQuery->fetch()){
    		$dependsOnRec = planning_Tasks::fetch($dRec->dependsOn, 'expectedTimeStart,timeDuration,timeEnd,progress,state');
    	
    		// Ако е чернова или оттеглена я пренебрегваме
    		if($dependsOnRec->state == 'draft' || $dependsOnRec->state == 'rejected') continue;
    	
    		// Допълваме и времената за всеки случай
    		self::fillGapsInRec($dependsOnRec);
    			
    		// Ако има изчислено ново текущо време за нея, взимаме него а не старото
    		if(isset($expectedTimes[$dependsOnRec->id])){
    			$dependsOnRec->expectedTimeStart = $expectedTimes[$dependsOnRec->id];
    		}
    			
    		// За условието изчисляваме времето му на стартиране спрямо подадените данни
    		$dRec->calcTime = planning_TaskConditions::getExpectedTime($dRec->offset, $dRec->progress, $dependsOnRec->expectedTimeStart, $dependsOnRec->timeDuration, $dependsOnRec->progress);
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
    
    



    function act_Test()
    {
    	$this->cron_CheckTasks();
    }
}