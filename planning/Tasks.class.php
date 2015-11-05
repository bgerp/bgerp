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
class planning_Tasks extends tasks_Tasks
{
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, change_Plugin, plg_Clone, plg_Sorting, plg_Printing,plg_RowTools,bgerp_plg_Blank';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Задачи за производство';
	
	
	/**
	 * Еденично заглавие
	 */
	public $singleTitle = 'Задача за производство';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Pts';
	
	
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.8|Производство";
	
	
	/**
	 * Подготвя задачите към заданията
	 */
	public function prepareTasks($data)
	{
		$data->recs = $data->rows = array();
		 
		// Дали според продуктовия драйвер на артикула в заданието има дефолтни задачи
		$ProductDriver = cat_Products::getDriver($data->masterData->rec->productId);
		if(!empty($ProductDriver)){
			$defaultTasks = $ProductDriver->getDefaultJobTasks();
		}
		 
		// Намираме всички задачи към задание
		$query = $this->getQuery();
		$query->where("#state != 'rejected'");
		
		$containerId = $data->masterData->rec->containerId;
		$query->where("#originId = {$containerId}");
		$query->XPR('orderByState', 'int', "(CASE #state WHEN 'wakeup' THEN 1 WHEN 'active' THEN 2 WHEN 'stopped' THEN 3 WHEN 'closed' THEN 4 WHEN 'pending' THEN 5 ELSE 6 END)");
		$query->orderBy('#orderByState=ASC');
		 
		// Подготвяме данните
		while($rec = $query->fetch()){
			$data->recs[$rec->id] = $rec;
			$row = $this->recToVerbal($rec);
			$row->modified = $row->modifiedOn . " " . tr('от') . " " . $row->modifiedBy;
			$row->modified = "<div style='text-align:center'> {$row->modified} </div>";
			$data->rows[$rec->id] = $row;
			
			// Премахваме от масива с дефолтни задачи, тези с чието име има сега създадена задача
			$title = $data->rows[$rec->id]->title;
			if(isset($rec->systemId) && is_array($defaultTasks)){
				unset($defaultTasks[$rec->systemId]);
			}
		}
		 
		// Ако има дефолтни задачи, показваме ги визуално в $data->rows за по-лесно добавяне
		if(count($defaultTasks)){
			foreach ($defaultTasks as $index => $taskInfo){
	
				// Ако не може да бъде доабвена задача не показваме реда
				if(!self::haveRightFor('add', (object)array('originId' => $containerId, 'innerClass' => $taskInfo->driver))) continue;
				 
				$url = array('planning_Tasks', 'add', 'originId' => $containerId, 'driverClass' => $taskInfo->driverClass, 'systemId' => $index, 'ret_url' => TRUE);
				$row = new stdClass();
				$row->title = $taskInfo->title;
				$row->tools = ht::createLink('', $url, FALSE, 'ef_icon=img/16/add.png,title=Добавяне на нова задача за производство');
				$row->ROW_ATTR['style'] .= 'background-color:#f8f8f8;color:#777';
	
				$data->rows[] = $row;
			}
		}
		 
		// Бутон за нова задача ако има права
		$driverClass = planning_drivers_ProductionTask::getClassId();
		if(self::haveRightFor('add', (object)array('originId' => $containerId, 'driverClass' => $driverClass))){
			$data->addUrl = array('planning_Tasks', 'add', 'originId' => $containerId, 'driverClass' => $driverClass, 'ret_url' => TRUE);
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
		$tpl = $table->get($data->rows, 'tools=Пулт,progress=Прогрес,name=Документ,title=Заглавие,expectedTimeStart=Очаквано начало, timeDuration=Продължителност, timeEnd=Край, modified=Модифицирано');
		
		// Добавя бутон за създаване на нова задача
		if(isset($data->addUrl)){
			$addBtn = ht::createLink('', $data->addUrl, FALSE, 'title=Създаване на задача по заданието,ef_icon=img/16/add.png');
			$tpl->append($addBtn, 'ADD_BTN');
		}
		
		// Връщаме шаблона
		return $tpl;
	}
}