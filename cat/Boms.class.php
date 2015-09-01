<?php


/**
 * Мениджър за технологични рецепти на артикули
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Boms extends core_Master
{
   
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_Boms';
	
	
   /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Технологични рецепти";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, cat_Wrapper, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, doc_ActivatePlg, plg_Search';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт,title=Документ,productId=За артикул,state,createdOn,createdBy,modifiedOn,modifiedBy";
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'productId,notes';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'cat_BomDetails';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Технологична рецепта';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/article.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Bom";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cat,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cat,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'cat,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,cat';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,cat';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'cat/tpl/SingleLayoutBom.shtml';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('quantity', 'double(smartRound,Min=0)', 'caption=За,silent,refreshForm,mandatory');
    	$this->FLD('notes', 'richtext(rows=4)', 'caption=Забележки');
    	$this->FLD('expenses', 'percent', 'caption=Режийни разходи');
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 'caption=Статус, input=none');
    	$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
    	
    	$this->setDbIndex('productId');
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
    	if($rec->id){
    		$detailsKeywords = '';
    		
    		// Добавяме данни от детайла към ключовите думи на документа
    		$dQuery = cat_BomDetails::getQuery();
    		$dQuery->where("#bomId = '{$rec->id}'");
    		while($dRec = $dQuery->fetch()){
    			$detailsKeywords .= " " . plg_Search::normalizeText(cat_Products::getTitleById($dRec->resourceId));
    			if($dRec->stageId){
    				$detailsKeywords .= " " . plg_Search::normalizeText(planning_Stages::getTitleById($dRec->stageId));
    			}
    		}
    		
    		$res = " " . $res . " " . $detailsKeywords;
    	}
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
    	
    	$productInfo = cat_Products::getProductInfo($form->rec->productId);
    	$shortUom = cat_UoM::getShortName($productInfo->productRec->measureId);
    	$form->setField('quantity', "unit={$shortUom}");
    	$form->setDefault('quantity', 1);
    	
    	// При създаване на нова рецепта
    	if(empty($form->rec->id)){
    		$limit = core_Packs::getConfig('cat')->CAT_BOM_REMEMBERED_RESOURCES;
    		
    		$alreadyUsedResources = array();
    		 
    		// Опитваме се да намерим последно използваните ресурси в рецепти към този артикул
    		$dQuery = cat_BomDetails::getQuery();
    		$dQuery->EXT('productId', 'cat_Boms', 'externalName=productId,externalKey=bomId');
    		$dQuery->where("#productId = {$form->rec->productId} AND #type = 'input'");
    		$dQuery->groupBy('resourceId');
    		$dQuery->show('resourceId');
    		$dQuery->limit($limit);
    		while($dRec = $dQuery->fetch()){
    			$alreadyUsedResources[] = $dRec->resourceId;
    		}
    		 
    		// Ако има такива, добавяме ги като полета във формата
    		if(count($alreadyUsedResources)){
    			foreach ($alreadyUsedResources as $i => $resId){
    				$form->FNC("resourceId{$i}", 'key(mvc=cat_Products,select=name,allowEmpty)', 'input=hidden');
    				$form->setDefault("resourceId{$i}", $resId);
    				$caption = cat_Products::getTitleById($resId);
    				$caption = str_replace(',', '.', $caption);
    				 
    				if(isset($form->rec->quantity)){
    					$right = "за {$form->rec->quantity} {$shortUom}";
    				}
    				 
    				$form->FNC("quantities{$i}", "complexType(left=Начално,right={$right},require=one)", "input,caption=|*{$caption}->|К-ва|*");
    			}
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	$count = core_Packs::getConfig('cat')->CAT_BOM_REMEMBERED_RESOURCES;
    	$count = $count -1;
    	
    	// Проверяваме имали избрани ресурси още от формата
    	foreach (range(0, $count) as $i){
    		if(isset($rec->{"resourceId{$i}"})){
    			if(!empty($rec->{"quantities{$i}"})){
    				$parts = type_ComplexType::getParts($rec->{"quantities{$i}"});
    	
    				// Ако някой от ресурсите в формата има количество добавяме го като детайл, автоматично
    				$dRec = (object)array('bomId' => $rec->id,
				    					  'type' => 'input',
				    					  'resourceId' => $rec->{"resourceId{$i}"},
				    					  'baseQuantity' => ($parts['left']) ? $parts['left'] : NULL,
				    					  'propQuantity' => ($parts['right']) ? $parts['right'] : NULL);
    	
    				// Запис на детайла
    				cat_BomDetails::save($dRec);
    			}
    		}
    	}
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
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
    	if(($action == 'write' || $action == 'add') && isset($rec)){
    		
    		// Може да се добавя само ако има ориджин
    		if(empty($rec->productId)){
    			$res = 'no_one';
    		} else {
    			$productRec = cat_Products::fetch($rec->productId);
    			
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
    	}
    	
    	if(($action == 'activate' || $action == 'restore' || $action == 'conto' || $action == 'write') && isset($rec->productId) && $res != 'no_one'){
    		
    		// Ако има активна карта, да не може друга да се възстановява,контира,създава или активира
    		if($mvc->fetch("#productId = {$rec->productId} AND #state = 'active'")){
    			$res = 'no_one';
    		}
    	}
    	
    	// Ако няма ид, не може да се активира
    	if($action == 'activate' && empty($rec->id)){
    		$res = 'no_one';
    	}
    	
    	// Не може да се активира, ако няма избрани ресурси
    	if($action == 'activate' && isset($rec->id)){
    		if(!count(cat_BomDetails::fetchField("#bomId = {$rec->id}"))){
    			$res = 'no_one';
    		}
    	}
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
    	$row->recTitle = $rec->title;
    	
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    
    	return "{$self->singleTitle} №{$rec->id}";
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
    	$row->title = $mvc->getLink($rec->id, 0);
    	
    	if($row->quantity){
    		$measureId = cat_Products::getProductInfo($rec->productId)->productRec->measureId;
    		$row->quantity .= " " . cat_UoM::getShortName($measureId);
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Връща сумата на спецификацията според подадения ориджин
     * Ако някой от ресурсите няма себестойност не може да се пресметне сумата
     * 
     * @param int $productId - ид на артикул
     * @return mixed $total - обект съдържащ сумарната пропорционална и начална цена
     * 		 o $total->base - началната сума (в основната валута за периода)
     * 		 o $total->prop - пропорционалната сума (в основната валута за периода)
     */
    public static function getPrice($productId, $bomId = NULL)
    {
    	// Намираме активната карта за обекта
    	$where = "#productId = {$productId} AND #state = 'active'";
    	if($bomId){
    		$where .= " AND #id = {$bomId}";
    	}
    	$rec = self::fetch($where);
    	
    	// Ако няма, връщаме нулеви цени
    	if(empty($rec)) return FALSE;
    	
    	// Кои ресурси участват в спецификацията
    	$rInfo = static::getResourceInfo($rec);
    	$amounts = (object)array('base' => 0, 'prop' => 0);
    	
    	// За всеки ресурс
    	if(count($rInfo['resources'])){
    		foreach ($rInfo['resources'] as $dRec){
    			$sign = ($dRec->type == 'input') ? 1 : -1;
    			
    			// Опитваме се да намерим себестойност за артикула
    			$selfValue = planning_ObjectResources::getSelfValue($dRec->productId);
    			
    			// Ако не може да се определи себестойност на ресурса, не може и по рецептата
    			if(!$selfValue) return FALSE;
    			
    			// Добавяме към началната сума и пропорционалната
    			$amounts->base += $dRec->baseQuantity * $selfValue * $sign;
    			$amounts->prop += $dRec->propQuantity * $selfValue * $sign;
    		}
    	}
    	
    	$amounts->base /= $rInfo['quantity'];
    	$amounts->prop /= $rInfo['quantity'];
    	
    	// Връщаме изчислените суми
    	return $amounts;
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
    public static function getResourceInfo($id)
    {
    	$resources = array();
    	
    	expect($rec = static::fetchRec($id));
    	$resources['quantity'] = ($rec->quantity) ? $rec->quantity : 1;
    	$resources['expenses'] = ($rec->expenses) ? $rec->expenses : NULL;
    	
    	// Намираме всички етапи в рецептата
    	$dQuery = cat_BomDetails::getQuery();
    	$dQuery->where("#bomId = {$rec->id}");
    	$dQuery->orderBy('id', 'ASC');
    	
    	// За всеки етап
    	while($dRec = $dQuery->fetch()){
    		
    		$arr = array();
    		$arr['productId']      = $dRec->resourceId;
    		$arr['type']           = $dRec->type;
    		$arr['packagingId']    = $dRec->packagingId;
    		$arr['quantityInPack'] = $dRec->quantityInPack;
    		$arr['baseQuantity']   = $dRec->baseQuantity * $dRec->quantityInPack;
    		$arr['propQuantity']   = $dRec->propQuantity * $dRec->quantityInPack;
    		 
    		$resources['resources'][] = (object)$arr;
    	}
    	
    	// Връщаме намерените ресурси
    	return $resources;
    }
    
    
    /**
     * Функция, която се извиква преди активирането на документа
     */
    public static function on_BeforeActivation($mvc, $res)
    {
    	if($res->id){
    		$dQuery = cat_BomDetails::getQuery();
    		$dQuery->where("#bomId = {$res->id}");
    		$dQuery->where("#type = 'input'");
    		
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
    public static function createNewDraft($productId, $quantity, $details = array(), $notes = NULL, $expenses = NULL)
    {
    	// Проверка на подадените данни
    	expect($pRec = cat_Products::fetch($productId));
    	expect($pRec->canManifacture == 'yes');
    	
    	$Double = cls::get('type_Double');
    	$Richtext = cls::get('type_RichText');
    	
    	$rec = (object)array('productId' => $productId,
    						 'originId'  => $pRec->containerId, 
    						 'folderId'  => $pRec->folderId, 
    						 'threadId'  => $pRec->threadId, 
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
    			if($d->stageId){
    				expect(planning_Stages::fetch($d->stageId));
    			}
    		}
    	}
    	
    	// Ако всичко е наред, записваме мастъра на рецептата
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
}