<?php



/**
 * Клас 'planning_drivers_ProductionTaskDetails'
 *
 * Детайли на драйверите за за задачи за производство
 *
 * @category  bgerp
 * @package   tasks
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_drivers_ProductionTaskDetails extends tasks_TaskDetails
{
    
	
	/**
     * Заглавие
     */
    public $title = 'Детайли на задачите за производство';


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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'type=Операция,serial,taskProductId,quantity,scrappedQuantity,packagingId=Мярка,weight=Тегло (кг),employees,fixedAsset,modified=Модифицирано';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'serial,weight,employees,fixedAsset, scrappedQuantity';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD("taskId", 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Задача');
    	$this->FLD('taskProductId', 'key(mvc=planning_drivers_ProductionTaskProducts,select=productId,allowEmpty)', 'caption=Артикул,mandatory,silent,refreshForm,tdClass=productCell leftCol wrap');
    	$this->FLD('type', 'enum(input=Влагане,product=Произвеждане,waste=Отпадък,start=Пускане)', 'input=hidden,silent,smartCenter');
    	$this->FLD('serial', 'varchar(32)', 'caption=С. номер,smartCenter,focus');
    	$this->FLD('quantity', 'double(Min=0)', 'caption=Количество,mandatory,smartCenter');
    	$this->FLD('scrappedQuantity', 'double(Min=0)', 'caption=Брак,input=none');
    	$this->FLD('weight', 'double', 'caption=Тегло,smartCenter,unit=кг');
    	$this->FLD('employees', 'keylist(mvc=crm_Persons,select=id)', 'caption=Работници,smartCenter,tdClass=nowrap');
    	$this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=code)', 'caption=Обордуване,input=none,smartCenter');
    	$this->FLD('notes', 'richtext(rows=2)', 'caption=Забележки');
    	$this->FLD('state', 'enum(active=Активирано,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull');
    	$this->FNC('packagingId', 'int', 'smartCenter,tdClass=small-field nowrap');
    	$this->FLD('actionRecId', 'int', 'input=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$data->form->rec;
    	$taskInfo = planning_Tasks::getTaskInfo($rec->taskId);
    	
    	// Добавяме последните данни за дефолтни
    	$query = $mvc->getQuery();
    	$query->where("#taskId = {$rec->taskId}");
    	$query->orderBy('id', 'DESC');
    	 
    	// Задаваме последно въведените данни
    	if($lastRec = $query->fetch()){
    		$form->setDefault('employees', $lastRec->employees);
    		$form->setDefault('fixedAsset', $lastRec->fixedAsset);
    	}
    	
    	// Ако в мастъра са посочени машини, задаваме ги като опции
    	if(isset($taskInfo->fixedAssets)){
    		$keylist = $taskInfo->fixedAssets;
    		$arr = keylist::toArray($keylist);
    			
    		foreach ($arr as $key => &$value){
    			$value = planning_AssetResources::getVerbal($key, 'code');
    		}
    		$form->setOptions('fixedAsset', array('' => '') + $arr);
    		$form->setField('fixedAsset', 'input');
    	}
    	
    	if($rec->type != 'product' && $rec->type != 'start'){
    		$productOptions = planning_drivers_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
    		$form->setOptions('taskProductId', $productOptions);
    		if(count($productOptions) == 1 && $form->cmd != 'refresh'){
    			$form->setDefault('taskProductId', key($productOptions));
    			$form->setReadOnly('taskProductId');
    		}
    	} else {
    		$form->FNC('productId', 'int', 'caption=Артикул,input,before=serial');
    		$form->setOptions('productId', array($taskInfo->productId = cat_Products::getTitleById($taskInfo->productId, FALSE)));
    		$form->setField('taskProductId', 'input=none');
    		
    		if($rec->type != 'start'){
    			$unit = cat_UoM::getShortName($taskInfo->packagingId);
    			$form->setField('quantity', "unit={$unit}");
    		} else {
    			$form->setField('quantity', "input=none");
    		}
    		
    		if(isset($rec->id)){
    			$form->setReadOnly('serial');
    			$form->setReadOnly('quantity');
    			$form->setField('scrappedQuantity', 'input');
    			$form->setField('scrappedQuantity', "unit={$unit}");
    			$form->setFieldTypeParams('scrappedQuantity', array('max' => $rec->quantity, 'min' => 0));
    			$form->setField('employees', 'input=none');
    			$form->setField('fixedAsset', 'input=none');
    			$form->setField('notes', 'input=none');
    		}
    		
    		if($rec->type == 'start'){
    			$form->setField('weight', 'input=none');
    			$form->setField('notes', 'input=none');
    			$form->setField('serial', 'input=none');
    		}
    	}
    	
    	// Добавяме мярката
    	if(isset($rec->taskProductId)){
    		$pRec = planning_drivers_ProductionTaskProducts::fetch($rec->taskProductId);
    		$unit = $pRec->packagingId;
    		$unit = cat_UoM::getShortName($unit);
    		
    		
    		$planned = tr("Планирано|*: <b>") . planning_drivers_ProductionTaskProducts::getVerbal($pRec, 'plannedQuantity') . "</b>";
    		$real = tr("Изпълнено|*: <b>") . planning_drivers_ProductionTaskProducts::getVerbal($pRec, 'realQuantity') . "</b>";
    		$form->info = "{$planned}<br>$real";
    		
    		
    		$form->setField('quantity', "unit={$unit}");
    	}
    	
    	// Връща слижителите с код
    	$employees = crm_ext_Employees::getEmployeesWithCode();
    	if(count($employees)){
    		$form->setSuggestions('employees', $employees);
    	} else {
    		$form->setField('employees', 'input=none');
    	}
    	
    	if($taskInfo->showadditionalUom != 'yes'){
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
    		$productId = ($rec->taskProductId) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'productId') : planning_Tasks::getTaskInfo($rec->taskId)->productId;
    		
    		// Ако няма код и операцията е 'произвеждане' задаваме дефолтния код
    		if($rec->type == 'product'){
    			if(empty($rec->serial)){
    				$rec->serial = planning_TaskSerials::forceAutoNumber($rec->taskId, $productId);
    			}
    		}
    		
    		if(empty($rec->serial)){
    			$rec->serial = NULL;
    		} else {
    			
    			// Ако има въведен сериен номер, проверяваме дали е валиден
    			$type = ($rec->type == 'product') ? 'product' : 'input';
    			if($error = planning_TaskSerials::isSerialinValid($rec->serial, $productId, $rec->taskId, $type)){
    				$form->setError('serial', $error);
    			}
    		}
    		
    		if($rec->type == 'start'){
    			$rec->quantity = 1;
    		}
    	}
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(isset($rec->fixedAsset)){
    		if(!Mode::isReadOnly()){
    			$singleUrl = planning_AssetResources::getSingleUrlArray($rec->fixedAsset);
    			$row->fixedAsset = ht::createLink($row->fixedAsset, $singleUrl);
    		}
    	}
    	 
    	$row->modified = "<div class='nowrap'>" . $mvc->getFieldType('modifiedOn')->toVerbal($rec->modifiedOn);
    	$row->modified .= " " . tr('от||by') . " " . crm_Profiles::createLink($rec->modifiedBy) . "</div>";
    	 
    	if(isset($rec->serial)){
    		$row->serial = "<b>{$row->serial}</b>";
    	}
    	 
    	$class = ($rec->state == 'rejected') ? 'state-rejected' : (($rec->type == 'input') ? 'row-added' : (($rec->type == 'product') ? 'state-active' : (($rec->type == 'start') ? 'state-stopped' : 'row-removed')));
    	$row->ROW_ATTR['class'] = $class;
    	if($rec->state == 'rejected'){
    		$row->ROW_ATTR['title'] = tr('Оттеглено от') . " " . core_Users::getVerbal($rec->modifiedBy, 'nick');
    	}
    	
    	$productId = ($rec->taskProductId) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'productId') : planning_Tasks::getTaskInfo($rec->taskId)->productId;
    	if($productId){
    		$row->taskProductId = cat_Products::getShortHyperlink($productId);
    		$row->taskProductId = "<div class='nowrap'>" . $row->taskProductId . "</div>";
    	}
    	
    	if($rec->type != 'start'){
    		$measureId = ($rec->taskProductId) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'packagingId') : planning_Tasks::getTaskInfo($rec->taskId)->packagingId;
    		$row->packagingId = cat_UoM::getShortName($measureId);
    	} else {
    		unset($row->quantity);
    	}
    	
    	if(!empty($rec->notes)){
    		$notes = $mvc->getFieldType('notes')->toVerbal($rec->notes);
    		$row->taskProductId .= "<small>{$notes}</small>";
    	}
    	
    	if(!empty($rec->serial)){
    		$taskId = planning_TaskSerials::fetchField("#serial = '{$rec->serial}'", 'taskId');
    		if($taskId != $rec->taskId){
    			
    			if(!Mode::isReadOnly()){
    				$url = planning_Tasks::getSingleUrlArray($taskId);
    				$url['Q'] = $rec->serial;
    				 
    				$row->serial = ht::createLink($row->serial, $url, FALSE, "title=Към задачата от която е генериран серийния номер");
    			}
    		}
    	}
    	
    	if(isset($rec->employees)){
    		$row->employees = self::getVerbalEmployees($rec->employees);
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
    	if(isset($rec->taskProductId)){
    		planning_drivers_ProductionTaskProducts::updateRealQuantity($rec->taskProductId);
    	}
    	
    	// Ако е записва прозиведен артикул
    	if($rec->type == 'product'){
    		
    		// И има колчиество за скрап
    		if(isset($rec->scrappedQuantity)){
    			$sRec = clone $rec;
    			$sRec->quantity = $sRec->scrappedQuantity;
    			
    			// Ако не е имало досега добавя се
    			if(!$rec->actionRecId){
    				$rec->actionRecId = self::addAction($sRec, 'add', 'scrap');
    				$mvc->save_($rec, 'actionRecId');
    			} else {
    				
    				// Ако реда е оттеглен или възстановен променя се и състоянието му
    				$action = ($rec->state == 'rejected') ? 'reject' : (($rec->isRestored === TRUE) ? 'restore' : 'add');
    				if($action == 'reject' || $action == 'restore'){
    					$rec->actionRecId = self::addAction($sRec, $action, 'scrap');
    				} else {
    					planning_TaskActions::delete($rec->actionRecId);
    					$rec->actionRecId = self::addAction($sRec, 'edit', 'scrap');
    				}
    			}
    		} elseif(isset($rec->actionRecId)) {
    				
    			 	// Ако е имало бракувано количество, но вече няма
    				planning_TaskActions::delete($rec->actionRecId);
    				$rec->actionRecId = NULL;
    				$mvc->save_($rec);
    			}
    	}
    }
    
    
    /**
     * Изпълнява се преди възстановяването на документа
     */
    public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
    	// Отбелязваме че реда се редактира
    	$id->isRestored = TRUE;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	// Записване че е добавено
    	self::addAction($rec, 'add', $rec->type);
    }
    
    
    /**
     * Реакция в счетоводния журнал при оттегляне на счетоводен документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
    	// Записване че е имало оттегляне
    	$rec = static::fetchRec($id);
    	self::addAction($rec, 'reject', $rec->type);
    }
    
    
    /**
     * Реакция в счетоводния журнал при възстановяване на оттеглен счетоводен документ
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
    	// Записване че е имало възстановяване
    	$rec = static::fetchRec($id);
    	self::addAction($rec, 'restore', $rec->type);
    }
    
    
    /**
     * Добавяне на действие
     * 
     * @param stdClass $rec   - запис
     * @param varchar $action - действие
     * @param varchar $type   - тип
     * @return int
     */
    private static function addAction($rec, $action, $type)
    {
    	$productId = (!empty($rec->taskProductId)) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'productId') : planning_Tasks::getTaskInfo($rec->taskId)->productId;
    	$packagingId = (!empty($rec->taskProductId)) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'packagingId') : planning_Tasks::getTaskInfo($rec->taskId)->packagingId;
    	
    	return planning_TaskActions::add($rec->taskId, $productId, $action, $type, $packagingId, $rec->quantity, $rec->serial, $rec->employees, $rec->fixedAsset);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'product'))){
    			$data->toolbar->addBtn('Произвеждане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'product', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/package.png,title=Добавяне на произведен артикул');
    		}
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'input'))){
    			$data->toolbar->addBtn('Влагане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'input', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/wooden-box.png,title=Добавяне на вложен артикул');
    		}
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'waste'))){
    			$data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'waste', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/recycle.png,title=Добавяне на отпаден артикул');
    		}
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'start'))){
    			$data->toolbar->addBtn('Пускане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'start', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/media_playback_start.png,title=Пускане на произведения артикул');
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
    			$pOptions = planning_drivers_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
    			if(!count($pOptions)){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'edit' && isset($rec)){
    		if($rec->type != 'product' || $rec->state == 'rejected'){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// Ограничаване на броя на пусканията, според конфигурацията
    	if(($action == 'add' || $action == 'restore') && $rec->type == 'start'){
    		$counter = core_Packs::getConfigValue('planning', 'PLANNING_TASK_START_COUNTER');
    		$count = self::count("#taskId = {$rec->taskId} AND #type = 'start' AND #state != 'rejected'");
    		
    		// Не може да бъде надминат максималния брой пускания
    		if($count >= $counter){
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
    	$data->singleTitle = ($rec->type == 'input') ? 'влагане' : (($rec->type == 'waste') ? 'отпадък' : (($rec->type == 'start') ? 'пускане' : 'произвеждане'));
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @param date $date
     * @return array $result
     */
    public static function getIndicatorValues($timeline)
    {
    	$query = self::getQuery();
   
        $query->where("#modifiedOn >= '{$timeline}'");
        $iRec = hr_IndicatorNames::force('Време', __CLASS__, 1);
        
        $result = array();
        $tplObj = (object) array (
            'docClass' => core_Classes::getId('planning_Tasks'), 
            'indicatorId' => $iRec->id,
            );
        
        $queryProduct = planning_drivers_ProductionTaskProducts::getQuery();
        $queryMasterm = planning_Tasks::getQuery();

        while ($rec = $query->fetch()) { 
            
            // За дата на записа, приемаме датата на създаването му
            list($tplObj->date, ) = explode(' ', $rec->createdOn);
            
            // За източник - задачата, към който е този прогрес
            $tplObj->docId = $rec->taskId;
            
            if($rec->state == 'rejected') {
                $time = 0;
            } else {
                switch($rec->type){
                    case 'input':
                        $time = planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'indTime');	
                        break;
                    case 'waste':
                        $time = -planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'indTime');
                        break;
                    case 'product':
                        $time = planning_Tasks::getTaskInfo($rec->taskId)->indTime;
                        break;
                    case 'start':
                        $time = planning_Tasks::getTaskInfo($rec->taskId)->startTime;
                        break;
                    default:
                        $time = planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'indTime');
                        break;
                }
            }
            
            if($rec->employees) {
                $persons = keylist::toArray($rec->employees);
                
                $timePerson = ($rec->quantity * $time) / count($persons) ;
                
                foreach ($persons as $person) {
                    $res = clone($tplObj);
                    $res->personId = $person;
                    $res->docId = $rec->taskId;

                    $key = "{$res->date}|{$res->docId}|{$res->personId}";
                    
                    if(isset($result[$key])) {
                        $result[$key]->value += $timePerson;
                    }

                    $result[$key] = $res;
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
}