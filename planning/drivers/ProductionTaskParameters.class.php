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
class planning_drivers_ProductionTaskParameters extends tasks_TaskDetails
{
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Информация за задача за производство';
    
    
    /**
     * Заглавие
     */
    public $title = 'Информация за задачата за производство';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = '';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    //public $listFields = 'tools=Пулт,type,productId,packagingId,plannedQuantity=Количества->Планувано,realQuantity=Количества->Изпълнено,storeId,indTime,totalTime';
  
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'taskPlanning,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
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
     * Активен таб на менюто
     */
    public $currentTab = 'Задачи';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('taskId', 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory');
    	$this->FLD('description', 'richtext(rows=4, bucket=Notes)', 'mandatory,caption=Описание');
    	$this->setDbUnique('taskId');
    }
    
    
    /**
     * Подготвя детайла
     */
    public function prepareDetail_($data)
    {
    	$data->TabCaption = 'Информация';
    	$data->Tab = 'top';
    
    	$query = $this->getQuery();
    	$query->where("#taskId = {$data->masterId}");
    	$query->limit(1);
    	
    	$data->rec = $query->fetch();
    	
    	if(!empty($data->rec)){
    		$data->row = $this->recToVerbal($data->rec);
    	}
    	
    	if($this->haveRightFor('edit', $data->rec)){
    		$data->editUrl = array($this, 'edit', $data->rec->id, 'ret_url' => TRUE);
    	}
    	
    	$masterRec = $data->masterData->rec;
    	$productId = $masterRec->productId;
    	if(cat_Products::getDriver($productId)->getClassId() == cat_GeneralProductDriver::getClassId()){
    		$d = clone $data;
    		$d->masterId = $productId;
    		$d->masterClassId = planning_Tasks::getClassId();
    		if($masterRec->state == 'closed' || $masterRec->state == 'stopped' || $masterRec->state == 'rejected'){
    			$d->noChange = TRUE;
    			unset($data->editUrl);
    		}
    		cat_products_Params::prepareParams($d);
    		$data->paramData = $d;
    	}
    }
    
    
    /**
     * Рендира детайла
     */
    public function renderDetail_($data)
    {
    	$tpl = getTplFromFile("planning/tpl/PlanningTaskParameters.shtml");
    	$tpl->append($data->row->description, 'description');
    	
    	if(isset($data->editUrl)){
    		$btn = ht::createLink('', $data->editUrl, FALSE, 'title=Редактиране на информацията за задачата,ef_icon=img/16/edit.png');
    		$tpl->append($btn, 'editBtn');
    	}
    	
    	if(isset($data->paramData)){
    		$paramTpl = cat_products_Params::renderParams($data->paramData);
    		$tpl->append($paramTpl, 'PARAMS');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Обновява информацията от артикула към задачата
     * 
     * @param int $taskId    - ид на задача
     * @param int $productId - ид на артикула
     * @return void
     */
    public static function saveProductParams($taskId, $productId)
    {
    	expect($pRec = cat_Products::fetch($productId));
    	$nRec = (object)array('taskId' => $taskId, 'description' => $pRec->info);
    	if($id = planning_drivers_ProductionTaskParameters::fetchField("#taskId = {$taskId}", 'id')){
    		$nRec->id = $id;
    	}
    	planning_drivers_ProductionTaskParameters::save($nRec, NULL, 'REPLACE');
    	
    	$Products = cls::get('cat_Products');
    	$paramQuery = cat_products_Params::getQuery();
    	$paramQuery->where("#productId = {$pRec->id} AND #classId = {$Products->getClassId()}");
    	while($paramRec = $paramQuery->fetch()){
    		$newRec = clone $paramRec;
    		$newRec->classId = planning_Tasks::getClassId();
    		unset($newRec->id);
    		
    		if($idd = cat_products_Params::fetchField("#classId = {$newRec->classId} AND #productId = {$newRec->productId} AND #paramId = {$newRec->paramId}", 'id')){
    			$newRec->id = $idd;
    		}
    		cat_products_Params::save($newRec, NULL, "REPLACE");
    	}
    }
}