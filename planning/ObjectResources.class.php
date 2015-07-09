<?php



/**
 * Мениджър на ресурсите свързани с обекти
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ObjectResources extends core_Manager
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_ObjectResources';
	
	
    /**
     * Заглавие
     */
    public $title = 'Ресурси на обекти';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, planning_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,planning';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,planning';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,debug';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,likeProductId=Влагане като,measureId=Мярка,conversionRate=Конверсия,selfValue=Себестойност';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Информация за влагане';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('classId', 'class(interface=cat_ProductAccRegIntf)', 'input=hidden,silent');
    	$this->FLD('objectId', 'int', 'input=hidden,caption=Обект,silent');
    	$this->FLD('likeProductId', 'key(mvc=cat_Products,select=name)', 'caption=Влагане като,removeAndRefreshForm=conversionRate|measureId,silent');
    	
    	$this->FLD('resourceId', 'key(mvc=planning_Resources,select=title,allowEmpty,makeLink)', 'caption=Ресурс,input=none,removeAndRefreshForm=conversionRate,silent');
    	$this->FLD('measureId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,input=hidden,silent,removeAndRefreshForm=conversionRate');
    	$this->FLD('conversionRate', 'double(smartRound)', 'caption=Конверсия,silent,notNull,value=1,mandatory');
    	$this->FLD('selfValue', 'double(decimals=2)', 'caption=Себестойност');
    	
    	// Поставяне на уникални индекси
    	$this->setDbUnique('classId,objectId');
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
    	
    	$Class = cls::get($rec->classId);
    	$pInfo = $Class->getProductInfo($rec->objectId);
    	
    	$products = array('' => '') + $Class::getByproperty('canConvert');
    	$form->setOptions('likeProductId', $products);
    	
    	$baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
    	$form->setField('selfValue', "unit={$baseCurrencyCode}");
    	
    	if(!isset($rec->likeProductId)){
    		$form->setField('measureId', 'input');
    		$measureId = $pInfo->productRec->measureId;
    	} else {
    		if($lInfo = cat_Products::getProductInfo($rec->likeProductId)){
    			$measureId = $lInfo->productRec->measureId;
    		}
    	}
    	
    	$form->setDefault('measureId', $measureId);
    	
    	$unit = $mvc->getConversionUnit($rec->objectId, $measureId);
    	$form->setField('conversionRate', "unit={$unit}");
    	
    	$title = ($rec->id) ? 'Редактиране на информацията за влагане на' : 'Добавяне на информация за влагане на';
    	$form->title = $title . "|* <b>". $Class->getTitleByid($rec->objectId) . "</b>";
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		//@TODO да се добавят проверки
    	}
    }
    
    
    /**
     *  Помощна ф-я за показване на конверсията от коя мярка към коя се отнася
     * 
     * @param int $objectId - ид на обекта
     * @param int $measureId - мярката за влагане
     * @return string - във формат [object_measure_name] за 1 [resource_measure_name]
     */
    private function getConversionUnit($objectId, $measureId)
    {
    	if($pInfo = cat_Products::getProductInfo($objectId)){
    		$sMeasureShort = cat_UoM::getShortName($pInfo->productRec->measureId);
    		if(!$measureId){
    			$measureId = $pInfo->productRec->measureId;
    		}
    		$rMeasureShort = cat_UoM::getShortName($measureId);
    		
    		return "|*{$sMeasureShort} |за|* 1 {$rMeasureShort}";
    	}
    	
    	return '';
    }
    
    
    /**
     * Подготвя показването на ресурси
     */
    public function prepareResources(&$data)
    {
    	$data->rows = array();
    	$classId = $data->masterMvc->getClassId();
    	$query = $this->getQuery();
    	$query->where("#classId = {$classId} AND #objectId = {$data->masterId}");
    	while($rec = $query->fetch()){
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	
    	$pInfo = $data->masterMvc->getProductInfo($data->masterId);
    	if(!(count($data->rows) || isset($pInfo->meta['canConvert']))){
    		return NULL;
    	}
    	
    	if(!isset($pInfo->meta['canConvert'])){
    		$data->notConvertableAnymore = TRUE;
    	}
    	
    	$data->TabCaption = 'Влагане';
    	$data->Tab = 'top';
    	
    	if(!Mode::is('printing')) {
    		if(self::haveRightFor('add', (object)array('classId' => $classId, 'objectId' => $data->masterId))){
    			$data->addUrl = array($this, 'add', 'classId' => $classId, 'objectId' => $data->masterId, 'ret_url' => TRUE);
    		}
    	}
    }
    
    
    /**
     * Рендира показването на ресурси
     */
    public function renderResources(&$data)
    {
    	$tpl = getTplFromFile('planning/tpl/ResourceObjectDetail.shtml');
    	
    
    	if($data->notConvertableAnymore === TRUE){
    		$title = tr('Артикула вече не е вложим');
    		$title = "<span class='red'>{$title}</span>";
    		$tpl->append($title, 'title');
    	} else {
    		$tpl->append(tr('Влагане'), 'title');
    	}
    	
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	$table->setFieldsToHideIfEmptyColumn('selfValue,likeProductId');
    	if(!count($data->rows)){
    		unset($fields['tools']);
    	}
    	
    	$tpl->append($table->get($data->rows, $this->listFields), 'content');
    	
    	if(isset($data->addUrl)){
    		$addLink = ht::createLink('', $data->addUrl, NULL, 'ef_icon=img/16/add.png, title=Промяна на информацията за влагане'); 
    		$tpl->append($addLink, 'BTNS');
    	}
    	
    	return $tpl;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec)){
    		
    		$Class = cls::get($rec->classId);
    		$masterRec = $Class->fetchRec($rec->objectId);
    		
    		// Не може да добавяме запис ако не може към обекта, ако той е оттеглен или ако нямаме достъп до сингъла му
    		if($masterRec->state != 'active' || !$Class->haveRightFor('single', $rec->objectId)){//bp();
    			$res = 'no_one';
    		} else {
    			if($pInfo = cls::get($rec->classId)->getProductInfo($rec->objectId)){
    				if(!isset($pInfo->meta['canConvert'])){
    					$res = 'no_one';
    				}
    			}
    		}
    	}
    	 
    	// За да се добави ресурс към обект, трябва самия обект да може да има ресурси
    	if($action == 'add' && isset($rec)){
    		if($mvc->fetch("#classId = {$rec->classId} AND #objectId = {$rec->objectId}")){
    			$res = 'no_one';
    		}
    	}
    	
    	if($action == 'delete' && isset($rec)){
    		
    		// Ако обекта е използван вече в протокол за влагане, да не може да се изтрива докато протокола е активен
    		$consumptionQuery = planning_ConsumptionNoteDetails::getQuery();
    		$consumptionQuery->EXT('state', 'planning_ConsumptionNotes', 'externalName=state,externalKey=noteId');
    		if($consumptionQuery->fetch("#classId = {$rec->classId} AND #productId = {$rec->objectId} AND #state = 'active'")){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$Source = cls::get($rec->classId);
    	$row->objectId = $Source->getHyperlink($rec->objectId, TRUE);
    	if($Source->fetchField($rec->objectId, 'state') == 'rejected'){
    		$row->objectId = "<span class='state-rejected-link'>{$row->objectId}</span>";
    	}
    	
    	$row->objectId = "<span style='float:left'>{$row->objectId}</span>";
    	$row->conversionRate .= " " . tr($mvc->getConversionUnit($rec->objectId, $rec->measureId));
    	$row->conversionRate = "<span style='float:right'>{$row->conversionRate}</span>";
    	
    	if($rec->selfValue){
    		$baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
    		$row->selfValue = "{$row->selfValue} <span class='cCode'>{$baseCurrencyCode}</span>";
    	}
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Връща ресурса на обекта
     * 
     * @param mixed $class - клас
     * @param int $objectId - ид
     * @return mixed - записа на ресурса или FALSE ако няма
     */
    public static function getResource($objectId, $class = 'cat_Products')
    {
    	$Class = cls::get($class);
    	$fields = 'likeProductId,measureId,conversionRate,selfValue';
    	
    	// Проверяваме имали такъв запис
    	if($rec = self::fetch("#classId = {$Class->getClassId()} AND #objectId = {$objectId}", $fields)){
    		unset($rec->id);
    		return $rec;
    	}
    	
    	$pInfo = $Class->getProductInfo($objectId);
    	if($pInfo){
    		$measureId = $pInfo->productRec->measureId;
    	}
    	
    	$rec = (object)array('likeProductId' => NULL, 
    						 'conversionRate' => 1, 
    			             'selfValue' => NULL, 
    			             'measureId' => $measureId);
    	
    	return $rec;
    }
    
    
    /**
     * Връща записите според указаните параметри
     * 
     * @param int $resourceId  - ид на ресурс
     * @param int|NULL $classId - ид на клас на обекта
     * @param labor|material|equipment|NULL $type - тип на ресурса
     * @return array - намерените записи
     */
    public static function fetchRecsByClassAndType($resourceId, $classId = NULL, $type = NULL)
    {
    	$query = self::getQuery();
    	$query->EXT('type', 'planning_Resources', 'externalName=type,externalKey=resourceId');
    	$query->where("#resourceId = {$resourceId}");
    	
    	if($classId){
    		$query->where("#classId = {$classId}");
    	}
    	
    	if($type){
    		$query->where("#type = '{$type}'");
    	}
    	
    	return $query->fetchAll();
    }
}