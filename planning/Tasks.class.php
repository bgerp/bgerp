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
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'planning_DriverIntf';
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		expect(is_subclass_of($mvc->driverInterface, 'tasks_DriverIntf'), 'Невалиден интерфейс');
	}
	
	
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
	 * Детайли
	 */
	public $details = 'tasks_TaskConditions';
	
	
	
	/**
	 * След рендиране на задачи към задание
	 * 
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 * @return void
	 */
	public static function on_AfterPrepareTasks($mvc, &$data)
	{
		// Можели на артикула да се добавят задачи за производство
		$defaultTasks = cat_Products::getDefaultProductionTasks($data->masterData->rec->productId, $data->masterData->rec->quantity);
		$containerId = $data->masterData->rec->containerId;
		
		// Ако има дефолтни задачи, показваме ги визуално в $data->rows за по-лесно добавяне
		if(count($defaultTasks)){
			foreach ($defaultTasks as $index => $taskInfo){
		
				// Ако не може да бъде доабвена задача не показваме реда
				if(!$mvc->haveRightFor('add', (object)array('originId' => $containerId, 'innerClass' => $taskInfo->driver))) continue;
		
				$url = array('planning_Tasks', 'add', 'originId' => $containerId, 'driverClass' => $taskInfo->driver, 'totalQuantity' => $taskInfo->quantity, 'systemId' => $index, 'title' => $taskInfo->title, 'ret_url' => TRUE);
				
				$row = new stdClass();
				$row->title = $taskInfo->title;
				$row->tools = ht::createLink('', $url, FALSE, 'ef_icon=img/16/add.png,title=Добавяне на нова задача за производство');
				$row->ROW_ATTR['style'] .= 'background-color:#f8f8f8;color:#777';
		
				$data->rows[] = $row;
			}
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'add'){
			if(isset($rec->originId)){
				
				// Може да се добавя само към активно задание
				if($origin = doc_Containers::getDocument($rec->originId)){
					if(!$origin->isInstanceOf('planning_Jobs')){
						$requiredRoles = 'no_one';
					}
				}
			}
		}
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
	 * Генерираме ключа за кеша
	 * Интерфейсен метод
	 *
	 * @param core_Mvc $mvc
	 * @param NULL|FALSE|string $res
	 * @param NULL|integer $id
	 * @param object $cRec
	 *
	 * @see doc_DocumentIntf
	 */
	public static function on_AfterGenerateCacheKey($mvc, &$res, $id, $cRec)
	{
		if ($res === FALSE) return ;
	
		$dealHistory = Request::get("TabTop{$cRec->id}");
		
		$res = md5($res . '|' . $dealHistory);
	}
}