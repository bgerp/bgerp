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
    public $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    public $title = 'Параметри';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Параметър';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Продукт №, paramId, paramValue';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools2, plg_LastUsedKeys, plg_SaveAndNew';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'paramId';
    
    
    /**
     * При колко линка в тулбара на реда да не се показва дропдауна
     * 
     * @param int
     * @see plg_RowTools2
     */
    public $rowToolsMinLinksToShow = 2;
    
    
    /**
     * Поле за пулт-а
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    public $tabName = 'cat_Products';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да листва
     */
    public $canList = 'ceo,cat';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,cat';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'ceo,cat';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    public $fetchFieldsBeforeDelete = 'id, productId, paramId';
    

    /**  
     * Предлог в формата за добавяне/редактиране  
     */  
    public $formTitlePreposition = 'на';  

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('classId', 'class(interface=cat_ProductAccRegIntf)', 'input=hidden,silent');
    	$this->FLD('productId', 'int', 'input=hidden,silent');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=name)', 'input,caption=Параметър,mandatory,silent');
        $this->FLD('paramValue', 'varchar(255)', 'input=none,caption=Стойност,mandatory');
        
        $this->setDbUnique('classId,productId,paramId');
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
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$paramRec = cat_Params::fetch($rec->paramId, 'driverClass,suffix');
    	
    	if($ParamType = cat_Params::getTypeInstance($paramRec)){
    		$row->paramValue = $ParamType->toVerbal(trim($rec->paramValue));
    	}
    	
    	if(!empty($paramRec->suffix)){
    		$row->paramValue .=  ' ' . cat_Params::getVerbal($paramRec, 'suffix');
    	}
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    { 
        $form = &$data->form;
        
    	if(!$form->rec->id){
    		$form->setField('paramId', array('removeAndRefreshForm' => "paramValue|paramValue[lP]|paramValue[rP]"));
	    	$options = self::getRemainingOptions($form->rec->classId, $form->rec->productId, $form->rec->id);
			
	        $form->setOptions('paramId', array('' => '') + $options);
    	} else {
    		$form->setReadOnly('paramId');
    	}
    	
        if($form->rec->paramId){
        	if($Driver = cat_Params::getDriver($form->rec->paramId)){
        		$form->setField('paramValue', 'input');
        		$pRec = cat_Params::fetch($form->rec->paramId);
        		if($Type = $Driver->getType($pRec)){
        			$form->setFieldType('paramValue', $Type);
        			
        			if(!empty($pRec->suffix)){
        				$suffix = cat_Params::getVerbal($pRec, 'suffix');
        				$form->setField('paramValue', "unit={$suffix}");
        			}
        		}
        	} else {
        		$form->setError('paramId', 'Има проблем при зареждането на типа');
        	}
        }
    }

    
    /**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     */
    public static function getRemainingOptions($classId, $productId, $id = NULL)
    {
        $options = cat_Params::makeArray4Select();
        
        if(count($options)) {
            $query = self::getQuery();
            $query->show('paramId');
            if($id) {
                $query->where("#id != {$id}");
            }
			
            while($rec = $query->fetch("#productId = {$productId} AND #classId = '{$classId}'")) {
               unset($options[$rec->paramId]);
            }
        } else {
            $options = array();
        }
		
        return $options;
    }
    
    
    /**
     * Връща стойноста на даден параметър за даден продукт по негово sysId
     * 
     * @param string $classId - ид на ембедъра
     * @param int $productId - ид на продукт
     * @param int $sysId - sysId на параметъра
     * @return varchar $value - стойността на параметъра
     */
    public static function fetchParamValue($classId, $productId, $sysId)
    {
     	if($paramId = cat_Params::fetchIdBySysId($sysId)){
     		$paramValue = self::fetchField("#productId = {$productId} AND #paramId = {$paramId} AND #classId = {$classId}", 'paramValue');
     		
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
        
        if($data->noChange !== TRUE){
        	$tpl->append($data->changeBtn, 'addParamBtn');
        }
        
        $mvc = cls::get(get_called_class());
        foreach((array)$data->params as $row) {
        	core_RowToolbar::createIfNotExists($row->_rowTools);
        	if($data->noChange !== TRUE){
        		$row->tools = $row->_rowTools->renderHtml($mvc->rowToolsMinLinksToShow);
        	} else {
        		unset($row->tools);
        	}
        	
            $block = clone $tpl->getBlock('param');
            $block->placeObject($row);
            $block->removeBlocks();
            $block->append2Master();
        }
        
        if(!$data->params){
        	$tpl->append("<i>" . tr('Няма') . "</i>", 'NO_ROWS');
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
        $query->where("#classId = {$data->masterClassId}");
        
        // Ако подготвяме за външен документ, да се показват само параметрите за външни документи
    	if($data->documentType === 'public'){
    		$query->EXT('showInPublicDocuments', 'cat_Params', 'externalName=showInPublicDocuments,externalKey=paramId');
    		$query->where("#showInPublicDocuments = 'yes'");
    	}
        
    	while($rec = $query->fetch()){
    		$data->params[$rec->id] = static::recToVerbal($rec);
    		
    		if(!self::haveRightFor('add', $rec)) {
    			unset($data->params[$rec->id]->tools);
    		}
    	}
      	
        if(self::haveRightFor('add', (object)array('productId' => $data->masterId))) {
            $data->addUrl = array(__CLASS__, 'add', 'productId' => $data->masterId, 'classId' => $data->masterClassId, 'ret_url' => TRUE);
        }
    }
    
    
	/**
     * След проверка на ролите
     */
    protected static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
        if($requiredRoles == 'no_one') return;
    	
        if (($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec->productId)) {
        	$pRec = cat_Products::fetch($rec->productId);
        	
        	// Ако няма оставащи параметри или състоянието е оттеглено, не може да се добавят параметри
        	if($action == 'add'){
        		if (!count($mvc::getRemainingOptions($rec->classId, $rec->productId))) {
        			$requiredRoles = 'no_one';
        		} elseif($pRec->innerClass != cat_GeneralProductDriver::getClassId()) {
        			 
        			// Добавянето е разрешено само ако драйвера на артикула е универсалния артикул
        			$requiredRoles = 'no_one';
        		}
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
        if($data->addUrl  && !Mode::is('text', 'xhtml') && !Mode::is('printing')) {
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
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	cat_Products::touchRec($rec->productId);
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
        	cat_Products::touchRec($rec->productId);
        	if(cat_Params::fetchField("#id = '{$rec->paramId}'", 'isFeature') == 'yes'){
        		acc_Features::syncFeatures(cat_Products::getClassId(), $rec->productId);
        	}
        }
    }
}