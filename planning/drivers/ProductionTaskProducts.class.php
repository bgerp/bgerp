<?php


/**
 * Клас 'planning_drivers_ProductionTaskProducts'
 *
 * Детайли на задачите за производство
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_drivers_ProductionTaskProducts extends tasks_TaskDetails
{
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'RowNumb=Пулт,type,productId,packagingId,planedQuantity=Количества->Планувано,realQuantity=Количества->Изпълнено,indTime,totalTime';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_RowNumbering, plg_AlignDecimals2, plg_SaveAndNew, plg_Modified, plg_Created';
    
    
    /**
     * Кой има право да оттегля?
     */
    public $canReject = 'planning,ceo';
    
    
    /**
     * Кой има право да възстановява?
     */
    public $canRestore = 'planning,ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'planning,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canList = 'no_one';
    
   
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD("taskId", 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Задача');
    	$this->FLD("type", 'enum(input=Вложим,product=Производим,waste=Отпадък)', 'caption=Вид,removeAndRefreshForm=productId|packagingId,remember');
    	$this->FLD("productId", 'key(mvc=cat_Products,select=name,allowEmpty)', 'silent,mandatory,caption=Артикул,removeAndRefreshForm=packagingId');
    	$this->FLD("packagingId", 'key(mvc=cat_UoM,select=name)', 'mandatory,caption=Опаковка,smartCenter');
    	$this->FLD("planedQuantity", 'double', 'mandatory,caption=Планувано к-во');
    	$this->FLD("realQuantity", 'double', 'caption=Количество->Изпълнено,input=none');
    	$this->FLD("indTime", 'time', 'mandatory,caption=Времена->Изпълнение,smartCenter');
    	$this->FNC('totalTime', 'time', 'caption=Времена->Общо,smartCenter');
    	
    	$this->setDbUnique('taskId,productId');
    }
    
    
    /**
     * Общото време
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public static function on_CalcTotalTime(core_Mvc $mvc, $rec)
    {
    	if (empty($rec->indTime) || empty($rec->realQuantity)) {
    		return;
    	}
    
    	$rec->totalTime = $rec->indTime * $rec->realQuantity;
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
    	
    	$form->setDefault('type', 'input');
    	
    	if(isset($rec->type)){
    		switch($rec->type){
    			case 'input':
    				$meta = 'canConvert';
    				break;
    			case 'product':
    				$meta = 'canManifacture';
    				break;
    			case 'waste':
    				$meta = 'canStore';
    				break;
    		}
    		
    		$products = cat_Products::getByProperty($meta);
    		if($rec->productId){
    			$products[$rec->productId] = cat_Products::getTitleById($rec->productId, FALSE);
    		}
    		$form->setOptions('productId', $products);
    	}
    	
    	if(isset($rec->productId)){
    		$packs = cat_Products::getPacks($rec->productId);
    		$form->setOptions('packagingId', $packs);
    	} else {
    		$form->setReadOnly('packagingId');
    	}
    }
    
    
    /**
     * Подготвя детайла
     */
    public function prepareDetail_($data)
    {
    	$data->TabCaption = 'Артикули';
    	$data->Tab = 'top';
    
    	parent::prepareDetail_($data);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$data)
    {
    	if(!count($data->recs)) return;
    	
    	foreach ($data->rows as $id => $row){
    		$row->ROW_ATTR['class'] = "state-active";
    		$row->productId = cat_Products::getShortHyperlink($data->recs[$id]->productId);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'reject' || $action == 'restore' || $action == 'edit' || $action == 'delete') && isset($rec->taskId)){
    		$state = $mvc->Master->fetchField($rec->taskId, 'state');
    		if($state != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Ъпдейтва реалното количество
     * 
     * @param int $taskProductId - ид на ред за ъпдейт
     * @return void
     */
    public static function updateRealQuantity($taskProductId)
    {
    	$rec = self::fetch($taskProductId);
    	$rec->realQuantity = 0;
    	
    	$query = planning_drivers_ProductionTaskDetails::getQuery();
    	$query->where("#taskId = {$rec->taskId}");
    	$query->where("#type = '{$rec->type}'");
    	$query->show('quantity');
    	
    	while($dRec = $query->fetch()){
    		$rec->realQuantity += $dRec->quantity;
    	}
    	
    	self::save($rec, 'realQuantity');
    }
    
    
    /**
     * Намира всички допустими артикули от дадения тип за една задача
     * 
     * @param int $taskId
     * @param input|product|waste $type
     * @return array
     */
    public static function getOptionsByType($taskId, $type)
    {
    	$options = array();
    	expect(in_array($type, array('input', 'product', 'waste')));
    	
    	$query = self::getQuery();
    	$query->where("#taskId = {$taskId}");
    	$query->where("#type = '{$type}'");
    	while($rec = $query->fetch()){
    		$options[$rec->id] = cat_Products::getTitleById($rec->productId, FALSE);
    	}
    	
    	return $options;
    }
}