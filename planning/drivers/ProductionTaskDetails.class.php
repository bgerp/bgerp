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
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_RowNumbering, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created, plg_LastUsedKeys, plg_Sorting, planning_Wrapper';
    
    
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
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'RowNumb=Пулт,type=Операция,serial,taskProductId,packagingId=Мярка,quantity,weight,employees,fixedAsset,modified=Модифицирано';
    

    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'serial,weight,employees,fixedAsset';
    
    
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
    	$this->FLD('quantity', 'double', 'caption=Количество,mandatory,smartCenter');
    	$this->FLD('weight', 'cat_type_Weight', 'caption=Тегло,smartCenter');
    	$this->FLD('employees', 'keylist(mvc=crm_Persons,select=id)', 'caption=Работници,smartCenter,tdClass=nowrap');
    	$this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=code)', 'caption=Машина,input=none,smartCenter');
    	$this->FLD('notes', 'richtext(rows=2)', 'caption=Забележки');
    	$this->FLD('state', 'enum(active=Активирано,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull');
    	$this->FNC('packagingId', 'int', 'smartCenter,tdClass=small-field nowrap');
    	$this->FLD('time', 'time', 'caption=Време,smartCenter,input=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$data->form->rec;
    	
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
    	if(isset($data->masterRec->fixedAssets)){
    		$keylist = $data->masterRec->fixedAssets;
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
    		$form->setOptions('productId', array($data->masterRec->productId = cat_Products::getTitleById($data->masterRec->productId, FALSE)));
    		$form->setField('taskProductId', 'input=none');
    		$unit = cat_UoM::getShortName($data->masterRec->packagingId);
    		$form->setField('quantity', "unit={$unit}");
    		
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
    		
    		
    		$planned = tr("Планувано|*: <b>") . planning_drivers_ProductionTaskProducts::getVerbal($pRec, 'plannedQuantity') . "</b>";
    		$real = tr("Изпълнено|*: <b>") . planning_drivers_ProductionTaskProducts::getVerbal($pRec, 'realQuantity') . "</b>";
    		$form->info = "{$planned}<br>$real";
    		
    		
    		$form->setField('quantity', "unit={$unit}");
    	}
    	
    	$employees = crm_Persons::getEmployeesOptions(FALSE);
    	if(count($employees)){
    		$form->setSuggestions('employees', array('' => '') + $employees);
    	} else {
    		$form->setReadOnly('employees');
    	}
    }
    

    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	 
    	if($form->isSubmitted()){
    		$productId = ($rec->taskProductId) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'productId') : planning_Tasks::fetch($rec->taskId)->productId;
    		
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
    		
    		switch($rec->type){
    			case 'product':
    				$time = planning_Tasks::fetch($rec->taskId)->indTime;
    				break;
    			case 'start':
    				$time = planning_Tasks::fetch($rec->taskId)->startTime;
    				break;
    			default:
    				$time = planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'indTime');
    				break;
    		}
    		
    		if(!empty($time)){
    			$rec->time = $rec->quantity * $time;
    		}
    	}
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(isset($rec->fixedAsset)){
    		if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')){
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
    	
    	$productId = ($rec->taskProductId) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'productId') : planning_Tasks::fetch($rec->taskId)->productId;
    	if($productId){
    		$row->taskProductId = cat_Products::getShortHyperlink($productId);
    		$row->taskProductId = "<div class='nowrap'>" . $row->taskProductId . "</div>";
    	}
    	
    	$measureId = ($rec->taskProductId) ? planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'packagingId') : planning_Tasks::fetch($rec->taskId)->packagingId;
    	$row->packagingId = cat_UoM::getShortName($measureId);
    	
    	if(!empty($rec->notes)){
    		$notes = $mvc->getFieldType('notes')->toVerbal($rec->notes);
    		$row->taskProductId .= "<small>{$notes}</small>";
    	}
    	
    	if(!empty($rec->serial)){
    		$taskId = planning_TaskSerials::fetchField("#serial = '{$rec->serial}'", 'taskId');
    		if($taskId != $rec->taskId){
    			
    			if(!Mode::is('text', 'xhtml') && !Mode::is('printing') && !Mode::is('pdf')){
    				$url = planning_Tasks::getSingleUrlArray($taskId);
    				$url['Q'] = $rec->serial;
    				 
    				$row->serial = ht::createLink($row->serial, $url, FALSE, "title=Към задачата от която е генериран серийния номер");
    			}
    		}
    	}
    	
    	if(isset($rec->employees)){
    		$verbalEmployees = array();
    		$employees = keylist::toArray($rec->employees);
    		foreach ($employees as $eId){
    			
    			$el = crm_ext_EmployeeCodes::getCode($eId, TRUE);
    			$name = crm_Persons::getVerbal($eId, 'name');
    			
    			$singleUrl = crm_Persons::getSingleUrlArray($eId);
    			if(count($singleUrl)){
    				$singleUrl['Tab'] = 'PersonsDetails';
    			}
    			
    		    $el = ht::createLink($el, $singleUrl, FALSE, "title=Към визитката на|* '{$name}'");
    		    $el = ht::createHint($el, $name, 'img/16/vcard.png', FALSE);
    			$verbalEmployees[$eId] = $el;
    		}
    		
    		$row->employees = implode(', ', $verbalEmployees);
    	}
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
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'reject' || $action == 'restore' || $action == 'edit' || $action == 'delete') && isset($rec->taskId)){
    		$state = $mvc->Master->fetchField($rec->taskId, 'state');
    		if($state != 'active' && $state != 'pending' && $state != 'wakeup'){
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
    }


    /**
     * Преди подготовка на заглавието на формата
     */
    protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
    {
    	$rec = &$data->form->rec;
    	$data->singleTitle = ($rec->type == 'input') ? 'влагане' : (($rec->type == 'waste') ? 'отпадък' : (($rec->type == 'start') ? 'пускане' : 'произвеждане'));
    }
}