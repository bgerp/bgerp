<?php



/**
 * Клас 'cat_products_Params' - продуктови параметри
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
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
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    public $tabName = 'cat_Products';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой може да листва
     */
    public $canList = 'ceo,cat';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'powerUser';
    
    
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
    	$this->FLD('classId', 'class', 'input=hidden,silent');
    	$this->FLD('productId', 'int', 'input=hidden,silent');
        $this->FLD('paramId', 'key(mvc=cat_Params,select=typeExt)', 'input,caption=Параметър,mandatory,silent');
        $this->FLD('paramValue', 'varchar(255)', 'input=none,caption=Стойност,mandatory');
        
        $this->setDbUnique('classId,productId,paramId');
        $this->setDbIndex('classId,productId');
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
    	$paramRec = cat_Params::fetch($rec->paramId);
    	$paramRec->name = tr($paramRec->name);
    	$row->paramId = cat_Params::getVerbal($paramRec, 'name');
    	if(!empty($paramRec->group)){
    		$paramRec->group = tr($paramRec->group);
    		$row->group = cat_Params::getVerbal($paramRec, 'group');
    	}
    	
    	$row->paramValue = cond_Parameters::toVerbal($paramRec, $rec->classId, $rec->productId, $rec->paramValue);
    	
    	if(!empty($paramRec->suffix)){
    		$suffix = cat_Params::getVerbal($paramRec, 'suffix');
    		$row->paramValue .=  ' ' . tr($suffix);
    	}
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    { 
        $form = &$data->form;
        $rec = $form->rec;
        
    	if(!$rec->id){
    		$form->setField('paramId', array('removeAndRefreshForm' => "paramValue|paramValue[lP]|paramValue[rP]"));
	    	$options = self::getRemainingOptions($rec->classId, $rec->productId, $rec->id);
	    	
			if(!count($options)){
				return followRetUrl(NULL, 'Няма параметри за добавяне', 'warning');
			}
	    	
	    	$form->setOptions('paramId', array('' => '') + $options);
			$form->paramOptions = $options;
			
			if(count($options) == 1){
				$form->setDefault('paramId', key($options));
				$form->setReadOnly('paramId');
			}
    	} else {
    		$form->setReadOnly('paramId');
    	}
    	
        if($rec->paramId){
        	if($Type = cat_Params::getTypeInstance($rec->paramId, $rec->classId, $rec->productId, $rec->paramValue)){
        		$form->setField('paramValue', 'input');
        		$form->setFieldType('paramValue', $Type);
        		
        		if(!empty($pRec->suffix)){
        			$suffix = cat_Params::getVerbal($pRec, 'suffix');
        			$form->setField('paramValue', "unit={$suffix}");
        		}
        	} else {
        		$form->setError('paramId', 'Има проблем при зареждането на типа');
        	}
        }
    }

    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	if(isset($rec->classId) && isset($rec->productId)){
    		$data->form->title = core_Detail::getEditTitle($rec->classId, $rec->productId, $mvc->singleTitle, $rec->id, $mvc->formTitlePreposition);
    	}
    
    	if(isset($data->form->paramOptions) && count($data->form->paramOptions) <= 1){
    		$data->form->toolbar->removeBtn('saveAndNew');
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
    	$query = self::getQuery();
    	$query->where("#classId = {$classId} AND #productId = {$productId}");
    	$ids = array_map(create_function('$o', 'return $o->paramId;'), $query->fetchAll());
    	
    	$where = "";
    	if(count($ids)){
    		$ids = array_combine($ids, $ids);
    		$ids = implode(',', $ids);
    		$where = "#id NOT IN ({$ids})";
    	}
    	
    	$options = cat_Params::makeArray4Select(NULL, $where);
		
        return $options;
    }
    
    
    /**
     * Връща стойноста на даден параметър за даден продукт по негово sysId
     * 
     * @param string $classId - ид на ембедъра
     * @param int $productId - ид на продукт
     * @param int $sysId - sysId на параметъра
     * @param boolean $verbal - вербално представяне
     * @return varchar $value - стойността на параметъра
     */
    public static function fetchParamValue($classId, $productId, $sysId, $verbal = FALSE)
    {
     	if($paramId = cat_Params::fetchIdBySysId($sysId)){
     		$paramValue = self::fetchField("#productId = {$productId} AND #paramId = {$paramId} AND #classId = {$classId}", 'paramValue');
     		
     		// Ако има записана конкретна стойност за този продукт връщаме я, иначе глобалния дефолт
     		$paramValue = ($paramValue) ? $paramValue : cat_Params::getDefault($paramId);
     		if($verbal === TRUE){
     			$ParamType = cat_Params::getTypeInstance($paramId, $classId, $productId, $paramValue);
     			$paramValue = $ParamType->toVerbal(trim($paramValue));
     		}
     		
     		return $paramValue;
     	}
     	
     	return NULL;
    }
    
    
    /**
     * Рендиране на общия изглед за 'List'
     */
    public static function renderDetail($data)
    {
        if(is_array($data->params)){
        	foreach($data->params as &$row) {
        		core_RowToolbar::createIfNotExists($row->_rowTools);
        		if($data->noChange !== TRUE && !Mode::isReadOnly()){
        			$row->tools = $row->_rowTools->renderHtml();
        		} else {
        			unset($row->tools);
        		}
        	}
        }
        
        $tpl = cat_Params::renderParamBlock($data->params);
        $tpl->replace(get_called_class(), 'DetailName');
        
        if($data->noChange !== TRUE){
        	$tpl->append($data->changeBtn, 'addParamBtn');
        }
        
        if(!$data->params){
        	$tpl->append("<i>" . tr('Няма') . "</i>", 'NO_ROWS');
        }
        $tpl->removeBlocks();
        
        return $tpl;
    }
    

    /**
     * Подготвя данните за екстеншъна с параметрите на продукта
     */
    public static function prepareParams(&$data)
    {
        $query = self::getQuery();
        $query->EXT('group', 'cat_Params', 'externalName=group,externalKey=paramId');
        $query->EXT('order', 'cat_Params', 'externalName=order,externalKey=paramId');
        $query->where("#productId = {$data->masterId}");
        $query->where("#classId = {$data->masterClassId}");
        $query->orderBy('group,order', 'ASC');
        
        // Ако подготвяме за външен документ, да се показват само параметрите за външни документи
    	if($data->documentType == 'public' || $data->documentType == 'invoice'){
    		$query->EXT('showInPublicDocuments', 'cat_Params', 'externalName=showInPublicDocuments,externalKey=paramId');
    		$query->where("#showInPublicDocuments = 'yes'");
    	}
        
    	while($rec = $query->fetch()){
    		$data->params[$rec->id] = static::recToVerbal($rec);
    	}
      	
        if(self::haveRightFor('add', (object)array('productId' => $data->masterId, 'classId' => $data->masterClassId))) {
            $data->addUrl = array(__CLASS__, 'add', 'productId' => $data->masterId, 'classId' => $data->masterClassId, 'ret_url' => TRUE);
        }
    }
    
    
	/**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако потрбителя няма достъп до сингъла на артикула, не може да модифицира параметрите
        if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
        	if(isset($rec->classId)){
        		if($rec->classId == planning_Tasks::getClassId()){
        			$requiredRoles = 'taskPlanning,ceo';
        		} elseif($rec->classId == marketing_Inquiries2::getClassId()){
        			$requiredRoles = 'marketing,ceo';
        		} elseif($rec->classId == cat_Products::getClassId()){
        			$requiredRoles = 'cat,ceo,catEdit,sales,purchase';
        			$isPublic = cat_Products::fetchField($rec->productId, 'isPublic');
        			if($isPublic == 'yes'){
        				$requiredRoles = 'cat,ceo';
        			}
        		}
        	}
        }
       
        if(isset($rec->productId) && isset($rec->classId)){
        	
        	if(isset($rec->classId)){
        		$pRec = cls::get($rec->classId)->fetch($rec->productId);
        		
        		if($action == 'add' && $rec->classId == cat_Products::getClassId()){
        			if($pRec->innerClass != cat_GeneralProductDriver::getClassId()) {
        			
        				// Добавянето е разрешено само ако драйвера на артикула е универсалния артикул
        				$requiredRoles = 'no_one';
        			}
        		}
        		
        		if($pRec->state != 'active' && $pRec->state != 'draft' && $pRec->state != 'template'){
        			$requiredRoles = 'no_one';
        		}
        		 
        		if(!cat_Products::haveRightFor('single', $rec->productId)){
        			$requiredRoles = 'no_one';
        		}
        	}
        }
        
        // Ако има указани роли за параметъра, потребителя трябва да ги има за редакция/изтриване
        if(($action == 'edit' || $action == 'delete') && $res != 'no_one' && isset($rec)){
        	$roles = cond_Parameters::fetchField($rec->paramId, 'roles');
        	if(!empty($roles) && !haveRole($roles, $userId)){
        		$res = 'no_one';
        	}
        }
    }
    
    
    /**
     * Рендира екстеншъна с параметри на продукт
     */
    public static function renderParams($data)
    {
        if($data->addUrl  && !Mode::isReadOnly()) {
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
    	$mvc->syncWithFeature($rec->paramId, $rec->productId);
    }
    
    
	/**
     * Преди изтриване се обновяват свойствата на перата
     */
    public static function on_AfterDelete($mvc, &$res, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
        	$mvc->syncWithFeature($rec->paramId, $rec->productId);
        }
    }
    
    
    /**
     * Синхронизира свойствата
     * 
     * @param int $paramId
     * @param int $productId
     * @return void
     */
    private function syncWithFeature($paramId, $productId)
    {
    	cat_Products::touchRec($productId);
    	if(cat_Params::fetchField("#id = '{$paramId}'", 'isFeature') == 'yes'){
    		acc_Features::syncFeatures(cat_Products::getClassId(), $productId);
    	}
    }
}