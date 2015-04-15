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
    public $loadList = 'plg_RowTools, plg_LastUsedKeys, plg_Created, planning_Wrapper';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'resourceId';
    
    
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
    public $listFields = 'tools=Пулт,resourceId,objectId,conversionRate,createdOn,createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ресурс на обект';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Ресурси->Отношения';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('classId', 'class(interface=planning_ResourceSourceIntf)', 'input=hidden,silent');
    	$this->FLD('objectId', 'int', 'input=hidden,caption=Обект,silent');
    	$this->FLD('resourceId', 'key(mvc=planning_Resources,select=title,allowEmpty,makeLink)', 'caption=Ресурс,mandatory,removeAndRefreshForm=conversionRate,silent');
    	$this->FLD('conversionRate', 'double(smartRound)', 'caption=Конверсия,silent,notNull,value=1,mandatory');
    	
    	// Поставяне на уникални индекси
    	$this->setDbUnique('classId,objectId,resourceId');
    }

    
    /**
     * Екшън създаващ нов ресурс и свързващ го с обекта
     */
    public function act_NewResource()
    {
    	planning_Resources::requireRightFor('add');
    	
    	expect($classId = Request::get('classId', 'int'));
    	expect($objectId = Request::get('objectId', 'int'));
    	
    	$this->requireRightFor('add', (object)array('classId' => $classId, 'objectId' => $objectId));
    	
    	$form = cls::get('core_Form');
    	$form->title = tr("Създаване на ресурс към") . " |*<b>" . cls::get($classId)->getTitleById($objectId) . "</b>";
    	$form->FNC('newResource', 'varchar', 'mandatory,caption=Нов ресурс,input');
    	$form->FNC('classId', 'class(interface=planning_ResourceSourceIntf)', 'input=hidden');
    	$form->FNC('objectId', 'int', 'input=hidden,caption=Обект');
    	
    	$form->setDefault('classId', $classId);
    	$form->setDefault('objectId', $objectId);
    	
    	// По подразбиране името на новия ресурс съвпада с името на източника
    	$sourceInfo = cls::get($form->rec->classId)->getResourceSourceInfo($form->rec->objectId);
    	$form->setDefault('newResource', $sourceInfo->name);
    	
    	$form->input();
    	
    	// Ако формата е събмитната
    	if($form->isSubmitted()){
    		
    		// Трябва ресурса да е уникален
    		if(planning_Resources::fetch(array("#title = '[#1#]'", $form->rec->newResource))){
    			$form->setError("newResource", "Има вече запис със същите данни");
    		} else {
    			
    			// Създава нов запис и го свързва с обекта 
    			$resourceId = planning_Resources::save((object)array('title' => $form->rec->newResource, 'type' => $sourceInfo->type, 'measureId' => $sourceInfo->measureId, 'state' => 'active'));
    			$nRec = (object)array('classId' => $classId, 'objectId' => $objectId, 'resourceId' => $resourceId);
    			
    			$this->save($nRec);
    		}
    		
    		if(!$form->gotErrors()){
    			return followRetUrl(NULL, tr('Успешно е добавен ресурса'));
    		}
    	}
    	
    	$form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png');
        
        return $this->renderWrapping($form->renderHtml());
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
    	
    	$sourceInfo = $Class->getResourceSourceInfo($rec->objectId);
    	
    	// Възможни за избор са всички ресурси от посочения тип, които не са заготовки към технологична рецепта
    	$options = planning_Resources::makeArray4Select('title', "#type = '{$sourceInfo->type}' AND #bomId IS NULL");
    	
    	if(count($options)){
    		$form->setOptions('resourceId', $options);
    	} else {
    		$form->setReadOnly('resourceId');
    		$resourceType = cls::get('planning_Resources')->getFieldType('type')->toVerbal($sourceInfo->type);
    		$form->info = tr("|Няма ресурси от тип|* <b>'{$sourceInfo->type}'</b>");
    	}
    	
    	$form->setDefault('conversionRate', 1);
    	
    	if(isset($rec->resourceId)){
    		$unit = $mvc->getConversionUnit($sourceInfo->measureId, $rec->resourceId);
    		$form->setField('conversionRate', "unit={$unit}");
    	}
    }
    
    
    /**
     *  Помощна ф-я за показване на конверсията от коя мярка към коя се отнася
     * 
     * @param int $measureSourceId - ид на мярката на обекта
     * @param int $resourceId - ид на мярката на ресурса
     * @return string - във формат [object_measure_name] за 1 [resource_measure_name]
     */
    private function getConversionUnit($measureSourceId, $resourceId)
    {
    	$sMeasureShort = cat_UoM::getShortName($measureSourceId);
    	
    	$resourseMeasureId = planning_Resources::fetchField($resourceId, 'measureId');
    	$rMeasureShort = cat_UoM::getShortName($resourseMeasureId);
    	
    	return "|*{$sMeasureShort} |за|* 1 {$rMeasureShort}";
    }
    
    
    /**
     * Подготвя показването на ресурси
     */
    public function prepareResources(&$data)
    {
    	$data->TabCaption = 'Ресурси';
    	$data->rows = array();
    	
    	// Таба излиза на горния ред, само ако е в документ
    	$classId = $data->masterMvc->getClassId();
		if(cls::haveInterface('doc_DocumentIntf', $data->masterMvc)){
			$data->Tab = 'top';
		}
    	
    	$query = $this->getQuery();
    	$query->where("#classId = {$classId} AND #objectId = {$data->masterId}");
    	
    	while($rec = $query->fetch()){
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    		$data->rows[$rec->id]->type = planning_Resources::getVerbal($rec->resourceId, 'type');
    		$data->rows[$rec->id]->ROW_ATTR['class'] = 'state-active';
    	}
    	 
    	if(!Mode::is('printing')) {
    		if(self::haveRightFor('add', (object)array('classId' => $classId, 'objectId' => $data->masterId))){
    			
    			$type = $data->masterMvc->getResourceSourceInfo($data->masterId)->type;
    			if(planning_Resources::fetch("#type = '{$type}'")){
    				$data->addUrl = array($this, 'add', 'classId' => $classId, 'objectId' => $data->masterId, 'ret_url' => TRUE);
    			}
    			
    			$data->addUrlNew = array($this, 'NewResource', 'classId' => $classId, 'objectId' => $data->masterId, 'ret_url' => TRUE);
    		}
    	}
    }
    
    
    /**
     * Рендира показването на ресурси
     */
    public function renderResources(&$data)
    {
    	$tpl = getTplFromFile('planning/tpl/ResourceObjectDetail.shtml');
    	$classId = $data->masterMvc->getClassId();
    
    	$tpl->append(tr('Ресурси'), 'title');
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	$fields = arr::make('tools=Пулт,resourceId=Ресурс,type=Вид,conversionRate=Конверсия,createdOn=Създадено от,createdBy=Създадено на');
    	if(!count($data->rows)){
    		unset($fields['tools']);
    	}
    	
    	$tpl->append($table->get($data->rows, $fields), 'content');
    	
		if(isset($data->addUrlNew)){
    		$tpl->append(ht::createBtn('Нов', $data->addUrlNew, NULL, NULL, 'ef_icon=img/16/star_2.png, title=Създаване на нов ресурс'), 'BTNS');
    	}
    	
    	if(isset($data->addUrl)){
    		$tpl->append(ht::createBtn('Избор', $data->addUrl, NULL, NULL, 'ef_icon=img/16/find.png, title=Свързване със съществуващ ресурс'), 'BTNS');
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
    		if($masterRec->state != 'active' || !$Class->haveRightFor('single', $rec->objectId)){
    			$res = 'no_one';
    		}
    	}
    	 
    	if($action == 'add' && isset($rec)){
    		
    		if(!$Class->canHaveResource($rec->objectId)){
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
    	
    	$row->resourceId = planning_Resources::getHyperlink($rec->resourceId, TRUE);
    	
    	$sourceInfo = $Source->getResourceSourceInfo($rec->objectId);
    	$row->conversionRate .= " " . tr($mvc->getConversionUnit($sourceInfo->measureId, $rec->resourceId));
    	$row->conversionRate = "<span style='float:right'>{$row->conversionRate}</span>";
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
    public static function getResource($class, $objectId)
    {
    	$Class = cls::get($class);
    	
    	// Проверяваме имали такъв запис
    	if($rec = self::fetch("#classId = {$Class->getClassId()} AND #objectId = {$objectId}")){
    		
    		return $rec;
    	}
    	
    	return FALSE;
    }
}