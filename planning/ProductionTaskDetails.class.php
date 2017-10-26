<?php



/**
 * Клас 'planning_ProductionTaskDetails'
 *
 * Детайли на драйверите за за задачи за производство
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_ProductionTaskDetails extends core_Detail
{
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'planning_drivers_ProductionTaskDetails';
	
	
	/**
     * Заглавие
     */
    public $title = 'Детайли на производствените операции';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Прогрес';
    
    
    /**
     * Интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created, plg_LastUsedKeys, plg_Sorting, planning_Wrapper';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'employees,fixedAsset';
    
    
    /**
     * Кой има право да оттегля?
     */
    public $canReject = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да възстановява?
     */
    public $canRestore = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да редактира?
     */
    public $canEdit = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'type=Операция,serial,productId,quantity,shortUoM=Мярка,weight=Тегло (кг),employees,fixedAsset,modified=Модифицирано';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'serial,weight,employees,fixedAsset,scrappedQuantity';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD("taskId", 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Операция');
    	$this->FLD("productId", 'key(mvc=cat_Products,select=name)', 'silent,mandatory,caption=Артикул,removeAndRefreshForm=packagingId|serial,tdClass=productCell leftCol wrap');
    	$this->FLD('type', 'enum(input=Влагане,production=Произв.,waste=Отпадък)', 'input=hidden,silent,tdClass=small-field nowrap');
    	$this->FLD('serial', 'varchar(32)', 'caption=Сер. №,smartCenter,focus');
    	$this->FLD('quantity', 'double(Min=0)', 'caption=Количество');
    	$this->FLD('scrappedQuantity', 'double(Min=0)', 'caption=Брак,input=none');
    	$this->FLD('weight', 'double', 'caption=Тегло,smartCenter,unit=кг');
    	$this->FLD('employees', 'keylist(mvc=crm_Persons,select=id)', 'caption=Работници,smartCenter,tdClass=nowrap');
    	$this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=fullName)', 'caption=Обордуване,input=none,smartCenter');
    	$this->FLD('notes', 'richtext(rows=2,bucket=Notes)', 'caption=Забележки');
    	$this->FLD('state', 'enum(active=Активирано,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull');
    	$this->FNC('packagingId', 'int', 'smartCenter,tdClass=small-field nowrap');
    	
    	$this->setDbIndex('type');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$data->form->rec;
    	$masterRec = planning_Tasks::fetch($rec->taskId);
    	
    	// Добавяме последните данни за дефолтни
    	$query = $mvc->getQuery();
    	$query->where("#taskId = {$rec->taskId}");
    	$query->orderBy('id', 'DESC');
    	 
    	// Задаваме последно въведените данни
    	if($lastRec = $query->fetch()){
    		$form->setDefault('employees', $lastRec->employees);
    		$form->setDefault('fixedAsset', $lastRec->fixedAsset);
    	}
    	
    	// Ако в мастъра са посочени машини, задават се като опции
    	if(isset($masterRec->fixedAssets)){
    		$keylist = $masterRec->fixedAssets;
    		$arr = keylist::toArray($keylist);
    			
    		foreach ($arr as $key => &$value){
    			$value = planning_AssetResources::getVerbal($key, 'code');
    		}
    		$form->setOptions('fixedAsset', array('' => '') + $arr);
    		$form->setField('fixedAsset', 'input');
    	}
    	
    	$productOptions = planning_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
    	$form->setOptions('productId', array('' => '') + $productOptions);
    	
    	if($rec->type == 'production'){
    		$form->setDefault('productId', $masterRec->productId);
    		
    		if(isset($rec->id)){
    			$form->setReadOnly('productId');
    			$form->setReadOnly('serial');
    			$form->setReadOnly('quantity');
    			$form->setField('scrappedQuantity', 'input');
    			$form->setFieldTypeParams('scrappedQuantity', array('max' => $rec->quantity, 'min' => 0));
    			$form->setField('employees', 'input=none');
    			$form->setField('fixedAsset', 'input=none');
    			$form->setField('notes', 'input=none');
    		}
    	} 
    	
    	if(count($productOptions) == 1 && $form->cmd != 'refresh'){
    		$form->setDefault('productId', key($productOptions));
    		$form->setReadOnly('productId');
    	}
    	
    	if(isset($rec->productId)){
    		$measureId = cat_Products::fetchField($rec->productId, 'measureId');
    		$packagingId = $measureId;
    		
    		if($foundRec = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type)){
    			$packagingId = $foundRec->packagingId;
    			$unit = cat_UoM::getShortName($foundRec->packagingId);
    			
    			if(!empty($foundRec->plannedQuantity) || !empty($foundRec->totalQuantity)){
    				$planned = tr("Планирано|*: <b>") . cls::get('planning_ProductionTaskProducts')->getFieldType('plannedQuantity')->toVerbal($foundRec->plannedQuantity) . "</b>";
    				$real = tr("Изпълнено|*: <b>") . cls::get('planning_ProductionTaskProducts')->getFieldType('totalQuantity')->toVerbal($foundRec->totalQuantity) . "</b>";
    				$form->info = "{$planned}<br>{$real}";
    			}
    			
    			$form->setField('quantity', "unit={$unit}");
    		}
    		
    		$shortMeasure = cat_UoM::getShortName($measureId);
    		if($measureId != $packagingId){
    			$packName = $unit = cat_UoM::getShortName($packagingId);
    			$unit = $shortMeasure . " " . tr('в') . " " . $packName;
    			$form->setField('quantity', "unit={$unit}");
    		} else {
    			$form->setField('quantity', "unit={$shortMeasure}");
    		}
    	}
    	
    	// Връща служителите с код
    	$employees = crm_ext_Employees::getEmployeesWithCode();
    	if(count($employees)){
    		$form->setSuggestions('employees', $employees);
    	} else {
    		$form->setField('employees', 'input=none');
    	}
    	
    	if($masterRec->showadditionalUom != 'yes'){
    		$form->setField('weight', 'input=none');
    	}
    }
    

    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	 
    	if($form->isSubmitted()){
    		if(empty($rec->serial) && $rec->type == 'production'){
    			$rec->serial = planning_TaskSerials::forceAutoNumber($rec);
    		}
    		
    		// Ако има въведен сериен номер, проверяваме дали е валиден
    		if(!empty($rec->serial)){
    			$type = ($rec->type == 'production') ? 'production' : 'input';
    			if($error = planning_TaskSerials::isSerialinValid($rec->serial, $rec->productId, $rec->taskId, $type, $rec->id)){
    				$form->setError('serial', $error);
    			}
    		}
    		
    		if($rec->type == 'production'){
    			if(self::fetchField("#taskId = {$rec->taskId} AND #serial = '{$rec->serial}' AND #id != '{$rec->id}'")){
    				$form->setError('serial', 'Сер. № при произвеждане трябва да е уникален');
    			}
    		}
    		
    		if(!$form->gotErrors()){
    			if(!empty($rec->serial) && empty($rec->quantity)){
    				$rec->quantity = planning_TaskSerials::fetchField(array("#serial = '[#1#]'", $rec->serial), 'quantityInPack');
    			}
    			 
    			if(empty($rec->quantity)){
    				$form->setError('quantity', 'Трябва да въведете количество');
    			}
    		}
    		
    		$rec->serial = (empty($rec->serial)) ? NULL : $rec->serial;
    	}
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(isset($rec->fixedAsset) && !Mode::isReadOnly()){
    		$row->fixedAsset = ht::createLink($row->fixedAsset, planning_AssetResources::getSingleUrlArray($rec->fixedAsset));
    	}
    	 
    	$row->modified = "<div class='nowrap'>" . $mvc->getFieldType('modifiedOn')->toVerbal($rec->modifiedOn);
    	$row->modified .= " " . tr('от||by') . " " . crm_Profiles::createLink($rec->modifiedBy) . "</div>";
    	 
    	if(isset($rec->serial)){
    		$row->serial = "<b>{$row->serial}</b>";
    	}
    	 
    	$class = ($rec->state == 'rejected') ? 'state-rejected' : (($rec->type == 'input') ? 'row-added' : (($rec->type == 'production') ? 'state-active' : 'row-removed'));
    	$row->ROW_ATTR['class'] = $class;
    	if($rec->state == 'rejected'){
    		$row->ROW_ATTR['title'] = tr('Оттеглено от') . " " . core_Users::getVerbal($rec->modifiedBy, 'nick');
    	}
    	
    	$row->productId = cat_Products::getShortHyperlink($rec->productId);
    	$row->productId = "<div class='nowrap'>" . $row->productId . "</div>";
    	
    	$measureId = cat_Products::fetchField($rec->productId, 'measureId');
    	$shortUom = cat_UoM::getShortName($measureId);
    	$packagingId = $measureId;
    	
    	$foundRec = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type);
    	
    	if(!empty($foundRec)){
    		$packagingId = $foundRec->packagingId;
    	}
    	
    	if($measureId != $packagingId){
    		$packagingId = cat_UoM::getShortName($packagingId);
    		$row->type .= " " . tr($packagingId);
    	} elseif($rec->type == 'production'){
    		$row->type = tr('Произвеждане');
    	}
    	
    	if(!empty($rec->scrappedQuantity)){
    		$rec->quantity = $rec->quantity - $rec->scrappedQuantity;
    		$row->scrappedQuantity = $mvc->getFieldType('scrappedQuantity')->toVerbal($rec->scrappedQuantity);
    	}
    	
    	$row->shortUoM = tr($shortUom);
    	 
    	if(!empty($rec->notes)){
    		$notes = $mvc->getFieldType('notes')->toVerbal($rec->notes);
    		$row->productId .= "<small>{$notes}</small>";
    	}
    		
    	if(!empty($rec->serial)){
    		$row->serial = planning_TaskSerials::getLink($rec->taskId, $rec->serial);
    	}
    	
    	if(isset($rec->employees)){
    		$row->employees = self::getVerbalEmployees($rec->employees);
    	}
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
    	$rows = &$data->rows;
    	if(!count($rows)) return;
    
    	foreach ($rows as $id => $row){
    		$rec = $data->recs[$id];
    			
    		if(!empty($row->shortUoM)){
    			$row->quantity = "<b>{$row->quantity}</b>";
    			
    			if(!empty($row->scrappedQuantity)){
    				$hint = "Брак|* {$row->scrappedQuantity} {$row->shortUoM}";
    				$row->quantity = ht::createHint($row->quantity, $hint, 'warning', FALSE, 'width=14px;height=14px');
    			}
    		}
    	}
    }
    
    
    /**
     * Показва вербалното име на служителите
     * 
     * @param text $employees - кейлист от служители
     * @return string $verbalEmployees
     */
    public static function getVerbalEmployees($employees)
    {
    	$verbalEmployees = array();
    	$employees = keylist::toArray($employees);
    	foreach ($employees as $eId){
    		$el = crm_ext_Employees::getCodeLink($eId);
    		$verbalEmployees[$eId] = $el;
    	}
    	
    	return implode(', ', $verbalEmployees);
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
    	planning_ProductionTaskProducts::updateTotalQuantity($rec->taskId, $rec->productId, $rec->type);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'production'))){
    			$data->toolbar->addBtn('Произвеждане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'production', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/package.png,title=Добавяне на произведен артикул');
    		}
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'input'))){
    			$data->toolbar->addBtn('Влагане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'input', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/wooden-box.png,title=Добавяне на вложен артикул');
    		}
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'waste'))){
    			$data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'waste', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/recycle.png,title=Добавяне на отпаден артикул');
    		}
    	}
    	
    	// Махане на кошчето
    	$data->toolbar->removeBtn('binBtn');
    }


    /**
     * Подготвя детайла
     */
    public function prepareDetail_($data)
    {
    	$data->TabCaption = 'Прогрес';
    	$data->Tab = 'top';
    
    	parent::prepareDetail_($data);
    }


    /**
     * Преди извличане на записите от БД
     */
    protected static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	// Искаме да показваме и оттеглените детайли
    	$data->query->orWhere("#state = 'rejected'");
    	$data->query->orderBy('createdOn', 'DESC');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'reject' || $action == 'restore' || $action == 'edit' || $action == 'delete') && isset($rec->taskId)){
    		$state = $mvc->Master->fetchField($rec->taskId, 'state');
    		
    		if($state != 'active' && $state != 'waiting' && $state != 'wakeup'){
    			$requiredRoles = 'no_one';
    		} 
    	}
    	
    	// Трябва да има поне един артикул възможен за добавяне
    	if($action == 'add' && isset($rec->type) && $rec->type != 'product' && $rec->type != 'start'){
    		if($requiredRoles != 'no_one'){
    			$pOptions = planning_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
    			if(!count($pOptions)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'edit' && isset($rec)){
    		if($rec->type != 'production' || $rec->state == 'rejected'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }


    /**
     * Преди подготовка на заглавието на формата
     */
    protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
    {
    	$rec = &$data->form->rec;
    	$data->singleTitle = ($rec->type == 'input') ? 'влагане' : (($rec->type == 'waste') ? 'отпадък' : 'произвеждане');
    }
    
    
    /**
	 * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал 
	 *
	 * @param date $timeline  - Времето, след което да се вземат всички модифицирани/създадени записи
	 * @return array $result  - масив с обекти
	 *
	 * 			o date        - дата на стайноста
	 * 		    o personId    - ид на лицето
	 *          o docId       - ид на документа
	 *          o docClass    - клас ид на документа
	 *          o indicatorId - ид на индикатора
	 *          o value       - стойноста на инфикатора
	 *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
	 */
    public static function getIndicatorValues($timeline)
    {
    	$result = array();
    	
    	return $result;
    	
    	
    	
    	
    	
    	
    	$query = self::getQuery();
        $query->where("#modifiedOn >= '{$timeline}'");
        
        $iRec = hr_IndicatorNames::force('Време', __CLASS__, 1);
        $classId = planning_Tasks::getClassId();
        $indicatorId = $iRec->id;
        
        
        $queryProduct = planning_ProductionTaskProducts::getQuery();
        $queryMasterm = planning_Tasks::getQuery();

        while ($rec = $query->fetch()) {
           switch($rec->type){
               case 'input':
                    $time = planning_ProductionTaskProducts::fetchField($rec->taskProductId, 'indTime');	
                    break;
               case 'waste':
                    $time = -planning_ProductionTaskProducts::fetchField($rec->taskProductId, 'indTime');
                    break;
               case 'product':
               		$taskInfo = planning_Tasks::fetch($rec->taskId);
               		$time = (!empty($taskInfo->startTime)) ? ($taskInfo->startTime / $taskInfo->quantityInPack) : NULL;
                    break;
            }
            
            if(empty($time)) continue;
            
            if($rec->employees) {
                $persons = keylist::toArray($rec->employees);
               
                $timePerson = ($rec->quantity * $time) / count($persons) ;
                $date = dt::verbal2mysql($rec->createdOn, FALSE);
                foreach ($persons as $personId) {
                	
                	$key = "{$personId}|{$classId}|{$rec->taskId}|{$date}|{$indicatorId}";
                	if(!array_key_exists($key, $result)){
                		$result[$key] = (object)array('date'        => $date,
												      'personId'    => $personId,
									                  'docId'       => $rec->taskId,
									                  'docClass'    => $classId,
									                  'indicatorId' => $indicatorId,
									                  'value'       => 0,
									                  'isRejected'  => ($rec->state == 'rejected'));
                	}
                	
                	$result[$key]->value += $timePerson;
                }
            }
        }
        
        return $result;
    }

    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $result
     */
    public static function getIndicatorNames()
    {
    	$result = array();
    	$rec = hr_IndicatorNames::force('Време', __CLASS__, 1);
    	$result[$rec->id] = $rec->name;
    	 
    	return $result;
    }
    
    
    /**
     * Връща количеството произведено по задачи по дадено задание
     *
     * @param int $jobId
     * @param product|input|waste|start $type
     * @return double $quantity
     */
    public static function getQuantityForJob($jobId, $type)
    {return;
    	expect(in_array($type, array('product', 'input', 'waste', 'start')));
    	expect($jobRec = planning_Jobs::fetch($jobId));
    	
    	$query = self::getQuery();
    	$query->EXT('taskState', 'planning_Tasks', 'externalName=state,externalKey=taskId');
    	$query->EXT('productId', 'planning_Tasks', 'externalName=productId,externalKey=taskId');
    	$query->EXT('originId', 'planning_Tasks', 'externalName=originId,externalKey=taskId');
    	$query->where("#taskState != 'rejected'");
    	$query->where("#state != 'rejected'");
    	$query->where("#type = '{$type}'");
    	$query->where("#originId = {$jobRec->containerId}");
    	$query->where("#productId = {$jobRec->productId}");
    	
    	$quantity = 0;
    	while($rec = $query->fetch()){
    		$quantity += $rec->quantity;
    	}
    
    	return $quantity;
    }
}