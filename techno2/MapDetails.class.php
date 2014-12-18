<?php



/**
 * Мениджър на детайли на технологични карти
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_MapDetails extends doc_Detail
{
    
	
    /**
     * Заглавие
     */
    var $title = "Детайл на технологичните карти";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Ресурс';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'mapId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_GroupByField, plg_RowTools, techno2_Wrapper, plg_LastUsedKeys, plg_RowNumbering, plg_StyleNumbers, plg_AlignDecimals';
    
    
    /**
     * По кое поле да се групират записите
     */
    var $groupByField = 'stageId';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'stageId,resourceId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'rowNumb=Пулт, stageId, resourceId, baseQuantity, propQuantity, measureId=Мярка';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'rowNumb';
    
    
    /**
     * Активен таб
     */
    //var $currentTab = 'Операции->Разлики';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,techno';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,techno';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,techno';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,techno';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,techno';
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('mapId', 'key(mvc=techno2_Maps)', 'column=none,input=hidden,silent');
    	
    	$this->FLD("stageId", 'key(mvc=mp_Stages,select=name,allowEmpty)', 'caption=Етап');
    	
    	$this->FLD("resourceId", 'key(mvc=mp_Resources,select=title,allowEmpty)', 'caption=Ресурс,mandatory,silent', array('attr' => array('onchange' => 'addCmdRefresh(this.form);this.form.submit();')));
    	$this->FLD("baseQuantity", 'double', 'caption=Количество->Твърдо,hint=Твърдо количество');
    	$this->FLD("propQuantity", 'double', 'caption=Количество->Пропорционално,hint=Пропорционално количество');
    
    	// Уникални индекси
    	$this->setDbUnique('mapId,stageId,resourceId');
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
    	
    	if(isset($rec->resourceId)){
    		$uomId = mp_Resources::fetchField($rec->resourceId, 'measureId');
    		$uomName = cat_UoM::getShortName($uomId);
    			
    		$form->setField('baseQuantity', "unit={$uomName}");
    		$form->setField('propQuantity', "unit={$uomName}");
    	}
    	
    	if($form->isSubmitted()){
    		if(empty($rec->baseQuantity) && empty($rec->propQuantity)){
    			$form->setError('baseQuantity,propQuantity', 'Трябва да е въведено поне едно количество');
    		}
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
     * След обръщане на записа във вербален вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	//$row->resourceId = mp_Resources::getHyperlink($rec->resourceId, TRUE);
    	
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
    		$row->resourceId = ht::createLinkRef($row->resourceId, array('mp_Resources', 'single', $rec->resourceId));
    	}
    	
    	$uomId = mp_Resources::fetchField($rec->resourceId, 'measureId');
    	$row->measureId = cat_UoM::getShortName($uomId);
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
    			$rec->order = mp_Stages::fetchField($rec->stageId, 'order');
    		} else {
    			$rec->order = 0;
    		}
    	}
    	
    	// Сортираме по подредбата на производствения етап
    	usort($recs, function($a, $b) {
    		if($a->order == $b->order)  return 0;
    		
    		return ($a->order > $b->order) ? 1 : -1;
    	});
    }
}