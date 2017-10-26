<?php



/**
 * Мениджър на Задания за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задания за производство
 */
class planning_Jobs extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf,store_iface_ReserveStockSourceIntf,hr_IndicatorsSourceIntf';
    
    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = TRUE;
    /**
     * Заглавие
     */
    public $title = 'Задания за производство';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Задание за производство';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Job';
    
    
    /**
     * За кои действия да се изисква основание
     * 
     * @see planning_plg_StateManager
     */
    public $demandReasonChangeState = 'stop,wakeup';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, plg_Sorting, acc_plg_DocumentSummary, plg_Search, doc_SharablePlg, change_Plugin, plg_Clone, plg_Printing';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'dueDate,quantity,notes,tolerance';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, job';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, job';
    
    
    /**
     * Кой може да създава задание от продажба
     */
    public $canClonetasks = 'taskPlanning,ceo';
    
    
    /**
     * Кой може да създава задание от продажба
     */
    public $canCreatejobfromsale = 'ceo, job';
    
    
    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'ceo, job';
    
    
    /**
     * Кой може да променя активирани записи
     * @see change_Plugin
     */
    public $canChangerec = 'ceo, job';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, planning, job';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, planning, job';
    
    
	/**
	 * Полета за търсене
	 */
	public $searchFields = 'productId, notes, saleId, deliveryPlace, deliveryDate, deliveryTermId, deliveryPlace';
	
	
	/**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/clipboard_text.png';
    
    
    /**
     * Кой може да клонира
     */
    public $canClonerec = 'ceo, job';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title=Документ, dueDate, packQuantity=Количество->|*<small>|Планирано|*</small>,quantityFromTasks=Количество->|*<small>|Произведено|*</small>, quantityProduced=Количество->|*<small>|Заскладено|*</small>, quantityNotStored=Количество->|*<small>|Незаскладено|*</small>, packagingId,folderId, state, modifiedOn,modifiedBy';
    
    
    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutJob.shtml';
    
    
    /**
     * Поле за дата по което ще филтрираме
     */
    public $filterDateField = 'createdOn, dueDate,deliveryDate,modifiedOn';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'Tasks=planning_Tasks';
    
    
    /**
     * Вербални наименования на състоянията
     */
    private static $actionNames = array('created'  => 'Създаване', 
    								    'active'   => 'Активиране', 
    								    'stopped'  => 'Спиране', 
    								    'closed'   => 'Приключване', 
    									'rejected' => 'Оттегляне',
    									'restore'  => 'Възстановяване',
    								    'wakeup'   => 'Събуждане');
    
    
    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = TRUE;
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'dueDate,quantityProduced,history,oldJobId';

    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'silent,mandatory,caption=Артикул');
    	$this->FLD('oldJobId', 'int', 'silent,after=productId,caption=Предишно задание,removeAndRefreshForm=notes|department|sharedUsers|packagingId|quantityInPack|storeId,input=none');
    	$this->FLD('dueDate', 'date(smartTime)', 'caption=Падеж,mandatory');
    	
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка','smartCenter,mandatory,input=hidden,before=packQuantity');
    	$this->FNC('packQuantity', 'double(Min=0,smartRound)', 'caption=Количество,input,mandatory,after=jobQuantity');
    	$this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
    	$this->FLD('quantity', 'double(decimals=2)', 'caption=Количество->Планирано,input=none');
    	
    	$this->FLD('quantityFromTasks', 'double(decimals=2)', 'input=none,caption=Количество->Произведено,notNull,value=0');
    	$this->FLD('quantityProduced', 'double(decimals=2)', 'input=none,caption=Количество->Заскладено,notNull,value=0');
    	$this->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Забележки');
    	$this->FLD('tolerance', 'percent(suggestions=5 %|10 %|15 %|20 %|25 %|30 %)', 'caption=Толеранс,silent');
    	$this->FLD('department', 'key(mvc=hr_Departments,select=name,allowEmpty)', 'caption=Структурно звено');
    	$this->FLD('deliveryDate', 'date(smartTime)', 'caption=Данни от договора->Срок');
    	$this->FLD('deliveryTermId', 'key(mvc=cond_DeliveryTerms,select=codeName,allowEmpty)', 'caption=Данни от договора->Условие');
    	$this->FLD('deliveryPlace', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Данни от договора->Място');
    	
    	$this->FLD('weight', 'cat_type_Weight', 'caption=Тегло,input=none');
    	$this->FLD('brutoWeight', 'cat_type_Weight', 'caption=Бруто,input=none');
    	$this->FLD('state',
    			'enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Приключен, stopped=Спрян, wakeup=Събуден)',
    			'caption=Състояние, input=none'
    	);
    	$this->FLD('saleId', 'key(mvc=sales_Sales)', 'input=hidden,silent,caption=Продажба');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад');
    	
    	$this->FLD('sharedUsers', 'userList', 'caption=Споделяне->Потребители');
    	$this->FLD('history', 'blob(serialize, compress)', 'caption=Данни,input=none');
    	
    	$this->setDbIndex('productId');
    }
    
    
    /**
     * Връща последните валидни задания за артикула
     * 
     * @param int $productId    - ид на артикул
     * @param string $saleId    - ид на продажба, NULL ако няма
     * @return array $res       - масив с предишните задания
     */
    public static function getOldJobs($productId, $saleId = NULL)
    {
    	$res = array();
    	$query = self::getQuery();
    	$query->show('id,productId,state');
    	$where = "#productId = {$productId} AND (#state = 'active' OR #state = 'wakeup' OR #state = 'stopped' OR #state = 'closed') AND ";
    	$where .= ($saleId) ? "#saleId = {$saleId}" : "#saleId IS NULL";
    	$query->where($where);
    	$query->orderBy('id', 'DESC');
    	
    	while($rec = $query->fetch()){
    		$res[$rec->id] = self::getRecTitle($rec);
    	}
    	
    	return $res;
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
    	
    	// Ако има предишни задания зареждат се за избор
    	$oldJobs = self::getOldJobs($form->rec->productId);
    	if(count($oldJobs)){
    		$form->setField('oldJobId', 'input');
    		$form->setOptions('oldJobId', array('' => '') + $oldJobs);
    	}
    	
    	$form->setReadOnly('productId');
    	$pInfo = cat_Products::getProductInfo($rec->productId);
    	$uomName = cat_UoM::getShortName($pInfo->productRec->measureId);
    	
    	$packs = cat_Products::getPacks($rec->productId);
    	$form->setOptions('packagingId', $packs);
    	
    	// Ако артикула не е складируем, скриваме полето за мярка
    	$canStore = cat_Products::fetchField($rec->productId, 'canStore');
    	if($canStore == 'no'){
    		$form->setDefault('packagingId', key($packs));
    		$measureShort = cat_UoM::getShortName($rec->packagingId);
    		$form->setField('packQuantity', "unit={$measureShort}");
    	} else {
    		$form->setField('packagingId', 'input');
    	}
    	
		if($tolerance = cat_Products::getParams($rec->productId, 'tolerance')){
    		$form->setDefault('tolerance', $tolerance);
    	}
    	
    	if(isset($rec->saleId)){
    		$saleRec = sales_Sales::fetch($rec->saleId);
    		$dRec = sales_SalesDetails::fetch("#saleId = {$rec->saleId} AND #productId = {$rec->productId}");
    		$form->setDefault('packagingId', $dRec->packagingId);
    		$form->setDefault('packQuantity', $dRec->packQuantity);
    		
    		// Ако има данни от продажба, попълваме ги
    		$form->setDefault('storeId', $saleRec->shipmentStoreId);
    		$form->setDefault('deliveryTermId', $saleRec->deliveryTermId);
    		$form->setDefault('deliveryDate', $saleRec->deliveryTime);
    		$form->setDefault('deliveryPlace', $saleRec->deliveryLocationId);
    		$locations = crm_Locations::getContragentOptions($saleRec->contragentClassId, $saleRec->contragentId);
    		$form->setOptions('deliveryPlace', $locations);
    		$caption = "|Данни от|* <b>" . sales_Sales::getRecTitle($rec->saleId) . "</b>";
    		$caption = str_replace(',', ' ', str_replace(', ', ' ', $caption));
    		
    		$form->setField('deliveryTermId', "caption={$caption}->Условие,changable");
    		$form->setField('deliveryDate', "caption={$caption}->Срок,changable");
    		$form->setField('deliveryPlace', "caption={$caption}->Място,changable");
    	} else {
    		
    		// Ако заданието не е към продажба, скриваме полетата от продажбата
    		$form->setField('deliveryTermId', 'input=none');
    		$form->setField('deliveryDate', 'input=none');
    		$form->setField('deliveryPlace', 'input=none');
    		$form->setField('department', 'mandatory');
    	}
    	
    	// Ако е избрано предишно задание зареждат се данните от него
    	if(isset($rec->oldJobId)){
    		$oRec = self::fetch($rec->oldJobId, 'notes,sharedUsers,department,packagingId,storeId');
    		
    		$form->setDefault('notes', $oRec->notes);
    		$form->setDefault('sharedUsers', $oRec->sharedUsers);
    		$form->setDefault('department', $oRec->department);
    		$form->setDefault('packagingId', $oRec->packagingId);
    		$form->setDefault('storeId', $oRec->storeId);
    	} else {
    		// При ново задание, ако текущия потребител има права го добавяме като споделен
    		if(haveRole('planning,ceo') && empty($rec->id)){
    			$form->setDefault('sharedUsers', keylist::addKey($rec->sharedUsers, core_Users::getCurrent()));
    		}
    	}
    	
    	$form->setDefault('packagingId', key($packs));
    	$departments = cls::get('hr_Departments')->makeArray4Select('name', "#type = 'workshop'", 'id');
    	$form->setOptions('department', array('' => '') + $departments);
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	if(!Request::get('Rejected', 'int')){
    		$data->listFilter->FNC('view', "enum(createdOn=По дата на създаване,dueDate=По дата на падеж,deliveryDate=По дата за доставка,progress=Според изпълнението,all=Всички,draft=Черновите,active=Активните,activenotasks=Активните без задачи,stopped=Спрените,closed=Приключените,wakeup=Събудените)", 'caption=Изглед,input,silent');
    		$data->listFilter->input('view', 'silent');
    		$data->listFilter->setDefault('view', 'createdOn');
    		$data->listFilter->showFields .= ',view';
    	}
    	
    	$data->listFilter->setField("selectPeriod", "caption=Падеж");
    	$contragentsWithJobs = self::getContragentsWithJobs();
    	if(count($contragentsWithJobs)){
    		$data->listFilter->FLD('contragent', "int", 'caption=Контрагенти,input,silent');
    		$data->listFilter->setOptions('contragent', array('' => '') + $contragentsWithJobs);
    		$data->listFilter->input('contragent', 'silent');
    	}
    	
    	$data->listFilter->input();
    	$data->listFilter->showFields .= ',contragent';
    	
    	if($filter = $data->listFilter->rec){
    		if(isset($filter->contragent)){
    			
    			// Намиране на ид-та на всички продажби в избраната папка на контрагента
    			$sQuery = sales_Sales::getQuery();
    			$sQuery->where("#folderId = {$filter->contragent}");
    			$sQuery->show('id');
    			$sales = arr::extractValuesFromArray($sQuery->fetchAll(), 'id');
    			
    			// Филтрират се само тези задания към посочените продажби
    			$data->query->where("#saleId IS NOT NULL");
    			$data->query->in("saleId", $sales);
    		}
    		
    		// Филтър по изглед
    		if(isset($filter->view)){
    			switch($filter->view){
    				case 'createdOn':
    					unset($data->listFields['modifiedOn']);
    					unset($data->listFields['modifiedBy']);
    					$data->listFields['createdOn'] = 'Създаване||Created->На';
    					$data->listFields['createdBy'] = 'Създаване||Created->От||By';
    					$data->query->orderBy('createdOn', 'DESC');
    					break;
    				case 'dueDate':
    					$data->query->orderBy('dueDate', 'ASC');
    					$data->query->where("#state = 'active'");
    					break;
    				case 'deliveryDate':
    					arr::placeInAssocArray($data->listFields, array('deliveryDate' => 'Дата за доставка'), 'modifiedOn');
    					$data->query->orderBy('deliveryDate', 'ASC');
    					break;
    				case 'draft':
    					$data->query->where("#state = 'draft'");
    					break;
    				case 'active':
    					$data->query->where("#state = 'active'");
    					break;
    				case 'stopped':
    					$data->query->where("#state = 'stopped'");
    					break;
    				case 'closed':
    					$data->query->where("#state = 'closed'");
    					break;
    				case 'wakeup':
    					$data->query->where("#state = 'wakeup'");
    					break;
    				case 'all':
    					break;
    				case 'progress':
    					$data->query->XPR('progress', 'double', 'ROUND(#quantity / #quantityProduced, 2)');
    					$data->query->where("#state = 'active'");
    					$data->query->orderBy('progress', 'DESC');
    					break;
    				case 'activenotasks':
    					$tQuery = tasks_Tasks::getQuery();
    					$tQuery->where("#originId IS NOT NULL");
    					$tQuery->EXT('docClass', 'doc_Containers', 'externalName=docClass,externalKey=originId');
    					$tQuery->EXT('docId', 'doc_Containers', 'externalName=docId,externalKey=originId');
    					$tQuery->where("#originId IS NOT NULL");
    					$tQuery->where("#docClass = {$mvc->getClassId()}");
    					$tQuery->show('docId');
    					$jobIdsWithTasks = arr::extractValuesFromArray($tQuery->fetchAll(), 'docId');
    					$data->query->where("#state = 'active'");
    					
    					if(count($jobIdsWithTasks)){
    						$data->query->notIn('id', $jobIdsWithTasks);
    					}
    					
    					break;
    			}
    		}	
    	}
    }
    
    
    /**
     * Извличане с кеширане на списъка на контрагентите със задания
     * 
     * @return array $options
     */
    private static function getContragentsWithJobs()
    {
    	$oldOptions = core_Cache::get("planning_Jobs", 'contragentsWithJobs');
    	$options = ($oldOptions === FALSE) ? array() : $oldOptions;
    	
    	$query = self::getQuery();
    	$query->EXT('sFolderId', 'sales_Sales', 'externalName=folderId,externalKey=saleId');
    	$query->where("#saleId IS NOT NULL");
    	
    	if(count($options)){
    		$query->notIn("sFolderId", array_keys($options));
    	}
    	
    	while($jRec = $query->fetch()){
    		$sRec = sales_Sales::fetch($jRec->saleId, 'folderId');
    		$options[$sRec->folderId] = doc_Folders::getTitleById($sRec->folderId);
    	}
    	
    	if($oldOptions !== $options){
    		self::logInfo("Кеширане на папките на контрагентите със задания");
    		core_Cache::set("planning_Jobs", 'contragentsWithJobs', $options, 120);
    	}
    	
    	return $options;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, &$data)
    {
    	$tpl->push('planning/tpl/styles.css', "CSS");
    	
    	// Рендираме историята на действията със заданието
    	if(count($data->row->history)){
    		foreach ($data->row->history as $hRow){
    			$clone = clone $tpl->getBlock('HISTORY_ROW');
    			$clone->placeObject($hRow);
    			$clone->removeBlocks();
    			$clone->append2master();
    		}
    	}
    	
    	$data->packagingData->listFields['packagingId'] = 'Опаковка';
    	$packagingTpl = cls::get('cat_products_Packagings')->renderPackagings($data->packagingData);
    	$tpl->replace($packagingTpl, 'PACKAGINGS');
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
    	
    	if($rec->state != 'draft' && $rec->state != 'rejected'){
    		if(cat_Boms::haveRightFor('add', (object)array('productId' => $rec->productId, 'type' => 'production', 'originId' => $rec->containerId))){
    			$data->toolbar->addBtn("Рецепта", array('cat_Boms', 'add', 'productId' => $rec->productId, 'originId' => $rec->containerId, 'quantityForPrice' => $rec->quantity, 'ret_url' => TRUE, 'type' => 'production'), 'ef_icon = img/16/add.png,title=Създаване на нова работна рецепта');
    		}
    	}

    	// Бутон за добавяне на документ за производство
    	if(planning_DirectProductionNote::haveRightFor('add', (object)array('originId' => $rec->containerId))){
    		 $pUrl = array('planning_DirectProductionNote', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE);
    		 $data->toolbar->addBtn("Произвеждане", $pUrl, 'ef_icon = img/16/page_paste.png,title=Създаване на протокол за производство от заданието');
    	}
    	
    	// Бутон за добавяне на документ за влагане
    	if(planning_ConsumptionNotes::haveRightFor('add', (object)array('threadId' => $rec->threadId))){
    		$pUrl = array('planning_ConsumptionNotes', 'add', 'threadId' => $rec->threadId, 'ret_url' => TRUE);
    		$data->toolbar->addBtn("Влагане", $pUrl, 'ef_icon = img/16/produce_in.png,title=Създаване на протокол за влагане към заданието');
    	}
    	
    	if($data->toolbar->hasBtn('btnActivate')){
    		if(self::fetchField("#productId = {$rec->productId} AND (#state = 'active' OR #state = 'stopped' OR #state = 'wakeup') AND #id != '{$rec->id}'")){
    			$data->toolbar->setWarning('btnActivate', 'В момента има активно задание, желаете ли да създадете още едно?');
    		}
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
    		if(hr_Departments::count("#type = 'workshop'") && empty($rec->department)){
    			$form->setWarning('department', 'В Заданието липсва избран цех и ще бъде записано в нишката');
    		}
    		
    		$weight = cat_Products::getWeight($rec->productId, NULL, $rec->quantity);
    		$rec->brutoWeight = ($weight) ? $weight : NULL;
    			
    		// Колко е еденичното тегло
    		$weight = cat_Products::getParams($rec->productId, 'transportWeight');
    		$rec->weight = ($weight) ? $weight * $rec->quantity : NULL;
    		
    		if($rec->dueDate < dt::today()){
    			$form->setWarning('dueDate', 'Падежът е в миналото');
    		}
    			
    		if(empty($rec->id)){
    			if(isset($rec->department)){
    				$rec->folderId = hr_Departments::forceCoverAndFolder($rec->department);
    				unset($rec->threadId);
    			} elseif(empty($rec->saleId)){
    				$emptyId = hr_Departments::fetch("#systemId = 'emptyCenter'")->id;
    				$rec->folderId = hr_Departments::forceCoverAndFolder($emptyId);
    				unset($rec->threadId);
    			}
    		}
    		
    		$productInfo = cat_Products::getProductInfo($form->rec->productId);
    		$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
    		$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
    	}
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
    	if (empty($rec->quantity) || empty($rec->quantityInPack)) return;
    
    	$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
    	// Споделяме текущия потребител със нишката на заданието
    	$cu = core_Users::getCurrent();
    	doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
    	
    	// Записваме в историята на действията, че кога и от кого е създаден документа
    	self::addToHistory($rec->history, 'created', $rec->createdOn, $rec->createdBy);
    	$mvc->save($rec, 'history');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->title = $mvc->getLink($rec->id);
    	$row->quantity = $mvc->getFieldType('quantity')->toVerbal($rec->quantityFromTasks);
    	$Double = core_Type::getByName('double(smartRound)');
    	
    	if(isset($rec->productId)){
    		$measureId = cat_Products::fetchField($rec->productId, 'measureId');
    		$shortUom = cat_UoM::getShortName($measureId);
    		$rec->quantityFromTasks = planning_Tasks::getProducedQuantityForJob($rec->id);;
    		$rec->quantityFromTasks /= $rec->quantityInPack;
    		$row->quantityFromTasks = $Double->toVerbal($rec->quantityFromTasks);
    	}
    	
    	$rec->quantityProduced /= $rec->quantityInPack;
    	$row->quantityProduced = $Double->toVerbal($rec->quantityProduced);
    	
    	$rec->quantityNotStored = $rec->packQuantity - $rec->quantityProduced;
    	$row->quantityNotStored = $Double->toVerbal($rec->quantityNotStored);
    	
    	$rec->quantityToProduce = $rec->packQuantity - $rec->quantityProduced;
    	$row->quantityToProduce = $Double->toVerbal($rec->quantityToProduce);
    	
    	foreach (array('quantityNotStored', 'quantityToProduce') as $fld){
    		if($rec->{$fld} < 0){
    			$row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
    		}
    	}
    	
    	if($fields['-list']){
    		$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    		
    		if($rec->quantityNotStored > 0){
    			if(planning_DirectProductionNote::haveRightFor('add', (object)array('originId' => $rec->containerId))){
    				core_RowToolbar::createIfNotExists($row->_rowTools);
    				$row->_rowTools->addLink('Нов протокол', array('planning_DirectProductionNote', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), array('order' => 19, 'ef_icon' => "img/16/page_paste.png", 'title' => "Създаване на протокол за производство"));
    				$row->quantityNotStored = ht::createHint($row->quantityNotStored, 'Заданието очаква да се създаде протокол за производство', 'warning', FALSE);
    			}
    		}
    		
    		$row->quantityNotStored = "<div class='fright'>{$row->quantityNotStored}</div>";
    	}
    	 
    	if($rec->saleId){
    		$row->saleId = sales_Sales::getlink($rec->saleId, 0);
    	}
    	$row->measureId = cat_UoM::getShortName($rec->packagingId);
    	
    	$tolerance = ($rec->tolerance) ? $rec->tolerance : 0;
    	$diff = $rec->packQuantity * $tolerance;
    	
    	foreach (array('quantityFromTasks', 'quantityProduced') as $fld){
    		if($rec->{$fld} < ($rec->packQuantity - $diff)){
    			$color = 'black';
    		} elseif($rec->{$fld} >= ($rec->packQuantity - $diff) && $rec->{$fld} <= ($rec->packQuantity + $diff)){
    			$color = 'green';
    		} else {
    			$row->{$fld} = ht::createHint($row->{$fld}, 'Произведено е повече от планираното', 'warning', FALSE);
    			$color = 'red';
    		}
    		 
    		if($rec->{$fld} != 0){
    			$quantityRow = new core_ET("<span style='color:[#color#]'>[#quantity#]</span>");
    			$quantityRow->placeArray(array('color' => $color, 'quantity' => $row->{$fld}));
    			$row->{$fld} = $quantityRow;
    		}
    	}
    	
    	if($fields['-single']){
    		$canStore = cat_Products::fetchField($rec->productId, 'canStore');
    		$row->captionProduced = ($canStore == 'yes') ? tr('Заскладено') : tr('Изпълнено');
    		$row->captionNotStored = ($canStore == 'yes') ? tr('Незаскладено') : tr('Неизпълнено');
    		
    		if(isset($rec->deliveryPlace)){
    			$row->deliveryPlace = crm_Locations::getHyperlink($rec->deliveryPlace, TRUE);
    		}
    		
    		if(isset($rec->oldJobId)){
    			$row->oldJobId = planning_Jobs::getLink($rec->oldJobId, 0);
    		}
    		
    		if($sBomId = cat_Products::getLastActiveBom($rec->productId, 'sales')->id){
    			$row->sBomId = cat_Boms::getLink($sBomId, 0);
    		}
    		
    		if($pBomId = cat_Products::getLastActiveBom($rec->productId, 'production')->id){
    			$row->pBomId = cat_Boms::getLink($pBomId, 0);
    		}
    		
    		$date = ($rec->state == 'draft') ? NULL : $rec->modifiedOn;
    		$lg = core_Lg::getCurrent();
    		$row->origin = cat_Products::getAutoProductDesc($rec->productId, $date, 'detailed', 'internal', $lg, $rec->quantity);
    		if(isset($rec->department)){
    			$row->department = hr_Departments::getHyperlink($rec->department, TRUE);
    		}
    		
    		// Ако има сделка и пакета за партиди е инсталиран показваме ги
    		if(isset($rec->saleId) && core_Packs::isInstalled('batch')){
    			$query = batch_BatchesInDocuments::getQuery();
    			$saleContainerId = sales_Sales::fetchField($rec->saleId, 'containerId');
    			$query->where("#containerId = {$saleContainerId} AND #productId = {$rec->productId}");
    			$query->show('batch,productId');
    			
    			$batchArr = array();
    			while($bRec = $query->fetch()){
    				$batchArr = $batchArr + batch_Movements::getLinkArr($bRec->productId, $bRec->batch);
    				
    			}
    			
    			$row->batches = implode(', ', $batchArr);
    		}
    	}
    	
    	foreach (array('quantityProduced', 'quantityToProduce', 'quantityFromTasks', 'quantityNotStored') as $fld){
    		if(empty($rec->{$fld})){
    			$row->{$fld} = "<b class='quiet'>{$row->{$fld}}</b>";
    		}
    	}
    		
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')){
    		if(isset($rec->dueDate)){
    			$row->dueDate = ht::createLink($row->dueDate, array('cal_Calendar', 'day', 'from' => $row->dueDate, 'Task' => 'true'), NULL, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
    		}
    	}
    	
    	if($fields['-single']){
    		if(!$rec->quantityFromTasks){
    			unset($row->quantityFromTasks, $row->quantityNotStored);
    			unset($row->captionNotStored);
    		} else {
    			$row->measureId2 = $row->measureId;
    			$row->quantityFromTasksCaption = tr('Произведено');
    		}
    		
    		if(isset($rec->storeId)){
    			$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    		}
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$rec = static::fetchRec($rec);
    	$self = cls::get(get_called_class());
    	$pTitle = cat_Products::getTitleById($rec->productId);
    	
    	return "Job{$rec->id} - {$pTitle}";
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	$row->title = $this->getRecTitle($rec);
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $this->getRecTitle($rec);
    	
    	return $row;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'write' || $action == 'add' || $action == 'edit'){
    		
    		if(isset($rec)){
    			// Може да се добавя само ако има ориджин
    			if(empty($rec->productId)){
    				$res = 'no_one';
    			} else {
    				$productRec = cat_Products::fetch($rec->productId, 'state,canManifacture');
    				 
    				// Трябва да е активиран
    				if($productRec->state != 'active'){
    					$res = 'no_one';
    				}
    				 
    				// Трябва и да е производим
    				if($res != 'no_one'){
    					 
    					if($productRec->canManifacture == 'no'){
    						$res = 'no_one';
    					}
    				}
    			}
    			
    			// Ако се създава към продажба, тя трябва да е активна
    			if(!empty($rec->saleId)){
    				if(sales_Sales::fetchField($rec->saleId, "state") != 'active'){
    					$res = 'no_one';
    				}
    			}
    		}
    			
    		if($action == 'add' && empty($rec)){
	    		$res = 'no_one';
    		}
    	}
    	
    	if(($action == 'activate' || $action == 'restore' || $action == 'conto' || $action == 'write' || $action == 'add' || $action == 'wakeup') && isset($rec->productId) && $res != 'no_one'){
    		$isPublic = cat_Products::fetchField($rec->productId, 'isPublic');
    		
    		// Само за нестандартните артикули
    		if($isPublic != 'yes'){
    			
    			// Ако има активно задание, да не може друга да се възстановява,контира,създава или активира
    			$where = "#productId = {$rec->productId}" . ((isset($rec->saleId)) ? " AND #saleId = {$rec->saleId}" : " AND #saleId IS NULL");
    			if($mvc->fetchField("{$where} AND (#state = 'active' OR #state = 'stopped' OR #state = 'wakeup')", 'id')){
    				$res = 'no_one';
    			}
    		}
    	}
    	 
    	// Ако няма ид, не може да се активира
    	if($action == 'activate' && empty($rec->id)){
    		$res = 'no_one';
    	}
    	
    	// Ако потрбителя няма достъп до сингъла на артикула, не може да модифицира заданията към артикула
    	if(($action == 'add' || $action == 'delete') && isset($rec) && $res != 'no_one'){
    		if(!cat_Products::haveRightFor('single', $rec->productId)){
    			$res = 'no_one';
    		}
    	}
    	
    	// Само спрените могат да се променят
    	if($action == 'changerec' && isset($rec)){
    		if($rec->state != 'stopped'){
    			$res = 'no_one';
    		}
    	}
    	
    	// Ако създаваме задание от продажба искаме наистина да можем да създадем
    	if($action == 'createjobfromsale' && isset($rec)){
    		$count = $mvc->getSelectableProducts($rec->saleId);
    		if(!$count){
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'clonetasks' && isset($rec)){
    		if(empty($rec->oldJobId) || ($rec->state != 'wakeup' && $rec->state != 'active')){
    			$res = 'no_one';
    		} else {
    			$tasks = planning_Tasks::getTasksByJob($rec->oldJobId);
    			if(!count($tasks)){
    				$res = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Добавя действие към историята
     * 
     * @param array $history - масив с историята
     * @param string $action - действие
     * @param datetime $date - кога
     * @param int $userId - кой
     * @return void
     */
    private static function addToHistory(&$history, $action, $date, $userId, $reason = NULL)
    {
    	if(empty($history)){
    		$history = array();
    	}
    	
    	$arr = array('action' => self::$actionNames[$action], 'date' => $date, 'user' => $userId, 'engaction' => $action);
    	if(isset($reason)){
    		$arr['reason'] = $reason;
    	}
    	
    	$history[] = $arr;
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    protected static function on_AfterActivation($mvc, &$rec)
    {
    	// След активиране на заданието, добавяме артикула като перо
    	$listId = acc_Lists::fetchBySystemId('catProducts')->id;
    	acc_Items::force('cat_Products', $rec->productId, $listId);
    	
    	// След активиране на заданието, ако е към продажба, форсираме я като разходно перо
    	if(isset($rec->saleId)) {
    		if(cat_Products::fetchField($rec->productId, 'canStore') == 'no'){
    			if(!acc_Items::isItemInList('sales_Sales', $rec->saleId, 'costObjects')){
    				$listId = acc_Lists::fetchBySystemId('costObjects')->id;
    				acc_Items::force('sales_Sales', $rec->saleId, $listId);
    				doc_ExpensesSummary::save((object)array('containerId' => sales_Sales::fetchField($rec->saleId, 'containerId')));
    			}
    		}
    	}
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	// Подготвяме данните на историята за показване
    	$data->row->history = array();
    	if(count($data->rec->history)){
    		foreach($data->rec->history as $historyRec){
    			$historyRec['action'] = tr($historyRec['action']);
    			
    			$historyRow = (object)array('date'       => cls::get('type_DateTime')->toVerbal($historyRec['date']),
								    	    'user'       => crm_Profiles::createLink($historyRec['user']),
								    		'action'     => "<span>{$historyRec['action']}</span>",
								    		'stateclass' => "state-{$historyRec['engaction']}");
    			
    			if(isset($historyRec['reason'])){
    				$historyRow->reason = cls::get('type_Text')->toVerbal($historyRec['reason']);
    			}
    			
    			$data->row->history[] = $historyRow;
    		}
    	}
    	
    	$data->row->history = array_reverse($data->row->history, TRUE);
    	
    	$data->packagingData = new stdClass();
    	$data->packagingData->masterMvc = cls::get('cat_Products');
    	$data->packagingData->masterId = $data->rec->productId;
    	$data->packagingData->tpl = new core_ET("[#CONTENT#]");
    	cls::get('cat_products_Packagings')->preparePackagings($data->packagingData);
    }
    
    
    /**
     * След промяна на състоянието
     */
    protected static function on_AfterChangeState($mvc, &$rec, $action)
    {
    	// Записваме в историята действието
    	self::addToHistory($rec->history, $action, $rec->modifiedOn, $rec->modifiedBy, $rec->_reason);
    	$mvc->save($rec, 'history');
    	
    	// Ако заданието е затворено, затваряме и задачите към него
    	if($rec->state == 'closed'){
    		$count = 0;
    		/*
    		$tQuery = planning_Tasks::getQuery();
    		$tQuery->where("#originId = {$rec->containerId} AND #state != 'draft' AND #state != 'rejected' AND #state != 'stopped'");
    		while($tRec = $tQuery->fetch()){
    			$tRec->state = 'closed';
    			cls::get('planning_Tasks')->save_($tRec, 'state');
    			$count++;
    		}*/
    		
    		core_Statuses::newStatus(tr("|Затворени са|* {$count} |задачи по заданието|*"));
    	}
    	
    	doc_Containers::touchDocumentsByOrigin($rec->containerId);
    	
    	// Нотификация на абонираните потребители
    	if(in_array($action, array('active', 'closed', 'wakeup', 'stopped', 'rejected'))){
    		$caption = self::$actionNames[$action];
    		$jobName = $mvc->getRecTitle($rec);
    		$msg = "{$caption} на|* \"{$jobName}\"";
    		doc_Containers::notifyToSubscribedUsers($rec->containerId, $msg);
    	}
    }
    
    
    /**
     * Подготовка на заданията за артикула
     * 
     * @param stdClass $data
     */
    public function prepareJobs($data)
    {
    	$data->rows = array();
    	$data->hideToolsCol = $data->hideSaleCol = TRUE;
    	
    	// Намираме неоттеглените задания
    	$query = $this->getQuery();
    	$query->where("#productId = {$data->masterId}");
    	$query->where("#state != 'rejected'");
    	$query->orderBy("id", 'DESC');
    	while($rec = $query->fetch()){
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    		if(isset($rec->saleId)){
    			$data->hideSaleCol = FALSE;
    		}
    		
    		if($this->haveRightFor('edit', $rec)){
    			$data->hideToolsCol = FALSE;
    		}
    	}
    	
    	$masterInfo = $data->masterMvc->getProductInfo($data->masterId);
    	if(!isset($masterInfo->meta['canManifacture'])){
    		$data->notManifacturable = TRUE;
    	}
    	
    	if(!haveRole('ceo,planning,job') || ($data->notManifacturable === TRUE && !count($data->rows)) || $data->masterData->rec->state == 'template' || $data->masterData->rec->brState == 'template'){
    		$data->hide = TRUE;
    		return;
    	}
    	
    	$data->TabCaption = 'Задания';
    	$data->Tab = 'top';
    	
    	// Проверяваме можем ли да добавяме нови задания
    	if($this->haveRightFor('add', (object)array('productId' => $data->masterId))){
    		$data->addUrl = array($this, 'add', 'threadId' => $data->masterData->rec->threadId, 'productId' => $data->masterId, 'ret_url' => TRUE);
    	}
    }
    
    
    /**
     * Рендиране на заданията към артикул
     * 
     * @param stdClass $data
     * @return core_ET $tpl - шаблон на детайла
     */
    public function renderJobs($data)
    {
    	 if($data->hide === TRUE) return;
    	
    	 $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
    	 $title = tr('Задания за производство');
    	 $tpl->append($title, 'title');
    	 
    	 if(isset($data->addUrl)){
    	 	$addBtn = ht::createLink('', $data->addUrl, FALSE, 'ef_icon=img/16/add.png,title=Добавяне на ново задание за производство');
    	 	$tpl->append($addBtn, 'title');
    	 }
    	 
    	 $listFields = arr::make('tools=Пулт,title=Документ,dueDate=Падеж,saleId=Към продажба,packQuantity=Количество,quantityProduced=Произведено,packagingId=Мярка');
    	 
    	 if($data->hideSaleCol){
    	 	unset($listFields['saleId']);
    	 }
    	 
    	 if($data->hideToolsCol){
    	 	unset($listFields['tools']);
    	 }
    	 
    	 $table = cls::get('core_TableView', array('mvc' => $this));
    	 $details = $table->get($data->rows, $listFields);
    	 
    	 // Ако артикула не е производим, показваме в детайла
    	 if($data->notManifacturable === TRUE){
    	 	$tpl->append(" <span class='red small'>(" . tr('Артикулът не е производим') . ")</span>", 'title');
    	 	$tpl->append("state-rejected", 'TAB_STATE');
    	 }
    	 
    	 $tpl->replace($details, 'content');
    	 
    	 return $tpl;
    }
    
    
    /**
     * Преизчисляваме какво количество е произведено по заданието
     * 
     * @param int $id - ид на запис
     * @return void
     */
    public static function updateProducedQuantity($id)
    {
    	$rec = static::fetchRec($id);
    	$producedQuantity = 0;
    	
    	// Взимаме к-та на произведените артикули по заданието в протокола за производство
    	$directProdQuery = planning_DirectProductionNote::getQuery();
    	$directProdQuery->where("#originId = {$rec->containerId}");
    	$directProdQuery->where("#state = 'active'");
    	$directProdQuery->XPR('totalQuantity', 'double', 'SUM(#quantity)');
    	$directProdQuery->show('totalQuantity');
    	
    	$producedQuantity += $directProdQuery->fetch()->totalQuantity;
    	
    	// Обновяваме произведеното к-то по заданието
    	$rec->quantityProduced = $producedQuantity;
    	self::save($rec, 'quantityProduced');
    }
    
    
    /**
     * Екшън за избор на артикул за създаване на задание
     */
    public function act_CreateJobFromSale()
    {
    	$this->requireRightFor('createjobfromsale');
    	expect($saleId = Request::get('saleId', 'int'));
    	$this->requireRightFor('createjobfromsale', (object)array('saleId' => $saleId));
    	
    	$form = cls::get('core_Form');
    	$form->title = 'Създаване на задание към продажба|* <b>' . sales_Sales::getHyperlink($saleId, TRUE) . "</b>";
    	$form->FLD('productId', 'key(mvc=cat_Products)', 'caption=Артикул,mandatory');
    	$threadId = sales_Sales::fetchField($saleId, 'threadId');
    	
    	$selectable = $this->getSelectableProducts($saleId);
    	if(count($selectable) == 1){
    		$selectable = array_keys($selectable);
    		redirect(array($this, 'add', 'threadId' => $threadId, 'productId' => $selectable[0], 'saleId' => $saleId, 'ret_url' => array('sales_Sales', 'single', $saleId)));
    	}
    	
    	$form->setOptions('productId', array('' => '') + $selectable);
    	$form->input();
    	if($form->isSubmitted()){
    		if(isset($form->rec->productId)){
    			redirect(array($this, 'add', 'threadId' => $threadId, 'productId' => $form->rec->productId, 'saleId' => $saleId, 'ret_url' => TRUE));
    		}
    	}
    	
    	$form->toolbar->addSbBtn('Ново задание', 'default', array('class' => 'btn-next fright'), 'ef_icon = img/16/move.png, title=Създаване на ново задание');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	
    	$tpl = $this->renderWrapping($form->renderHtml());
    	core_Form::preventDoubleSubmission($tpl, $form);
    	
    	return $tpl;
    }
    
    
    /**
     * Екшън клониращ задачите от предишно задание
     */
    public function act_CloneTasks()
    {
    	$this->requireRightFor('cloneTasks');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('cloneTasks', $rec);
    	
    	$form = cls::get('core_Form');
    	$form->title = 'Клониране на пр. операции от предишно задание|* <b>' . self::getHyperlink($rec->oldJobId, TRUE) . "</b>";
    	$tasks = planning_Tasks::getTasksByJob($rec->oldJobId);
    	$form->FLD('tasks', 'keylist(mvc=planning_Tasks,select=id)', 'caption=Пр. операции,mandatory');
    	$form->setSuggestions('tasks', $tasks);
    	$form->input();
    	if($form->isSubmitted()){
    		$Tasks = cls::get('planning_Tasks');
    		$arr = keylist::toArray($form->rec->tasks);
    		
    		$count = 0;
    		foreach ($arr as $taskId){
    			$taskRec = planning_Tasks::fetch($taskId);
    			$newTask = clone $taskRec;
    			plg_Clone::unsetFieldsNotToClone($Tasks, $newTask, $taskRec);
    			$newTask->_isClone = TRUE;
    			$newTask->originId = $rec->containerId;
    			$newTask->state = 'draft';
    			unset($newTask->id);
    			unset($newTask->threadId);
    			unset($newTask->containerId);
    			
    			if ($Tasks->save($newTask)) {
    				$Tasks->invoke('AfterSaveCloneRec', array($taskRec, &$newTask));
    				$count++;
    			}
    		}
    		
    		followRetUrl(NULL, "|Клонирани задачи|*: {$count}");
    	}
    	
    	$form->toolbar->addSbBtn('Клониране на избраните', 'default', 'ef_icon = img/16/clone.png, title=Създаване на ново задание');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	 
    	$tpl = $this->renderWrapping($form->renderHtml());
    	core_Form::preventDoubleSubmission($tpl, $form);
    	 
    	return $tpl;
    }
    
    
    /**
     * Намира всички производими артикули по една продажба, към които може да се създават задания
     * 
     * @param int $saleId
     * @return array $res
     */
    private function getSelectableProducts($saleId)
    {
    	$res = sales_Sales::getManifacurableProducts($saleId);
    	foreach ($res as $productId => $name){
    		if(!$this->haveRightFor('add', (object)array('productId' => $productId, 'saleId' => $saleId))){
    			unset($res[$productId]);
    		}
    	}
    	
    	return $res;
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
    	$rec = hr_IndicatorNames::force('Активирани_задания', __CLASS__, 1);
    	$result[$rec->id] = $rec->name;
    
    	return $result;
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
    	$result = array();
    	$iRec = hr_IndicatorNames::force('Активирани_задания', __CLASS__, 1);
    	
    	$query = self::getQuery();
    	$query->where("#state = 'active' || #state = 'closed' || (#state = 'rejected' && (#brState = 'active' || #brState = 'closed'))");
    	$query->where("#activatedOn >= '{$timeline}'");
    	$query->show('activatedBy,activatedOn,state');
    	
    	while($rec = $query->fetch()){
    		$personId = crm_Profiles::fetchField("#userId = {$rec->activatedBy}", 'personId');
    		$result[] = (object)array('date'        => dt::verbal2mysql($rec->activatedOn, FALSE),
    								  'personId'    => $personId,
    								  'docId'       => $rec->id,
    				                  'docClass'    => planning_Jobs::getClassId(),
    				                  'indicatorId' => $iRec->id,
    								  'value'       => 1,
    								  'isRejected'  => $rec->state == 'rejected',
    		);
    	}
    
    	return $result;
    }
    
    
    function act_Test()
    {
    	$s = cls::get('planning_Setup');
    	$s->deleteTasks();
    }
}
