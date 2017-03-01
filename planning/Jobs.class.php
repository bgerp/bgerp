<?php



/**
 * Мениджър на Задания за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задания за производство
 */
class planning_Jobs extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
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
    public $demandReasonChangeState = 'stop,wakeup,activateAgain';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, plg_Sorting, acc_plg_DocumentSummary, plg_Search, doc_SharablePlg, change_Plugin, plg_Clone, plg_Printing,bgerp_plg_Blank';
    
    
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
     * Койможе да създава задание от продажба
     */
    public $canCreatejobfromsale = 'ceo, job';
    
    
    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'ceo, job';
    
    
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
    public $listFields = 'title=Документ, dueDate, quantity=Количество->|*<small>|Планирано|*</small>,quantityFromTasks=Количество->|*<small>|Произведено|*</small>, quantityProduced=Количество->|*<small>|Заскладено|*</small>, quantityNotStored=Количество->|*<small>|Незаскладено|*</small>, folderId, state,modifiedOn,modifiedBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutJob.shtml';
    
    
    /**
     * Поле за дата по което ще филтрираме
     */
    public $filterDateField = 'dueDate';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'Tasks=tasks_Tasks';
    
    
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
    public $fieldsNotToClone = 'dueDate,quantityProduced,history';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'silent,mandatory,caption=Артикул');
    	$this->FLD('dueDate', 'date(smartTime)', 'caption=Падеж,mandatory');
    	$this->FLD('quantity', 'double(decimals=2)', 'caption=Количество->Планирано,mandatory,silent');
    	$this->FLD('quantityFromTasks', 'double(decimals=2)', 'input=none,caption=Количество->Произведено,notNull,value=0');
    	$this->FLD('quantityProduced', 'double(decimals=2)', 'input=none,caption=Количество->Заскладено,notNull,value=0');
    	$this->FLD('notes', 'richtext(rows=3,bucket=Notes)', 'caption=Забележки');
    	$this->FLD('tolerance', 'percent', 'caption=Толеранс,silent');
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
    	
    	$this->FLD('sharedUsers', 'userList(roles=planning|ceo)', 'caption=Споделяне->Потребители');
    	$this->FLD('history', 'blob(serialize, compress)', 'caption=Данни,input=none');
    	
    	$this->setDbIndex('productId');
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
    	
    	$form->setReadOnly('productId');
    	$pInfo = cat_Products::getProductInfo($rec->productId);
    	$uomName = cat_UoM::getShortName($pInfo->productRec->measureId);
    	
    	$form->setField('quantity', "unit={$uomName}");
    	$form->setSuggestions('tolerance', array('' => '') + arr::make('5 %,10 %,15 %,20 %,25 %,30 %', TRUE));
    	
		if($tolerance = cat_Products::getParams($rec->productId, 'tolerance')){
    		$form->setDefault('tolerance', $tolerance);
    	}
    	
    	if(isset($rec->saleId)){
    		$saleRec = sales_Sales::fetch($rec->saleId);
    		$dQuantity = sales_SalesDetails::fetchField("#saleId = {$rec->saleId} AND #productId = {$rec->productId}", 'quantity');
    		$form->setDefault('quantity', $dQuantity);
    		
    		// Ако има данни от продажба, попълваме ги
    		$form->setDefault('deliveryTermId', $saleRec->deliveryTermId);
    		$form->setDefault('deliveryDate', $saleRec->deliveryTime);
    		$form->setDefault('deliveryPlace', $saleRec->deliveryLocationId);
    		$locations = crm_Locations::getContragentOptions($saleRec->contragentClassId, $saleRec->contragentId);
    		$form->setOptions('deliveryPlace', $locations);
    		$caption = "|Данни от|* <b>" . sales_Sales::getRecTitle($rec->saleId) . "</b>";
    		
    		$form->setField('deliveryTermId', "caption={$caption}->Условие,changable");
    		$form->setField('deliveryDate', "caption={$caption}->Срок,changable");
    		$form->setField('deliveryPlace', "caption={$caption}->Място,changable");
    	} else {
    		
    		// Ако заданието не е към продажба, скриваме полетата от продажбата
    		$form->setField('deliveryTermId', 'input=none');
    		$form->setField('deliveryDate', 'input=none');
    		$form->setField('deliveryPlace', 'input=none');
    	}
    	
    	// При ново задание, ако текущия потребител има права го добавяме като споделен
    	if(haveRole('planning,ceo') && empty($rec->id)){
    		$form->setDefault('sharedUsers', keylist::addKey($rec->sharedUsers, core_Users::getCurrent()));
    	}
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
    	
    	$contragentsWithJobs = self::getContragentsWithJobs();
    	if(count($contragentsWithJobs)){
    		$enum = arr::fromArray($contragentsWithJobs);
    		$data->listFilter->FLD('contragent', "enum(,{$enum})", 'caption=Контрагенти,input,silent');
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
    					arr::placeInAssocArray($data->listFields, array('createdOn' => 'Създаване||Created->На'), 'modifiedOn');
    					arr::placeInAssocArray($data->listFields, array('createdBy' => 'Създаване||Created->От||By'), 'modifiedOn');
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
    	$options = core_Cache::get("planning_Jobs", 'contragentsWithJobs');
    	if($options === FALSE) {
    		$options = array();
    		$query = self::getQuery();
    		$query->where("#saleId IS NOT NULL");
    		while($jRec = $query->fetch()){
    			$sRec = sales_Sales::fetch($jRec->saleId, 'folderId');
    			$options[$sRec->folderId] = doc_Folders::getTitleById($sRec->folderId);
    		}
    	
    		self::logInfo("Кеширане на папките на контрагентите със задания");
    		core_Cache::set("planning_Jobs", 'contragentsWithJobs', $options, 20);
    	}
    	
    	return $options;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, &$data)
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
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		$weight = cat_Products::getWeight($rec->productId, NULL, $rec->quantity);
    		$rec->brutoWeight = ($weight) ? $weight : NULL;
    		
    		// Колко е еденичното тегло
    		if($weight = cat_Products::getParams($rec->productId, 'transportWeight')){
    			$rec->weight = $weight * $rec->quantity;
    		} else {
    			$rec->weight = NULL;
    		}
    		
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
    	}
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	// Споделяме текущия потребител със нишката на заданието
    	$cu = core_Users::getCurrent();
    	doc_ThreadUsers::addShared($rec->threadId, $rec->containerId, $cu);
    	
    	// Записваме в историята на действията, че кога и от кого е създаден документа
    	self::addToHistory($rec->history, 'created', $rec->createdOn, $rec->createdBy);
    	$mvc->save($rec, 'history');
    	
    	core_Cache::remove("planning_Jobs", 'contragentsWithJobs');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->title = $mvc->getLink($rec->id, 0);
    	
    	if(isset($rec->productId)){
    		$measureId = cat_Products::fetchField($rec->productId, 'measureId');
    		$shortUom = cat_UoM::getShortName($measureId);
    		$rec->quantityFromTasks = planning_TaskActions::getQuantityForJob($rec->id, 'product');
    		$row->quantityFromTasks = $mvc->getFieldType('quantity')->toVerbal($rec->quantityFromTasks);
    	}
    	
    	$rec->quantityNotStored = $rec->quantityFromTasks - $rec->quantityProduced;
    	$row->quantityNotStored = $mvc->getFieldType('quantity')->toVerbal($rec->quantityNotStored);
    	
    	$rec->quantityToProduce = $rec->quantity - $rec->quantityProduced;
    	$row->quantityToProduce = $mvc->getFieldType('quantity')->toVerbal($rec->quantityToProduce);
    	
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
    	$row->measureId = $shortUom;
    	
    	$tolerance = ($rec->tolerance) ? $rec->tolerance : 0;
    	$diff = $rec->quantity * $tolerance;
    	
    	foreach (array('quantityFromTasks', 'quantityProduced') as $fld){
    		if($rec->{$fld} < ($rec->quantity - $diff)){
    			$color = 'black';
    		} elseif($rec->{$fld} >= ($rec->quantity - $diff) && $rec->{$fld} <= ($rec->quantity + $diff)){
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
    		
    		if($sBomId = cat_Products::getLastActiveBom($rec->productId, 'sales')->id){
    			$row->sBomId = cat_Boms::getLink($sBomId, 0);
    		}
    		
    		if($pBomId = cat_Products::getLastActiveBom($rec->productId, 'production')->id){
    			$row->pBomId = cat_Boms::getLink($pBomId, 0);
    		}
    		
    		$date = ($rec->state == 'draft') ? NULL : $rec->modifiedOn;
    		$lg = core_Lg::getCurrent();
    		$row->origin = cat_Products::getAutoProductDesc($rec->productId, $date, 'detailed', 'internal', $lg, $rec->quantity);
    		
    		if(isset($rec->departments)){
    			
    			$row->departments = '';
    			$departments = keylist::toArray($rec->departments);
    			foreach ($departments as $dId){
    				$row->departments .= hr_Departments::getHyperlink($dId, TRUE) . "<br>";
    			}
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
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
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
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
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
    	 
    	if(($action == 'activate' || $action == 'restore' || $action == 'conto' || $action == 'write' || $action == 'add') && isset($rec->productId) && $res != 'no_one'){
    		
    		// Ако има активно задание, да не може друга да се възстановява,контира,създава или активира
    		$where = "#productId = {$rec->productId}" . ((isset($rec->saleId)) ? " AND #saleId = {$rec->saleId}" : " AND #saleId IS NULL");
    		if($mvc->fetchField("{$where} AND (#state = 'active' || #state = 'stopped' || #state = 'wakeup')", 'id')){
    			$res = 'no_one';
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
    }
    
    
    /**
     * Връща масив от използваните документи в даден документ (като цитат или са включени в детайлите му)
     * 
     * @param int $id - запис
     * @return param $res - масив с използваните документи
     * 					[class] - инстанция на документа
     * 					[id] - ид на документа
     */
    function getUsedDocs_($id)
    {
    	$rec = $this->fetchRec($id);
    	$cid = cat_Products::fetchField($rec->productId, 'containerId');
    	$res[$cid] = $cid;
    
    	return $res;
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
    public static function on_AfterActivation($mvc, &$rec)
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
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
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
    }
    
    
    /**
     * След промяна на състоянието
     */
    public static function on_AfterChangeState($mvc, &$rec, $action)
    {
    	// Записваме в историята действието
    	self::addToHistory($rec->history, $action, $rec->modifiedOn, $rec->modifiedBy, $rec->_reason);
    	$mvc->save($rec, 'history');
    	
    	// Ако заданието е затворено, затваряме и задачите към него
    	if($rec->state == 'closed'){
    		$count = 0;
    		$tQuery = planning_Tasks::getQuery();
    		$tQuery->where("#originId = {$rec->containerId} AND #state != 'draft' AND #state != 'rejected' AND #state != 'stopped'");
    		while($tRec = $tQuery->fetch()){
    			$tRec->state = 'closed';
    			cls::get('planning_Tasks')->save_($tRec, 'state');
    			$count++;
    		}
    		
    		core_Statuses::newStatus(tr("|Затворени са|* {$count} |задачи по заданието|*"));
    	}
    	
    	doc_Containers::touchDocumentsByOrigin($rec->containerId);
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
    	 
    	 $listFields = arr::make('tools=Пулт,title=Документ,dueDate=Падеж,saleId=Към продажба,quantity=Количество,quantityProduced=Произведено,createdBy=Oт||By,createdOn=На');
    	 
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
     * Може ли документа да се добави в посочената папка?
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$cover = doc_Folders::getCover($folderId);
    	
    	return $cover->isInstanceOf('hr_Departments') || $cover->haveInterface('crm_ContragentAccRegIntf');
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
    	$db = new core_Db();
    	
    	//if ($db->tableExists("planning_production_note_details") && ($db->tableExists("planning_production_note"))) {
    		//$prodQuery = planning_ProductionNoteDetails::getQuery();
    		
    		//$prodQuery->EXT('state', 'planning_ProductionNotes', 'externalName=state,externalKey=noteId');
    		//$prodQuery->XPR('totalQuantity', 'double', 'SUM(#quantity)');
    		//$prodQuery->where("#jobId = {$rec->id}");
    		//$prodQuery->where("#state = 'active'");
    		//$prodQuery->show('totalQuantity');
    		
    		//$producedQuantity += $prodQuery->fetch()->totalQuantity;
    	//}
    	
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
    	$form->setOptions('productId', array('' => '') + $this->getSelectableProducts($saleId));
    	$form->input();
    	if($form->isSubmitted()){
    		if(isset($form->rec->productId)){
    			$threadId = sales_Sales::fetchField($saleId, 'threadId');
    			
    			redirect(array($this, 'add', 'threadId' => $threadId, 'productId' => $form->rec->productId, 'saleId' => $saleId, 'ret_url' => TRUE));
    		}
    	}
    	
    	$form->toolbar->addSbBtn('Ново задание', 'default', array('class' => 'btn-next fright'), 'ef_icon = img/16/move.png, title=Създаване на ново задание');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    	
    	$form = $form->renderHtml();
    	$tpl = $this->renderWrapping($form);
    	
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
}
