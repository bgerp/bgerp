<?php


/**
 * Клас 'planning_drivers_ProductionTaskDetails'
 *
 * Детайли на драйверите за за задачи за производство
 *
 * @category  bgerp
 * @package   tasks
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
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
    public $loadList = 'plg_RowTools, plg_RowNumbering, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created, plg_LastUsedKeys, plg_Sorting';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'employees,fixedAsset';
    
    
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
    	$this->FLD('taskProductId', 'key(mvc=planning_drivers_ProductionTaskProducts,select=productId,allowEmpty)', 'caption=Артикул,mandatory,silent,refreshForm');
    	$this->FLD('type', 'enum(input=Влагане,product=Произвеждане,waste=Отпадък)', 'input=hidden,silent,smartCenter');
    	$this->FLD('serial', 'varchar(32)', 'caption=С. номер,smartCenter');
    	$this->FLD('quantity', 'double', 'caption=К-во,mandatory');
    	$this->FLD('weight', 'cat_type_Weight', 'caption=Тегло');
    	$this->FLD('employees', 'keylist(mvc=planning_HumanResources,select=code,makeLinks)', 'caption=Работници,smartCenter,tdClass=nowrap');
    	$this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=code)', 'caption=Машина,input=none,smartCenter');
    	$this->FLD('notes', 'richtext(rows=2)', 'caption=Забележки');
    	$this->FLD('state', 'enum(active=Активирано,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
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
    	
    	if($rec->type != 'product'){
    		$productOptions = planning_drivers_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
    		$form->setOptions('taskProductId', $productOptions);
    		if(count($productOptions) == 1 && $form->cmd != 'refresh'){
    			$form->setDefault('taskProductId', key($productOptions));
    		}
    	} else {
    		$form->FNC('productId', 'int', 'caption=Артикул,input,before=serial');
    		$form->setOptions('productId', array($data->masterRec->productId = cat_Products::getTitleById($data->masterRec->productId, FALSE)));
    		$form->setField('taskProductId', 'input=none');
    		$unit = cat_UoM::getShortName($data->masterRec->packagingId);
    		$form->setField('quantity', "unit={$unit}");
    	}
    	
    	// Добавяме мярката
    	if(isset($rec->taskProductId)){
    		$unit = planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'packagingId');
    		$unit = cat_UoM::getShortName($unit);
    		$form->setField('quantity', "unit={$unit}");
    	}
    }
    

    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	 
    	if($form->isSubmitted()){
    		
    		// Ако няма код и операцията е 'произвеждане' задаваме дефолтния код
    		if($rec->type == 'product'){
    			if(empty($rec->serial)){
    				$rec->serial = $mvc->getDefaultSerial();
    			}
    		}
    		
    		if(empty($rec->serial)){
    			$rec->serial = NULL;
    		}
    	}
    }


    /**
     * Връща следващия най-голям свободен код
     *
     * @return int $code - код
     */
    private function getDefaultSerial()
    {
    	// Намираме последния въведен код
    	$query = self::getQuery();
    	$query->XPR('maxCode', 'int', 'MAX(#serial)');
    	$code = $query->fetch()->maxCode;
    	 
    	// Инкрементираме кода, докато достигнем свободен код
    	$code++;
    	while(self::fetch("#serial = '{$code}'")){
    		$code++;
    	}
    	 
    	return $code;
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(isset($rec->fixedAsset)){
    		if(!Mode::is('text', 'xhtml') && !Mode::is('printing')){
    			$singleUrl = planning_AssetResources::getSingleUrlArray($rec->fixedAsset);
    			$row->fixedAsset = ht::createLink($row->fixedAsset, $singleUrl);
    		}
    	}
    	 
    	$row->modified = "<div class='nowrap'>" . $mvc->getFieldType('modifiedOn')->toVerbal($rec->modifiedOn);
    	$row->modified .= " " . tr('от') . " " . $row->modifiedBy . "</div>";
    	 
    	if(isset($rec->serial)){
    		$row->serial = "<b>{$row->serial}</b>";
    	}
    	 
    	$class = ($rec->state == 'rejected') ? 'state-rejected' : (($rec->type == 'input') ? 'row-added' : (($rec->type == 'product') ? 'state-active' : 'row-removed'));
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
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
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
    		if($state != 'active' && $state != 'pending' && $state != 'wakeup'){
    			$requiredRoles = 'no_one';
    		} 
    	}
    	
    	// Трябва да има поне един артикул възможен за добавяне
    	if($action == 'add' && isset($rec->type) && $rec->type != 'product'){
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
    	$data->singleTitle = ($rec->type == 'input') ? 'влагане' : (($rec->type == 'waste') ? 'отпадък' : 'произвеждане');
    }
}