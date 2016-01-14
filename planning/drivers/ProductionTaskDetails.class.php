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
    public $listFields = 'RowNumb=Пулт,type=Операция,serial,taskProductId,quantity,weight,employees,fixedAsset,modified=Модифицирано';
    

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
     * Опашка
     */
    protected $queueProducts = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD("taskId", 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Задача');
    	$this->FLD('taskProductId', 'key(mvc=planning_drivers_ProductionTaskProducts,select=productId)', 'caption=Артикул,smartCenter,silent,refreshForm');
    	$this->FLD('type', 'enum(input=Влагане,product=Произвеждане,waste=Отпадък)', 'input=hidden,silent,smartCenter');
    	$this->FLD('serial', 'varchar(32)', 'caption=С. номер,smartCenter');
    	$this->FLD('quantity', 'double', 'caption=К-во,mandatory');
    	$this->FLD('weight', 'cat_type_Weight', 'caption=Тегло');
    	$this->FLD('employees', 'keylist(mvc=planning_HumanResources,select=code,makeLinks)', 'caption=Работници,smartCenter');
    	$this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=code)', 'caption=Машина,input=none,smartCenter');
    	$this->FLD('notes', 'richtext(rows=2)', 'caption=Забележки');
    	$this->FLD('state', 'enum(active=Активирано,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull');
    	
    	$this->setDbUnique('code');
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
    	
    	$groupTitle = $data->singleTitle = ($rec->type == 'input') ? 'За влагане' : (($rec->type == 'waste') ? 'Отпадъци' : 'За произвеждане');
    	$productOptions = planning_drivers_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
    	$productOptions = array('x' => (object)array('group' => TRUE, 'title' => tr($groupTitle))) + $productOptions;
    	
    	$form->setOptions('taskProductId', array('' => '') + $productOptions);
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
    	 
    	$row->modified = "<div class='centered'>" . $mvc->getFieldType('modifiedOn')->toVerbal($rec->modifiedOn);
    	$row->modified .= " " . tr('от') . " " . $row->modifiedBy . "</div>";
    	 
    	if(isset($rec->code)){
    		$row->code = "<b>{$row->code}</b>";
    	}
    	 
    	$row->ROW_ATTR['class'] .= " state-{$rec->state}";
    	if($rec->state == 'rejected'){
    		$row->ROW_ATTR['title'] = tr('Оттеглено от') . " " . core_Users::getVerbal($rec->modifiedBy, 'nick');
    	}
    	
    	if($rec->taskProductId){
    		$productId = planning_drivers_ProductionTaskProducts::fetchField($rec->taskProductId, 'productId');
    		$row->taskProductId = cat_Products::getShortHyperlink($productId);
    	}
    	
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
    	$mvc->queueProducts[$rec->taskProductId] = $rec->taskProductId;
    }
    
    
    /**
     * Изчиства записите, заопашени за запис
     */
    public static function on_Shutdown($mvc)
    {
    	if(is_array($mvc->queueProducts)){
    		foreach ($mvc->queueProducts as $taskProductId) {
    			planning_drivers_ProductionTaskProducts::updateRealQuantity($taskProductId);
    		}
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
    			$data->toolbar->addBtn('Влагане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'input', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/package.png,title=Добавяне на произведен артикул');
    		}
    		
    		if($mvc->haveRightFor('add', (object)array('taskId' => $data->masterId, 'type' => 'waste'))){
    			$data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'waste', 'ret_url' => TRUE), FALSE, 'ef_icon = img/16/package.png,title=Добавяне на отпаден артикул');
    		}
    	}
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
    	if($action == 'add' && isset($rec->type)){
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
    	$data->singleTitle = ($rec->type == 'input') ? 'вложен артикул' : (($rec->type == 'waste') ? 'отпадък' : 'произведен артикул');
    }
}