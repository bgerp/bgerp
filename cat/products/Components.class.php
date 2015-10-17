<?php

/**
 * Клас 'cat_products_Params' - продуктови параметри
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class cat_products_Components extends doc_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    var $title = 'Компоненти';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Компонент';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'productId, componentId, tools=Пулт';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper, plg_RowTools, plg_LastUsedKeys, plg_SaveAndNew';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'componentId';
    
    
    /**
     * Поле за пулт-а
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canEdit = 'ceo,cat';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canDelete = 'ceo,cat';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    var $fetchFieldsBeforeDelete = 'id, productId, componentId, quantity';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products)', 'input=hidden,silent,mandatory');
        $this->FLD('componentId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'input,caption=Компонент,mandatory,silent,refreshForm');
        $this->FLD('quantity', 'double(smartRound)', 'input,caption=Количество,mandatory');
        
        $this->setDbUnique('productId,componentId');
    }
    
    
    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    public static function prepareComponents(&$data)
    {
    	$query = self::getQuery();
    	$query->where("#productId = {$data->masterId}");
    
    	while($rec = $query->fetch()){
    		$data->components[$rec->id] = static::recToVerbal($rec);
    		if(!self::haveRightFor('add', (object)array('productId' => $data->masterId))) {
    			unset($data->components[$rec->id]->tools);
    		}
    	}
    	 
    	if(self::haveRightFor('add', (object)array('productId' => $data->masterId))) {
    		$data->addCompUrl = array(__CLASS__, 'add', 'productId' => $data->masterId, 'ret_url' => TRUE);
    	}
    }
    
    
    /**
     * Рендира екстеншъна с параметри на продукт
     */
    public static function renderComponents($data)
    {
    	if($data->addCompUrl) {
    		$data->addCompUrl = ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addCompUrl, FALSE, 'title=Добавяне на нов компонент');
    	}
   
    	return self::renderDetail($data);
    }
    
    
    /**
     * Рендиране на общия изглед за 'List'
     */
    public static function renderDetail($data)
    {
    	$tpl = getTplFromFile('cat/tpl/products/Components.shtml');
    	$tpl->replace(get_called_class(), 'DetailName');
    
    	if($data->noChange !== TRUE){
    		$tpl->append($data->addCompUrl, 'addComponentBtn');
    	}
    	
    	foreach((array)$data->components as $row) {
    		if($data->noChange === TRUE){
    			unset($row->tools);
    		}
    		 
    		$block = clone $tpl->getBlock('param');
    		$block->placeObject($row);
    		$block->removeBlocks();
    		$block->append2Master();
    	}
    
    	return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->componentId = cat_Products::getShortHyperlink($rec->componentId);
    	$measureId = cat_Products::fetchField($rec->componentId, 'measureId');
    	$measureName = cat_UoM::getShortName($measureId);
    	$row->quantity .= " {$measureName}";
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$form = &$data->form;
    	$convertable = cat_Products::getByProperty('canConvert');
    	unset($convertable[$form->rec->productId]);
    	$form->setOptions('componentId', array('' => '') + $convertable);
    	
    	if(isset($form->rec->componentId)){
    		$measureId = cat_Products::fetchField($form->rec->componentId, 'measureId');
    		$measureName = cat_UoM::getShortName($measureId);
    		$form->setField('quantity', "unit={$measureName}");
    	}
    }
    
    
    /**
     * Кой е мастър класа
     */
    public function getMasterMvc($rec)
    {
    	$masterMvc = cls::get('cat_Products');
    		
    	return $masterMvc;
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
    	if($requiredRoles == 'no_one') return;
    	 
    	if (($action == 'add' || $action == 'delete') && isset($rec->productId)) {
    		$state = cat_Products::fetchField($rec->productId, 'state');
    		if($state != 'active' && $state != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    
    	// Ако потрбителя няма достъп до сингъла на артикула, не може да модифицира параметрите
    	if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec) && $requiredRoles != 'no_one'){
    		if(!cat_Products::haveRightFor('single', $rec->productId)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}