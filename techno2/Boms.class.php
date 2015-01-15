<?php


/**
 * Мениджър за технологични карти (Рецепти)
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_Boms extends core_Master
{
   
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_Maps';
	
	
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
    var $loadList = 'plg_RowTools, techno2_Wrapper, plg_Sorting, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, doc_ActivatePlg';
    
    
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
    var $details = 'Stages=techno2_BomStages';
    
    
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
    var $canRead = 'techno,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'techno,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'techno,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'techno,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,techno';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,techno';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'techno2/tpl/SingleLayoutMap.shtml';
    
    
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
    			if(!($origin->getInstance() instanceof techno2_SpecificationDoc)){
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
    		expect($origin->getInstance() instanceof techno2_SpecificationDoc);
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
    	$dQuery = techno2_BomStages::getQuery();
    	$dQuery->where("#bomId = {$rec->id}");
    	
    	// За всеки етап
    	while($dRec = $dQuery->fetch()){
    		
    		// Проверяваме имали вързани ресурси към него
    		$sQuery = techno2_BomStageDetails::getQuery();
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
     * Връща ресурсите които могат да се добавят към дадена рецепта, това са тези, които вече не са добавени
     *
     * @param int $bomId - ид
     * @param boolean $notIntKeys - дали ключовете да са естествени числа
     * @return array - свободните за добавяне ресурси
     */
    public static function makeResourceOptions($bomId, $notIntKeys = FALSE)
    {
    	$usedRes = array();
    	 
    	// Намираме всички ресурси, които са използвани в рецептата
    	$query = techno2_BomStages::getQuery();
    	$query->where("#bomId = {$bomId}");
    	while($qRec = $query->fetch()){
    		$dQuery = techno2_BomStageDetails::getQuery();
    		$dQuery->where("#bomstageId = {$qRec->id}");
    		while($dRec = $dQuery->fetch()){
    			$usedRes[$dRec->resourceId] = mp_Resources::getTitleById($dRec->resourceId, FALSE);
    		}
    	}
    	 
    	// Намираме всички стандартни ресурси
    	$allResources = cls::get('mp_Resources')->makeArray4Select('title', array("#bomId IS NULL && state NOT IN ('rejected')"));
    	
    	// Намираме ресурсите, които са заготовки за тази рецепта
    	$bomResources = cls::get('mp_Resources')->makeArray4Select('title', array("#bomId IS NOT NULL && state NOT IN ('rejected') && #bomId = {$bomId}"));
    	
    	if($notIntKeys === TRUE){
    		$allResources = arr::make($allResources, TRUE);
    		$bomResources = arr::make($bomResources, TRUE);
    	}
    	
    	// Добавяме ги към списъка, ако има
    	if(count($bomResources)){
    		$allResources[0] = (object)array(
    				'title' => 'Заготовки',
    				'group' => TRUE,
    		);
    		$allResources = $allResources + $bomResources;
    	}
    	
    	// Намираме тези ресурси, които не са използвани в рецептата
    	$diffArr = array_diff_key($allResources, $usedRes);
    	
    	// Връщаме масива
    	return $diffArr;
    }
    
    
    /**
     * Връща позволените опции за избор на етапи вече участващи към рецептата
     * 
     * @param int $id - ид на запис
     * @param string $except - коя опция да изключим
     * @return array $stages - Масив с достъпните опции
     */
    public static function makeStagesOptions($id, $except = NULL)
    {
    	// Добавяме за налични етапи само тези избрани в рецептата (без текущия)
    	$stages = array();
    	$mQuery = techno2_BomStages::getQuery();
    	$mQuery->where("#bomId = {$id}");
    	while($mRec = $mQuery->fetch()){
    		$stages[$mRec->stage] = mp_Stages::fetchField($mRec->stage, 'name');
    	}
    	
    	if(isset($except)){
    		
    		// Премахваме от опциите, текущия етап
    		unset($stages[$except]);
    	}
    	
    	return $stages;
    }
}