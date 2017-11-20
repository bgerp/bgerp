<?php



/**
 * Мениджър за технологични рецепти на артикули
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Boms extends core_Master
{
	
	
   /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Технологични рецепти";
    
   
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, cat_Wrapper, doc_DocumentPlg, plg_Printing, doc_plg_Close, acc_plg_DocumentSummary, doc_ActivatePlg, plg_Clone, cat_plg_AddSearchKeywords, plg_Search, change_Plugin';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'showInProduct';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "title=Документ,productId=За артикул,type,state,createdOn,createdBy,modifiedOn,modifiedBy";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId,notes';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'cat_BomDetails';
    
    
    /**
     * Кой е основния детайл
     */
    public $mainDetail = 'cat_BomDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     * 
     * @see plg_Clone
     */
    public $cloneDetails = 'cat_BomDetails';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Технологична рецепта';
    
    
    /**
     * Икона на единичния изглед на търговската рецепта
     */
    public $singleIcon = 'img/16/article2.png';
    
    
    /**
     * Икона на единичния изглед на работната рецепта
     */
    public $singleProductionBomIcon = 'img/16/article.png';
  
    
    /**
     * Абревиатура
     */
    public $abbr = "Bom";
    
    
    /**
     * Кой може да пише?
     */
    public $canEdit = 'cat,ceo,sales';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'cat,ceo,sales';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,cat,sales';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,cat,sales';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cat/tpl/SingleLayoutBom.shtml';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Искаме ли в листовия филтър да е попълнен филтъра по дата
     * @see acc_plg_DocumentSummary
     */
    public $filterAutoDate = FALSE;
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'cat,ceo';
    
    
    /**
     * Коефициент за изчисляване на минималния и максималния тираж
     */
    const PRICE_COEFFICIENT = 0.5;
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('quantity', 'double(smartRound,Min=0)', 'caption=За,silent,mandatory');
    	$this->FLD('type', 'enum(sales=Търговска,production=Работна)', 'caption=Вид,input=none');
    	$this->FLD('notes', 'richtext(rows=4,bucket=Notes)', 'caption=Забележки');
    	$this->FLD('expenses', 'percent(Мin=0)', 'caption=Общи режийни');
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен, closed=Затворен)', 'caption=Статус, input=none');
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
    	$this->FLD('showInProduct', 'enum(,auto=Автоматично,product=В артикула,job=В заданието,yes=Навсякъде,no=Никъде)', 'caption=Показване в артикула,changeable');
    	$this->FLD('quantityForPrice', 'double(smartRound,min=0)', 'caption=Изчисляване на себестойност->При тираж,silent');
    	$this->FLD('hash', 'varchar', 'input=none');
    	
    	$this->setDbIndex('productId');
    }
    
    
    /**
     * Показване на рецептата в артикула
     *
     * @param int $bomId
     * @param core_Mvc $mvc
     * @return boolean
     */
    public static function showIn($id, $className)
    {
    	$rec = self::fetchRec($id);
    	$showInProduct = !empty($rec->showInProduct) ? $rec->showInProduct : cat_Setup::get('SHOW_BOM_IN_PRODUCT');
    	
    	switch($showInProduct){
    		case 'auto':
    			$res = (cat_Products::fetchField($rec->productId, 'fixedAsset') == 'yes');
    			break;
    		case 'yes':
    			$res = TRUE;
    			break;
    		case 'product':
    			$res = ($className == 'cat_Products');
    			break;
    		case 'job':
    			$res = ($className == 'planning_Jobs');
    			break;
    		default:
    			$res = FALSE;
    	}
    	
    	return $res;
    }
    
    
    /**
     * Преди подготвяне на едит формата
     */
    protected static function on_BeforePrepareEditForm($mvc, &$res, $data)
    {
    	$type = Request::get('type');
    	if(!$type) return;
    	
    	$mvc->singleTitle = ($type == 'sales') ? 'Търговска рецепта' : 'Работна рецепта';
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
    	
    	$productInfo = cat_Products::getProductInfo($form->rec->productId);
    	$shortUom = cat_UoM::getShortName($productInfo->productRec->measureId);
    	$form->setField('quantity', "unit={$shortUom}");
    	$form->setField('quantityForPrice', "unit={$shortUom}");
    	
    	// К-то е дефолтното от заданието
    	if(isset($form->rec->originId)){
    		$origin = doc_Containers::getDocument($form->rec->originId);
    		if($origin->isInstanceOf('planning_Jobs')){
    			$form->setDefault('quantity', $origin->fetchField('quantity'));
    		}
    	}
    	$form->setDefault('quantity', 1);
    	
    	// При създаване на нова рецепта
    	if(empty($form->rec->id)){
    		if($expenses = cat_Products::getParams($form->rec->productId, 'expenses')){
    			$form->setDefault('expenses', $expenses);
    		}
    	}
    }
    
    
    /**
     * Преди запис
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(isset($rec->threadId)){
    		$rec->type = 'sales';
    		$firstDocument = doc_Containers::getDocument($rec->originId);
    		
    		if($firstDocument->isInstanceOf('planning_Jobs')){
    			$rec->type = 'production';
    		}
    	}
    }
    
    
    /**
     * Преди запис на клониран запис
     */
    protected static function on_BeforeSaveCloneRec($mvc, $rec, &$nRec)
    {
    	$nRec->cloneDetails = TRUE;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
    	if($rec->cloneDetails === TRUE) return;
    	
    	cat_BomDetails::addProductComponents($rec->productId, $rec->id, NULL, $activeBom, TRUE);
    }
    
    
    /**
     * Активира последната затворена рецепта за артикула
     * 
     * @param mixed $id
     * @return FALSE|int
     */
    private function activateLastBefore($id)
    {
    	$rec = $this->fetchRec($id);
    	if($rec->state != 'closed' && $rec->state != 'rejected') return FALSE;
    	
    	// Намираме последната приключена рецепта (различна от текущата за артикула)
    	$query = $this->getQuery();
    	$query->where("#state = 'closed' AND #id != {$rec->id} AND #productId = {$rec->productId} AND #type = '{$rec->type}'");
    	$query->orderBy('id', 'DESC');
    	$query->limit(1);
    	
    	$nextActiveBomRec = $query->fetch();
    	if($nextActiveBomRec){
    		$nextActiveBomRec->state = 'active';
    		$nextActiveBomRec->brState = 'closed';
    		$nextActiveBomRec->modifiedOn = dt::now();
    			
    		// Ако има такава я активираме
    		return $this->save_($nextActiveBomRec, 'state,brState,modifiedOn');
    	}
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
    	// При оттегляне или затваряне, ако преди документа е бил активен
    	if($rec->state == 'closed' || $rec->state == 'rejected'){
    		
    		if($rec->brState == 'active'){
    			if($nextId = $mvc->activateLastBefore($rec)){
					core_Statuses::newStatus("|Активирана е рецепта|* #Bom{$nextId}");
    			}
    		} 
    	}
    	
    	// При активиране, 
    	if($rec->state == 'active'){
    		$cRec = $mvc->fetch($rec->id);
    		
    		// Намираме всички останали активни рецепти
    		$query = static::getQuery();
    		$query->where("#state = 'active' AND #id != {$rec->id} AND #productId = {$cRec->productId} AND #type = '{$cRec->type}'");
    		
    		// Затваряме ги
    		$idCount = 0;
    		while($bomRec = $query->fetch()){
    			$bomRec->state = 'closed';
    			$bomRec->brState = 'active';
    			$bomRec->modifiedOn = dt::now();
    			$mvc->save_($bomRec, 'state,brState,modifiedOn');
    			$idCount++;
    		}
    		
    		if($idCount){
    			core_Statuses::newStatus("|Затворени са|* {$idCount} |рецепти|*");
    		}
    	}
    	
    	//  При активацията на рецептата променяме датата на модифициране на артикула
    	$type = (isset($rec->type)) ? $rec->type : $mvc->fetchField($rec->id, 'type');
    	if($type == 'sales' && $rec->state != 'draft'){
    		$productId = (isset($rec->productId)) ? $rec->productId : $mvc->fetchField($rec->id, 'productId');
    		cat_Products::touchRec($productId);
    	}
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	// Обновяваме датата на модифициране на артикула след промяна по рецептата
    	if($rec->productId){
    		$bRec = cat_Products::getLastActiveBom($rec->productId, 'sales');
    		
    		if(($rec->type == 'sales' && !$bRec) || $bRec->id == $rec->id){
    			
    			$pRec = cat_Products::fetch($rec->productId);
    			$pRec->modifiedOn = dt::now();
    			cat_Products::save($pRec);
    		}
    	}
    	
    	return $this->save($rec, 'modifiedOn,modifiedBy,searchKeywords');
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->form->toolbar->buttons['btnNewThread'])){
    		$data->form->toolbar->removeBtn('btnNewThread');
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'edit') && isset($rec)){
    		
    		// Може да се добавя само ако има ориджин
    		if(empty($rec->productId)){
    			$res = 'no_one';
    		} else {
    			$productRec = cat_Products::fetch($rec->productId, 'state,canManifacture,threadId');
    			if($rec->type != 'production' && !doc_Threads::haveRightFor('single', $productRec->threadId)){
    				$res = 'no_one';
    			} else {
    				
    				// Трябва да е активиран
    				if($productRec->state != 'active' && $productRec->state != 'template'){
    					$res = 'no_one';
    				} else {
    					if($productRec->canManifacture == 'no'){
    						$res = 'no_one';
    					}
    				}
    			}
    		}
    	}
    	
    	if(($action == 'add' || $action == 'edit' || $action == 'reject' || $action == 'restore') && isset($rec)){
    		if($rec->type == 'production'){
    			if(!haveRole('techno,ceo', $userId)){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	// Ако няма ид, не може да се активира
    	if($action == 'activate' && empty($rec->id)){
    		$res = 'no_one';
    	} elseif($action == 'activate' && isset($rec->id)){
    		if(!count(cat_BomDetails::fetchField("#bomId = {$rec->id}", 'id'))){
    			$res = 'no_one';
    		}
    	}
    	
    	// Кой може да оттегля и възстановява
    	if(($action == 'reject' || $action == 'restore') && isset($rec)){
    	
    		// Ако не можеш да редактираш записа, не можеш да оттегляш/възстановяваш
    		if(!haveRole($mvc->getRequiredRoles('edit'))){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	$row = new stdClass();
    	$row->title = $this->getRecTitle($rec);
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $rec->title;
    	
    	return $row;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(empty($rec->showInProduct)){
    		$showInProduct = cat_Setup::get('SHOW_BOM_IN_PRODUCT');
    		$row->showInProduct = $mvc->getFieldType('showInProduct')->toVerbal($showInProduct);
    		$row->showInProduct = ht::createHint($row->showInProduct, "По подразбиране");
    	}
    	
    	$row->productId = cat_Products::getShortHyperlink($rec->productId);
    	$row->title = $mvc->getLink($rec->id, 0);
    	$row->singleTitle = ($rec->type == 'sales') ? tr('Търговска рецепта') : ('Работна рецепта');
    	
    	if($row->quantity){
    		$measureId = cat_Products::getProductInfo($rec->productId)->productRec->measureId;
    		$shortUom = cat_UoM::getShortName($measureId);
    		$row->quantity .= " " . $shortUom;
    	}
    	
    	if($fields['-single'] && !doc_HiddenContainers::isHidden($rec->containerId)) {
    		
    		$rec->quantityForPrice = isset($rec->quantityForPrice) ? $rec->quantityForPrice : $rec->quantity;
    		$price = cat_Boms::getBomPrice($rec->id, $rec->quantityForPrice, 0, 0, dt::now(), price_ListRules::PRICE_LIST_COST);
    		
    		if(haveRole('ceo, acc, cat, price')){
    			$row->quantityForPrice = $mvc->getFieldType('quantity')->toVerbal($rec->quantityForPrice);
    			$rec->primeCost = ($price) ? $price : 0;
    			
    			$baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->modifiedOn);
    			$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    			$row->primeCost = $Double->toVerbal($rec->primeCost);
    			$row->primeCost = ($rec->primeCost === 0 && cat_BomDetails::fetchField("#bomId = {$rec->id}", 'id')) ? "<b class='red'>???</b>" : "<b>{$row->primeCost}</b>";
    			
    			$row->primeCost .= tr("|* <span class='cCode'>{$baseCurrencyCode}</span>, |при тираж|* {$row->quantityForPrice} {$shortUom}");
    		
    			if(!Mode::isReadOnly() && $rec->state != 'rejected'){
    				$row->primeCost .= ht::createLink('', array($mvc, 'RecalcSelfValue', $rec->id), FALSE, 'ef_icon=img/16/arrow_refresh.png,title=Преизчисляване на себестойността');
    			}
    		}
    	}
    }
    
    
    /**
     * Връща информация с ресурсите използвани в технологичната рецепта
     *
     * @param mixed $id - ид или запис
     * @return array $res - Информация за рецептата
     * 				->quantity - к-во
     * 				->resources
     * 			        o $res->productId      - ид на материала
     * 					o $res->type           - вложим или отпаден материал
	 * 			        o $res->baseQuantity   - начално количество наматериала (к-во в опаковка по брой опаковки)
	 * 			        o $res->propQuantity   - пропорционално количество на ресурса (к-во в опаковка по брой опаковки)
     */
    public static function getResourceInfo($id, $quantity, $date)
    {
    	$resources = array();
    	
    	expect($rec = static::fetchRec($id));
    	$resources['quantity'] = ($rec->quantity) ? $rec->quantity : 1;
    	$resources['expenses'] = NULL;
    	$resources['primeCost'] = static::getBomPrice($id, $quantity, 0, 0, $date, price_ListRules::PRICE_LIST_COST, $materials);
    	$resources['resources'] = array_values($materials);
    	
    	if(is_array($materials)){
    		foreach ($materials as &$m){
    			$m->propQuantity /= $m->quantityInPack;
    		}
    	}
    	
    	if($rec->expenses){
    		$resources['expenses'] = $rec->expenses;
    	}
    	
    	// Връщаме намерените ресурси
    	return $resources;
    }
    
    
    /**
     * Функция, която се извиква преди активирането на документа
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
    	if($res->id){
    		$dQuery = cat_BomDetails::getQuery();
    		$dQuery->where("#bomId = {$res->id}");
    		$dQuery->where("#type = 'input'");
    		$dQuery->show('id');
    		
    		if(!$dQuery->count()){
    			core_Statuses::newStatus('Рецептатата не може да се активира, докато няма поне един вложим ресурс', 'warning');
    			
    			return FALSE;
    		}
    	}
    }
    
    
    /**
     * Ф-я за добавяне на нова рецепта към артикул
     * 
     * @param int $productId   - ид на производим артикул
     * @param int $quantity    - количество за което е рецептата
     * @param array $details   - масив с обекти за детайли
     * 		          ->resourceId   - ид на ресурс
     * 				  ->type         - действие с ресурса: влагане/отпадък, ако не е подаден значи е влагане
     * 				  ->stageId      - опционално, към кой производствен етап е детайла
     * 				  ->baseQuantity - начално количество на ресурса
     * 				  ->propQuantity - пропорционално количество на ресурса
     * 
     * @param text $notes      - забележки
     * @param double $expenses - процент режийни разходи
     * @return int $id         - ид на новосъздадената рецепта
     */
    public static function createNewDraft($productId, $quantity, $originId, $details = array(), $notes = NULL, $expenses = NULL)
    {
    	// Проверка на подадените данни
    	expect($pRec = cat_Products::fetch($productId));
    	expect($pRec->canManifacture == 'yes', $pRec);
    	$origin = doc_Containers::getDocument($originId);
    	$type = ($origin->isInstanceOf('planning_Jobs')) ? 'production' : 'sales';
    	
    	$Double = cls::get('type_Double');
    	$Richtext = cls::get('type_Richtext');
    	
    	$rec = (object)array('productId' => $productId,
    						 'type'		 => $type,
    						 'originId'  => $originId,
    						 'folderId'  => $origin->rec()->folderId,
    						 'threadId'  => $origin->rec()->threadId,
    						 'quantity'  => $Double->fromVerbal($quantity), 
    						 'expenses'  => $expenses);
    	if($notes){
    		$rec->notes = $Richtext->fromVerbal($notes);
    	}
    	
    	// Ако има данни за детайли, проверяваме дали са валидни
    	if(count($details)){
    		foreach ($details as &$d){
    			expect($d->resourceId);
    			expect(cat_Products::fetch($d->resourceId));
    			$d->type = ($d->type) ? $d->type : 'input';
    			expect(in_array($d->type, array('input', 'pop')));
    			 
    			$d->baseQuantity   = $Double->fromVerbal($d->baseQuantity);
    			$d->propQuantity   = $Double->fromVerbal($d->propQuantity);
    			$d->quantityInPack = $Double->fromVerbal($d->quantityInPack);
    			expect($d->baseQuantity || $d->propQuantity);
    		}
    	}
    	
    	// Ако всичко е наред, записваме мастъра на рецептата
    	$rec->cloneDetails = TRUE;
    	$id = self::save($rec);
    	
    	// За всеки детайл, добавяме го към рецептата
    	if(count($details)){
    		foreach ($details as $d1){
    			$d1->bomId = $id;
    			
    			if(cls::get('cat_BomDetails')->isUnique($d1, $fields)){
    				cat_BomDetails::save($d1);
    			}
    		}
    	}
    	
    	// Връщаме ид-то на новосъздадената рецепта
    	return $id;
    }
    
    
    /**
     * Форсира изчисляването на себестойността по рецептата
     */
    function act_RecalcSelfValue()
    {
    	requireRole('ceo, acc, cat, price');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	
    	$rec->modifiedOn = dt::now();
    	$this->save($rec, 'modifiedOn');
    	
    	return new Redirect(array($this, 'single', $id), 'Себестойността е преизчислена');
    }
    
    
    /**
     * Създава дефолтната рецепта за артикула.
     * Проверява за артикула може ли да се създаде дефолтна рецепта, 
     * ако може затваря предишната дефолтна рецепта (ако е различна) и създава нова
     * активна рецепта с подадените данни.
     * 
     * @param int $productId - ид на артикул
     * @return void
     */
    public static function createDefault($productId)
    {
    	$pRec = cat_Products::fetch($productId);
    	$Driver = cat_Products::getDriver($productId);
    	$bomInfo = $Driver->getDefaultBom($pRec);
    	
    	// Ако има информация за дефолтна рецепта
    	if($bomInfo){
    		$hash = md5(serialize($bomInfo));
    		$details = array();
    		$error = array();
    		$hasInputMats = FALSE;
    		
    		// И има материали
    		if(is_array($bomInfo['materials'])){
    			foreach ($bomInfo['materials'] as $matRec){
    				
    				// Имали артикул с такъв код
    				if(!$prod = cat_Products::getByCode($matRec->code)){
    					$error[$matRec->code] = $matRec->code;
    					continue;
    				}
    				
    				// Подготвяме детайлите на рецептата
    				$nRec = new stdClass();
    				$nRec->resourceId = $prod->productId;
    				$nRec->baseQuantity = $matRec->baseQuantity;
    				$nRec->propQuantity = $matRec->propQuantity;
    				$nRec->quantityInPack = 1;
    				$nRec->type = ($matRec->waste) ? 'pop' : 'input';
    				if(isset($prod->packagingId)){
    					$nRec->packagingId = $prod->packagingId;
    					if($pRec = cat_products_Packagings::getPack($prod->productId, $prod->packagingId)){
    						$nRec->quantityInPack= $pRec->quantity;
    					}
    				} else {
    					$nRec->packagingId = cat_Products::fetchField($prod->productId, 'measureId');
    				}
    				
    				// Форсираме производствения етап
    				$details[] = $nRec;
    				
    				if($nRec->type == 'input'){
    					$hasInputMats = TRUE;
    				}
    			}
    		}
    		
    		// Ако някой от артикулите липсва, не създаваме нищо
    		if(count($error)){
    			$string = implode(',', $error);
    			$msg = tr("Базовата рецепта не може да бъде създадена|*, |защото материалите с кодове|*: <b>{$string}</b> |не са въведени в системата|*");
    			core_Statuses::newStatus($msg, 'warning');
    			return;
    		}
    		
    		// Ако няма вложими материали, не създаваме рецепта
    		if($hasInputMats === FALSE){
    			$msg = tr("Базовата рецепта не може да бъде създадена|*, |защото не са подадени вложими материали|*, |а само отпадаци|*");
    			core_Statuses::newStatus($msg, 'warning');
    			return;
    		}
    		
    		try{
    			// Ако има стара активна дефолтна рецепта със същите данни не правим нищо
    			if($oldRec = static::fetch("#productId = {$productId} AND #state = 'active'  AND #hash IS NOT NULL")){
    				
    				// Ако дефолтната рецепта е различна от текущата дефолтна затваряме я
    				if($oldRec->hash != $hash){
    					$oldRec->state = 'closed';
    					static::save($oldRec);
    				} else {
    					// Не правим нищо
    					return;
    				}
    			}
    			
    			// Създаваме нова дефолтна рецепта от системния потребител
    			core_Users::forceSystemUser();
    			$bomId = static::createNewDraft($productId, $bomInfo['quantity'], $pRec->containerId, $details, 'Автоматична рецепта', $bomInfo['expenses']);
    			$bomRec = static::fetchRec($bomId);
    			$bomRec->state = 'active';
    			$bomRec->hash = $hash;
    			static::save($bomRec);
    			core_Users::cancelSystemUser();
    			
    			core_Statuses::newStatus('|Успешно е създадена нова базова рецепта');
    		} catch(core_exception_Expect $e){
    			
    			// Ако има проблем, репортваме
    			core_Statuses::newStatus('|Проблем при създаването на нова базова рецепта', 'error');
    			reportException($e);
    		}
    	}
    }
    
    
    /**
     * Подготвяне на рецептите за един артикул
     * 
     * @param stdClass $data
     * @return void
     */
    public function prepareBoms(&$data)
    {
    	$data->rows = array();
    	
    	// Намираме неоттеглените задания
    	$query = cat_Boms::getQuery();
    	$query->XPR('orderByState', 'int', "(CASE #state WHEN 'active' THEN 1 WHEN 'closed' THEN 2 ELSE 3 END)");
    	
    	$query->where("#productId = {$data->masterId}");
    	$query->where("#state != 'rejected'");
    	$query->orderBy("orderByState", 'ASC');
    	while($rec = $query->fetch()){
    		$data->recs[$rec->id] = $rec;
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	
    	$masterInfo = cat_Products::getProductInfo($data->masterId);
    	if(!isset($masterInfo->meta['canManifacture'])){
    		$data->notManifacturable = TRUE;
    	}
    	
    	if(!haveRole('ceo,sales,cat,planning') || ($data->notManifacturable === TRUE && !count($data->rows))){
    		$data->hide = TRUE;
    		return;
    	}
    	
    	$data->TabCaption = 'Рецепти';
    	$data->Tab = 'top';
    	 
    	// Проверяваме можем ли да добавяме нови рецепти
    	if($this->haveRightFor('add', (object)array('productId' => $data->masterId, 'originId' => $data->masterData->rec->containerId))){
    		$data->addUrl = array('cat_Boms', 'add', 'productId' => $data->masterData->rec->id, 'originId' => $data->masterData->rec->containerId, 'type' => 'sales', 'ret_url' => TRUE);
    	}
    }
    
    
    /**
     * Рендиране на рецептите на един артикул
     * 
     * @param stdClass $data
     * @return core_ET
     */
    public function renderBoms($data)
    {
    	 if($data->hide === TRUE) return;
    	
    	 $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
    	 $title = tr('Технологични рецепти');
    	 $tpl->append($title, 'title');
    	 
    	 if(isset($data->addUrl) && !Mode::isReadOnly()){
    	 	$addBtn = ht::createLink('', $data->addUrl, FALSE, 'ef_icon=img/16/add.png,title=Добавяне на нова търговска технологична рецепта');
    	 	$tpl->append($addBtn, 'title');
    	 }
    	 
    	 $data->listFields = arr::make('title=Рецепта,type=Вид,quantity=Количество,createdBy=Oт||By,createdOn=На');
		 $table = cls::get('core_TableView', array('mvc' => $this));
    	 $this->invoke('BeforeRenderListTable', array($tpl, &$data));
    	 $details = $table->get($data->rows, $data->listFields);
    	 
    	 // Ако артикула не е производим, показваме в детайла
    	 if($data->notManifacturable === TRUE){
    	 	$tpl->append(" <span class='red small'>(" . tr('Артикулът не е производим') . ")</span>", 'title');
    	 	$tpl->append("state-rejected", 'TAB_STATE');
    	 }
    	 
    	 $tpl->replace($details, 'content');
    	 
    	 return $tpl;
    }
    
    
    /**
     * Клонира и разпъва рецептата на един артикул към друг
     * 
     * @param int $fromProductId
     * @param int $toProductId
     */
    public static function cloneBom($fromProductId, $toProductId)
    {
    	$toProductRec = cat_Products::fetchRec($toProductId);
    	$activeBom = cat_Products::getLastActiveBom($fromProductId, 'sales');
    	
    	// Ако има рецепта за клониране
    	if($activeBom){
    		$nRec = clone $activeBom;
    		$nRec->folderId  = $toProductRec->folderId;
    		$nRec->threadId  = $toProductRec->threadId;
    		$nRec->productId = $toProductRec->id;
    		$nRec->originId  = $toProductRec->containerId;
    		$nRec->state     = 'draft';
    		foreach (array('id', 'modifiedOn', 'modifiedBy', 'createdOn', 'createdBy', 'containerId') as $fld){
    			unset($nRec->{$fld});
    		}
    		
    		if(static::save($nRec)) {
    			cls::get('cat_Boms')->invoke('AfterSaveCloneRec', array($activeBom, &$nRec));
    		} else {
    			core_Statuses::newStatus('|Грешка при клониране на запис', 'warning');
    		}
    	}
    }
    
    
    /**
     * Връща допустимите параметри за формулите
     *
     * @param stdClass $rec - запис
     * @return array - допустимите параметри с техните стойностти
     */
    public static function getProductParams($productId)
    {
    	$res = array();
    	$params = cat_Products::getParams($productId);
    	
    	if(is_array($params)){
    		foreach ($params as $paramId => $value){
    			if(!is_numeric($value)) continue;
    			$key = "$" . cat_Params::getNormalizedName($paramId);
    			$res[$key] = $value;
    		}
    	}
    	
    	if(count($res)) return array($productId => $res);
    	
    	return $res;
    }
    
    
    /**
     * Пушва параметри в началото на масива
     * 
     * @param array $array
     * @param array $params
     * @return void
     */
    public static function pushParams(&$array, $params)
    {
    	if(is_array($params) && count($params)){
    		$array = $params + $array;
    	}
    }
    
    
    /**
     * Маха параметър от масива
     * 
     * @param array $array
     * @param string $key
     * @return void
     */
    public static function popParams(&$array, $key)
    {
    	unset($array[$key]);
    }
    
    
    /**
     * Връща контекста на параметрите
     *
     * @param array $params
     * @return array $scope
     */
    public static function getScope($params)
    {
    	$scope = array();
    	
    	if(is_array($params)){
    		foreach ($params as $arr){
    			if(is_array($arr)){
    				foreach ($arr as $k => $v){
    					if(!isset($scope[$k])){
    						$scope[$k] = $v;
    					}
    				}
    			}
    		}
    	}
    	
    	return $scope;
    }
    
    
    /**
     * Връща цената на материала за рецептата
     * 
     * @param sales|production $type - типа за която рецепта ще проверяваме
     * @param int $productId         - ид на артикула
     * @param double $quantity       - количество за което искаме цената
     * @param date $date             - към коя дата
     * @param int $priceListId       - по кой ценоразпис
     * @return double|FALSE $price   - намерената цена или FALSE ако няма
     */
    private static function getPriceForBom($type, $productId, $quantity, $date, $priceListId)
    {
    	// Ако търсим цената за търговска рецепта
    	if($type == 'sales'){
    		
    		// Първо проверяваме имали цена по политиката
    		$price = price_ListRules::getPrice($priceListId, $productId, NULL, $date);
    		
    		if(!isset($price)){
    			
    			// Ако няма, търсим по последната търговска рецепта, ако има
    			if($salesBom = cat_Products::getLastActiveBom($productId, 'sales')){
    				$price = static::getBomPrice($salesBom, $quantity, 0, 0, $date, $priceListId);
    			}
    		}
    		
    		if(!isset($price)){
    			$price = planning_ObjectResources::getAvgPriceEquivalentProducts($productId, $date);
    		}
    		
    		// Ако и по рецепта няма тогава да гледа по складова
    		if(!isset($price)){
    			$pInfo = cat_Products::getProductInfo($productId);
    			
    			// Ако артикула е складируем търсим средната му цена във всички складове, иначе търсим в незавършеното производство
    			if(isset($pInfo->meta['canStore'])){
    				$price = cat_Products::getWacAmountInStore(1, $productId, $date);
    			} else {
    				$price = planning_ObjectResources::getWacAmountInProduction(1, $productId, $date);
    			}
    		}
    	} else {
    		$pInfo = cat_Products::getProductInfo($productId);
    		
    		// Ако артикула е складируем търсим средната му цена във всички складове, иначе търсим в незавършеното производство
    		if(isset($pInfo->meta['canStore'])){
    			$price = cat_Products::getWacAmountInStore(1, $productId, $date);
    		} else {
    			$price = planning_ObjectResources::getWacAmountInProduction(1, $productId, $date);
    		}
    		
    		if(!isset($price)){
    			
    			// Ако няма такава, търсим по последната работна рецепта, ако има
    			if($prodBom = cat_Products::getLastActiveBom($productId)){
    				$price = static::getBomPrice($prodBom, $quantity, 0, 0, $date, $priceListId);
    			}
    		}
    		
    		if(!isset($price)){
    			$price = planning_ObjectResources::getAvgPriceEquivalentProducts($productId, $date);
    		}
    		
    		// В краен случай взимаме мениджърската себестойност
    		if(!isset($price)){
    			$price = price_ListRules::getPrice($priceListId, $productId, NULL, $date);
    		}
    	}
    	
    	// Ако няма цена връщаме FALSE
    	if(!isset($price)) return FALSE;
    	if(!$quantity) return FALSE;
    	
    	// Умножаваме цената по количеството
    	$price *= $quantity;
    	
    	// Връщаме намерената цена
    	return $price;
    }
    
    
    /**
     * Изчислява сумата на реда и я записва
     * 
     * @param stdCladd $rec          - Записа на реда
     * @param array $params          - Параметрите за реда
     * @param double $t              - Тиража
     * @param double $q              - Изчислимото количество
     * @param date $date             - Към коя дата
     * @param int $priceListId       - ид на ценоразпис
     * @param boolean $savePriceCost - дали да кешираме изчислената цена
     * @param array $materials       - масив със сумираните вложени материали
     * @return double|FALSE $price   - намерената цена или FALSE ако не можем
     */
    private static function getRowCost($rec, $params, $t, $q, $date, $priceListId, $savePriceCost = FALSE, &$materials = array())
    {
    	// Изчисляваме количеството ако можем
    	$rowParams = self::getProductParams($rec->resourceId);
    	self::pushParams($params, $rowParams);
    	
    	$scope = self::getScope($params);
    	$rQuantity = cat_BomDetails::calcExpr($rec->propQuantity, $scope);
    	if($rQuantity != cat_BomDetails::CALC_ERROR){
    		
    		// Искаме количеството да е за единица, не за опаковка
    		$rQuantity *= $rec->quantityInPack;
    	}
    	
    	// Сумираме какви количества ще вложим към материалите
    	if($rec->type != 'stage'){
    		$index = "{$rec->resourceId}|$rec->type";
    		if(!isset($materials[$index])){
    			$materials[$index] = (object)array('productId'      => $rec->resourceId, 
    											   'packagingId'    => $rec->packagingId, 
    											   'quantityInPack' => $rec->quantityInPack,
    											   'type'           => $rec->type,
    											   'propQuantity'   => $t * $rQuantity * $rec->quantityInPack);
    		} else {
    			$d = &$materials[$index];
    			if($rQuantity != cat_BomDetails::CALC_ERROR){
    				$d->propQuantity += $t * $rQuantity;
    			} else {
    				$d->propQuantity = $rQuantity;
    			}
    		}
    	}
    	
    	// Какъв е типа на рецептата
    	$type = static::fetchField($rec->bomId, 'type');
    	
    	// Ако реда не е етап а е материал или отпадък
    	if($rec->type != 'stage'){
    		
    		if($rec->type == 'pop'){
    			
    			// Ако е отпадък търсим твърдо мениджърската себестойност
    			$price = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->resourceId, $rec->packagingId, $date);
    			if(!isset($price)) {
    				$price = FALSE;
    			} else {
    				$price *= $q * $rQuantity;
    			}
    		} else {
    			// Ако не е търсим най-подходящата цена за рецептата
    			$price = self::getPriceForBom($type, $rec->resourceId, $q * $rQuantity, $date, $priceListId);
    		}
    		
    		// Записваме намерената цена
    		if($savePriceCost === TRUE){
    			$primeCost = ($price === FALSE) ? NULL : $price;
    			$params1 = $scope;
    			
    			// Ъпдейтваме кешираните стойност и параметри само при промяна
    			if(trim($rec->primeCost) != trim($primeCost) || serialize($rec->params) != serialize($params1)){
    				$rec->primeCost = $primeCost;
    				$rec->params = $params1;
    				
    				cls::get('cat_BomDetails')->save_($rec, 'primeCost,params');
    			}
    		}
    	} else {
    		$price = NULL;
    		if(isset($rec->coefficient)){
    			$rQuantity /= $rec->coefficient;
    		}
    		
    		// Ако е етап, новите параметри са неговите данни + количестото му по тиража
    		$flag = FALSE;
    		if(!array_key_exists($rec->resourceId, $params)){
    			$empty = array($rec->resourceId => array());
    			self::pushParams($params, $empty);
    			$flag = TRUE;
    		}
    		$params[$rec->resourceId]['$T'] = ($rQuantity == cat_BomDetails::CALC_ERROR) ? $rQuantity : $t * $rQuantity;
    		
    		// Намираме кои редове са му детайли
    		$query = cat_BomDetails::getQuery();
    		$query->where("#parentId = {$rec->id}");
    		
    		// За всеки детайл
    		while($dRec = $query->fetch()){
    			
    			// Опитваме се да намерим цената му
    			$dRec->primeCost = self::getRowCost($dRec, $params, $t * $rQuantity, $q * $rQuantity, $date, $priceListId, $savePriceCost, $materials);
    			
    			// Ако няма цена връщаме FALSE
    			if($dRec->primeCost === FALSE){
					$price = FALSE;
    			}
    			
    			// Добавяме цената на реда към цената на етапа
    			if($dRec->primeCost !== FALSE && $price !== FALSE){
    				$price += $dRec->primeCost;
    			}
    		}
			
    		// Попваме данните, за да кешираме оригиналните
    		if($flag === TRUE){
    			self::popParams($params, $rec->resourceId);
    		}
    		
    		// Кешираме параметрите само при нужда
    		if($savePriceCost === TRUE){
    			$scope = static::getScope($params);
    			$params1 = $scope;
    			
    			if(serialize($rec->params) != serialize($params1)){
    				$rec->params = $params1;
    				cls::get('cat_BomDetails')->save_($rec, 'params');
    			}
    		}
    	}
    	
    	// Ако реда е отпадък то ще извадим цената му от себестойността
    	if($rec->type == 'pop' && $price !== FALSE){
    		$price *= -1;
    	}
    	
    	self::popParams($params, $rec->resourceId);
    	
    	// Връщаме намерената цена
    	return $price;
    }
    
    
    /**
     * Връща цената на артикул по рецепта
     * 
     * @param int $id - ид на рецепта
     * @param double $quantity    - количеството
     * @param double $minDelta    - минималната търговска отстъпка
     * @param double $maxDelta    - максималната търговска надценка
     * @param date   $date        - към коя дата
     * @param int    $priceListId - ид на ценоразпис
     * @param array  $materials   - какви материали са вложени
     * @return FALSE|double       - намерената цена или FALSE ако няма
     */
    public static function getBomPrice($id, $quantity, $minDelta, $maxDelta, $date, $priceListId, &$materials = array())
    {
    	$baseAmount = NULL;
    	$price = NULL;
    	$primeCost1 = $primeCost2 = NULL;
    	
    	// Трябва да има такъв запис
    	expect($rec = static::fetchRec($id));
    	
    	$savePrimeCost = FALSE;
    	$bomQuantity = ($rec->quantityForPrice) ? $rec->quantityForPrice : $rec->quantity;
    	 
    	if($minDelta === 0 && $maxDelta === 0 && $priceListId == price_ListRules::PRICE_LIST_COST && $bomQuantity == $quantity){
    		$savePrimeCost = TRUE;
    	}
    	
    	$quantity /= $rec->quantity;
    	
    	// Количеството за което изчисляваме е 1-ца
    	$q = 1;
    	
    	// Изчисляваме двата тиража (минимум и максимум)
    	$t1 = $quantity / self::PRICE_COEFFICIENT;
    	$t2 = $quantity * self::PRICE_COEFFICIENT;
    	
    	// Намираме всички детайли от първи етап
    	$query = cat_BomDetails::getQuery();
    	$query->where("#bomId = {$rec->id}");
    	$query->where('#parentId IS NULL');
    	$details = $query->fetchAll();
    	
    	// Ако изчисляваме цената на рецептата по себестойност, ще кешираме изчислените цени на редовете
    	$canCalcPrimeCost = TRUE;
    	
    	// За всеки от тях
    	if(is_array($details)){
    		foreach ($details as $dRec){
    			
    			// Параметрите са на продукта на рецептата
    			$params = array();
    			$pushParams = static::getProductParams($rec->productId);
    			$pushParams[$rec->productId]['$T'] = $quantity;
    			self::pushParams($params, $pushParams);
    			
    			// Опитваме се да намерим себестойността за основното количество
    			$rowCost1 = self::getRowCost($dRec, $params, $quantity, $q, $date, $priceListId, $savePrimeCost, $materials);
    			
    			// Ако няма връщаме FALSE
    			if($rowCost1 === FALSE) $canCalcPrimeCost = FALSE;
    			
    			// Ако мин и макс делта са различни изчисляваме редовете за двата тиража
    			if($minDelta != $maxDelta){
    				
    				$params[$rec->productId]['$T'] = $t1;
    				$rowCost1 = self::getRowCost($dRec, $params, $t1, $q, $date, $priceListId);
    				
    				if($rowCost1 === FALSE) $canCalcPrimeCost = FALSE;
    				$primeCost1 += $rowCost1;
    					
    				$params[$rec->productId]['$T'] = $t2;
    				$rowCost2 = self::getRowCost($dRec, $params, $t2, $q, $date, $priceListId);
    				if($rowCost2 === FALSE) $canCalcPrimeCost = FALSE;
    				$primeCost2 += $rowCost2;
    				
    			} else {
    				if($rowCost1 === FALSE) $canCalcPrimeCost = FALSE;
    				$primeCost1 += $rowCost1;
    			}
    		}
    	}
    	
    	if($canCalcPrimeCost === FALSE) return NULL;
    	
    	// Ако са равни връщаме себестойността
    	if($minDelta == $maxDelta){
    		$price = $primeCost1 * (1 + $minDelta);
    
    	} else {
    		$primeCost1 *= $t1;
    		$primeCost2 *= $t2;
    		
	    	// Изчисляваме началната и пропорционалната сума
	    	$basePrice = ($primeCost2 * $t1 - $primeCost1 * $t2) / ($t1 - $t2); 
	    	$propPrice = ($primeCost1 - $primeCost2) / ($t1 - $t2);
	    	
	    	// Прилагаме и максималната надценка и минималната отстъпка
	    	$price = $basePrice * (1 + $maxDelta) / $quantity + $propPrice * (1 + $minDelta);
    	}
    	
    	$price /= $rec->quantity;
    	
    	// Връщаме намерената цена
    	return $price;
    }
    
    
    /**
     * Връща иконата за сметката
     */
    function getIcon($id)
    {
    	$rec = $this->fetch($id);
    	$icon = ($rec->type == 'sales') ? $this->singleIcon : $this->singleProductionBomIcon;
    	
    	return $icon;
    }
    
    
    /**
     * След подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter(core_Mvc $mvc, $data)
    {
    	$data->listFilter->showFields .= ',type';
    	$data->listFilter->setOptions('type', array('all' => 'Всички', 'sales' => 'Търговски', 'production' => 'Работни'));
    	$data->listFilter->setDefault('type', 'all');
    	
    	$data->listFilter->input();
    	if($filter = $data->listFilter->rec) {
    		if($filter->type != 'all'){
    			$data->query->where("#type = '{$filter->type}'");
    		}
    	}
    }
    

    /**
     * Опит за връщане на масив със задачи за производство от рецептата
     * 
     * @param mixed $id - ид на рецепта
     * @param double $quantity - количество
     * @return array  $tasks - масив със задачи за производство за генерирането на всеки етап
     */
    public static function getTasksFromBom($id, $quantity = 1)
    {
    	expect($rec = self::fetchRec($id));
    	$tasks = array();
    	$pName = cat_Products::getTitleById($rec->productId, FALSE);
    	
    	// За основния артикул подготвяме задача
    	// В която самия той е за произвеждане
    	$tasks = array(1 => (object)array('title'           => $pName,
    									  'plannedQuantity' => $quantity,
    									  'quantityInPack'  => 1,
    									  'packagingId'     => cat_Products::fetchField($rec->productId, 'measureId'),
    									  'productId'       => $rec->productId,
    									  'products'        => array('input'    => array(),'waste'    => array())));
    	 
    	// Намираме неговите деца от първо ниво те ще бъдат артикулите за влагане/отпадък
    	$dQuery = cat_BomDetails::getQuery();
    	$dQuery->where("#bomId = {$rec->id}");
    	$dQuery->where("#parentId IS NULL");
    	while($detRec = $dQuery->fetch()){
    		$detRec->params['$T'] = $quantity;
    		$quantityE = cat_BomDetails::calcExpr($detRec->propQuantity, $detRec->params);
    		if($quantityE == cat_BomDetails::CALC_ERROR){
    			$quantityE = 0;
    		}
    		$quantityE = ($quantityE / $rec->quantity) * $quantity;
    		
    		$place = ($detRec->type == 'pop') ? 'waste' : 'input';
    		$tasks[1]->products[$place][] = array('productId' => $detRec->resourceId, 'packagingId' => $detRec->packagingId, 'packQuantity' => $quantityE / $quantity, 'quantityInPack' => $detRec->quantityInPack);
    	}
    	
    	// Отделяме етапите за всеки етап ще генерираме отделна задача в която той е за произвеждане
    	// А неговите подетапи са за влагане/отпадък
    	$query = cat_BomDetails::getQuery();
    	$query->where("#bomId = {$rec->id}");
    	$query->where("#type = 'stage'");
    	
    	// За всеки етап намираме подетапите му
    	while($dRec = $query->fetch()){
    		$query2 = cat_BomDetails::getQuery();
    		$query2->where("#parentId = {$dRec->id}");
    		
    		$quantityP = cat_BomDetails::calcExpr($dRec->propQuantity, $dRec->params);
    		if($quantityP == cat_BomDetails::CALC_ERROR){
    			$quantityP = 0;
    		}
    
    		$parent = $dRec->parentId;
    		while($parent && ($pRec = cat_BomDetails::fetch($parent))) {
    			$q = cat_BomDetails::calcExpr($pRec->propQuantity, $pRec->params);
    			if($q == cat_BomDetails::CALC_ERROR){
    				$q = 0;
    			}
    			$quantityP *= $q;
    			$parent = $pRec->parentId;
    		}
    		
    		$quantityP = ($quantityP / $rec->quantity) * $quantity;
    		
    		// Подготвяме задачата за етапа, с него за производим
    		$arr = (object)array('title'    => $pName . " / " . cat_Products::getTitleById($dRec->resourceId, FALSE),
    							 'plannedQuantity' => $quantityP,
    							 'productId' => $dRec->resourceId,
    							 'packagingId' => $dRec->packagingId,
    							 'quantityInPack' => $dRec->quantityInPack,
    							 'products' => array('input' => array(), 'waste' => array()));
    
    		// Добавяме директните наследници на етапа като материали за влагане/отпадък
    		while($cRec = $query2->fetch()){
    			$quantityS = cat_BomDetails::calcExpr($cRec->propQuantity, $cRec->params);
    			if($quantityS == cat_BomDetails::CALC_ERROR){
    				$quantityS = 0;
    			}
    			
    			$place = ($cRec->type == 'pop') ? 'waste' : 'input';
    			$arr->products[$place][] =  array('productId' => $cRec->resourceId, 'packagingId' => $cRec->packagingId, 'packQuantity' => $quantityS, 'quantityInPack' => $cRec->quantityInPack);
    		}
    
    		// Събираме задачите
    		$tasks[] = $arr;
    	}
    	
    	// Връщаме масива с готовите задачи
    	return $tasks;
    }

    
    /**
     * Проверка след изпращането на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	if ($form->isSubmitted()){
    		
    		// Проверка на к-то
    		$measureId = cat_Products::fetchField($rec->productId, 'measureId');
    		if(!deals_Helper::checkQuantity($measureId, $rec->quantity, $warning)){
    			$form->setError('quantity', $warning);
    		}
    		
    		$firstDocument = doc_Containers::getDocument($rec->originId);
    		if(empty($rec->id) && $firstDocument->isInstanceOf('planning_Jobs')){
    			
    			// Ако има търговска рецепта за друго количество, при създаване на работната
    			// се добавя предупреждение, ако има разминаване в к-та
    			$bRec = cat_Products::getLastActiveBom($rec->productId, 'sales');
    			if(!empty($bRec)){
    				if($bRec->quantity != $rec->quantity){
    					$q1 = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($bRec->quantity);
    					$q2 = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($rec->quantity);
    					$uom = cat_Uom::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
    					$handle = cat_Boms::getLink($bRec->id, 0);
    					
    					$form->setWarning('quantity', "|Данните от търговската рецепта|* {$handle} |няма да се прехвърлят защото тя е за|* <b>{$q1} {$uom}</b>, |а работната рецепта е за|* <b>{$q2} {$uom}</b>");
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Връща складируемите материали по-рецепта, ако е подаден склад се
     * отсяват само ненулевите количества
     * 
     * @param int $bomId
     * @param double $quantity
     * @param int $storeId
     * @return array $res
     * 			['productId']      - ид на артикул
     * 		    ['packagingId']    - ид на опаковка
     * 		    ['quantity']       - к-во
     * 			['quantityInPack'] - к-во в опаковка
     */
    public static function getBomMaterials($bomId, $quantity, $storeId = NULL)
    {
    	$res = array();
    	$bomInfo = cat_Boms::getResourceInfo($bomId, $quantity, dt::now());
    	if(!count($bomInfo['resources'])) return $res;
    	
    	foreach ($bomInfo['resources'] as $pRec){
    		$canStore = cat_Products::fetchField($pRec->productId, 'canStore');
    		if($canStore != 'yes' || $pRec->type != 'input') continue;
    		
    		// Ако има склад се отсяват артикулите, които имат нулева наличност
    		if(isset($storeId)){
    			$quantity = store_Products::getQuantity($pRec->productId, $storeId);
    			if(empty($quantity)) continue;
    		}
    		
    		$res[] = (object)array('productId'      => $pRec->productId,
    				               'packagingId'    => $pRec->packagingId,
    				               'quantity'       => $pRec->propQuantity,
    				               'quantityInPack' => $pRec->quantityInPack);
    	}
    	
    	return $res;
    }
}
