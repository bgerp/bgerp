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

class cat_products_Params extends cat_products_Detail
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
    var $canDelete = 'ceo,cat';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    var $fetchFieldsBeforeDelete = 'id, productId, paramId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=name,maxSuggestions=10000)', 'input,caption=Параметър,mandatory,silent');
        $this->FLD('paramValue', 'varchar(255)', 'input,caption=Стойност,mandatory');
        
        $this->setDbUnique('productId,paramId');
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
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        
    	if(!$form->rec->id){
    		$form->addAttr('paramId', array('onchange' => "addCmdRefresh(this.form);this.form.submit();"));
	    	expect($productId = $form->rec->productId);
			$options = self::getRemainingOptions($productId, $form->rec->id);
			expect(count($options));
	        
	        if(!$data->form->rec->id){
	        	$options = array('' => '') + $options;
	        }
	        $form->setOptions('paramId', $options);
    	} else {
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
    static function getRemainingOptions($productId, $id = NULL)
    {
        $options = cat_Params::makeArray4Select();
        
        if(count($options)) {
            $query = self::getQuery();
            
            if($id) {
                $query->where("#id != {$id}");
            }

            while($rec = $query->fetch("#productId = $productId")) {
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
    public static function fetchParamValue($productId, $sysId)
    {
     	if($paramId = cat_Params::fetchIdBySysId($sysId)){
     		$paramValue = static::fetchField("#productId = {$productId} AND #paramId = {$paramId}", 'paramValue');
     		
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
    function renderDetail_($data)
    {
        $tpl = getTplFromFile('cat/tpl/products/Params.shtml');
        $tpl->append($data->changeBtn, 'TITLE');
        
        foreach((array)$data->rows as $row) {
            $block = $tpl->getBlock('param');
            $block->placeObject($row);
            $block->append2Master();
        }
            
        return $tpl;
    }
    

    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    public function prepareParams($data)
    {
        $this->prepareDetail($data);
        
        if($this->haveRightFor('add', (object)array('productId' => $data->masterId)) && count(self::getRemainingOptions($data->masterId))) {
            $data->addUrl = array($this, 'add', 'productId' => $data->masterId, 'ret_url' => TRUE);
        }
    }
    
	/**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
        if($requiredRoles == 'no_one') return;
    	
        if ($action == 'add' && isset($rec->productId)) {
        	if (!count($mvc::getRemainingOptions($rec->productId))) {
                $requiredRoles = 'no_one';
            } 
        }
    }
    
    
    /**
     * Рендира екстеншъна с параметри на продукт
     */
    public function renderParams($data)
    {
        if($data->addUrl) {
            $data->changeBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " valign=bottom style='margin-left:5px;'>", $data->addUrl);
        }

        return  $this->renderDetail($data);
    }
    
    
	/**
     * Добавяне на свойтвата към обекта
     */
    public function getFeatures($class, $objectId, $features)
    {
    	$query = $this->getQuery();
    	
    	$query->where("#productId = '{$objectId}'");
    	$query->EXT('isFeature', 'cat_Params', 'externalName=isFeature,externalKey=paramId');
    	$query->where("#isFeature = 'yes'");
    	
    	while($rec = $query->fetch()){
    		$row = $this->recToVerbal($rec, 'paramId,paramValue');
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
    		acc_Features::syncFeatures($mvc->Master->getClassId(), $rec->productId);
    	}
    }
    
    
	/**
     * Преди изтриване се обновяват свойствата на перата
     */
    public static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
        	if(cat_Params::fetchField("#id='{$rec->paramId}'", 'isFeature') == 'yes'){
        		acc_Features::syncFeatures($mvc->Master->getClassId(), $rec->productId);
        	}
        }
    }
}