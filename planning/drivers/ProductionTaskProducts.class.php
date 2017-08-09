<?php



/**
 * Клас 'planning_drivers_ProductionTaskProducts'
 *
 * Детайли на задачите за производство
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_drivers_ProductionTaskProducts extends tasks_TaskDetails
{
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайл на производствените операции';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'type,productId,packagingId=Eдиница,plannedQuantity=Количества->Планирано,realQuantity=Количества->Изпълнено,measureId=Количества->Мярка,storeId,indTime,totalTime';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'indTime,totalTime,storeId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_AlignDecimals2, plg_SaveAndNew, plg_Modified, plg_Created,planning_Wrapper';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'taskPlanning,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'taskPlanning,ceo';
    
    
    /**
     * Кой има право да добавя артикули към активна задача?
     */
    public $canAddtoactive = 'taskPlanning,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'taskPlanning,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canList = 'no_one';
    
   
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Операции';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD("taskId", 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Задача');
    	$this->FLD("type", 'enum(input=Вложим,waste=Отпадък)', 'caption=Вид,remember,silent,input=hidden');
    	$this->FLD("productId", 'key(mvc=cat_Products,select=name)', 'silent,mandatory,caption=Артикул,removeAndRefreshForm=packagingId,tdClass=productCell leftCol wrap');
    	$this->FLD("packagingId", 'key(mvc=cat_UoM,select=shortName)', 'mandatory,caption=Пр. единица,smartCenter,tdClass=small-field nowrap');
    	$this->FLD("plannedQuantity", 'double(smartRound,Min=0)', 'mandatory,caption=Планирано к-во,smartCenter,oldFieldName=planedQuantity');
    	$this->FLD("storeId", 'key(mvc=store_Stores,select=name)', 'mandatory,caption=Склад');
    	$this->FLD("quantityInPack", 'double', 'mandatory,input=none');
    	$this->FLD("realQuantity", 'double(smartRound)', 'caption=Количество->Изпълнено,input=none,notNull,smartCenter');
    	$this->FLD("indTime", 'time(noSmart)', 'caption=Норма->Време,smartCenter');
    	$this->FNC('totalTime', 'time(noSmart)', 'caption=Норма->Общо,smartCenter');
    	
    	$this->setDbUnique('taskId,productId');
    }
    
    
    /**
     * Общото време
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_CalcTotalTime(core_Mvc $mvc, $rec)
    {
    	if (empty($rec->indTime) || empty($rec->realQuantity)) return;
    
    	$rec->totalTime = $rec->indTime * $rec->realQuantity;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$form->setDefault('type', 'input');
    	
    	// Ако има тип
    	if(isset($rec->type)){
    		switch($rec->type){
    			case 'input':
    				
    				// за влагане може да се изберат само вложимите артикули
    				$meta = 'canConvert';
    				$products = cat_Products::getByProperty($meta);
    				break;
    			case 'waste':
    				$meta = 'canStore,canConvert';
    				$products = cat_Products::getByProperty($meta);
    				break;
    		}
    		
    		// Ако има избран артикул, той винаги присъства в опциите
    		if(isset($rec->productId)){
    			if(!isset($products[$rec->productId])){
    				$products[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
    			}
    		}
    		
    		// Задаваме опциите с артикулите за избор
    		$form->setOptions('productId', array('' => '') + $products);
    		if(count($products) == 1){
    			$form->setDefault('productId', key($products));
    		}
    	}
    	
    	if(isset($rec->productId)){
    		$packs = cat_Products::getPacks($rec->productId);
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    		
    		$productInfo = cat_Products::getProductInfo($rec->productId);
    		if(!isset($productInfo->meta['canStore'])){
    			$form->setField('storeId', "input=none");
    		} else {
    			$form->setDefault('storeId', store_Stores::getCurrent('id', FALSE));
    		}
    		
    		if(empty($rec->id)){
    			$caption = ($rec->type == 'input') ? 'Вложено' : 'Отпадък';
    			$form->FLD('inputedQuantity', 'double(Min=0)', "caption={$caption},before=storeId");
    		}
    		
    		$taskInfo = planning_Tasks::getTaskInfo($data->masterRec);
    		$Double = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')));
    		$shortUom = cat_UoM::getShortName($taskInfo->packagingId);
    		$measureUom = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
    		$unit = tr($measureUom) . " " . tr('за') . " " . $Double->toVerbal($taskInfo->plannedQuantity) . " " . $shortUom;
    		$unit = str_replace("&nbsp;", ' ', $unit);
    		 
    		$form->setField('plannedQuantity', array('unit' => $unit));
    	} else {
    		$form->setField('packagingId', 'input=hidden');
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if($form->isSubmitted()){
    		if($rec->type == 'waste'){
    			$selfValue = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->productId);
    			
    			if(!isset($selfValue)){
    				$form->setWarning('productId', 'Отпадъкът няма себестойност');
    			}
    		}
    		
    		$pInfo = cat_Products::getProductInfo($rec->productId);
    		$rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
    	}
    }
    
    
    /**
     * Подготвя детайла
     */
    public function prepareDetail_($data)
    {
    	$data->TabCaption = 'Артикули';
    	$data->Tab = 'top';
    
    	parent::prepareDetail_($data);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$data)
    {
    	if(!count($data->recs)) return;
    	
    	foreach ($data->rows as $id => $row){
    		$rec = $data->recs[$id];
    		$row->measureId = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'), 'name');
    		
    		if(isset($rec->storeId)){
    			$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    		}
    		$row->ROW_ATTR['class'] = ($rec->type == 'input') ? 'row-added' : 'row-removed';
    		$row->productId = cat_Products::getShortHyperlink($rec->productId);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec->taskId)){
    		$state = $mvc->Master->fetchField($rec->taskId, 'state');
    		if($state == 'active' || $state == 'waiting' || $state == 'wakeup' || $state == 'draft'){
    			if($action == 'add'){
    				$requiredRoles = $mvc->getRequiredRoles('addtoactive', $rec);
    			}
    		} else {
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($requiredRoles == 'no_one') return;
    	
    	if(($action == 'delete' || $action == 'edit') && isset($rec->taskId) && isset($rec->id)){
    		if(planning_drivers_ProductionTaskDetails::fetchField("#taskId = {$rec->taskId} AND #taskProductId = {$rec->id}")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Ъпдейтва реалното количество
     * 
     * @param int $taskProductId - ид на ред за ъпдейт
     * @return void
     */
    public static function updateRealQuantity($taskProductId)
    {
    	$rec = self::fetch($taskProductId);
    	$rec->realQuantity = 0;
    	
    	$query = planning_drivers_ProductionTaskDetails::getQuery();
    	$query->where("#taskId = {$rec->taskId}");
    	$query->where("#type = '{$rec->type}'");
    	$query->where("#taskProductId = {$taskProductId}");
    	$query->where("#state != 'rejected'");
    	$query->show('quantity');
    	
    	while($dRec = $query->fetch()){
    		$rec->realQuantity += $dRec->quantity;
    	}
    	
    	self::save($rec, 'realQuantity');
    }
    
    
    /**
     * Намира всички допустими артикули от дадения тип за една задача
     * 
     * @param int $taskId
     * @param input|product|waste $type
     * @return array
     */
    public static function getOptionsByType($taskId, $type)
    {
    	$options = array();
    	expect(in_array($type, array('input', 'waste')));
    	
    	$query = self::getQuery();
    	$query->where("#taskId = {$taskId}");
    	$query->where("#type = '{$type}'");
    	while($rec = $query->fetch()){
    		$options[$rec->id] = cat_Products::getTitleById($rec->productId, FALSE);
    	}
    	
    	return $options;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	if(!empty($rec->inputedQuantity)){
    		$dRec = (object)array('taskId' => $rec->taskId, 'taskProductId' => $rec->id, 'type' => $rec->type, 'quantity' => $rec->inputedQuantity);
    		planning_drivers_ProductionTaskDetails::save($dRec);
    	}
    	
    	// При добавянето на артикул за влагане/отпадък ако за него има чернова задача за произвеждането му
    	// искаме текущата задача да зависи от изпълнението на другата задача.Т.е да активираме задачата
    	// за влагането на артикула само след завършването на задачата за произвеждането му
    	
    	// Коя е задачата
    	$taskRec = planning_Tasks::fetch($rec->taskId);
    	
    	// Ако задачата няма източник няма от къде да зареждаме
    	if(!isset($taskRec->originId)) return;
    	
    	// Търсим дали има друга чернова задача за произвеждането на артикула, който влагаме/отпадък
    	$tQuery = planning_drivers_ProductionTaskProducts::getQuery();
    	$tQuery->where("#type = 'product' AND #productId = {$rec->productId}");
    	$tQuery->EXT('state', 'planning_Tasks', 'externalName=state,externalKey=taskId');
    	$tQuery->EXT('originId', 'planning_Tasks', 'externalName=originId,externalKey=taskId');
    	
    	$tQuery->where("#originId = {$taskRec->originId}");
    	$tQuery->where("#state NOT IN ('closed', 'rejected')");
    	$tQuery->where("#taskId != '{$taskRec->id}'");
    	$tQuery->show('taskId,plannedQuantity');
    	
    	// За всяка от намерените задачи
    	while($tRec = $tQuery->fetch()){
    		try{
    			
    			// Добавяме текущата задача да зависи от нея
    			$progress = ($tRec->plannedQuantity == 1) ? 1 : 0.1;
    			tasks_TaskConditions::add($taskRec, $tRec->taskId, $progress);
    		} catch(core_exception_Expect $e){
    			
    		}
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    		
    		if(cat_Products::getByProperty('canConvert', NULL, 1)){
    			if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'input'))){
    				$data->toolbar->addBtn('Влагане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'input', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/wooden-box.png,title=Добавяне на вложим артикул');
    			}
    		}
    		
    		if(cat_Products::getByProperty('canStore', NULL, 1)){
    			if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'waste'))){
    				$data->toolbar->addBtn('Отпадъци', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'waste', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/recycle.png,title=Добавяне на отпаден артикул');
    			}
    		}
    	}
    	 
    	$data->toolbar->removeBtn('binBtn');
    }
    
    
    /**
     * Преди подготовка на заглавието на формата
     */
    protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
    {
    	$rec = &$data->form->rec;
    	$data->singleTitle = ($rec->type == 'input') ? 'артикул за влагане' : 'отпадъчен артикул';
    }
    
    
    /**
     * Изпълнява се преди клониране
     */
    protected static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
    	unset($rec->realQuantity);
    }
}