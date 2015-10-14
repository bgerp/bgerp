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

class cat_products_Params extends doc_Detail
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
    var $fetchFieldsBeforeDelete = 'id, productId, paramId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products)', 'input=hidden,silent');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=name)', 'input,caption=Параметър,mandatory,silent');
        $this->FLD('paramValue', 'varchar(255)', 'input,caption=Стойност,mandatory');
        
        $this->setDbUnique('productId,paramId');
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
        $masterTitle = cat_Products::getTitleById($form->rec->productId);
        
    	if(!$form->rec->id){
    		$form->title = "Добавяне на параметър към|* <b>{$masterTitle}</b>";
    		$form->setField('paramId', array('removeAndRefreshForm' => "paramValue|paramValue[lP]|paramValue[rP]"));
	    	expect($productId = $form->rec->productId);
			$options = self::getRemainingOptions($productId, $form->rec->id);
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
    public static function getRemainingOptions($productId, $id = NULL)
    {
        $options = cat_Params::makeArray4Select();
        
        if(count($options)) {
            $query = self::getQuery();
            
            if($id) {
                $query->where("#id != {$id}");
            }
			
            while($rec = $query->fetch("#productId = {$productId}")) {
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
     		$paramValue = self::fetchField("#productId = {$productId} AND #paramId = {$paramId}", 'paramValue');
     		
     		// Ако има записана конкретна стойност за този продукт връщаме я
     		if($paramValue) return $paramValue;
     		
     		// Връщаме дефолт стойността за параметъра
     		return cat_Params::getDefault($paramId);
     	}
     	
     	return FALSE;
    }
    
    
    /**
     * Рендиране на общия изглед за 'List'
     */
    public static function renderDetail($data)
    {
        $tpl = getTplFromFile('cat/tpl/products/Params.shtml');
        $tpl->replace(get_called_class(), 'DetailName');
        
        $title = tr('Параметри');
        if(cat_Params::haveRightFor('list') && $data->noChange !== TRUE){
        	$title = ht::createLink($title, array('cat_Params', 'list'));
        }
        
        $tpl->append($title, 'TITLE');
        
        if($data->noChange !== TRUE){
        	$tpl->append($data->changeBtn, 'TITLE');
        }
        
        foreach((array)$data->params as $row) {
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
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    public static function prepareParams(&$data)
    {
        $query = self::getQuery();
        $query->where("#productId = {$data->masterId}");
    	
        // Ако подготвяме за външен документ, да се показват само параметрите за външни документи
    	if($data->documentType === 'public'){
    		$query->EXT('showInPublicDocuments', 'cat_Params', 'externalName=showInPublicDocuments,externalKey=paramId');
    		$query->where("#showInPublicDocuments = 'yes'");
    	}
        
    	while($rec = $query->fetch()){
    		$data->params[$rec->id] = static::recToVerbal($rec);
    		
    		if(!self::haveRightFor('add', (object)array('productId' => $data->masterId))) {
    			unset($data->params[$rec->id]->tools);
    		}
    	}
      	
        if(self::haveRightFor('add', (object)array('productId' => $data->masterId))) {
            $data->addUrl = array(__CLASS__, 'add', 'productId' => $data->masterId, 'ret_url' => TRUE);
        }
    }
    
    
	/**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
        if($requiredRoles == 'no_one') return;
    	
        if (($action == 'add' || $action == 'delete') && isset($rec->productId)) {
        	$pRec = cat_Products::fetch($rec->productId);
        	
        	// Ако няма оставащи параметри или състоянието е оттеглено, не може да се добавят параметри
        	if (!count($mvc::getRemainingOptions($rec->productId))) {
                $requiredRoles = 'no_one';
            } elseif($pRec->innerClass != cat_GeneralProductDriver::getClassId()) {
            	
            	// Добавянето е разрешено само ако драйвера на артикула е универсалния артикул
            	$requiredRoles = 'no_one';
            }
            
            if($pRec->state != 'active' && $pRec->state != 'draft'){
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
    
    
    /**
     * Рендира екстеншъна с параметри на продукт
     */
    public static function renderParams($data)
    {
        if($data->addUrl) {
            $data->changeBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, FALSE, 'title=Добавяне на нов параметър');
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
    	$classId = cls::get($classId)->getClassId();
    	
    	$query->where("#productId = '{$objectId}'");
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
    		acc_Features::syncFeatures(cat_Products::getClassId(), $rec->productId);
    	}
    }
    
    
	/**
     * Преди изтриване се обновяват свойствата на перата
     */
    public static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
        	if(cat_Params::fetchField("#id = '{$rec->paramId}'", 'isFeature') == 'yes'){
        		acc_Features::syncFeatures(cat_Products::getClassId(), $rec->productId);
        	}
        }
    }
}