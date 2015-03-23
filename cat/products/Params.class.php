<?php

/**
 * Клас 'cat_products_Params'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class cat_products_Params extends core_Manager
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    var $title = 'Параметри';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Параметър';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'paramId, paramValue, tools=Пулт';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper, plg_RowTools, plg_LastUsedKeys, plg_SaveAndNew';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'paramId';
    
    
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
    var $fetchFieldsBeforeDelete = 'id, productId, paramId, classId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('classId', 'class(interface=cat_ProductAccRegIntf)', 'input=hidden,silent');
    	$this->FLD('productId', 'int', 'input=hidden,silent');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=name)', 'input,caption=Параметър,mandatory,silent');
        $this->FLD('paramValue', 'varchar(255)', 'input,caption=Стойност,mandatory');
        
        $this->setDbUnique('classId,productId,paramId');
    }
    
    
    /**
     * Кой е мастър класа
     */
    public function getMasterMvc_($rec)
    {
    	$masterMvc = cls::get($rec->classId);
    		
    	return $masterMvc;
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
    	if($paramRec = cat_Params::fetch($rec->paramId)){
	    	if($paramRec->type != 'enum'){
	           $Type = cls::get(cat_Params::$typeMap[$paramRec->type]);
	           $row->paramValue = $Type->toVerbal($rec->paramValue);
	        }
	        
	        if($paramRec->type != 'percent'){
	           $row->paramValue .=  ' ' . cat_Params::getVerbal($paramRec, 'suffix');
	        }
    	}
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    { 
        $form = &$data->form;
        $masterTitle = cls::get($form->rec->classId)->getTitleById($form->rec->productId);
        
    	if(!$form->rec->id){
    		$form->title = "Добавяне на параметър към|* <b>{$masterTitle}</b>";
    		$form->setField('paramId', array('removeAndRefreshForm' => "paramValue|paramValue[lP]|paramValue[rP]"));
	    	expect($productId = $form->rec->productId);
			$options = self::getRemainingOptions($productId, $form->rec->classId, $form->rec->id);
			expect(count($options));
	        
	        if(!$data->form->rec->id){
	        	$options = array('' => '') + $options;
	        }
	        $form->setOptions('paramId', $options);
    	} else {
    		$form->title = "Редактиране на параметър към|* <b>{$masterTitle}</b>";
    		$form->setReadOnly('paramId');
    	}
    	
        if($form->rec->paramId){
        	$form->fields['paramValue']->type = cat_Params::getParamTypeClass($form->rec->paramId, 'cat_Params');
        } else {
        	$form->setField('paramValue', 'input=hidden');
        }
    }

    
    /**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     */
    public static function getRemainingOptions($productId, $classId, $id = NULL)
    {
        $options = cat_Params::makeArray4Select();
        
        if(count($options)) {
            $query = self::getQuery();
            
            if($id) {
                $query->where("#id != {$id}");
            }
			
            while($rec = $query->fetch("#productId = {$productId} AND #classId = {$classId}")) {
               unset($options[$rec->paramId]);
            }
        } else {
            $options = array();
        }

        return $options;
    }
    
    
    /**
     * Връща стойноста на даден параметър за даден продукт по негово sysId
     * @param int $productId - ид на продукт
     * @param int $sysId - sysId на параметъра
     * @return varchar $value - стойността на параметъра
     */
    public static function fetchParamValue($productId, $classId, $sysId)
    {
     	if($paramId = cat_Params::fetchIdBySysId($sysId)){
     		$paramValue = static::fetchField("#productId = {$productId} AND #paramId = {$paramId} AND #classId= {$classId}", 'paramValue');
     		
     		// Ако има записана конкретна стойност за този продукт връщаме я
     		if($paramValue) return $paramValue;
     		
     		// Връщаме дефолт стойността за параметъра
     		return cat_Params::getDefault($paramId);
     	}
     	
     	return NULL;
    }
    
    
    /**
     * Рендиране на общия изглед за 'List'
     */
    public static function renderDetail($data)
    {
        $tpl = getTplFromFile('cat/tpl/products/Params.shtml');
        $tpl->replace(get_called_class(), 'DetailName');
        $tpl->append(tr('Параметри'), 'TITLE');
        
        if($data->noChange !== TRUE){
        	$tpl->append($data->changeBtn, 'TITLE');
        }
        
        foreach((array)$data->params as $row) {
        	if($data->noChange === TRUE){
        		unset($row->tools);
        	}
        	
            $block = clone $tpl->getBlock('param');
            $block->placeObject($row);
            $block->append2Master();
        }
      
        if(!count($data->params)){
        	$tpl->replace(tr('Няма записи'), 'NO_ROWS');
        }
        
        return $tpl;
    }
    

    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    public static function prepareParams(&$data)
    {
        $query = self::getQuery();
        $query->where("#classId = {$data->masterClassId} AND #productId = {$data->masterId}");
    	
        // Ако подготвяме за външен документ, да се показват само параметрите за външни документи
    	if($data->prepareForPublicDocument === TRUE){
    		$query->EXT('showInPublicDocuments', 'cat_Params', 'externalName=showInPublicDocuments,externalKey=paramId');
    		$query->where("#showInPublicDocuments = 'yes'");
    	}
        
        $Cls = cls::get(get_called_class());
        
    	while($rec = $query->fetch()){
    		$data->params[$rec->id] = $Cls->recToVerbal($rec);
    		
    		if(!self::haveRightFor('add', (object)array('productId' => $data->masterId, 'classId' => $data->masterClassId))) {
    			unset($data->params[$rec->id]->tools);
    		}
    	}
      	
        if(self::haveRightFor('add', (object)array('productId' => $data->masterId, 'classId' => $data->masterClassId))) {
            $data->addUrl = array(__CLASS__, 'add', 'productId' => $data->masterId, 'classId' => $data->masterClassId, 'ret_url' => TRUE);
        }
    }
    
    
	/**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
        if($requiredRoles == 'no_one') return;
    	
        if (($action == 'add' || $action == 'delete') && isset($rec->productId) && isset($rec->classId)) {
        	$pRec = cls::get($rec->classId)->fetch($rec->productId);
        	
        	// Ако няма оставащи параметри или състоянието е оттеглено, не може да се добавят параметри
        	if (!count($mvc::getRemainingOptions($rec->productId, $rec->classId))) {
                $requiredRoles = 'no_one';
            } elseif($pRec->innerClass != cat_GeneralProductDriver::getClassId()) {
            	
            	// Добавянето е разрешено само ако драйвера на артикула е универсалния артикул
            	$requiredRoles = 'no_one';
            }
            
            if($pRec->state != 'active' && $pRec->state != 'draft'){
            	$requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Рендира екстеншъна с параметри на продукт
     */
    public static function renderParams($data)
    {
        if($data->addUrl) {
            $data->changeBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " valign=bottom style='margin-left:5px;'>", $data->addUrl, FALSE, 'title=Добавяне на нов параметър');
        }

        return self::renderDetail($data);
    }
    
    
	/**
     * Добавяне на свойтвата към обекта
     */
    public static function getFeatures($classId, $objectId)
    {
    	$features = array();
    	$query = self::getQuery();
    	
    	$query->where("#productId = '{$objectId}' AND #classId = {$classId}");
    	$query->EXT('isFeature', 'cat_Params', 'externalName=isFeature,externalKey=paramId');
    	$query->where("#isFeature = 'yes'");
    	
    	while($rec = $query->fetch()){
    		$row = self::recToVerbal($rec, 'paramId,paramValue');
    		$features[$row->paramId] = $row->paramValue;
    	}
    	
    	return $features;
    }
    
    
	/**
     * След запис се обновяват свойствата на перата
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if(cat_Params::fetchField("#id='{$rec->paramId}'", 'isFeature') == 'yes'){
    		acc_Features::syncFeatures($rec->classId, $rec->productId);
    	}
    }
    
    
	/**
     * Преди изтриване се обновяват свойствата на перата
     */
    public static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
        	if(cat_Params::fetchField("#id = '{$rec->paramId}'", 'isFeature') == 'yes'){
        		acc_Features::syncFeatures($rec->classId, $rec->productId);
        	}
        }
    }
}