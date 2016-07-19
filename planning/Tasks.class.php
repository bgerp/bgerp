<?php


/**
 * Мениджър на задачи за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задачи за производство
 */
class planning_Tasks extends tasks_Tasks
{
    
    
	/**
	 * Интерфейси
	 */
    public $interfaces = 'label_SequenceIntf';
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'planning_DriverIntf';
	
	
	/**
	 * Шаблон за единичен изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutTask.shtml';
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Master &$mvc)
	{
		expect(is_subclass_of($mvc->driverInterface, 'tasks_DriverIntf'), 'Невалиден интерфейс');
	}
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, change_Plugin, plg_Clone, plg_Sorting, plg_Printing,plg_RowTools2,bgerp_plg_Blank';
	
	
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
	 * Клас обграждащ горния таб
	 */
	public $tabTopClass = 'portal planning';
	
	
	/**
	 * Да не се кешира документа
	 */
	public $preventCache = TRUE;
	
	
	/**
	 * След рендиране на задачи към задание
	 * 
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 * @return void
	 */
	public static function on_AfterPrepareTasks($mvc, &$data)
	{
		if(Mode::is('text', 'xhtml') || Mode::is('printing') || Mode::is('pdf')) return;
		
		// Може ли на артикула да се добавят задачи за производство
		$defaultTasks = cat_Products::getDefaultProductionTasks($data->masterData->rec->productId, $data->masterData->rec->quantity);
		
		$containerId = $data->masterData->rec->containerId;
		
		// Ако има дефолтни задачи, показваме ги визуално в $data->rows за по-лесно добавяне
		if(count($defaultTasks)){
			foreach ($defaultTasks as $index => $taskInfo){
				
				// Имали от създадените задачи, такива с този индекс
				$foundObject = array_filter($data->recs, function ($a) use ($index) {
					return $a->systemId == $index;
				});
				
				// Ако има не показваме дефолтната задача
				if(is_array($foundObject) && count($foundObject)) continue;
				
				// Ако не може да бъде добавена задача не показваме реда
				if(!$mvc->haveRightFor('add', (object)array('originId' => $containerId, 'innerClass' => $taskInfo->driver))) continue;
				$row = new stdClass();
				$row->title = $taskInfo->title;
				$url = array('planning_Tasks', 'add', 'originId' => $containerId, 'driverClass' => $taskInfo->driver, 'totalQuantity' => $taskInfo->quantity, 'systemId' => $index, 'title' => $taskInfo->title, 'ret_url' => TRUE);
				
				core_RowToolbar::createIfNotExists($row->_rowTools);
				$row->_rowTools->addLink('', $url, array('ef_icon' => 'img/16/add.png', 'title' => "Добавяне на нова задача за производство"));
				
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

			if(empty($rec->threadId) && empty($rec->originId)){
				$requiredRoles = 'no_one';
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
	 * Преди запис на документ, изчислява стойността на полето `isContable`
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $rec
	 */
	public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		if(empty($rec->originId)){
			$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
			$rec->originId = $firstDoc->fetchField('containerId');
		}
		
		$rec->classId = ($rec->classId) ? $rec->classId : $mvc->getClassId();
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
	{
		if(isset($rec->originId)){
			
			// Ако има източник предизвикваме му обновяването да се инвалидира кеша ако има
			$origin = doc_Containers::getDocument($rec->originId);
			$originRec = $origin->fetch();
			$origin->getInstance()->save($originRec);
		}
	}
	

	/**
	 * След подготовка на тулбара на единичен изглед.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		if(core_Packs::isInstalled('label')){
			if (($data->rec->state != 'rejected' && $data->rec->state != 'draft') && label_Labels::haveRightFor('add')){
				
				$tQuery = label_Templates::getQuery();
				$tQuery->where("#classId = '{$mvc->getClassId()}'");
				$tQuery->where("#state != 'rejected'");
				$tQuery->show('id');
				
				$error = ($tQuery->fetch()) ? '' : ",error=Няма наличен шаблон за етикети от задачи за производство";
				
				core_Request::setProtected('class,objectId');
				$url = array('label_Labels', 'selectTemplate', 'class' => $mvc->className, 'objectId' => $data->rec->id, 'title' => "#" . $mvc->getHandle($data->rec->id), 'ret_url' => TRUE);
				$data->toolbar->addBtn('Етикетиране', toUrl($url), NULL, "target=_blank,ef_icon = img/16/price_tag_label.png,title=Разпечатване на етикети от задачата за производство{$error}");
				core_Request::removeProtected('class,objectId');
			}
		}
	}
	
	
	/**
	 * Връща данни за етикети
	 * 
	 * @param int $id - ид на задача
	 * @param number $labelNo - номер на етикета
	 * 
	 * @return array $res - данни за етикетите
     * 
     * @see label_SequenceIntf
	 */
	public function getLabelData($id, $labelNo = 0)
	{
		$res = array();
		expect($rec = planning_Tasks::fetchRec($id));
		expect($origin = doc_Containers::getDocument($rec->originId));
		$jobRec = $origin->fetch();
	    
		// Форсираме сериен номер
		$res['SERIAL'] = planning_TaskSerials::force($id, $labelNo, $rec->productId);
	
		// Хендлъра на заданието
		$res['JOB'] = "#" . $origin->getHandle();
	
		// Заглавие на заданието
		$res['JOB_NAME'] = $origin->getTitleById();
	
		// Хендлър на задачата за производство
		$res['TASK'] = "#" . planning_Tasks::getHandle($rec->id);
	
		// Данни от сделката към която е заданието (ако е към сделка)
		if(isset($jobRec->saleId)){
			$saleRec = sales_Sales::fetch($jobRec->saleId);
				
			// Хендлър на сделката
			$res['ORDER'] = "#" . sales_Sales::getHandle($saleRec->id);
				
			// Дата на сделката
			$res['ODDER_DATE'] = $saleRec->valior;
				
			$Contragent = cls::get($saleRec->contragentClassId);
			$countryName = $Contragent->getContragentData($saleRec->contragentClassId)->country;
			if(!empty($countryName)){
	
				// Държавата на контрагента от сделката
				$res['DES_COUNTRY'] = $countryName;
			}
				
			// Името на контрагента
			$res['COMPANY'] = $Contragent->getTitleById($saleRec->contragentId);
		}
	
		// Информация за производимия артикул
		if(isset($rec->productId)){
				
			// Кода на произведения артикул
			$res['ARTICLE_TITLE'] = cat_Products::getTitleById($rec->productId);
			$productCode = cat_Products::fetchField($rec->productId, 'code');
				
			// Кода на произведения артикул, ако няма код това е хендлъра му
			$res['ARTICLE'] = !empty($productCode) ? $productCode : "#" . cat_Products::getHandle($rec->productId);
		}
	
		// Връщаме данните за етикета от задачата
		return $res;
	}
    
    
    /**
     * Броя на етикетите, които могат да се отпечатат
     * 
     * @param integer $id
     * @param string $allowSkip
     * 
     * @return integer
     * 
     * @see label_SequenceIntf
     */
    public function getEstimateCnt($id, &$allowSkip)
    {
        $allowSkip = TRUE;
        
        return 100 + $id;
    }
}
