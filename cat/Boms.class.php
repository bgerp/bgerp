<?php


/**
 * Мениджър за технологични карти (Рецепти)
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
    var $loadList = 'plg_RowTools, cat_Wrapper, plg_Sorting, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, doc_ActivatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт,originId=Спецификация,createdOn,createdBy,modifiedOn,modifiedBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'Stages=cat_BomStages';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Технологична рецепта';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Map";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cat,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cat,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'cat,ceo';
    
    
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
    var $singleLayoutFile = 'cat/tpl/SingleLayoutMap.shtml';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('notes', 'richtext(rows=4)', 'caption=Забележки');
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 'caption=Статус, input=none');
    	$this->FLD('quantity', 'double(smartRound)', 'caption=За к-во');
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
    		if(empty($rec->originId)){
    			$res = 'no_one';
    		} else {
    			$origin = doc_Containers::getDocument($rec->originId);
    			if(!$origin->haveInterface('cat_ProductAccRegIntf')){
    				$res = 'no_one';
    			}
    			
    			// Трябва да е активиран
    			if($origin->fetchField('state') != 'active'){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	if(($action == 'activate' || $action == 'restore' || $action == 'conto' || $action == 'write') && isset($rec->originId) && $res != 'no_one'){
    		
    		// Ако има активна карта, да не може друга да се възстановява,контира,създава или активира
    		if($mvc->fetch("#originId = {$rec->originId} AND #state = 'active'")){
    			$res = 'no_one';
    		}
    	}
    	
    	// Ако няма ид, не може да се активира
    	if($action == 'activate' && empty($rec->id)){
    		$res = 'no_one';
    	}
    	
    	// Не може да се активира, ако няма избрани ресурси
    	if($action == 'activate' && isset($rec->id)){
    		if(!count($mvc->getResourceInfo($rec->id))){
    			$res = 'no_one';
    		} else {
    			
    			
    			return;
    			$exitResources = static::getExitResources($bomId);
    			
    			$query2 = static::getDetailQuery($rec->id);
    			$r = $query2->fetchAll();
    			bp($exitResources, $r);
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	// Ако има ориджин в рекуеста
    	if($originId = Request::get('originId', 'int')){
    		
    		// Очакваме той да е 'techno2_SpecificationDoc' - спецификация
    		$origin = doc_Containers::getDocument($originId);
    		expect($origin->haveInterface('cat_ProductAccRegIntf'));
    		expect($origin->fetchField('state') == 'active');
    		
    		// Ако е спецификация, документа може да се добави към нишката
    		return TRUE;
    	}
    	
    	return FALSE;
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
    	$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})" ;
    	 
    	$origin = doc_Containers::getDocument($rec->originId);
    	$row->originId = $origin->getHyperLink(TRUE);
    	
    	if($row->quantity){
    		$measureId = doc_Containers::getDocument($rec->originId)->getProductInfo()->productRec->measureId;
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
     * @param int $containerId - ид на контейнера, който е генерирал картата
     * @return stdClass $total - обект съдържащ сумарната пропорционална и начална цена
     * 		 o $total->base - началната сума (в основната валута за периода)
     * 		 o $total->prop - пропорционалната сума (в основната валута за периода)
     */
    public static function getTotalByOrigin($containerId)
    {
    	// Намираме активната карта за обекта
    	$rec = self::fetch("#originId = {$containerId} AND #state = 'active'");
    	
    	// Ако няма, връщаме нулеви цени
    	if(empty($rec)) return FALSE;
    	
    	// Кои ресурси участват в спецификацията
    	$rInfo = static::getResourceInfo($rec);
    	
    	$amounts = (object)array('base' => 0, 'prop' => 0);
    	
    	// За всеки ресурс
    	if(count($rInfo)){
    		foreach ($rInfo as $dRec){
    			$selfValue = mp_Resources::fetchField($dRec->resourceId, 'selfValue');
    			
    			// Добавяме към началната сума и пропорционалната
    			$amounts->base += $dRec->baseQuantity * $selfValue;
    			$amounts->prop += $dRec->propQuantity * $selfValue;
    		}
    	}
    	
    	// Връщаме изчислените суми
    	return $amounts;
    }
    
    
    /**
     * Връща информация с ресурсите използвани в технологичната рецепта
     *
     * @param mixed $id - ид или запис
     * @return array $res - масив с записи на участващите ресурси
     * 			o $res->resourceId       - ид на ресурса
     * 			o $res->activityCenterId - ид на центъра на дейност от производствения етап
     * 			o $res->baseQuantity     - начално количество на ресурса
     * 			o $res->propQuantity     - пропорционално количество на ресурса
     */
    public static function getResourceInfo($id)
    {
    	$resources = array();
    	
    	expect($rec = static::fetchRec($id));
    	
    	// Намираме всички етапи в рецептата
    	$dQuery = cat_BomStages::getQuery();
    	$dQuery->where("#bomId = {$rec->id}");
    	
    	// За всеки етап
    	while($dRec = $dQuery->fetch()){
    		
    		// Проверяваме имали вързани ресурси към него
    		$sQuery = cat_BomStageDetails::getQuery();
    		$sQuery->where("#bomstageId = {$dRec->id}");
    		while($sRec = $sQuery->fetch()){
    			$arr = array();
    			$arr['resourceId'] = $sRec->resourceId;
    			if(isset($dRec->stage)){
    				$arr['activityCenterId'] = mp_Stages::fetchField($dRec->stage, 'departmentId');
    			}
    			
    			$arr['baseQuantity'] = $sRec->baseQuantity;
    			$arr['propQuantity'] = $sRec->propQuantity;
    			
    			$resources[] = (object)$arr;
    		}
    	}
    	
    	// Връщаме намерените ресурси
    	return $resources;
    }


    /**
     * Връща масив с изходните ресурси на етапите
     * 
     * @param int $bomId - ид
     * @return array $exitResources - масив с изходните ресурси
     */
    public static function getExitResources($bomId)
    {
    	$exitResources = array();
    	$dQuery = cat_BomStages::getQuery();
    	$dQuery->where("#bomId = {$bomId}");
    	$dQuery->show('resourceId,exitQuantity');
    	while($dRec = $dQuery->fetch()){
    		$exitResources[$dRec->resourceId] = 1;
    	}
    	
    	return $exitResources;
    }
    
    
    /**
     * Връща ресурсите които могат да се добавят към дадена рецепта, това са тези, които вече не са добавени
     *
     * @param int $bomId - ид
     * @param boolean $stageId - за кой етап
     * @return array - свободните за добавяне ресурси
     */
    public static function makeResourceOptions($bomId, $stageId = NULL)
    {
    	$usedRes = array();
    	 
    	// Намираме всички ресурси, които са използвани в рецептата
    	$query = cat_BomStages::getQuery();
    	$query->where("#bomId = {$bomId}");
    	while($qRec = $query->fetch()){
    		$dQuery = cat_BomStageDetails::getQuery();
    		$dQuery->where("#bomstageId = {$qRec->id}");
    		while($dRec = $dQuery->fetch()){
    			$usedRes[$dRec->resourceId] = mp_Resources::getTitleById($dRec->resourceId, FALSE);
    		}
    	}
    	
    	// Намираме всички стандартни ресурси
    	$allResources = cls::get('mp_Resources')->makeArray4Select('title', array("#bomId IS NULL && state NOT IN ('rejected')"));
    	
    	// Намираме ресурсите, които са заготовки за тази рецепта
    	$bomResources = cls::get('mp_Resources')->makeArray4Select('title', array("#bomId IS NOT NULL && state NOT IN ('rejected') && #bomId = {$bomId}"));

    	// Добавяме ги към списъка, ако има
    	if(count($bomResources)){
    		
    		$notAllowed = array();
    		$needle = cat_BomStages::fetchField("#bomId = {$bomId} AND #stage = '{$stageId}'", 'resourceId');
    		
    		if(count($bomResources)){
    			foreach ($bomResources as $id => $name){
    				self::traverseTree($id, $needle, $notAllowed);
    			}
    		}
    		
    		if(count($notAllowed)){
    			foreach ($notAllowed as $notId){
    				unset($bomResources[$notId]);
    			}
    		}
    		
    		if(count($bomResources)){
    			$allResources['Заготовки'] = (object)array(
    					'title' => 'Заготовки',
    					'group' => TRUE,
    			);
    			
    			$allResources = $allResources + $bomResources;
    		}
    	}
    	
    	// Намираме тези ресурси, които не са използвани в рецептата
    	$diffArr = array_diff_key($allResources, $usedRes);
    	
    	
    	// Връщаме масива
    	return $diffArr;
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
    
    
    /**
     * Връща заявка за извличане на всички ресурси използвани в тази рецепта
     * 
     * @param mixed $bomId - ид или запис на рецепта
     * @return core_Query - готовата заявка
     */
    public static function getDetailQuery($id)
    {
    	$rec = static::fetchRec($id);
    	
    	// Намираме всички етапи в тази рецепта
    	$dQuery = cat_BomStages::getQuery();
    	$dQuery->where("#bomId = '{$rec->id}'");
    	$dQuery->show('id');
    	
    	// След това намираме всички детайли на етапите на рецептата
    	$query2 = cat_BomStageDetails::getQuery();
    	$query2->in("bomstageId", arr::make(array_keys($dQuery->fetchAll()), TRUE));
    	
    	// Връщаме заявката
    	return $query2;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->state == 'active'){
    		if($rejectedCount = $mvc->rejectNotUsedResources($rec->id)){
    			core_Statuses::newStatus(tr("|Оттеглени са|* {$rejectedCount} |неизползвани ресурси|*"));
    		}
    	} elseif($rec->state == 'rejected'){
    		
    	}
    }
    
    
    /**
     * Оттегля всички ресурси-заготовки за тази рецепта които вече не фигурират в нея
     * 
     * @param int $bomId
     * @return mixed $count/FALSE - Броя на оттеглените ресурси
     */
    private function rejectNotUsedResources($bomId)
    {
    	// Намираме всички изходни ресурси за тази рецепта
    	$exitResources = static::getExitResources($bomId);
    	
    	// Кои са създадените ресурси за рецептата като цяло
    	$resQuery = mp_Resources::getQuery();
    	$resQuery->where("#bomId = {$bomId}");
    	$resQuery->where("#state = 'active'");
    	$resQuery->show('id');
    	 
    	// Ако има създадени ресурси-заготовки, които не фигурират в рецептата към момента, ги оттегляме
    	$notUsedResources = array_diff_key($resQuery->fetchAll(), $exitResources);
    	if(count($notUsedResources)){
    		foreach ($notUsedResources as $resRec){
    			mp_Resources::reject($resRec);
    		}
    		
    		return count($notUsedResources);
    	}
    	
    	return FALSE;
    }
}