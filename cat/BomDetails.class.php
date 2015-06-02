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
    	$this->FLD("resourceId", 'key(mvc=planning_Resources,select=title,allowEmpty)', 'caption=Ресурс,mandatory,silent,refreshForm');
    	$this->FLD('stageId', 'key(mvc=planning_Stages,allowEmpty,select=name)', 'caption=Етап');
    	$this->FLD('type', 'enum(input=Влагане,pop=Отпадък)', 'caption=Действие,silent,removeAndRefreshForm=propQuantity');
    	
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
    	 
    	$form->setDefault('type', 'input');
    	$quantity = $data->masterRec->quantity;
    	$originInfo = cat_Products::getProductInfo($data->masterRec->productId);
    	$shortUom = cat_UoM::getShortName($originInfo->productRec->measureId);
    		
    	$propCaption = "|За|* |{$quantity}|* {$shortUom}";
    	$form->setField('propQuantity', "caption={$propCaption}");
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
    		if($uomId = planning_Resources::fetchField($rec->resourceId, 'measureId')){
    			$uomName = cat_UoM::getShortName($uomId);
    	
    			$form->setField('baseQuantity', "unit={$uomName}");
    			$form->setField('propQuantity', "unit={$uomName}");
    		}
    	}
    	
    	// Проверяваме дали е въведено поне едно количество
    	if($form->isSubmitted()){
    		if($rec->type == 'pop'){
    			$rType = planning_Resources::fetchField($rec->resourceId, 'type');
    			if($rType != 'material'){
    				$form->setError('resourceId,type', 'Отпадният ресурс трябва да е материал');
    			} else {
    				if(!planning_Resources::fetchField($rec->resourceId, 'selfValue')){
    					$form->setError('resourceId', 'Отпадният ресурс няма себестойност');
    				}
    			}
    		}
    		
    		// Не може и двете количества да са празни
    		if(empty($rec->baseQuantity) && empty($rec->propQuantity)){
    			$form->setError('baseQuantity,propQuantity', 'Трябва да е въведено поне едно количество');
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->resourceId = planning_Resources::getShortHyperlink($rec->resourceId);
    	$measureId = planning_Resources::fetchField($rec->resourceId, 'measureId');
    	$row->measureId = cat_UoM::getTitleById($measureId);
    	
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
    		$data->toolbar->addBtn('Ресурс', array($mvc, 'add', 'bomId' => $data->masterId, 'ret_url' => TRUE), NULL, "title=Добавяне на ресурс към рецептата,ef_icon=img/16/star_2.png");
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