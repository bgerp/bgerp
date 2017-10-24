<?php



/**
 * Мениджър на детайл на технологичната рецепта
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_BomDetails extends doc_Detail
{
	
	
	/**
	 * Константа за грешка при изчисление
	 */
	const CALC_ERROR = "Грешка при изчисляване";
	
	
    /**
     * Заглавие
     */
    public $title = "Детайл на технологичната рецепта";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'bomId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Modified, plg_RowTools2, cat_Wrapper, plg_SaveAndNew, plg_AlignDecimals2, planning_plg_ReplaceEquivalentProducts';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id,bomId,type';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Рецепти';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Детайл на технологична рецепта';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,cat,sales';
    
    
    /**
     * Кой има право да разгъва?
     */
    public $canExpand = 'ceo,cat,sales';
    
    
    /**
     * Кой има право да свива?
     */
    public $canShrink = 'ceo,cat,sales';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,cat,sales';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,cat,sales,techno';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'position=№, resourceId, packagingId=Мярка,propQuantity=Формула,rowQuantity=Вложено->Количество,primeCost,coefficient';
    
    
    /**
     * Поле за заместване на артикул
     * 
     * @see planning_plg_ReplaceEquivalentProducts
     */
    public $replaceProductFieldName = 'resourceId';
    
    
    /**
     * Поле за артикула
     */
    public $productFld = 'resourceId';
    
    
    /**
     * Поле за количеството на заместващ артикул
     * 
     * @see planning_plg_ReplaceEquivalentProducts
     */
    public $replaceProductQuantityFieldName = 'propQuantity';
    
    
    /**
     * При колко линка в тулбара на реда да не се показва дропдауна
     *
     * @param int
     * @see plg_RowTools2
     */
    public $rowToolsMinLinksToShow = 2;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('parentId', 'key(mvc=cat_BomDetails,select=id)', 'caption=Етап,remember,removeAndRefreshForm=propQuantity,silent');
    	$this->FLD('bomId', 'key(mvc=cat_Boms)', 'column=none,input=hidden,silent');
    	$this->FLD("resourceId", 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Материал,mandatory,silent,removeAndRefreshForm=packagingId|description');
    	$this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка','tdClass=small-field nowrap,smartCenter,silent,removeAndRefreshForm=quantityInPack,mandatory,input=hidden');
    	$this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
    	
    	$this->FLD("position", 'int(Min=0)', 'caption=Позиция,smartCenter,tdClass=leftCol');
    	$this->FLD("propQuantity", 'text(rows=2)', 'caption=Формула,tdClass=accCell,mandatory');
    	$this->FLD("description", 'richtext(rows=3,bucket=Notes)', 'caption=Допълнително->Описание');
    	$this->FLD('type', 'enum(input=Влагане,pop=Отпадък,stage=Етап)', 'caption=Действие,silent,input=hidden');
    	$this->FLD("primeCost", 'double', 'caption=Себестойност,input=none,tdClass=accCell');
    	$this->FLD('params', 'blob(serialize, compress)', 'input=none');
    	$this->FNC("rowQuantity", 'double(maxDecimals=4)', 'caption=Количество,input=none,tdClass=accCell');
    	$this->FLD("coefficient", 'double', 'input=none');
    	
    	$this->setDbIndex('parentId');
    	$this->setDbIndex('resourceId');
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
    	$baseCurrencyCode = acc_Periods::getBaseCurrencyCode($data->masterData->rec->modifiedOn);
    	$masterProductUomId = cat_Products::fetchField($data->masterData->rec->productId, 'measureId');
    	
    	$data->listFields['propQuantity'] = "|К-во влагане за|* {$data->masterData->row->quantity}->|Формула|*";
    	$data->listFields['rowQuantity'] = "|К-во влагане за|* {$data->masterData->row->quantity}->|Количество|*";
    	$data->listFields['primeCost'] = "|К-во влагане за|* {$data->masterData->row->quantity}->|Сума|* <small>({$baseCurrencyCode})</small>";
    	if(!haveRole('ceo, acc, cat, price')){
    		unset($data->listFields['primeCost']);
    	}
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
    	
    	$matCaption = ($rec->type == 'input') ? 'Артикул' : (($rec->type == 'pop') ? 'Отпадък' : 'Подетап');
    	$form->setField('resourceId', "caption={$matCaption}");
    	
    	// Добавяме всички вложими артикули за избор
    	if($rec->type == 'pop'){
    		$metas = 'canConvert,canStore';
    		$form->setField('description', 'input=none');
    	} else {
    		$metas = 'canConvert';
    	}
    	
    	$metas = ($rec->type == 'pop') ? 'canConvert,canStore' : 'canConvert';
    	$products = cat_Products::getByProperty($metas);
    	
    	// Ако артикула е избран, но не присъства в опциите добавяме
    	if(isset($rec->resourceId) && empty($products[$rec->resourceId])){
    		$products[$rec->resourceId] = cat_Products::getTitleById($rec->resourceId, FALSE);
    	}
    	
    	unset($products[$data->masterRec->productId]);
    	$form->setOptions('resourceId', $products);
    	
    	$form->setDefault('type', 'input');
    	$quantity = $data->masterRec->quantity;
    	$originInfo = cat_Products::getProductInfo($data->masterRec->productId);
    	$shortUom = cat_UoM::getShortName($originInfo->productRec->measureId);
    	
    	$propCaption = "Количество->|За|* |{$quantity}|* {$shortUom}";
    	$form->setField('propQuantity', "caption={$propCaption}");
    	
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
     * Преди подготовка на заглавието на формата
     */
    protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
    {
    	$rec = &$data->form->rec;
    	$data->singleTitle = ($rec->type == 'input') ? 'артикул за влагане' : (($rec->type == 'pop') ? 'отпадък' : 'етап');
    }
    
    
    /**
     * Изчислява израза
     * 
     * @param text $expr - формулата
     * @param array $params - параметрите
     * @return string $res - изчисленото количество
     */
    public static function calcExpr($expr, $params)
    {
    	$expr = preg_replace('/\$Начално\s*=\s*/iu', '1/$T*', $expr);

        $expr = preg_replace('/(\d+)+\,(\d+)+/', '$1.$2', $expr);

    	if(is_array($params)){
    		
    		// Да не променяме логиката, не позволяваме на потребителя да въвежда тиражът ръчно
    		$expr = str_replace('1/$T*', '_TEMP_', $expr);
    		$expr = str_replace('$T', '$Trr', $expr);
    		$expr = str_replace('_TEMP_', '1/$T*', $expr);
    		
    		$expr = strtr($expr, $params);
    	}
    	
    	if(str::prepareMathExpr($expr) === FALSE) {
    		$res = self::CALC_ERROR;
    	} else {
    		$res = str::calcMathExpr($expr, $success);
    		if($success === FALSE) {
    			$res = self::CALC_ERROR;
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     * Проверява за коректност израз и го форматира.
     */
    public static function highliteExpr($expr, $params, $coefficient)
    {
    	$rQuantity = cat_BomDetails::calcExpr($expr, $params);
    	if($rQuantity === self::CALC_ERROR) {
    		$style = 'color:red;';
    	}
    	
    	// Намира контекста и го оцветява
    	$context = array();
    	if(is_array($params)){
    		foreach ($params as $var => $val){
    			if($val !== self::CALC_ERROR && $var != '$T') {
    				$Double = cls::get('type_Double', array('params' => array('smartRound' => TRUE)));
    				$context[$var] = "<span style='color:blue' title='{$Double->toVerbal($val)}'>{$var}</span>";
    			} else {
    				$context[$var] = "<span title='{$val}'>{$var}</span>";
    			}
    		}
    	}
    	
    	$expr = strtr($expr, $context);
    	if(!is_numeric($expr)){
    		$expr = "<span style='{$style}'>{$expr}</span>";
    	}
    	$expr = preg_replace('/\$Начално\s*=\s*/iu', "<span style='color:blue'>" . tr('Начално') . "</span>=", $expr);
    	
    	if(isset($coefficient) && $coefficient != 1){
    		$expr = "( {$expr} ) / <span style='color:darkgreen' title='" . tr('Количеството от оригиналната рецепта') . "'>{$coefficient}</span>";
    	}
    	
    	if($rQuantity === self::CALC_ERROR) {
    		$expr = ht::createHint($expr, 'Формулата не може да бъде изчислена', 'warning');
    	}
    	return $expr;
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
    		$bomInfo = cat_Boms::getResourceInfo($bomId, 1, dt::now());
    		
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
     * @param cat_BomDetails $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	$masterProductId = cat_Boms::fetchField($rec->bomId, 'productId');
    	
    	// Ако има избран ресурс, добавяме му мярката до полетата за количества
    	if(isset($rec->resourceId)){
    		$params = cat_Boms::getProductParams($masterProductId);
    		
    		$path = $mvc->getProductPath($rec);
    		foreach ($path as $pId){
    			$newParams = cat_Boms::getProductParams($pId);
    			cat_Boms::pushParams($params, $newParams);
    		}
    		 
    		// Добавя допустимите параметри във формулата
    		$scope = cat_Boms::getScope($params);
    		$scope['$T'] = 1;
    		$scope['$Начално='] = '$Начално=';
    		
    		$rec->params = $scope;
    		
    		$context = array_keys($scope);
    		$context = array_combine($context, $context);
    		unset($context['$T']);
    		$form->setSuggestions('propQuantity', $context);
    		
    		$pInfo = cat_Products::getProductInfo($rec->resourceId);
    		
    		$packs = cat_Products::getPacks($rec->resourceId);
    		$form->setOptions('packagingId', $packs);
    		$form->setDefault('packagingId', key($packs));
    		
    		// Ако артикула не е складируем, скриваме полето за мярка
    		if(!isset($pInfo->meta['canStore'])){
    			$measureShort = cat_UoM::getShortName($rec->packagingId);
    			$form->setField('propQuantity', "unit={$measureShort}");
    		} else {
    			$form->setField('packagingId', 'input');
    		}
    		
    		if($rec->type != 'pop'){
    			$description = cat_Products::getDescription($rec->resourceId)->getContent();
    			$description = html2text_Converter::toRichText($description);
    			$description = cls::get('type_Richtext')->fromVerbal($description);
    			$description = str_replace("\n\n", "\n", $description);
    			
    			$form->setDefault('description', $description);
    		}
    	}
    	
    	// Проверяваме дали е въведено поне едно количество
    	if($form->isSubmitted()){
    		$calced = static::calcExpr($rec->propQuantity, $rec->params);
    		if($calced == static::CALC_ERROR){
    			$form->setWarning('propQuantity', 'Има проблем при изчисляването на количеството');
    		} elseif($calced <= 0){
    			$form->setError('propQuantity', 'Изчисленото количество трябва да е положително');
    		}
    		
    		if(isset($rec->resourceId)){
    			
    			// Ако е избран артикул проверяваме дали артикула от рецептата не се съдържа в него
    			$productVerbal = cat_Products::getTitleById($masterProductId);
    			
    			$notAllowed = array();
    			$mvc->findNotAllowedProducts($rec->resourceId, $masterProductId, $notAllowed);
    			if(isset($notAllowed[$rec->resourceId])){
    				$form->setError('resourceId', "Артикулът не може да бъде избран, защото в рецептата на някой от материалите му се съдържа|* <b>{$productVerbal}</b>");
    			}
    		}
    		
    		// Ако добавяме отпадък, искаме да има себестойност
    		if($rec->type == 'pop'){
    			$selfValue = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $rec->resourceId);
    			if(!isset($selfValue)){
    				$form->setWarning('resourceId', 'Отпадъкът няма себестойност');
    			}
    		} else {
    			
    			// Материалът може да се използва само веднъж в дадения етап
    			$cond = "#bomId = {$rec->bomId} AND #id != '{$rec->id}' AND #resourceId = {$rec->resourceId}";
    			$cond .= (empty($rec->parentId)) ? " AND #parentId IS NULL" : " AND #parentId = '{$rec->parentId}'";
    			if(self::fetchField($cond)){
    				$form->setError('resourceId,parentId', 'Артикулът вече се използва в този етап');
    			}
    		}
    		
    		$rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
    		
    		// Ако има артикул със същата позиция, или няма позиция добавяме нова
    		if(!isset($rec->position)){
    			$rec->position = $mvc->getDefaultPosition($rec->bomId, $rec->parentId);
    		}
    		
    		if($rec->type == 'stage'){
    			if($mvc->fetchField("#bomId = {$rec->bomId} AND #type = 'stage' AND #resourceId = '{$rec->resourceId}' AND #id != '{$rec->id}'")){
    				$form->setError('resourceId', 'Един етап може да се среща само веднъж в рецептата');
    			}
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
    					if(!empty($bom)){
    						
    						// и има детайли
    						$detailsToAdd = self::getOrderedBomDetails($bom->id);
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
    					$form->setError('parentId,resourceId', 'Артикулът не може да се повтаря в нивото');
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	// Показваме подробната информация за опаковката при нужда
    	deals_Helper::getPackInfo($row->packagingId, $rec->resourceId, $rec->packagingId, $rec->quantityInPack);
    	$row->resourceId = cat_Products::getShortHyperlink($rec->resourceId);
    	
    	if($rec->type == 'stage'){
    		$row->ROW_ATTR['style'] = 'background-color:#EFEFEF';
    		$row->ROW_ATTR['title'] = tr('Eтап');
    	} else {
    		$row->ROW_ATTR['class'] = ($rec->type != 'input' && $rec->type != 'stage') ? 'row-removed' : 'row-added';
    	}
    	
    	if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    		$extraBtnTpl = new core_ET("<!--ET_BEGIN BTN--><span style='float:right'>[#BTN#]</span><!--ET_END BTN-->");
    		
    		// Може ли да се разпъне реда
	    	if($mvc->haveRightFor('expand', $rec)){
	    		$link = ht::createLink('', array($mvc, 'expand', $rec->id, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/toggle1.png,title=Направи етап');
	    		$extraBtnTpl->append($link, 'BTN');
	    	}

	    	// Може ли да се свие етапа
	    	if($mvc->haveRightFor('shrink', $rec)){
	    		$link = ht::createLink('', array($mvc, 'shrink', $rec->id, 'ret_url' => TRUE), FALSE, 'ef_icon=img/16/toggle2.png,title=Свиване на етап');
	    		$extraBtnTpl->append($link, 'BTN');
	    	}
	    	
	    	$row->resourceId .= $extraBtnTpl;
    	}
    	
    	// Генерираме кода според позицията на артикула и етапите
    	$codePath = $mvc->getProductPath($rec, TRUE);
    	$position = implode('.', $codePath);
    	$position = cls::get('type_Varchar')->toVerbal($position);
    	$row->position = $position;
    	
    	if($rec->description){
    		$row->description = $mvc->getFieldType('description')->toVerbal($rec->description);
    		$row->resourceId .= "<br><small>{$row->description}</small>";
    	}
    	
    	$propQuantity = $rec->propQuantity;
    	$coefficient = NULL;
    	
    	if(isset($rec->parentId)){
    		$coefficient = $mvc->fetchField($rec->parentId, 'coefficient');
    		
    		if(isset($coefficient)){
    			$rec->propQuantity = "($rec->propQuantity) / $coefficient";
    		}
    	}
    	
    	$rec->rowQuantity = cat_BomDetails::calcExpr($rec->propQuantity, $rec->params);
    	
    	$highlightedExpr = static::highliteExpr($propQuantity, $rec->params, $coefficient);
    	$row->propQuantity = $highlightedExpr;
    	
    	if($rec->rowQuantity == static::CALC_ERROR){
    		$row->rowQuantity = "<span class='red'>???</span>";
    		$row->primeCost = "<span class='red'>???</span>";
    		$row->primeCost = ht::createHint($row->primeCost, 'Не може да бъде изчислена себестойността', 'warning', FALSE);
    	} else {
    		$row->rowQuantity = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($rec->rowQuantity);
    	}
    	
    	if(!$rec->primeCost && $rec->type != 'stage'){
    		$row->primeCost = "<span class='red'>???</span>";
    		$row->primeCost = ht::createHint($row->primeCost, 'Няма себестойност', 'warning', FALSE);
    	}
    	
    	$compare = str_replace(',', '.', $rec->propQuantity);
    	if(is_numeric($compare)){
    		$row->propQuantity = "<span style='float:right'>{$row->propQuantity}</span>";
    	} 
    	
    	if($rec->type == 'pop'){
    		$row->resourceId = ht::createHint($row->resourceId, 'Артикулът е отпадък', 'img/16/recycle.png');
    	}
    }
    
    
    /**
     * Екшън за разпъване на материал като етап с подетапи
     */
    function act_Expand()
    {
    	$this->requireRightFor('expand');
    	expect($id = Request::get('id', int));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('expand', $rec);
    	
    	$rec->type = 'stage';
    	$rec->primeCost = NULL;
    	
    	cat_BomDetails::addProductComponents($rec->resourceId, $rec->bomId, $rec->id, $bomRec);
    	if(isset($bomRec)){
    		$rec->coefficient = $bomRec->quantity;
    	}
    	$this->save($rec, 'type,primeCost,coefficient');
    	
    	$title = cat_Products::getTitleById($rec->resourceId);
    	$msg = "{$title} |вече е етап|*";
    	$this->Master->logRead("Разпъване на вложен артикул", $rec->bomId);
    	
    	return new Redirect(array('cat_Boms', 'single', $rec->bomId), $msg);
    }
    
    
    /**
     * Екшън за разпъване на материал като етап с подетапи
     */
    function act_Shrink()
    {
    	$this->requireRightFor('shrink');
    	expect($id = Request::get('id', int));
    	expect($rec = $this->fetch($id));
    	$this->requireRightFor('shrink', $rec);
    	
    	$rec->type = 'input';
    	$this->delete("#bomId = {$rec->bomId} AND #parentId = {$rec->id}");
    	$rec->coefficient = NULL;
    	$this->save($rec);
    	
    	$title = cat_Products::getTitleById($rec->resourceId);
    	$msg = "|Свиване на|* {$title}";
    	$this->Master->logRead("Свиване на етап", $rec->bomId);
    	
    	return new Redirect(array('cat_Boms', 'single', $rec->bomId), $msg);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    	if($mvc->haveRightFor('add', (object)array('bomId' => $data->masterId))){
    		$data->toolbar->addBtn('Влагане', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => TRUE, 'type' => 'input'), NULL, "title=Добавяне на артикул за влагане,ef_icon=img/16/package.png");
    		$data->toolbar->addBtn('Етап', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => TRUE, 'type' => 'stage'), NULL, "title=Добавяне на етап,ef_icon=img/16/wooden-box.png");
    		$data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => TRUE, 'type' => 'pop'), NULL, "title=Добавяне на отпадък,ef_icon=img/16/recycle.png");
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'edit' || $action == 'delete' || $action == 'add' || $action == 'expand' || $action == 'shrink') && isset($rec)){
    		$masterRec = cat_Boms::fetch($rec->bomId, 'state,originId');
    		if($masterRec->state != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// Може ли записа да бъде разширен
    	if(($action == 'expand' || $action == 'shrink') && isset($rec)){
    		
    		// Артикула трябва да е производим и да има активна рецепта
    		$canManifacture = cat_Products::fetchField($rec->resourceId, 'canManifacture');
    		if($canManifacture != 'yes'){
    			$requiredRoles = 'no_one';
    		} else {
    			$type = cat_Boms::fetchField($rec->bomId, 'type');
    			if($type == 'production'){
    				$aBom = cat_Products::getLastActiveBom($rec->resourceId, 'production');
    			}
    			if(!$aBom){
    				$aBom = cat_Products::getLastActiveBom($rec->resourceId, 'sales');
    			}
    				
    			if(!$aBom){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'expand' && isset($rec)){
    		// Само материал може да се разпъва
    		if($rec->type != 'input'){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($action == 'shrink' && isset($rec)){
    	
    		// Само етап може да се свива
    		if($rec->type != 'stage'){
    			$requiredRoles = 'no_one';
    		} else {
    			
    		}
    		
    		if($requiredRoles != 'no_one'){
    			if(!$mvc->checkComponents($rec)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	// Етап не може да се замества
    	if($action == 'replaceproduct' && isset($rec)){
    		if($rec->type == 'stage'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
	/**
	 * Помощна ф-я връщаща масив със всички записи, които са наследници на даден запис
	 */
	private function getDescendents($id, &$res = array())
	{
		$descendents = array();
		$query = $this->getQuery();
		$query->where("#parentId = {$id}");
		$query->show('resourceId,propQuantity,packagingId,quantityInPack');
		$query->orderBy('resourceId', 'ASC');
		
		while($rec = $query->fetch()){
			$obj = new stdClass();
			$obj->resourceId = $rec->resourceId;
			$obj->packagingId = $rec->packagingId;
			$obj->propQuantity = trim($rec->propQuantity);
			$res[$rec->resourceId . "|" . $rec->packagingId] = $obj;
			
			if($rec->type != 'stage'){
				self::getComponents($rec->resourceId, $res);
			}
			$this->getDescendents($rec->id, $res);
		}
		
		return $res;
	}
    
    
	/**
	 * Намира компонентите на един артикул
	 */
	private function getComponents($productId, &$res = array())
	{
		// Имали последна активна търговска рецепта за артикула?
		$rec = cat_Products::getLastActiveBom($productId, 'sales');
		if(!$rec) return $res;
	
		// Кои детайли от нея ще показваме като компоненти
		$details = cat_BomDetails::getOrderedBomDetails($rec->id);
		 
		// За всеки
		if(is_array($details)){
			foreach ($details as $dRec){
				$obj = new stdClass();
				$obj->resourceId = $dRec->resourceId;
				$obj->packagingId = $dRec->packagingId;
				$obj->propQuantity = trim($dRec->propQuantity);
				$res[$dRec->resourceId . "|" . $dRec->packagingId] = $obj;
				
				if($dRec->type != 'stage'){
					self::getComponents($dRec->resourceId, $res);
				}
			}
		}
	}
	
	
    /**
     * Проверява дали подетапите на един етап отговарят точно
     * на рецептата му
     */
    private function checkComponents($rec)
    {
    	$children = $bomDetails = array();
    	$this->getDescendents($rec->id, $children);
    	$components = $this->getComponents($rec->resourceId, $bomDetails);
    	ksort($children);
    	ksort($bomDetails);
    	
    	$areSame = TRUE;
    	foreach ($children as $index => $obj){
    		$other = $bomDetails[$index];
    		if($obj->propQuantity != $other->propQuantity || $obj->resourceId != $other->resourceId || $obj->packagingId != $other->packagingId){
    			$areSame = FALSE;
    			break;
    		}
    	}
    	
    	return $areSame;
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    protected static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
    	if(!count($data->recs)) return;
    	
    	// Подреждаме детайлите
    	self::orderBomDetails($data->recs, $outArr);
    	$data->recs = $outArr;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$data)
    {
    	$hasSameQuantities = TRUE;
    	
    	if(is_array($data->recs)){
			foreach ($data->recs as $id => &$rec){
				if($rec->parentId){
					if($data->recs[$rec->parentId]->rowQuantity != cat_BomDetails::CALC_ERROR){
						$rec->rowQuantity *= $data->recs[$rec->parentId]->rowQuantity;
						$data->rows[$id]->rowQuantity = $mvc->getFieldType('rowQuantity')->toVerbal($rec->rowQuantity);
					}
				}
				
				if($rec->rowQuantity != $rec->propQuantity){
					$hasSameQuantities = FALSE;
				}
			}
    	}

    	// Ако формулите и изчислените к-ва са равни, показваме само едната колонка
    	if($hasSameQuantities === TRUE){
    		unset($data->listFields['propQuantity']);
    	}
    	
    	unset($data->listFields['coefficient']);
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
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
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
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
    		static::addProductComponents($rec->resourceId, $rec->bomId, $rec->id, $bomRec);
    		if($bomRec){
    			$rec->coefficient = $bomRec->quantity;
    			$mvc->save_($rec, 'coefficient');
    		}
    	}
    }
    
    
    /**
     * Връща подредените детайли на рецептата
     * 
     * @param int $id - ид
     * @return array - подредените записи
     */
    public static function getOrderedBomDetails($id)
    {
    	// Извличаме и детайлите
    	$dQuery = self::getQuery();
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
    public static function addProductComponents($productId, $toBomId, $componentId, &$activeBom = NULL, $onlyIfQuantitiesAreEqual = FALSE)
    {
    	$me = cls::get(get_called_class());
    	$toBomRec = cat_Boms::fetch($toBomId);
    	
    	if($toBomRec->type == 'production'){
    		$activeBom = cat_Products::getLastActiveBom($productId, 'production');
    	}
    	 
    	if(!$activeBom){
    		$activeBom = cat_Products::getLastActiveBom($productId, 'sales');
    	}
    	
    	// Ако етапа има рецепта
    	if($activeBom){
    		if($onlyIfQuantitiesAreEqual === TRUE){
    			if($activeBom->quantity != $toBomRec->quantity) {
    				return;
    			}
    		}
    		
    		$outArr = static::getOrderedBomDetails($activeBom->id);
    		$cu = core_Users::getCurrent();
    		
    		// Копираме всеки запис
    		$map = array();
    		if(is_array($outArr)){
    			foreach ($outArr as $dRec){
    				$oldId = $dRec->id;
    				
    				unset($dRec->id);
    				$dRec->modidiedOn = dt::now();
    				$dRec->modifiedBy = $cu;
    				$dRec->bomId = $toBomId;
    				if(empty($dRec->parentId)){
    					$dRec->parentId = $componentId;
    				} else {
    					$dRec->parentId = $map[$dRec->parentId];
    				}
    				
    				// Добавяме записа
    				$me->save_($dRec);
    				$map[$oldId] = $dRec->id;
    			}
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
    private static function orderBomDetails(&$inArr, &$outArr, $parentId = NULL)
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
    			self::orderBomDetails($inArr, $outArr, $tRec->id);
    		}
    	}
    }
    
    
    /**
     * Клонира детайлите на рецептата
     * 
     * @param int $fromBomId
     * @param int $toBomId
     * @return void
     */
    public function cloneDetails($fromBomId, $toBomId)
    {
    	$fromBomRec = cat_Boms::fetchRec($fromBomId);
    	cat_BomDetails::addProductComponents($fromBomRec->productId, $toBomId, NULL);
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
