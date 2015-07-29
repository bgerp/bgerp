<?php



/**
 * Мениджър на задачи за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заявки за покупки
 */
class planning_Tasks extends core_Embedder
{
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $innerObjectInterface = 'planning_TaskDetailIntf';
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_Tasks';
	
	
    /**
     * Заглавие
     */
    public $title = 'Производствени задачи';
    
    
    /**
     * Еденично заглавие
     */
    public $singleTitle = 'Производствена задача';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, doc_SharablePlg, doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, change_Plugin';

    
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
    public $listFields = 'tools=Пулт, name = Документ, title,timeStart, timeDuration, timeEnd, sharedUsers, progress, state';
    
    
    /**
     * Детайли
     */
    public $details = 'planning_TaskDetails';
    
    
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
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar(128)', 'caption=Заглавие,mandatory,width=100%,changable');
    	
    	$this->FLD('priority', 'enum(low=Нисък,
                                    normal=Нормален,
                                    high=Висок,
                                    critical=Критичен)',
    			'caption=Приоритет,mandatory,maxRadio=4,columns=4,notNull,value=normal');
    	$this->FLD('inCharge' , 'key(mvc=core_Users, select=nick,roles=planning|ceo)', 'caption=Отговорник,mandatory');
    	$this->FLD('description', 'richtext(bucket=calTasks,rows=3)', 'caption=Описание,changable');
    	
    	$this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)',
    			'caption=Времена->Начало, silent, changable, tdClass=leftColImportant,formOrder=101');
    	$this->FLD('timeDuration', 'time', 'caption=Времена->Продължителност,changable,formOrder=102');
    	$this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00)', 'caption=Времена->Край,changable, tdClass=leftColImportant,formOrder=103');
    	$this->FLD('sharedUsers', 'userList(roles=planning|ceo)', 'caption=Допълнително->Споделени,changable,formOrder=104');
    	$this->FLD('progress', 'percent', 'caption=Прогрес,input=none,notNull,value=0');
    	$this->FLD('jobId', 'key(mvc=planning_Jobs)', 'input=none,caption=По задание');
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
    }
    
    
    /**
     * Подготвя задачие към заданията
     */
    public function prepareTasks($data)
    {
    	//@TODO
    }
    
    
    /**
     * Рендира задачите на заданията
     */
    public function renderTasks($data)
    {
    	//@TODO
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
    			if($Driver = $mvc->getDriver($id)){
    				
    				// Даваме възможност на драйвера да обнови мастъра ако иска
    				$Driver->updateEmbedder();
    			}
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
    		$data->listFilter->setOptions('state', array('' => '') + arr::make('draft=Чернова, active=Активирано, closed=Приключено, stopped=Спряно, wakeup=Събудено', TRUE));
    		$data->listFilter->setField('state', 'placeholder=Всички');
    		$data->listFilter->showFields .= ',state';
    		$data->listFilter->input('state');
    		 
    		if($state = $data->listFilter->rec->state){
    			$data->query->where("#state = '{$state}'");
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'changerec' && isset($rec)){
    		if($rec->state != 'pending'){
    			$requiredRoles = 'no_one';
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
    			$detailsKeywords .= " " . plg_Search::normalizeText($dRow->code);
    			if($dRec->fixedAsset){
    				$detailsKeywords .= " " . plg_Search::normalizeText($dRow->fixedAsset);
    			}
    		}
    		
    		// Добавяме новите ключови думи към старите
    		$res = " " . $res . " " . $detailsKeywords;
    	}
    }
    
    
    /**
     * Дефолт състояние при активиране
     */
    public function activateNow_($rec)
    {
    	//return FALSE;
    	//@TODO дали да е чакащо или директно активно
    }
    
    
    /**
     * Активиране на чакащите задачи по разписание ако са им изпълнени условията
     */
    public function cron_ActivatePendingTasks()
    {
    	$query = self::getQuery();
    	$query->where("#state = 'pending'");
    	while($rec = $query->fetch()){
    		
    	}
    	
    	//@TODO да се активират чакащите задачи по разписание ако са им изпълнени условията
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
    	if(planning_TaskDetails::haveRightFor('add', (object)array('taskId' => $data->rec->id))){
    		$data->toolbar->addBtn('Прогрес', array('planning_TaskDetails', 'add', 'taskId' => $data->rec->id, 'ret_url' => TRUE), 'ef_icon=img/16/progressbar.png', 'title=Добавяне на прогрес към задачата');
    	}
    		
    	if($data->rec->state == 'active' || $data->rec->state == 'pending'){
    		if(cal_Reminders::haveRightFor('add', (object)array('originId' => $data->rec->containerId))){
    			$data->toolbar->addBtn('Напомняне', array('cal_Reminders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), 'ef_icon=img/16/rem-plus.png, row=2', 'title=Създаване на ново напомняне');
    		}
    	}
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$cu = core_Users::getCurrent();
    	$data->form->setDefault('inCharge', $cu);
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $fields = NULL, $mode = NULL)
    {
    	$rec->sharedUsers = keylist::addKey($rec->sharedUsers, $rec->inCharge);
    }
    
    
    /**
     * Връща иконата на документа
     */
    function getIcon_($id)
    {
    	$rec = self::fetch($id);
    
    	return "img/16/task-" . $rec->priority . ".png";
    }
}