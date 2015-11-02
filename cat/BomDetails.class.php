<?php



/**
 * Мениджър на детайл на технологичната рецепта
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
    var $title = "Детайл на технологичната рецепта";
    
    
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
    var $loadList = 'plg_Modified, plg_RowTools, cat_Wrapper, plg_LastUsedKeys, plg_SaveAndNew, plg_AlignDecimals2';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'resourceId';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id,bomId,type';
    
    
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
    public $listFields = 'tools=Пулт, position=№, resourceId, packagingId=Мярка, baseQuantity=Начално,propQuantity,expensePercent=Режийни';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    protected $hideListFieldsIfEmpty = 'baseQuantity,expensePercent';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('parentId', 'key(mvc=cat_BomDetails,select=id)', 'caption=Етап,remember');
    	$this->FLD('bomId', 'key(mvc=cat_Boms)', 'column=none,input=hidden,silent');
    	$this->FLD("resourceId", 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Материал,mandatory,silent,removeAndRefreshForm=packagingId');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка','tdClass=small-field centerCol,smartCenter,silent,removeAndRefreshForm=quantityInPack,mandatory');
    	$this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
    	
    	$this->FLD("position", 'int(Min=0)', 'caption=Позиция,smartCenter');
    	$this->FLD('type', 'enum(input=Влагане,pop=Отпадък,stage=Етап)', 'caption=Действие,silent,input=hidden');
    	$this->FLD("baseQuantity", 'double(Min=0)', 'caption=Количество->Начално,hint=Начално количество,smartCenter');
    	$this->FLD("propQuantity", 'double(Min=0)', 'caption=Количество->Пропорционално,hint=Пропорционално количество,smartCenter');
    	$this->FLD('expensePercent', 'percent(min=0)', 'caption=Количество->Режийни');
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
    	$matCaption = ($rec->type == 'input') ? 'Материал' : (($rec->type == 'pop') ? 'Отпадък' : 'Подетап');
    	$action = ($rec->id) ? 'Редактиране' : 'Добавяне';
    	$form->title = "|{$action}|* на |{$typeCaption}|* |към|* <b>|{$mvc->Master->singleTitle}|* №{$rec->bomId}<b>";
    	$form->setField('resourceId', "caption={$matCaption}");
    	
    	// Добавяме всички вложими артикули за избор
    	if($rec->type != 'stage'){
    		$metas = ($rec->type == 'input') ? 'canConvert' : 'canConvert,canStore';
    		$products = cat_Products::getByProperty($metas);
    	} else {
    		
    		// При добавяне на етап да се избират само Инстантни (производими, нескладируеми) артикули
    		$products = cat_Products::getByProperty('canConvert,canManifacture', 'canStore');
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
    		$stages[$dRec->id] = cat_Products::getTitleById($dRec->resourceId, FALSE);
    	}
    	unset($stages[$rec->id]);
    	
    	// Добавяме намерените етапи за опции на етапите
    	if(count($stages)){
    		$form->setOptions('parentId', array('' => '') + $stages);
    	} else {
    		$form->setReadOnly('parentId');
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
    		
    		// Ако има артикул със същата позиция, или няма позиция добавяме нова
    		if(!isset($rec->position)){
    			$rec->position = $mvc->getDefaultPosition($rec->bomId, $rec->parentId);
    		}
    		
    		if(!$form->gotErrors()){
    			
    			// Пътя към този артикул
    			$thisPath = $mvc->getProductPath($rec);
    			unset($thisPath[0]);
    			
    			$canAdd = TRUE;
    			if(isset($rec->parentId)){
    				
    				// Ако добавяме етап
    				if($rec->type == 'stage'){
    					$bom = cat_Products::getLastActiveBom($rec->resourceId);
    					if(isset($bom)){
    						
    						// и има детайли
    						$detailsToAdd = $mvc->getOrderedBomDetails($bom->id);
    						if(is_array($detailsToAdd)){
    							
    							// Ако някой от артикулите в пътя който сме се повтаря в пътя на детайла
    							// който ще наливаме забраняваме да се добавя артикула
    							foreach ($detailsToAdd as $det){
    								$path = $mvc->getProductPath($det);
    									
    								$intersected = array_intersect($thisPath, $path);
    								if(count($intersected)){
    									$canAdd = FALSE;
    									break;
    								}
    							}
    							
    							if(in_array($rec->resourceId, $path)){
    								$canAdd = FALSE;
    							}
    						}
    					}
    				}
    				
    				// Ако артикула не може да се избере сетваме грешка
    				if($canAdd === FALSE){
    					$form->setError('parentId,resourceId', 'Артикула не може да се повтаря в нивото');
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Връща масив с пътя на един запис
     * 
     * @param stdClass $rec - запис
     * @param string $position - дали да върнем позициите или ид-та на артикули
     * @return array - масив с последователноста на пътя на записа в позиции или ид-та на артикули
     */
    private function getProductPath($rec, $position = FALSE)
    {
    	$path = array();
    	$path[] = ($position) ? $rec->position : $rec->resourceId;
    	
    	$parent = $rec->parentId;
    	while($parent && ($pRec = $this->fetch($parent, "parentId,position,resourceId"))) {
    		$path[] = ($position) ? $pRec->position : $pRec->resourceId;
    		$parent = $pRec->parentId;
    	}
    	
    	$path = array_reverse($path, TRUE);
    	
    	return $path;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	// Показваме подробната информация за опаковката при нужда
    	deals_Helper::getPackInfo($row->packagingId, $rec->resourceId, $rec->packagingId, $rec->quantityInPack);
    	
    	if($rec->type == 'stage'){
    		$row->ROW_ATTR['style'] = 'background-color:#DFDFDF';
    		$row->ROW_ATTR['title'] = tr('Eтап');
    	} else {
    		$row->ROW_ATTR['class'] = ($rec->type != 'input' && $rec->type != 'stage') ? 'row-removed' : 'row-added';
    		$row->ROW_ATTR['title'] = ($rec->type != 'input' && $rec->type != 'stage') ? tr('Отпадък') : NULL;
    		$row->resourceId = cat_Products::getShortHyperlink($rec->resourceId);
    	}
    	
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    		if($mvc->haveRightFor('edit', $rec)){
    			$convertableOptions = planning_ObjectResources::fetchConvertableProducts($rec->resourceId);
    			if(count($convertableOptions)){
    				$row->resourceId .= ht::createLink('', array($mvc, 'edit', $rec->id, 'likeProductId' => $rec->resourceId, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/dropdown.gif,title=Избор на заместващ материал');
    			}
    		}
    	}
    	
    	// Генерираме кода според позицията на артикула и етапите
    	$codePath = $mvc->getProductPath($rec, TRUE);
    	$position = implode('.', $codePath);
    	$position = cls::get('type_Varchar')->toVerbal($position);
    	
    	$row->position = "<span style='float:left;font-weight:bold'>{$position}</span>";
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
    	
    	static::orderBomDetails($data->recs, $outArr);
    	$data->recs = $outArr;
    	
    	if($data->masterData->rec->state != 'draft'){
    		//unset($data->listFields['tools']);
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
    	// Ако сме добавили нов етап
    	if(empty($rec->id) && $rec->type == 'stage'){
    		$rec->stageAdded = TRUE;
    	}
    }
    
    
    /**
     * Намира следващия най-голямa позиция за нивото
     * 
     * @param int $bomId
     * @param int $parentId
     * @return int
     */
    private function getDefaultPosition($bomId, $parentId)
    {
    	$query = $this->getQuery();
    	$cond = "#bomId = {$bomId} AND ";
    	$cond .= (isset($parentId)) ? "#parentId = {$parentId}" : '#parentId IS NULL';
    	$query->where($cond);
    	$query->XPR('maxPosition', 'int', "MAX(#position)");
    	$position = $query->fetch()->maxPosition;
    	$position += 1;
    	
    	return $position;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Ако има позиция, шифтваме всички с по-голяма или равна позиция напред
    	if(isset($rec->position)){
    		$query = $mvc->getQuery();
    		$cond = "#bomId = {$rec->bomId} AND #id != {$rec->id} AND #position >= {$rec->position} AND ";
    		$cond .= (isset($rec->parentId)) ? "#parentId = {$rec->parentId}" : "#parentId IS NULL";
    		
    		$query->where($cond);
    		while($nRec = $query->fetch()){
    			$nRec->position++;
    			$mvc->save_($nRec, 'position');
    		}
    	}
    	
    	// Ако сме добавили нов етап
    	if($rec->stageAdded === TRUE){
    		static::addProductComponents($rec->resourceId, $rec->bomId, $rec->id);
    	}
    }
    
    
    /**
     * Връща подредените детайли на рецептата
     * 
     * @param int $id - ид
     * @return array - подредените записи
     */
    private function getOrderedBomDetails($id)
    {
    	// Извличаме и детайлите
    	$dQuery = $this->getQuery();
    	$dQuery->where("#bomId = '{$id}'");
    	$dRecs = $dQuery->fetchAll();
    	
    	// Подреждаме ги
    	self::orderBomDetails($dRecs, $outArr);
    	
    	return $outArr;
    }
    
    
    /**
     * Добавя компонентите на един етап към рецепта
     * 
     * @param int $productId   - ид на артикул
     * @param int $toBomId     - ид на рецепта към която го добавяме
     * @param int $componentId - на кой ред в рецептата е артикула
     * @return void
     */
    public static function addProductComponents($productId, $toBomId, $componentId)
    {
    	$me = cls::get(get_called_class());
    	
    	// Коя е последната активна рецепта за артикула
    	$activeBom = cat_Products::getLastActiveBom($productId);
    	
    	// Ако етапа има рецепта
    	if($activeBom){
    		$outArr = $me->getOrderedBomDetails($activeBom->id);
    		$cu = core_Users::getCurrent();
    		
    		// Копираме всеки запис
    		foreach ($outArr as $dRec){
    			unset($dRec->id);
    			$dRec->modidiedOn = dt::now();
    			$dRec->modifiedBy = $cu;
    			$dRec->bomId = $toBomId;
    			if(empty($dRec->parentId)){
    				$dRec->parentId = $componentId;
    			} else {
    					
    				// Ако реда има етап, намираме на кой ред в новата рецепта съответства стария етап
    				$parentResource = $me->fetchField("#bomId = {$activeBom->id} AND #id = {$dRec->parentId}", 'resourceId');
    				$dRec->parentId = $me->fetchField("#bomId = {$toBomId} AND #resourceId = {$parentResource}", 'id');
    			}
    		
    			// Добавяме записа
    			$me->save_($dRec);
    		}
    	}
    }
    
    
    /**
     * Подрежда записите от детайла на рецептата по етапи
     * 
     * @param array $inArr  - масив от записи
     * @param array $outArr - подредения масив
     * @param int $parentId - кой е текущия баща
     * @return void		
     */
    public static function orderBomDetails(&$inArr, &$outArr, $parentId = NULL)
    {
    	// Временен масив
    	$tmpArr = array();
    	
    	// Оставяме само тези записи с баща посочения етап
    	if(is_array($inArr)){
    		foreach ($inArr as $rec){
    			if($rec->parentId == $parentId){
    				$tmpArr[$rec->id] = $rec;
    			}
    		}
    	}
    	
    	// Сортираме ги по позицията им, ако е еднаква, сортираме по датата на последната модификация
    	usort($tmpArr, function($a, $b) {
    		if($a->position == $b->position) {
    			return ($a->modifiedOn > $b->modifiedOn) ? -1 : 1;
    		}
    		return ($a->position < $b->position) ? -1 : 1;
    	});
    	
    	// За всеки от тях
    	$cnt = 1;
    	foreach ($tmpArr as &$tRec){
    		
    		// Ако позицията му е различна от текущата опресняваме я
    		// така се подсигуряваме че позициите са последователни числа
    		if($tRec->position != $cnt){
    			$tRec->position = $cnt;
    			cls::get(get_called_class())->save_($tRec);
    		}
    		
    		// Добавяме реда в изходящия масив
    		$outArr[$tRec->id] = $tRec;
    		$cnt++;
    		
    		// Ако реда е етап, викаме рекурсивно като филтрираме само записите с етап ид-то на етапа
    		if($tRec->type == 'stage'){
    			static::orderBomDetails($inArr, $outArr, $tRec->id);
    		}
    	}
    }
    
    
    /**
     * Преди клонирането на детайлите
     */
    public static function on_BeforeCloneDetails($mvc, &$details)
    {
    	// Подсигуряваме се че са подредени
    	static::orderBomDetails($details, $outArr);
    	$details = $outArr;
    }
    
    
    /**
     * Преди запис на клониран детайл
     */
    public static function on_BeforeSaveClonedDetail($mvc, &$rec, $oldRec)
    {
    	// Ако има баща подсигуряваме се че ще го заменим с клонирания му запис
    	if(isset($rec->parentId)){
    		$parentResource = $mvc->fetchField("#bomId = {$oldRec->bomId} AND #id = {$rec->parentId}", 'resourceId');
    		$rec->parentId = $mvc->fetchField("#bomId = {$rec->bomId} AND #resourceId = {$parentResource}", 'id');
    	}
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
    	// Ако изтриваме етап, изтриваме всичките редове от този етап
    	foreach ($query->getDeletedRecs() as $id => $rec) {
    		if($rec->type == 'stage'){
    			$mvc->delete("#bomId = {$rec->bomId} AND #parentId = {$rec->id}");
    		}
    	}
    }
}
