<?php



/**
 * Мениджър на етапи детайл на технологична рецепта
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_BomDetails extends doc_Detail
{
	
	
    /**
     * Заглавие
     */
    var $title = "Етапи на технологичните рецепти";
    
    
    /**
     * Заглавие
     */
    var $singleTitle = "Ресурс";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'bomId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper, plg_LastUsedKeys, plg_SaveAndNew, plg_GroupByField, plg_AlignDecimals2';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'resourceId';
    
    
    /**
     * По кое поле да се групират записите
     */
    var $groupByField = 'stageId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активен таб
     */
    var $currentTab = 'Рецепти';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,cat';
    
    
    /**
     * Кой има право да чете?
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,cat';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,cat';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт, stageId, resourceId, measureId=Мярка, baseQuantity=Начално,propQuantity';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('bomId', 'key(mvc=cat_Boms)', 'column=none,input=hidden,silent');
    	$this->FLD("resourceId", 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Материал,mandatory,silent,refreshForm');
    	
    	$this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty, select2MinItems=0)', 'caption=Мярка','tdClass=small-field,silent,removeAndRefreshForm=quantityInPack');
    	$this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
    	
    	$this->FLD('stageId', 'key(mvc=planning_Stages,allowEmpty,select=name)', 'caption=Етап');
    	$this->FLD('type', 'enum(input=Влагане,pop=Отпадък)', 'caption=Действие,silent,input=hidden');
    	
    	$this->FLD("baseQuantity", 'double(Min=0)', 'caption=Количество->Начално,hint=Начално количество');
    	$this->FLD("propQuantity", 'double(Min=0)', 'caption=Количество->Пропорционално,hint=Пропорционално количество');
    	
    	$this->setDbUnique('bomId,resourceId');
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
    	$data->listFields['propQuantity'] = "|За|* " . $data->masterData->row->quantity;
    	$data->query->orderBy("type", 'DESC');
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
    	$typeCaption = ($form->rec->type == 'input') ? 'материал' : 'отпадък';
    	$form->title = "|Добавяне на|* {$typeCaption} |към|* <b>|{$mvc->Master->singleTitle}|* №{$form->rec->bomId}<b>";
    	
    	// Добавяме всички вложими артикули за избор
    	$products = cat_Products::getByProperty('canConvert');
    	unset($products[$data->masterRec->productId]);
    	$form->setOptions('resourceId', $products);
    	
    	$form->setDefault('type', 'input');
    	$quantity = $data->masterRec->quantity;
    	$originInfo = cat_Products::getProductInfo($data->masterRec->productId);
    	$shortUom = cat_UoM::getShortName($originInfo->productRec->measureId);
    		
    	$propCaption = "|За|* |{$quantity}|* {$shortUom}";
    	$form->setField('propQuantity', "caption={$propCaption}");
    }
    
    
    /**
     * Търси в дърво, дали даден обект не е баща на някой от бащите на друг обект
     *
     * @param int $objectId - ид на текущия обект
     * @param int $needle - ид на обекта който търсим
     * @param array $notAllowed - списък със забранените обекти
     * @param array $path
     * @return void
     */
    private function findNotAllowedProducts($objectId, $needle, &$notAllowed, $path = array())
    {
    	// Добавяме текущия продукт
    	$path[$objectId] = $objectId;
    
    	// Ако стигнем до началния, прекратяваме рекурсията
    	if($objectId == $needle){
    		foreach($path as $p){
    
    			// За всеки продукт в пътя до намерения ние го
    			// добавяме в масива notAllowed, ако той, вече не е там
    			$notAllowed[$p] = $p;
    		}
    		return;
    	}
    	
    	// Имали артикула рецепта
    	if($bomId = cat_Products::getLastActiveBom($objectId)){
    		$bomInfo = cat_Boms::getResourceInfo($bomId);
    		
    		// За всеки продукт от нея проверяваме дали не съдържа търсения продукт
    		if(count($bomInfo['resources'])){
    			foreach ($bomInfo['resources'] as $res){
    				$this->findNotAllowedProducts($res->productId, $needle, $notAllowed, $path);
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	// Ако има избран ресурс, добавяме му мярката до полетата за количества
    	if(isset($rec->resourceId)){
    		
    		$pInfo = cat_Products::getProductInfo($rec->resourceId);
    		$form->setDefault('measureId', $pInfo->productRec->measureId);
    		$shortName = cat_UoM::getShortName($rec->measureId);
    		$form->setField('baseQuantity', "unit={$shortName}");
    		$form->setField('propQuantity', "unit={$shortName}");
    				
    		$packs = cls::get('cat_Products')->getPacks($rec->resourceId);
    		if(isset($rec->packagingId) && !isset($packs[$rec->packagingId])){
    			$packs[$rec->packagingId] = cat_Packagings::getTitleById($rec->packagingId, FALSE);
    		}
    		if(count($packs)){
    			$form->setOptions('packagingId', $packs);
    		} else {
    			$form->setReadOnly('packagingId');
    		}
    				
    		$form->setField('packagingId', "placeholder=" . cat_UoM::getTitleById($rec->measureId));
    	}
    	
    	// Проверяваме дали е въведено поне едно количество
    	if($form->isSubmitted()){
    		
    		if(isset($rec->resourceId)){
    			
    			// Ако е избран артикул проверяваме дали артикула от рецептата не се съдържа в него
    			$masterProductId = cat_Boms::fetchField($rec->bomId, 'productId');
    			$productVerbal = cat_Products::getTitleById($masterProductId);
    			
    			$notAllowed = array();
    			$mvc->findNotAllowedProducts($rec->resourceId, $masterProductId, $notAllowed);
    			if(isset($notAllowed[$rec->resourceId])){
    				$form->setError('resourceId', "Материала не може да бъде избран, защото в рецептата на някой от материалите му се съдържа|* <b>{$productVerbal}</b>");
    			}
    		}
    		
    		// Ако добавяме отпадък, искаме да има себестойност
    		if($rec->type == 'pop'){
    			$selfValue = planning_ObjectResources::getSelfValue($rec->resourceId);
    			if(!isset($selfValue)){
    				$form->setError('resourceId', 'Отпадакът няма себестойност');
    			}
    		}
    		
    		// Не може и двете количества да са празни
    		if(empty($rec->baseQuantity) && empty($rec->propQuantity)){
    			$form->setError('baseQuantity,propQuantity', 'Трябва да е въведено поне едно количество');
    		}
    		
    		$rec->quantityInPack = (empty($rec->packagingId)) ? 1 : (($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : $rec->quantityInPack);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->resourceId = cat_Products::getShortHyperlink($rec->resourceId);
    	$measureId = cat_Products::getProductInfo($rec->resourceId)->productRec->measureId;
    	
    	if($rec->packagingId){
    		$row->measureId = cat_Packagings::getTitleById($rec->packagingId);
    		if($rec->quantityInPack != 1){
    			$quantityInPack = cls::get('type_Double', array('params' => array('smartRound' => TRUE)))->toVerbal($rec->quantityInPack);
    			$shortUom = cat_UoM::getShortName($measureId);
    			$row->measureId .= " <span class='quiet'>({$quantityInPack} {$shortUom})</span>";
    		}
    	} else {
    		$row->measureId = cat_UoM::getTitleById($measureId);
    	}
    	
    	$row->ROW_ATTR['class'] = ($rec->type != 'input') ? 'row-removed' : 'row-added';
    	$row->ROW_ATTR['title'] = ($rec->type != 'input') ? tr('Отпадък') : NULL;
    	
    	if(empty($rec->stageId)){
    		$row->stageId = tr("без етап");
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    	if($mvc->haveRightFor('add', (object)array('bomId' => $data->masterId))){
    		$data->toolbar->addBtn('Материал', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => TRUE, 'type' => 'input'), NULL, "title=Добавяне на ресурс към рецептата,ef_icon=img/16/star_2.png");
    		$data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => TRUE, 'type' => 'pop'), NULL, "title=Добавяне на отпаден ресурс към рецептата,ef_icon=img/16/star_2.png");
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
    		if($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
    	if(!count($data->recs)) return;
    	 
    	$recs = &$data->recs;
    	
    	foreach ($recs as &$rec){
    		if($rec->stageId){
    			$rec->order = planning_Stages::fetchField($rec->stageId, 'order');
    		} else {
    			$rec->order = 0;
    		}
    		$rec->order .= $rec->id;
    	}
    	 
    	if($data->masterData->rec->state != 'draft'){
    		unset($data->listFields['tools']);
    	}
    	
    	// Сортираме по подредбата на производствения етап
    	usort($recs, function($a, $b) {
    		if($a->order == $b->order)  return 0;
    
    		return ($a->order > $b->order) ? 1 : -1;
    	});
    }
}