<?php



/**
 * Клас 'planning_drivers_ProductionTaskDetails'
 *
 * Детайли на драйверите за за задачи за производство
 *
 * @category  bgerp
 * @package   tasks
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_drivers_ProductionTaskDetails extends tasks_TaskDetails
{
    
	
	/**
     * Заглавие
     */
    public $title = 'Детайли на производствените операции';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Прогрес';
    
    
    /**
     * Интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created, plg_LastUsedKeys, plg_Sorting, planning_Wrapper';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'employees,fixedAsset';
    
    
    /**
     * Кой има право да оттегля?
     */
    public $canReject = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да възстановява?
     */
    public $canRestore = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да редактира?
     */
    public $canEdit = 'taskWorker,ceo';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'type=Операция,serial,taskProductId,quantity,shortUoM=Мярка,weight=Тегло (кг),employees,fixedAsset,modified=Модифицирано';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'serial,weight,employees,fixedAsset, scrappedQuantity';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD("taskId", 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Задача');
    	$this->FLD('taskProductId', 'key(mvc=planning_drivers_ProductionTaskProducts,select=productId,allowEmpty)', 'caption=Артикул,mandatory,silent,refreshForm,tdClass=productCell leftCol wrap');
    	$this->FLD('type', 'enum(input=Влагане,product=Произв.,waste=Отпадък,start=Пускане)', 'input=hidden,silent,tdClass=small-field nowrap');
    	$this->FLD('serial', 'varchar(32)', 'caption=Сер. №,smartCenter,focus');
    	$this->FLD('quantity', 'double(Min=0)', 'caption=Количество');
    	$this->FLD('scrappedQuantity', 'double(Min=0)', 'caption=Брак,input=none');
    	$this->FLD('weight', 'double', 'caption=Тегло,smartCenter,unit=кг');
    	$this->FLD('employees', 'keylist(mvc=crm_Persons,select=id)', 'caption=Работници,smartCenter,tdClass=nowrap');
    	$this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=code)', 'caption=Обордуване,input=none,smartCenter');
    	$this->FLD('notes', 'richtext(rows=2,bucket=Notes)', 'caption=Забележки');
    	$this->FLD('state', 'enum(active=Активирано,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull');
    	$this->FNC('packagingId', 'int', 'smartCenter,tdClass=small-field nowrap');
    	$this->FLD('actionRecId', 'int', 'input=none');
    	
    	$this->setDbIndex('type');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$data->form->rec;
    	$taskInfo = planning_Tasks::getTaskInfo($rec->taskId);
    	
    	// Добавяме последните данни за дефолтни
    	$query = $mvc->getQuery();
    	$query->where("#taskId = {$rec->taskId}");
    	$query->orderBy('id', 'DESC');
    	 
    	// Задаваме последно въведените данни
    	if($lastRec = $query->fetch()){
    		$form->setDefault('employees', $lastRec->employees);
    		$form->setDefault('fixedAsset', $lastRec->fixedAsset);
    	}
    	
    	// Ако в мастъра са посочени машини, задаваме ги като опции
    	if(isset($taskInfo->fixedAssets)){
    		$keylist = $taskInfo->fixedAssets;
    		$arr = keylist::toArray($keylist);
    			
    		foreach ($arr as $key => &$value){
    			$value = planning_AssetResources::getVerbal($key, 'code');
    		}
    		$form->setOptions('fixedAsset', array('' => '') + $arr);
    		$form->setField('fixedAsset', 'input');
    	}
    	
    	if($rec->type != 'product' && $rec->type != 'start'){
    		$productOptions = planning_drivers_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
    		$form->setOptions('taskProductId', $productOptions);
    		if(count($productOptions) == 1 && $form->cmd != 'refresh'){
    			$form->setDefault('taskProductId', key($productOptions));
    			$form->setReadOnly('taskProductId');
    		}
    	} else {
    		$form->FNC('productId', 'int', 'caption=Артикул,input,before=serial');
    		$form->setOptions('productId', array("{$taskInfo->productId}" => cat_Products::getTitleById($taskInfo->productId, FALSE)));
    		$form->setField('taskProductId', 'input=none');
    		
    		if(isset($rec->id)){
    			$form->setReadOnly('serial');
    			$form->setReadOnly('quantity');
    			$form->setField('scrappedQuantity', 'input');
    			$form->setFieldTypeParams('scrappedQuantity', array('max' => $rec->quantity, 'min' => 0));
    			$form->setField('employees', 'input=none');
    			$form->setField('fixedAsset', 'input=none');
    			$form->setField('notes', 'input=none');
    		}
    		
    		if($rec->type == 'start'){
    			$form->setField('quantity', "input=none");
    			$form->setField('weight', 'input=none');
    			$form->setField('notes', 'input=none');
    			$form->setField('serial', 'input=none');
    		}
    	}
    	
    	// Добавяне на мярката
    	if(isset($rec->taskProductId)){
    		$pRec = planning_drivers_ProductionTaskProducts::fetch($rec->taskProductId);
    		$unit = $pRec->packagingId;
    		$unit = cat_UoM::getShortName($unit);
    		
    		$planned = tr("Планирано|*: <b>") . planning_drivers_ProductionTaskProducts::getVerbal($pRec, 'plannedQuantity') . "</b>";
    		$real = tr("Изпълнено|*: <b>") . planning_drivers_ProductionTaskProducts::getVerbal($pRec, 'realQuantity') . "</b>";
    		$form->info = "{$planned}<br>$real";
    		
    		$form->setField('quantity', "unit={$unit}");
    	}
    	
    	// Връща слижителите с код
    	$employees = crm_ext_Employees::getEmployeesWithCode();
    	if(count($employees)){
    		$form->setSuggestions('employees', $employees);
    	} else {
    		$form->setField('employees', 'input=none');
    	}
    	
    	if($taskInfo->showadditionalUom != 'yes'){
    		$form->setField('weight', 'input=none');
    	}
    	
    	$hideMeasure = FALSE;
    	if($rec->type == 'product'){
    		$hideMeasure = TRUE;
    		$measureId = cat_Products::fetchField($taskInfo->productId, 'measureId');
    		$packagingId = $taskInfo->packagingId;
    	} elseif(isset($rec->taskProductId)) {
    		$hideMeasure = TRUE;
    		$pRec = planning_drivers_ProductionTaskProducts::fetch($rec->taskProductId);
    		$measureId = cat_Products::fetchField($pRec->productId, 'measureId');
    		$packagingId = $pRec->packagingId;
    	}
    	
    	if($hideMeasure === TRUE){
    		$shortMeasure = cat_UoM::getShortName($measureId);
    		if($measureId != $packagingId){
    			$packName = $unit = cat_UoM::getShortName($packagingId);
    			$unit = $shortMeasure . " " . tr('в') . " " . $packName;
    			$form->setField('quantity', "unit={$unit}");
    		} else {
    			$form->setField('quantity', "unit={$shortMeasure}");
    		}
    	}
    }
    

    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	 
    	if($form->isSubmitted()){
    		$productId = ($rec->taskProductId) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'productId') : planning_Tasks::getTaskInfo($rec->taskId)->productId;
    		
    		if(empty($rec->serial)){
    			if($rec->type == 'product'){
    				$rec->serial = planning_TaskSerials::forceAutoNumber($rec->taskId);
    			}
    		}
    		
    		if(!empty($rec->serial)){
    			
    			// Ако има въведен сериен номер, проверяваме дали е валиден
    			$type = ($rec->type == 'product') ? 'product' : 'input';
    			if($error = planning_TaskSerials::isSerialinValid($rec->serial, $productId, $rec->taskId, $type, $rec->id)){
    				$form->setError('serial', $error);
    			}
    		}
    		
    		if($rec->type == 'product'){
    			if(self::fetchField("#taskId = {$rec->taskId} AND #serial = '{$rec->serial}' AND #id != '{$rec->id}'")){
    				$form->setError('serial', 'Сер. № при произвеждане трябва да е уникален');
    			}
    		}
    		
    		if(!$form->gotErrors()){
    			if(!empty($rec->serial) && empty($rec->quantity)){
    				$quantityInSerial = NULL;
    				if($rec->type == 'product'){
    					$quantityInSerial = planning_TaskSerials::fetchField(array("#serial = '[#1#]'", $rec->serial), 'quantityInPack');
    				} else {
    					if($originId = planning_Tasks::fetchField($rec->taskId, 'originId')){
    						$query = self::getQuery();
    						$query->EXT('originId', 'planning_Tasks', 'externalName=originId,externalKey=taskId');
    						$query->where("#originId = {$originId}");
    						$query->where(array("#serial = '[#1#]' AND #type = 'product'", $rec->serial));
    						$query->show('quantity');
    						if($eRec = $query->fetch()){
    							$quantityInSerial = $eRec->quantity;
    						}
    					}
    				}
    				
    				$rec->quantity = $quantityInSerial;
    			}
    			 
    			if(empty($rec->quantity) && $rec->type != 'start'){
    				$form->setError('quantity', 'Трябва да въведете количество');
    			}
    		}
    		
    		$rec->serial = (empty($rec->serial)) ? NULL : $rec->serial;
    		$rec->quantity = ($rec->type == 'start') ? 1 : $rec->quantity;
    	}
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(isset($rec->fixedAsset)){
    		if(!Mode::isReadOnly()){
    			$singleUrl = planning_AssetResources::getSingleUrlArray($rec->fixedAsset);
    			$row->fixedAsset = ht::createLink($row->fixedAsset, $singleUrl);
    		}
    	}
    	 
    	$row->modified = "<div class='nowrap'>" . $mvc->getFieldType('modifiedOn')->toVerbal($rec->modifiedOn);
    	$row->modified .= " " . tr('от||by') . " " . crm_Profiles::createLink($rec->modifiedBy) . "</div>";
    	 
    	if(isset($rec->serial)){
    		$row->serial = "<b>{$row->serial}</b>";
    	}
    	 
    	$class = ($rec->state == 'rejected') ? 'state-rejected' : (($rec->type == 'input') ? 'row-added' : (($rec->type == 'product') ? 'state-active' : (($rec->type == 'start') ? 'state-stopped' : 'row-removed')));
    	$row->ROW_ATTR['class'] = $class;
    	if($rec->state == 'rejected'){
    		$row->ROW_ATTR['title'] = tr('Оттеглено от') . " " . core_Users::getVerbal($rec->modifiedBy, 'nick');
    	}
    	
    	$productId = ($rec->taskProductId) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'productId') : planning_Tasks::getTaskInfo($rec->taskId)->productId;
    	if($productId){
    		$row->taskProductId = cat_Products::getShortHyperlink($productId);
    		$row->taskProductId = "<div class='nowrap'>" . $row->taskProductId . "</div>";
    	}
    	
    	if($rec->type != 'start'){
    		if(isset($rec->taskProductId)){
    			$pRec = planning_drivers_ProductionTaskProducts::fetch($rec->taskProductId, 'quantityInPack,packagingId,productId');
    		} else {
    			$pRec = planning_Tasks::getTaskInfo($rec->taskId);
    		}
    		
    		$measureId = cat_Products::fetchField($pRec->productId, 'measureId');
    		$shortUom = cat_UoM::getShortName($measureId);
    		if($measureId != $pRec->packagingId){
    			$packagingId = cat_UoM::getShortName($pRec->packagingId);
    			$row->type .= " " . tr($packagingId);
    		} elseif($rec->type == 'product'){
    			$row->type = tr('Произвеждане');
    		}
    		
    		if(!empty($rec->scrappedQuantity)){
    			$rec->quantity = $rec->quantity - $rec->scrappedQuantity;
    			$row->scrappedQuantity = $mvc->getFieldType('scrappedQuantity')->toVerbal($rec->scrappedQuantity);
    		}
    		
    		$row->shortUoM = tr($shortUom);
    	} else {
    		unset($row->quantity);
    	}
    	
    	if(!empty($rec->notes)){
    		$notes = $mvc->getFieldType('notes')->toVerbal($rec->notes);
    		$row->taskProductId .= "<small>{$notes}</small>";
    	}
    	
    	if(!empty($rec->serial)){
    		$taskId = planning_TaskSerials::fetchField("#serial = '{$rec->serial}'", 'taskId');
    		if($taskId != $rec->taskId){
    			
    			if(!Mode::isReadOnly()){
    				$url = planning_Tasks::getSingleUrlArray($taskId);
    				$url['Q'] = $rec->serial;
    				$row->serial = ht::createLink($row->serial, $url, FALSE, "title=Към задачата от която е генериран серийния номер");
    			}
    		}
    	}
    	
    	if(isset($rec->employees)){
    		$row->employees = self::getVerbalEmployees($rec->employees);
    	}
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$rows = &$data->rows;
    	if(!count($rows)) return;
    
    	foreach ($rows as $id => $row){
    		$rec = $data->recs[$id];
    			
    		if(!empty($row->shortUoM)){
    			$row->quantity = "<b>{$row->quantity}</b>";
    			
    			if(!empty($row->scrappedQuantity)){
    				$hint = "Брак|* {$row->scrappedQuantity} {$row->shortUoM}";
    				$row->quantity = ht::createHint($row->quantity, $hint, 'warning', FALSE, 'width=14px;height=14px');
    			}
    		}
    	}
    }
    
    
    /**
     * Показва вербалното име на служителите
     * 
     * @param text $employees - кейлист от служители
     * @return string $verbalEmployees
     */
    public static function getVerbalEmployees($employees)
    {
    	$verbalEmployees = array();
    	$employees = keylist::toArray($employees);
    	foreach ($employees as $eId){
    		$el = crm_ext_Employees::getCodeLink($eId);
    		$verbalEmployees[$eId] = $el;
    	}
    	
    	return implode(', ', $verbalEmployees);
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if(isset($rec->taskProductId)){
    		planning_drivers_ProductionTaskProducts::updateRealQuantity($rec->taskProductId);
    	}
    	
    	// Ако е записва прозиведен артикул
    	if($rec->type == 'product'){
    		
    		// И има колчиество за скрап
    		if(isset($rec->scrappedQuantity)){
    			$sRec = clone $rec;
    			$sRec->quantity = $sRec->scrappedQuantity;
    			
    			// Ако не е имало досега добавя се
    			if(!$rec->actionRecId){
    				$rec->actionRecId = self::addAction($sRec, 'add', 'scrap');
    				$mvc->save_($rec, 'actionRecId');
    			} else {
    				
    				// Ако реда е оттеглен или възстановен променя се и състоянието му
    				$action = ($rec->state == 'rejected') ? 'reject' : (($rec->isRestored === TRUE) ? 'restore' : 'add');
    				if($action == 'reject' || $action == 'restore'){
    					$rec->actionRecId = self::addAction($sRec, $action, 'scrap');
    				} else {
    					planning_TaskActions::delete($rec->actionRecId);
    					$rec->actionRecId = self::addAction($sRec, 'edit', 'scrap');
    				}
    			}
    		} elseif(isset($rec->actionRecId)) {
    			
    			// Ако е имало бракувано количество, но вече няма
    			planning_TaskActions::delete($rec->actionRecId);
    			$rec->actionRecId = NULL;
    			$mvc->save_($rec);
    		}
    	}
    }
    
    
    /**
     * Изпълнява се преди възстановяването на документа
     */
    public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
    	// Отбелязваме че реда се редактира
    	$id->isRestored = TRUE;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	// Записване че е добавено
    	self::addAction($rec, 'add', $rec->type);
    }
    
    
    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
    	// Записване че е имало оттегляне
    	$rec = static::fetchRec($id);
    	self::addAction($rec, 'reject', $rec->type);
    }
    
    
    /**
     * Реакция в счетоводния журнал при възстановяване на оттеглен счетоводен документ
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
    	// Записване че е имало възстановяване
    	$rec = static::fetchRec($id);
    	self::addAction($rec, 'restore', $rec->type);
    }
    
    
    /**
     * Добавяне на действие
     * 
     * @param stdClass $rec   - запис
     * @param varchar $action - действие
     * @param varchar $type   - тип
     * @return int
     */
    private static function addAction($rec, $action, $type)
    {
    	$productId = (!empty($rec->taskProductId)) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'productId') : planning_Tasks::getTaskInfo($rec->taskId)->productId;
    	$packagingId = (!empty($rec->taskProductId)) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'packagingId') : planning_Tasks::getTaskInfo($rec->taskId)->packagingId;
    	$quantityInPack = (!empty($rec->taskProductId)) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'quantityInPack') : planning_Tasks::getTaskInfo($rec->taskId)->quantityInPack;
    	
    	return planning_TaskActions::add($rec->taskId, $productId, $action, $type, $packagingId, $rec->quantity, $quantityInPack, $rec->serial, $rec->employees, $rec->fixedAsset);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'product'))){
    			$data->toolbar->addBtn('Произвеждане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'product', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/package.png,title=Добавяне на произведен артикул');
    		}
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'input'))){
    			$data->toolbar->addBtn('Влагане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'input', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/wooden-box.png,title=Добавяне на вложен артикул');
    		}
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'waste'))){
    			$data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'waste', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/recycle.png,title=Добавяне на отпаден артикул');
    		}
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'start'))){
    			$data->toolbar->addBtn('Пускане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'start', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/media_playback_start.png,title=Пускане на произведения артикул');
    		}
    	}
    	
    	// Махане на кошчето
    	$data->toolbar->removeBtn('binBtn');
    }


    /**
     * Подготвя детайла
     */
    public function prepareDetail_($data)
    {
    	$data->TabCaption = 'Прогрес';
    	$data->Tab = 'top';
    
    	parent::prepareDetail_($data);
    }


    /**
     * Преди извличане на записите от БД
     */
    protected static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	// Искаме да показваме и оттеглените детайли
    	$data->query->orWhere("#state = 'rejected'");
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'reject' || $action == 'restore' || $action == 'edit' || $action == 'delete') && isset($rec->taskId)){
    		$state = $mvc->Master->fetchField($rec->taskId, 'state');
    		
    		if($state != 'active' && $state != 'waiting' && $state != 'wakeup'){
    			$requiredRoles = 'no_one';
    		} 
    	}
    	
    	// Трябва да има поне един артикул възможен за добавяне
    	if($action == 'add' && isset($rec->type) && $rec->type != 'product' && $rec->type != 'start'){
    		if($requiredRoles != 'no_one'){
    			$pOptions = planning_drivers_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
    			if(!count($pOptions)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'edit' && isset($rec)){
    		if($rec->type != 'product' || $rec->state == 'rejected'){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// Ограничаване на броя на пусканията, според конфигурацията
    	if(($action == 'add' || $action == 'restore') && $rec->type == 'start'){
    		$counter = core_Packs::getConfigValue('planning', 'PLANNING_TASK_START_COUNTER');
    		$count = self::count("#taskId = {$rec->taskId} AND #type = 'start' AND #state != 'rejected'");
    		
    		// Не може да бъде надминат максималния брой пускания
    		if($count >= $counter){
    			$requiredRoles = 'no_one';
    		}
    	}
    }


    /**
     * Преди подготовка на заглавието на формата
     */
    protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
    {
    	$rec = &$data->form->rec;
    	$data->singleTitle = ($rec->type == 'input') ? 'влагане' : (($rec->type == 'waste') ? 'отпадък' : (($rec->type == 'start') ? 'пускане' : 'произвеждане'));
    }
    
    
    /**
	 * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал 
	 *
	 * @param date $timeline  - Времето, след което да се вземат всички модифицирани/създадени записи
	 * @return array $result  - масив с обекти
	 *
	 * 			o date        - дата на стайноста
	 * 		    o personId    - ид на лицето
	 *          o docId       - ид на документа
	 *          o docClass    - клас ид на документа
	 *          o indicatorId - ид на индикатора
	 *          o value       - стойноста на инфикатора
	 *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
	 */
    public static function getIndicatorValues($timeline)
    {
    	$query = self::getQuery();
        $query->where("#modifiedOn >= '{$timeline}'");
        
        $iRec = hr_IndicatorNames::force('Време', __CLASS__, 1);
        $classId = planning_Tasks::getClassId();
        $indicatorId = $iRec->id;
        
        $result = array();
        $queryProduct = planning_drivers_ProductionTaskProducts::getQuery();
        $queryMasterm = planning_Tasks::getQuery();

        while ($rec = $query->fetch()) {
           switch($rec->type){
               case 'input':
                    $time = planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'indTime');	
                    break;
               case 'waste':
                    $time = -planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'indTime');
                    break;
               case 'product':
               		$taskInfo = planning_Tasks::getTaskInfo($rec->taskId);
               		$time = (!empty($taskInfo->startTime)) ? ($taskInfo->startTime / $taskInfo->quantityInPack) : NULL;
                    break;
               case 'start':
                    $time = planning_Tasks::getTaskInfo($rec->taskId)->indTime;
                    break;
            }
            
            if(empty($time)) continue;
            
            if($rec->employees) {
                $persons = keylist::toArray($rec->employees);
               
                $timePerson = ($rec->quantity * $time) / count($persons) ;
                $date = dt::verbal2mysql($rec->createdOn, FALSE);
                foreach ($persons as $personId) {
                	
                	$key = "{$personId}|{$classId}|{$rec->taskId}|{$date}|{$indicatorId}";
                	if(!array_key_exists($key, $result)){
                		$result[$key] = (object)array('date'        => $date,
												      'personId'    => $personId,
									                  'docId'       => $rec->taskId,
									                  'docClass'    => $classId,
									                  'indicatorId' => $indicatorId,
									                  'value'       => 0,
									                  'isRejected'  => ($rec->state == 'rejected'));
                	}
                	
                	$result[$key]->value += $timePerson;
                }
            }
        }
        
        return $result;
    }

    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $result
     */
    public static function getIndicatorNames()
    {
    	$result = array();
    	$rec = hr_IndicatorNames::force('Време', __CLASS__, 1);
    	$result[$rec->id] = $rec->name;
    	 
    	return $result;
    }
}