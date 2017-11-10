<?php



/**
 * Клас 'planning_ProductionTaskProducts'
 *
 * Артикули към производствените операции
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ProductionTaskProducts extends core_Detail
{
    

	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'planning_drivers_ProductionTaskProducts';
	
	
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
    public $listFields = 'type,productId,packagingId=Eдиница,plannedQuantity=Количества->Планирано,totalQuantity=Количества->Изпълнено,measureId=Количества->Мярка,storeId,indTime,totalTime';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'indTime,totalTime,storeId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_AlignDecimals2, plg_SaveAndNew, plg_Modified, plg_Created, planning_Wrapper';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'taskPlanning, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'taskPlanning, ceo';
    
    
    /**
     * Кой има право да добавя артикули към активна операция?
     */
    public $canAddtoactive = 'taskPlanning, ceo';
    
    
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
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'totalQuantity';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD("taskId", 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Операция');
    	$this->FLD("type", 'enum(input=Вложим,waste=Отпадък,production=Производим)', 'caption=Вид,remember,silent,input=hidden');
    	$this->FLD("productId", 'key(mvc=cat_Products,select=name)', 'silent,mandatory,caption=Артикул,removeAndRefreshForm=packagingId,tdClass=productCell leftCol wrap');
    	$this->FLD("packagingId", 'key(mvc=cat_UoM,select=shortName)', 'mandatory,caption=Пр. единица,smartCenter,tdClass=small-field nowrap');
    	$this->FLD("plannedQuantity", 'double(smartRound,Min=0)', 'mandatory,caption=Планирано к-во,smartCenter,oldFieldName=planedQuantity');
    	$this->FLD("storeId", 'key(mvc=store_Stores,select=name)', 'mandatory,caption=Склад');
    	$this->FLD("quantityInPack", 'double', 'mandatory,input=none');
    	$this->FLD("totalQuantity", 'double(smartRound)', 'caption=Количество->Изпълнено,input=none,notNull,smartCenter,oldFieldName=realQuantity');
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
    	if (empty($rec->indTime) || empty($rec->totalQuantity)) return;
    
    	$rec->totalTime = $rec->indTime * $rec->totalQuantity;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$form->setDefault('type', 'input');
    	$masterRec = planning_Tasks::fetch($data->masterId);
    	
    	// Ако има тип
    	if(isset($rec->type)){
    		$meta = ($rec->type == 'input') ? 'canConvert' : (($rec->type == 'waste') ? 'canStore,canConvert' : 'canManifacture');
    		$products = cat_Products::getByProperty($meta);
    		unset($products[$masterRec->productId]);
    		
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
    		
    		// Поле за бързо добавяне на прогрес, ако може
    		if(empty($rec->id) && planning_ProductionTaskDetails::haveRightFor('add', (object)array('taskId' => $masterRec->id))){
    			$caption = ($rec->type == 'input') ? 'Вложено' : (($rec->type == 'waste') ? 'Отпадък' : 'Произведено');
    			$form->FLD('inputedQuantity', 'double(Min=0)', "caption={$caption},before=storeId");
    		}
    		
    		$Double = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')));
    		$shortUom = cat_UoM::getShortName($masterRec->packagingId);
    		$measureUom = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
    		$unit = tr($measureUom) . " " . tr('за') . " " . $Double->toVerbal($masterRec->plannedQuantity) . " " . $shortUom;
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
    protected static function on_AfterInputEditForm($mvc, &$form)
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
    	
    		// Проверка дали артикула може да бъде избран
    		if(!self::canAddProductToTask($rec->taskId, $rec->productId, $msg, $error)){
    			$method = ($error === TRUE) ? 'setError' : 'setWarning';
    			$form->{$method}('productId', $msg);
    		}
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
    		$row->measureId = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
    		
    		if(isset($rec->storeId)){
    			$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    		}
    		$row->ROW_ATTR['class'] = ($rec->type == 'input') ? 'row-added' : (($rec->type == 'waste') ? 'row-removed' : 'state-active');
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
    		if(in_array($state, array('active', 'waiting', 'wakeup', 'draft'))){
    			if($action == 'add'){
    				$requiredRoles = $mvc->getRequiredRoles('addtoactive', $rec);
    			}
    		} else {
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if(($action == 'delete' || $action == 'edit') && isset($rec->taskId) && isset($rec->id)){
    		if(planning_ProductionTaskDetails::fetchField("#taskId = {$rec->taskId} AND #productId = {$rec->productId}")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Обновяване на изпълненото количество
     * 
     * @param int $taskId    - ид на задача
     * @param int $productId - ид на артикул
     * @param string $type   - вид на действието
     * @return void
     */
    public static function updateTotalQuantity($taskId, $productId, $type)
    {
    	$rec = self::fetch("#taskId = {$taskId} AND #productId = {$productId} AND #type = '{$type}'");
    	if(empty($rec)) return;
    	
    	$query = planning_ProductionTaskDetails::getQuery();
    	$query->where("#taskId = {$taskId} AND #productId = {$productId} AND #type = '{$type}' AND #state != 'rejected'");
    	$query->XPR('sum', 'double', 'SUM(#quantity)');
    	$query->show('quantity,sum');
    	
    	$sum = $query->fetch()->sum;
    	$rec->totalQuantity = (!empty($sum)) ? $sum : 0;
    	
    	self::save($rec, 'totalQuantity');
    }
    
    
    /**
     * Намира всички допустими артикули от дадения тип за една операция
     * 
     * @param int $taskId
     * @param input|product|waste $type
     * @return array
     */
    public static function getOptionsByType($taskId, $type)
    {
    	$taskRec = planning_Tasks::fetchRec($taskId);
    	$options = array();
    	expect(in_array($type, array('input', 'waste', 'production')));
    	
    	$query = self::getQuery();
    	$query->where("#taskId = {$taskId}");
    	$query->where("#type = '{$type}'");
    	$query->show('productId');
    	while($rec = $query->fetch()){
    		$options[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
    	}
    	
    	if($type == 'input'){
    		
    		// Всички избрани вложими артикули от задачи към същото задание
    		$tQuery = planning_Tasks::getQuery();
    		$tQuery->EXT('canConvert', 'cat_Products', 'externalName=canConvert,externalKey=productId');
    		$tQuery->notIn('productId', array_keys($options));
    		$tQuery->where("#originId = {$taskRec->originId} AND #inputInTask = {$taskRec->id} AND #state != 'draft' AND #state != 'rejected' AND #state != 'pending'");
    		$tQuery->show('productId');
    		
    		$taskOptions = array();
    		while($tRec = $tQuery->fetch()){
    			$taskOptions[$tRec->productId] = cat_Products::getTitleById($tRec->productId, FALSE);
    		}
    		
    		if(count($taskOptions)){
    			$options += array('t' => (object)array('group' => TRUE, 'title' => tr('Задачи'))) + $taskOptions;
    		}
    		
    		// Ако има избрано оборудване
    		if(!empty($taskRec->fixedAssets)){
    			
    			// Извличане на артикулите от групата му
    			$norms = planning_AssetResourcesNorms::getNorms($taskRec->fixedAssets);
    			
    			// Имали такива, които ги няма в масива
    			$addOptions = array();
    			if(is_array($norms)){
    				foreach ($norms as $nRec){
    					if(isset($nRec->productId) && !array_key_exists($nRec->productId, $options)){
    						$addOptions[$nRec->productId] = cat_Products::getTitleById($nRec->productId, FALSE);
    					}
    				}
    			}
    			
    			// Ако има добавят се с групата на оборудването в опциите
    			if(count($addOptions)){
    				$options += array($norms['g']) + $addOptions;
    			}
    		}
    	} elseif($type == 'production'){
    		if(!array_key_exists($taskRec->productId, $options)){
    			$options[$taskRec->productId] = cat_Products::getTitleById($taskRec->productId, FALSE);
    		}
    	}
    	
    	return $options;
    }
    
    
    
    /**
     * Информация за артикула в операцията
     * 
     * @param mixed $taskId  - ид или запис на операция
     * @param int $productId - ид на артикул
     * @param string $type   - вид на действието
     * @return stdClass
     * 			o productId       - ид на артикула
     *  		o packagingId     - ид на опаковката
     *   		o quantityInPack  - к-во в опаковката
     *    		o plannedQuantity - планирано к-во
     *     		o totalQuantity   - изпълнено к-во
     *         	o indTime         - норма
     */
    public static function getInfo($taskId, $productId, $type)
    {
    	expect(in_array($type, array('input', 'waste', 'production')));
    	
    	// Ако артикула е същия като от операцията, връща се оттам
    	$taskRec = planning_Tasks::fetchRec($taskId, 'totalQuantity,fixedAssets,productId,indTime,packagingId,quantityInPack,plannedQuantity');
    	if($taskRec->productId == $productId) return $taskRec;
    	
    	// Ако има запис в артикули за него, връща се оттам
    	$query = self::getQuery();
    	$query->where("#taskId = {$taskRec->id} AND #productId = {$productId} AND #type = '{$type}'");
    	$query->show('productId,indTime,packagingId,quantityInPack,plannedQuantity,totalQuantity');
    	if($rec = $query->fetch()) return $rec;
    	
    	// Ако е влагане и артикула в избран като вложим за тая операция, връща се оттам
    	if($type == 'input'){
    		$tQuery = planning_Tasks::getQuery();
    		$tQuery->where("#productId = {$productId} AND #inputInTask = {$taskRec->id} AND #state != 'rejected' AND #state != 'closed' AND #state != 'draft' AND #state != 'pending'");
    		$tQuery->show('productId,packagingId,quantityInPack,plannedQuantity,totalQuantity');
    		if($tRec = $tQuery->fetch()){
    			$tRec->totalQuantity = (!empty($tRec->totalQuantity)) ? $tRec->totalQuantity : 0;
    			
    			return $tRec;
    		}
    	}
    	
    	// В краен случай се връща от дефолтните данни в оборудването
    	if(isset($taskRec->fixedAssets)){
    		$norms = planning_AssetResourcesNorms::getNorms($taskRec->fixedAssets, $productId);
    		if(array_key_exists($productId, $norms)) return $norms[$productId];
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
    	if(!empty($rec->inputedQuantity)){
    		$dRec = (object)array('taskId' => $rec->taskId, 'productId' => $rec->productId, 'type' => $rec->type, 'quantity' => $rec->inputedQuantity);
    		planning_ProductionTaskDetails::save($dRec);
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
    				$data->toolbar->addBtn('За влагане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'input', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/wooden-box.png,title=Добавяне на вложим артикул');
    			}
    		}
    		
    		if(cat_Products::getByProperty('canManifacture', NULL, 1)){
    			if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'production'))){
    				$data->toolbar->addBtn('За произвеждане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'production', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/package.png,title=Добавяне на вложим артикул');
    			}
    		}
    		
    		if(cat_Products::getByProperty('canStore,canConvert', NULL, 1)){
    			if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'waste'))){
    				$data->toolbar->addBtn('За отпадък', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'waste', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/recycle.png,title=Добавяне на отпаден артикул');
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
    	$data->singleTitle = ($data->form->rec->type == 'input') ? 'артикул за влагане' : (($data->form->rec->type == 'waste') ? 'отпадъчен артикул' : 'заготовка');
    }
    
    
    /**
     * Помощна ф-я проверяваща може ли артикула да бъде избран
     * 
     * @param int $taskId
     * @param int $productId
     * @param string|NULL $msg
     * @param boolean|NULL $error
     * @return boolean
     */
    private static function canAddProductToTask($taskId, $productId, &$msg = NULL, &$error = NULL)
    {
    	$taskRec = planning_Tasks::fetch($taskId);
    	
    	// Ако има норма за артикула
    	if(isset($taskRec->fixedAssets)){
    	    $norm = planning_AssetResourcesNorms::getNorms($taskRec->fixedAssets, $productId);
    	    if(array_key_exists($productId, $norm)){
    	        $groupName = $norm['g']->title;
    	        $msg = "Артикула има зададена норма в|* <b>{$groupName}</b>";
    	        $error = 'FALSE';
    	        return FALSE;
    	    }
    	}
    	
    	// Ако е избран да се влага от друга задача
    	$inTaskId = planning_Tasks::fetchField("#inputInTask = {$taskRec->id} AND #productId = {$productId} AND (#state = 'active' || #state = 'wakeup' || #state = 'stopped' || #state = 'closed')");
    	if(!empty($inTaskId)){
    		$inTaskId = planning_Tasks::getLink($inTaskId, 0);
    		$msg = "Артикулът е избран да се влага в операцията от|* <b>{$inTaskId}</b>";
    		$error = 'FALSE';
    		return FALSE;
    	}
    	
    	return TRUE;
    }
}