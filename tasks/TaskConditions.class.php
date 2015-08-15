<?php


/**
 * Клас 'tasks_TaskConditions'
 * 
 * @title Задаване на условия към задачите
 *
 *
 * @category  bgerp
 * @package   tasks
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tasks_TaskConditions extends doc_Detail
{
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'tasks_TaskConditions';
	
	
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';

     
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,tasks_Wrapper,plg_RowTools,plg_SaveAndNew';


    /**
     * Заглавие
     */
    public $title = "Условия за започване";
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Условие за започване';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,taskId=Задача,progress,dependsOn,offset,calcTime,createdOn,createdBy';
    
    
    /**
     * Поле в което ще се показва тулбара
     */
    public $rowToolsField = 'tools';

    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'powerUser';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';

    
    /**
     * Кой е мастър класа
     */
    public function getMasterMvc($rec)
    {
    	$masterMvc = cls::get(tasks_Tasks::fetchField($rec->taskId, 'classId'));
    
    	return $masterMvc;
    }
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('taskId', 'key(mvc=tasks_Tasks,select=title)', 'mandatory,silent,input=hidden');
    	$this->FLD('dependsOn', 'key(mvc=tasks_Tasks,select=title, allowEmpty)', 'mandatory,caption=Зависи от');
    	$this->FLD('progress', 'percent(min=0,max=1,decimals=0)', 'mandatory,caption=Прогрес');
    	$this->FLD('offset', 'time()', 'notNull,value=0,caption=Отместване');
    	$this->FLD('calcTime', 'datetime(format=smartTime)', 'input=none,caption=Изчислено време');
    	
    	$this->setDbUnique('taskId,dependsOn');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	//bp($mvc->Master);
    	// Задаваме предложения за прогрес
    	$form->setSuggestions('progress', array('' => '') + arr::make('0 %,10 %,20 %,30 %,40 %,50 %, 60 %, 70 %, 80 %, 90 %, 100 %', TRUE));
    	
    	// Извличаме позволените задачи за избор
    	$form->setOptions('dependsOn', $mvc->getAllowedTaskToDepend($rec->taskId));
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec->taskId)){
    		
    		// Може да се модифицират детайлите само ако състоянието е чакащо, активно или събудено
    		$state = $mvc->Master->fetchField($rec->taskId, 'state');
    		if($state != 'pending' && $state != 'draft' && $state != 'active'){
    			$requiredRoles = 'no_one';
    		} else {
    			 
    			// Ако не може да бъде избран драйвера от потребителя, не може да добавя прогрес
    			if($Driver = $mvc->Master->getDriver($rec->taskId)){
    				if(!$Driver->canSelectDriver($userId)){
    					$requiredRoles = 'no_one';
    				}
    			} else {
    				$requiredRoles = 'no_one';
    			}
    		}
    		
    		// Ако няма възможни задачи от които да зависи, не може да се добавя
    		if($requiredRoles != 'no_one'){
    			$allowedTasks = $mvc->getAllowedTaskToDepend($rec->taskId);
    			if(!count($allowedTasks)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    public function renderDetail_($data)
    {
    	// Ако няма записи не рендираме нищо
    	if(!count($data->rows)) return NULL;
    	unset($data->listFields['taskId']);
    	
    	// Рендираме изгледа на детайла
    	$tpl = parent::renderDetail_($data);
    	
    	// Връщаме рендирания изглед
    	return $tpl;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(isset($rec->dependsOn)){
    		$row->dependsOn = tasks_Tasks::getLink($rec->dependsOn, 0);
    	}
    	$row->taskId = tasks_Tasks::getLink($rec->taskId, 0);
    	
    	switch($rec->progress){
    		case 0:
    			$row->progress = tr('След началото на|* ');
    			break;
    		case 1:
    			$row->progress = tr('След края на|* ');
    			break;
    		default:
    			$row->progress = "<b>" . $row->progress . "</b>" . tr("|* |от изпълнението на|* ");
    			break;
    	}
    }
    
    
    /**
     * Връща опции с позволени задачи за избор
     * 
     * @param int $taskId - подадената задача
     * @return array $taskArray - масив с задачи
     */
    protected function getAllowedTaskToDepend($taskId)
    {
    	// Взимаме всички задачи от същата папка които не са приключени или оттеглени
    	$taskFolderId = $this->Master->fetchField($taskId, 'folderId');
    	$notAllowed = self::getInheritors($taskId);
    	if(count($notAllowed)){
    		$notAllowedCond = "#id NOT IN (" . implode(',', $notAllowed) . ") AND";
    	}
    	$taskArray = $this->Master->makeArray4Select('title', array("{$notAllowedCond} #state NOT IN ('closed', 'rejected') AND #folderId={$taskFolderId}"));
    	
    	return $taskArray;
    }
    
    
    /**
     * Рекурсивно намира всички задачи, които пряко или косвено
     *  зависят от подадената задача
     * 
     * @param int $taskId - задачата която използваме като needle
     * @param array $arr  - масив с ид-та на задачи които небива да се срещат
     * @return array $arr
     */
    protected static function getInheritors($taskId, &$arr = array())
    {
    	$arr[$taskId] = $taskId;
    	$query = self::getQuery();
    	while($rec = $query->fetch("#dependsOn = '$taskId'")) {
    		self::getInheritors($rec->taskId, $arr);
    	}
    
    	return $arr;
    }
    
    
    /**
     * Изчислява очакваното начало на условието
     * 
     * @param int       $offset  - отместване (секунди)
     * @param double    $progress - прогреса на условието
     * @param datetime  $dependsOnTimeExpectedStart - очаквано начало на зависимата задача
     * @param datetime  $dependsOnTimeDuration - очакван
     * @param double    $dependsOnProgress - текущия прогрес на условната задача
     * @return datetime $expectedTime -очакваното време 
     */
    public static function getExpectedTime($offset, $progress, $dependsOnTimeExpectedStart, $dependsOnTimeDuration, $dependsOnProgress)
    {
    	// Намираме най-голямото от очакваното начало на условната задача и текущата дата
    	$expectedTime = max($dependsOnTimeExpectedStart, dt::now());
    	
    	// Добавяме отместването
    	$expectedTime = dt::addSecs($offset, $expectedTime);
    	
    	// Добавяме продължителността на желания прогрес към намереното време
    	$expectedTime = dt::addSecs($dependsOnTimeDuration * $progress, $expectedTime);
    	
    	// Връща очакваното време
    	return $expectedTime;
    }
}