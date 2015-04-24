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
     * Записи за обновяване
     */
    protected $updated = array();
    
    
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
    			$detailsKeywords .= " " . plg_Search::normalizeText(planning_Resources::getTitleById($dRec->resourceId));
    			if($dRec->stageId){
    				$detailsKeywords .= " " . plg_Search::normalizeText(planning_Stages::getTitleById($dRec->stageId));
    			}
    		}
    		
    		$res = " " . $res . " " . $detailsKeywords;
    	}
    }
    
    
    /**
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
    	// Запомняне кои документи трябва да се обновят
    	if(!empty($id)){
    		$mvc->updated[$id] = $id;
    	}
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
    	if(count($mvc->updated)){
    		foreach ($mvc->updated as $id) {
    			$rec = $mvc->fetchRec($id);
    			$mvc->save($rec);
    		}
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
    				$form->FNC("resourceId{$i}", 'key(mvc=planning_Resources,select=title,allowEmpty)', 'input=hidden');
    				$form->setDefault("resourceId{$i}", $resId);
    				$caption = planning_Resources::getTitleById($resId);
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
    	$count = count($count) -1;
    	
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
     * 
     * @param int $productId - ид на артикул
     * @return stdClass $total - обект съдържащ сумарната пропорционална и начална цена
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
    			$selfValue = planning_Resources::fetchField($dRec->resourceId, 'selfValue');
    			
    			// Добавяме към началната сума и пропорционалната
    			$amounts->base += $dRec->baseQuantity * $selfValue * $sign;
    			$amounts->prop += $dRec->propQuantity * $selfValue * $sign;
    		}
    	}
    	
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
     * 			        o $res->resourceId       - ид на ресурса
     * 					o $res->type             - вложим или отпаден ресурс
	 * 			        o $res->baseQuantity     - начално количество на ресурса
	 * 			        o $res->propQuantity     - пропорционално количество на ресурса
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
    	
    	// За всеки етап
    	while($dRec = $dQuery->fetch()){
    		
    		$arr = array();
    		$arr['resourceId']   = $dRec->resourceId;
    		$arr['type']         = $dRec->type;
    		$arr['baseQuantity'] = $dRec->baseQuantity;
    		$arr['propQuantity'] = $dRec->propQuantity;
    		 
    		$resources['resources'][] = (object)$arr;
    	}
    	
    	// Връщаме намерените ресурси
    	return $resources;
    }
    
    
    /**
     * Рекурсивно обхождаме дървото на рецептата и търсим дали
     * тя съдържа някъде определен ресурс, ако да то добавяме
     * всички ресурси които са част от дървото към масив.
     * 
     * @param int $resourceId - ид на ресурса
     * @param array $notAllowed - масив където се добавят
     * забранените ресурси
     * @param int $needle - ресурс, който търсим
     * @param array $path - пътя до ресурса в дървото
     */
    private static function traverseTree($resourceId, $needle, &$notAllowed, $path = array())
    {
    	// Добавяме текущия продукт
    	$path[] = $resourceId;
    	
    	// Ако стигнем до началния, прекратяваме рекурсията
    	if($resourceId == $needle){
    		foreach($path as $p){
    			 
    			// За всеки продукт в пътя до намерения ние го
    			// добавяме в масива notAllowed, ако той, вече не е там
    			if(!array_key_exists($p, $path)){
    				$notAllowed[$p] = $p;
    			}
    		}
    		return;
    	}
    	
    	// Взимаме вложените ресурси в етапа
    	$query = cat_BomStageDetails::getQuery();
    	$stageRec = cat_BomStages::fetch("#resourceId = {$resourceId}");
    	
    	$query->where("#bomstageId = {$stageRec->id} AND #type = 'input'");
    	
    	// За всеки
    	while($rec = $query->fetch()){
    		
    		// Ако някой от вложимите е изходен за друг етап от рецептата
    		if($sRec = cat_BomStages::fetch("#bomId = {$stageRec->bomId} AND #resourceId = {$rec->resourceId}")){
    			
    			// Извикваме рекурсивно
    			self::traverseTree($sRec->resourceId, $needle, $notAllowed, $path);
    		}
    	}
    }
}