<?php



/**
 * Мениджър на етапи детайл на технологична рецепта
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_BomDetails extends doc_Detail
{
	
	
    /**
     * Заглавие
     */
    var $title = "Етапи на технологичните рецепти";
    
    
    /**
     * Заглавие
     */
    var $singleTitle = "Материал";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'bomId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper, plg_LastUsedKeys, plg_SaveAndNew, plg_GroupByField, plg_AlignDecimals2';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'resourceId';
    
    
    /**
     * По кое поле да се групират записите
     */
    //var $groupByField = 'parentId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активен таб
     */
    var $currentTab = 'Рецепти';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,cat';
    
    
    /**
     * Кой има право да чете?
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,cat';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,cat';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, parentId, position=№, resourceId, packagingId=Мярка, baseQuantity=Начално,propQuantity,expensePercent=Режийни';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    protected $hideListFieldsIfEmpty = 'baseQuantity,expensePercent';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('parentId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Етап');
    	$this->FLD('bomId', 'key(mvc=cat_Boms)', 'column=none,input=hidden,silent');
    	$this->FLD("resourceId", 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Материал,mandatory,silent,removeAndRefreshForm=packagingId');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка','tdClass=small-field centerCol,smartCenter,silent,removeAndRefreshForm=quantityInPack,mandatory');
    	$this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
    	$this->FLD('dType', 'enum(product=Артикул,components=Артикул с компоненти,stage=Етап)', 'caption=Тип,remember,input=none');
    	
    	$this->FLD("position", 'int(Min=0)', 'caption=Позиция,smartCenter');
    	$this->FLD('type', 'enum(input=Влагане,pop=Отпадък,stage=Етап)', 'caption=Действие,silent,input=hidden');
    	$this->FLD("baseQuantity", 'double(Min=0)', 'caption=Количество->Начално,hint=Начално количество,smartCenter');
    	$this->FLD("propQuantity", 'double(Min=0)', 'caption=Количество->Пропорционално,hint=Пропорционално количество,smartCenter');
    	$this->FLD('expensePercent', 'percent(min=0)', 'caption=Количество->Режийни');
    	
    	//$this->FLD('stageId', 'key(mvc=planning_Stages,allowEmpty,select=name)', 'caption=Етап');
    	//$this->FLD('showInProduct', 'enum(hide=Не се показва,title=Заглавие,description=Заглавие + описание,components=Заглавие + описание + компоненти)', 'caption=Показване в артикулa->Избор,notNull,value=hide,remember');
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
    	$data->listFields['propQuantity'] = "|За|* " . $data->masterData->row->quantity;
    	$data->query->orderBy("type", 'DESC');
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
    	$form->FNC('likeProductId', 'key(mvc=cat_Products)', 'input=hidden');
    	$form->setDefault('likeProductId', Request::get('likeProductId', 'int'));
    	
    	$rec = &$form->rec;
    	$typeCaption = ($rec->type == 'input') ? 'материал' : (($rec->type == 'pop') ? 'отпадък' : 'етап');
    	$matCaption = ($rec->type == 'input') ? 'Материал' : (($rec->type == 'pop') ? 'Отпадък' : 'Етап');
    	$action = ($rec->id) ? 'Редактиране' : 'Добавяне';
    	$form->title = "|{$action}|* на {$typeCaption} |към|* <b>|{$mvc->Master->singleTitle}|* №{$rec->bomId}<b>";
    	$form->setField('resourceId', "caption={$matCaption}");
    	
    	// Добавяме всички вложими артикули за избор
    	if($rec->type != 'stage'){
    		$metas = ($rec->type == 'input') ? 'canConvert' : 'canConvert,canStore';
    		$products = cat_Products::getByProperty($metas);
    	} else {
    		
    		// При добавяне на етап да се избират само Инстантни (производими, нескладируеми) артикули
    		$products = cat_Products::getByProperty('canManifacture', 'canStore');
    	}
    	
    	unset($products[$data->masterRec->productId]);
    	$form->setOptions('resourceId', $products);
    	
    	$likeProductId = $form->rec->likeProductId;
    	if(isset($rec->id) && isset($likeProductId)){
    		$convertable = planning_ObjectResources::fetchConvertableProducts($likeProductId);
    		$convertable = array('x' => (object)array('title' => tr('Заместващи'), 'group' => TRUE)) + $convertable;
    		$convertable = array($likeProductId => $products[$likeProductId]) + $convertable;
    		$form->setOptions('resourceId', $convertable);
    	}
    	
    	$form->setDefault('type', 'input');
    	$quantity = $data->masterRec->quantity;
    	$originInfo = cat_Products::getProductInfo($data->masterRec->productId);
    	$shortUom = cat_UoM::getShortName($originInfo->productRec->measureId);
    		
    	$propCaption = "Количество->|За|* |{$quantity}|* {$shortUom}";
    	$form->setField('propQuantity', "caption={$propCaption}");
    	
    	if($data->masterRec->expenses){
    		$form->setDefault('expensePercent', $data->masterRec->expenses);
    	}
    	
    	// Възможните етапи са етапите от текущата рецепта
    	$stages = array();
    	$query = $mvc->getQuery();
    	$query->where("#bomId = {$rec->bomId} AND #type = 'stage'");
    	$query->show('resourceId');
    	while($dRec = $query->fetch()){
    		$stages[$dRec->resourceId] = cat_Products::getTitleById($dRec->resourceId, FALSE);
    	}
    	unset($stages[$rec->resourceId]);
    	
    	// Добавяме намерените етапи за опции на етапите
    	if(count($stages)){
    		$form->setOptions('parentId', $stages);
    	} else {
    		$form->setReadOnly('parentId');
    	}
    	
    	if(isset($rec->id) && cat_Products::getLastActiveBom($rec->resourceId)){
    		$form->setReadOnly('resourceId');
    	}
    }
    
    
    /**
     * Търси в дърво, дали даден обект не е баща на някой от бащите на друг обект
     *
     * @param int $objectId - ид на текущия обект
     * @param int $needle - ид на обекта който търсим
     * @param array $notAllowed - списък със забранените обекти
     * @param array $path
     * @return void
     */
    private function findNotAllowedProducts($objectId, $needle, &$notAllowed, $path = array())
    {
    	// Добавяме текущия продукт
    	$path[$objectId] = $objectId;
    
    	// Ако стигнем до началния, прекратяваме рекурсията
    	if($objectId == $needle){
    		foreach($path as $p){
    
    			// За всеки продукт в пътя до намерения ние го
    			// добавяме в масива notAllowed, ако той, вече не е там
    			$notAllowed[$p] = $p;
    		}
    		return;
    	}
    	
    	// Имали артикула рецепта
    	if($bomId = cat_Products::getLastActiveBom($objectId)){
    		$bomInfo = cat_Boms::getResourceInfo($bomId);
    		
    		// За всеки продукт от нея проверяваме дали не съдържа търсения продукт
    		if(count($bomInfo['resources'])){
    			foreach ($bomInfo['resources'] as $res){
    				$this->findNotAllowedProducts($res->productId, $needle, $notAllowed, $path);
    			}
    		}
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
    	
    	// Ако има избран ресурс, добавяме му мярката до полетата за количества
    	if(isset($rec->resourceId)){
    		
    		$pInfo = cat_Products::getProductInfo($rec->resourceId);
    		
    		$packs = cat_Products::getPacks($rec->resourceId);
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    		
    		$packname = cat_UoM::getTitleByid($rec->packagingId);
    		$form->setField('baseQuantity', "unit={$packname}");
    		$form->setField('propQuantity', "unit={$packname}");
    		
    	} else {
    		$form->setReadOnly('packagingId');
    	}
    	
    	// Проверяваме дали е въведено поне едно количество
    	if($form->isSubmitted()){
    		
    		if(!isset($rec->expensePercent)){
    			$rec->expensePercent = cat_Boms::fetchField($rec->bomId, 'expenses');
    		}
    		
    		if(isset($rec->resourceId)){
    			
    			// Ако е избран артикул проверяваме дали артикула от рецептата не се съдържа в него
    			$masterProductId = cat_Boms::fetchField($rec->bomId, 'productId');
    			$productVerbal = cat_Products::getTitleById($masterProductId);
    			
    			$notAllowed = array();
    			$mvc->findNotAllowedProducts($rec->resourceId, $masterProductId, $notAllowed);
    			if(isset($notAllowed[$rec->resourceId])){
    				$form->setError('resourceId', "Материалът не може да бъде избран, защото в рецептата на някой от материалите му се съдържа|* <b>{$productVerbal}</b>");
    			}
    		}
    		
    		// Ако добавяме отпадък, искаме да има себестойност
    		if($rec->type == 'pop'){
    			$selfValue = planning_ObjectResources::getSelfValue($rec->resourceId);
    			if(!isset($selfValue)){
    				$form->setWarning('resourceId', 'Отпадакът няма себестойност');
    			}
    		} else {
    			
    			// Материалът може да се използва само веднъж в дадения етап
    			$cond = "#bomId = {$rec->bomId} AND #id != '{$rec->id}' AND #resourceId = {$rec->resourceId}";
    			$cond .= (empty($rec->parentId)) ? " AND #parentId IS NULL" : " AND #parentId = '{$rec->parentId}'";
    			if(self::fetchField($cond)){
    				$form->setError('resourceId,parentId', 'Материалът вече се използва в този етап');
    			}
    		}
    		
    		// Не може и двете количества да са празни
    		if(empty($rec->baseQuantity) && empty($rec->propQuantity)){
    			$form->setError('baseQuantity,propQuantity', 'Трябва да е въведено поне едно количество');
    		}
    		
    		$rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->resourceId = cat_Products::getShortHyperlink($rec->resourceId);
    	
    	// Показваме подробната информация за опаковката при нужда
    	deals_Helper::getPackInfo($row->packagingId, $rec->resourceId, $rec->packagingId, $rec->quantityInPack);
    	
    	$row->ROW_ATTR['class'] = ($rec->type != 'input' && $rec->type != 'stage') ? 'row-removed' : 'row-added';
    	$row->ROW_ATTR['title'] = ($rec->type != 'input' && $rec->type != 'stage') ? tr('Отпадък') : NULL;
    	
    	if($rec->type == 'stage'){
    		$row->resourceId = "<b>[етап]</b> " . $row->resourceId;
    	}
    	
    	if($mvc->haveRightFor('edit', $rec)){
    		$convertableOptions = planning_ObjectResources::fetchConvertableProducts($rec->resourceId);
    		if(count($convertableOptions)){
    			$row->resourceId .= ht::createLink('', array($mvc, 'edit', $rec->id, 'likeProductId' => $rec->resourceId, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/dropdown.gif,title=Избор на заместващ материал');
    		}
    	}
    	
    	if($rec->position === 0){
    		unset($row->position);
    	}
    	
    	if($rec->type != 'stage'){
    		
    		$componentsArr = array();
    		cat_Products::prepareComponents($rec->resourceId, $componentsArr, 'internal');
    		if(count($componentsArr)){
    			$components = cat_Products::renderComponents($componentsArr);
    		}
    		
    		$tpl = new core_ET("[#resourceId#]
    							<!--ET_BEGIN description--><br>
    							<!--ET_BEGIN components--><span style='font-size:0.85em'>[#components#]</span><!--ET_BEGIN components-->");
    		$tpl->placeArray(array('resourceId' => $row->resourceId, 'description' => $description, 'components' => $components));
    		$row->resourceId = $tpl;
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    	if($mvc->haveRightFor('add', (object)array('bomId' => $data->masterId))){
    		$data->toolbar->addBtn('Материал', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => TRUE, 'type' => 'input'), NULL, "title=Добавяне на материал,ef_icon=img/16/package.png");
    		$data->toolbar->addBtn('Етап', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => TRUE, 'type' => 'stage'), NULL, "title=Добавяне на етап,ef_icon=img/16/package.png");
    		$data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => TRUE, 'type' => 'pop'), NULL, "title=Добавяне на отпадък,ef_icon=img/16/package.png");
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
    		if($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
    	if(!count($data->recs)) return;
    	 
    	$recs = &$data->recs;
    	arr::orderA($recs, 'id');
    	foreach ($recs as &$rec){
    		
    		if(!$rec->position){
    			$rec->position = 0;
    		}
    		//$rec->order .= $rec->id;
    	}
    
    	if($data->masterData->rec->state != 'draft'){
    		//unset($data->listFields['tools']);
    	}
    	
    	// Сортираме по подредбата на производствения етап
    	/*usort($recs, function($a, $b) {
    		if($a->position == $b->position)  return 0;
    		return ($a->position > $b->position) ? 1 : -1;
    	});*/
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate111($mvc, $rec)
    {
		if($rec->type == 'stage'){
			$title = cat_Products::getTitleById($rec->resourceId);
			core_Statuses::newStatus($title, 'warning');
    		$activeBom = cat_Products::getLastActiveBom($rec->resourceId);
    		if($activeBom){
    			$dQuery = $mvc->getQuery();
    			$dQuery->where("#bomId = '{$activeBom->id}'");
    			while($dRec = $dQuery->fetch()){
    				unset($dRec->id);
    				$dRec->bomId = $rec->bomId;
    				if(empty($dRec->parentId)){
    					$dRec->parentId = $rec->resourceId;
    				}
    				
    				$mvc->save_($dRec);
    			}
    		}
    	}
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(empty($rec->id) && isset($rec->type)){
    		$rec->stageAdded = TRUE;
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if(isset($rec->position)){
    		$query = $mvc->getQuery();
    		$cond = "#bomId = {$rec->bomId} AND #id != {$rec->id} AND #position >= {$rec->position} AND ";
    		$cond .= (isset($rec->parentId)) ? "#parentId = {$rec->parentId}" : "#parentId IS NULL";
    		
    		$query->where($cond);
    		while($rec = $query->fetch()){
    			$rec->position++;
    			$mvc->save_($rec, 'position');
    		}
    	}
    	
    	if($rec->stageAdded === TRUE){
    		$title = cat_Products::getTitleById($rec->resourceId);
    		core_Statuses::newStatus($title, 'warning');
    		$activeBom = cat_Products::getLastActiveBom($rec->resourceId);
    		
    		if($activeBom){
    			$dQuery = $mvc->getQuery();
    			$dQuery->where("#bomId = '{$activeBom->id}'");
    			while($dRec = $dQuery->fetch()){
    				unset($dRec->id);
    				$dRec->bomId = $rec->bomId;
    				if(empty($dRec->parentId)){
    					$dRec->parentId = $rec->resourceId;
    				}
    					 
    				$mvc->save_($dRec);
    			}
    		}
    	}
    }
}
