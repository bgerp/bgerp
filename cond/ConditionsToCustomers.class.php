<?php

/**
 * Клас 'cond_ConditionsToCustomers'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class cond_ConditionsToCustomers extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Търговски условия на клиенти';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Търговско условие';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, crm_Wrapper, plg_SaveAndNew';
    
    
    /**
     * Кой може да вижда списъчния изглед
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'powerUser';
    
    
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
    public $fetchFieldsBeforeDelete = 'id, cClass, cId, conditionId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'cId=Контрагент, conditionId, value';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('cClass', 'class(interface=crm_ContragentAccRegIntf)', 'caption=Контрагент->Клас,input=hidden,silent');
        $this->FLD('cId', 'int', 'caption=Контрагент->Обект,input=hidden,silent,tdClass=leftCol');
        $this->FLD('conditionId', 'key(mvc=cond_Parameters,select=typeExt,allowEmpty)', 'input,caption=Условие,mandatory,silent,removeAndRefreshForm=value');
        $this->FLD('value', 'text', 'caption=Стойност, mandatory');
    
        // Добавяне на уникални индекси
        $this->setDbUnique('cClass,cId,conditionId');
        $this->setDbIndex('cId');
    }
    
    
    /**
     * Извиква се след подготовка на формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$tab = ($rec->cClass == crm_Companies::getClassId()) ? 'Фирми' : 'Лица';
    	$mvc->currentTab = $tab;
    	
    	if(!$form->rec->id){
    		$options = static::getRemainingOptions($rec->cClass, $rec->cId);
    		$form->setOptions("conditionId", array('' => '') + $options);
    		if(count($options) == 1){
    			$form->setDefault('conditionId', key($options));
    			$form->setReadOnly('conditionId');
    		}
    		$form->conditionOptions = $options;
    	} else {
    		$form->setReadOnly('conditionId');
    	}
    	
    	if($form->rec->conditionId){
    		if($Type = cond_Parameters::getTypeInstance($rec->conditionId, $rec->cClass, $rec->cId, $rec->value)){
    			$form->setField('value', 'input');
    			$form->setFieldType('value', $Type);
    		} else {
        		$form->setError('conditionId', 'Има проблем при зареждането на типа');
        	}
        } else {
        	$form->setField('value', 'input=none');
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	$data->form->title = core_Detail::getEditTitle($rec->cClass, $rec->cId, $mvc->singleTitle, $rec->id, 'за');
    	
    	// Маха се бутона запис и нов, ако е само едно търговското условие
    	if(count($data->form->conditionOptions) <= 1){
    		$data->form->toolbar->removeBtn('saveAndNew');
    	}
    }
    
    
	/**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     */
    protected static function getRemainingOptions($cClass, $cId)
    {
        $query = self::getQuery();
        $query->where("#cClass = {$cClass} AND #cId = {$cId}");
    	$ids = array_map(create_function('$o', 'return $o->conditionId;'), $query->fetchAll());
    	
    	$where = "";
    	if(count($ids)){
    		$ids = array_combine($ids, $ids);
    		$ids = implode(',', $ids);
    		$where = "#id NOT IN ({$ids})";
    	}
    	
    	$options = cond_Parameters::makeArray4Select(NULL, $where);
    	
        return $options;
    }
    
    
    /**
     * Подготвя данните за екстеншъна с условия на офертата
     */
    public function prepareCustomerSalecond(&$data)
    {
    	$data->recs = $data->rows = array();
    	expect($data->cClass = core_Classes::getId($data->masterMvc));
        expect($data->masterId);
        $cData = $data->masterMvc->getContragentData($data->masterId);
        
        $query = static::getQuery();
        $query->EXT('group', 'cond_Parameters', 'externalName=group,externalKey=conditionId');
        $query->EXT('order', 'cond_Parameters', 'externalName=order,externalKey=conditionId');
        $query->where("#cClass = {$data->cClass} AND #cId = {$data->masterId}");
		$query->orderBy('id', 'ASC');
        
        while($rec = $query->fetch()) {
        	
        	// Според параметарът, се променя вербалното представяне на стойността
            $data->recs[$rec->conditionId] = $rec;
            $row = static::recToVerbal($rec);
            core_RowToolbar::createIfNotExists($row->_rowTools);
            
            $data->rows[$rec->conditionId] = $row; 
        }
       
        $defQuery = cond_Countries::getQuery();
        $defQuery->where("#country = '{$cData->countryId}' OR #country IS NULL");
        $defQuery->EXT('group', 'cond_Parameters', 'externalName=group,externalKey=conditionId');
        $defQuery->EXT('order', 'cond_Parameters', 'externalName=order,externalKey=conditionId');
        $defQuery->show('conditionId,value,group,order');
        $defQuery->orderBy('country', 'DESC');
        
        $conditionsArr = array_keys($data->recs);
        if(count($conditionsArr)){
        	$defQuery->notIn("conditionId", $conditionsArr);
        }
        
        while($dRec = $defQuery->fetch()){
        	if(!array_key_exists($dRec->conditionId, $data->recs)){
        		$data->recs[$dRec->conditionId] = $dRec;
        		$dRow = cond_Countries::recToVerbal($dRec);
        		
        		
        		$dRow->value = ht::createHint($dRow->value, "Стойноста е дефолтна за контрагентите от|* \"{$cData->country}\"", 'notice', TRUE, 'width=12px,height=12px');
        		unset($dRow->_rowTools);
        		
        		$data->rows[$dRec->conditionId] = $dRow;
        	}
        }
        
        // Сортиране на записите
        usort($data->rows, function($a, $b) {
        	if($a->group == $b->group){
        		if($a->order == $b->order){
        			return ($a->id < $b->id) ? -1 : 1;
        		}
        		return (strcasecmp($a->order, $b->order) < 0) ? -1 : 1;
        	}
        	return (strcasecmp($a->group, $b->group) < 0) ? -1 : 1;
        });
        
    	if($data->masterMvc->haveRightFor('edit', $data->masterId) && static::haveRightFor('add', (object)array('cClass' => $data->cClass, 'cId' => $data->masterId))){
		    $addUrl = array('cond_ConditionsToCustomers', 'add', 'cClass' => $data->cClass, 'cId' => $data->masterId, 'ret_url' => TRUE);
		    $data->addBtn = ht::createLink('', $addUrl, NULL, array("ef_icon" => 'img/16/add.png', 'class' => 'addSalecond', 'title' => 'Добавяне на ново търговско условие')); 
        }
	}
    

	/**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$paramRec = cond_Parameters::fetch($rec->conditionId);
    	$paramRec->name = tr($paramRec->name);
    	$row->conditionId = cond_Parameters::getVerbal($paramRec, 'name');
    	
    	if(!empty($paramRec->group)){
    		$paramRec->group = tr($paramRec->group);
    		$row->group = cond_Parameters::getVerbal($paramRec, 'group');
    	}
    	
    	$row->value = cond_Parameters::toVerbal($paramRec, $rec->cClass, $rec->cId, $rec->value);
    	$row->value = cond_Parameters::limitValue($paramRec->driverClass, $row->value);
    	
    	if(!empty($paramRec->suffix)){
    		$row->value .= " " . cls::get('type_Varchar')->toVerbal(tr($paramRec->suffix));
    	}
    	
    	$row->cId = cls::get($rec->cClass)->getHyperLink($rec->cId, TRUE);
    	
    	if(isset($fields['-list'])){
    		$row->ROW_ATTR['class'] .= " state-active";
    	}
    }
    
    
    /**
     * Рендира екстеншъна с условия на офертата
     */
    public function renderCustomerSalecond($data)
    {
      	$tpl = new core_ET("");
        $tpl->append(tr('Търговски условия'), 'condTitle');
        
        if(isset($data->addBtn)){
        	$tpl->append($data->addBtn, 'condTitle');
        }
      
	    if(count($data->rows)) {
	    	foreach($data->rows as $id => &$row) {
	    		if(is_object($row->_rowTools)){
	    			$row->tools = $row->_rowTools->renderHtml();
	    		}
	    	}

	    	$tpl->append(static::renderParamBlock($data->rows));
	    } else {
	    	$tpl->append(tr("Все още няма условия"));
	    }
	    
	    return $tpl;
    }
    
    
    /**
     * Рендира блок с параметри за артикули
     *
     * @param array $paramArr
     * @return core_ET $tpl
     */
    public static function renderParamBlock($paramArr)
    {
    	$tpl = getTplFromFile('cond/tpl/ConditionsToCustomers.shtml');
    	$lastGroupId = NULL;
    	
    	if(is_array($paramArr)){
    		foreach($paramArr as &$row2) {
    			 
    			$block = clone $tpl->getBlock('PARAM_GROUP_ROW');
    			if($row2->group != $lastGroupId){
    				$block->replace($row2->group, 'group');
    			}
    			$lastGroupId = $row2->group;
    			unset($row2->group);
    			$block->placeObject($row2);
    			$block->removeBlocks();
    			$block->removePlaces();
    			$tpl->append($block, 'ROWS');
    		}
    	}
    
    	return $tpl;
    }
    
    
    /**
     * Връща условие на даден контрагент или всички негови условия
     * @param int $cClass - ид на клас на контрагент
     * @param int $cId - ид на контрагент
     * @param $conditionId = NULL - ако е зададено връща стойността
     * на параметъра, ако не масив от всички условия за клиента
     * @return string/array
     */
    public static function fetchByCustomer($cClass, $cId, $conditionId = NULL)
    {
    	$Class = cls::get($cClass);
    	
    	$query = static::getQuery();
    	$query->where("#cClass = {$Class->getClassId()}");
    	$query->where("#cId = {$cId}");
    	if($conditionId){
    		$query->where("#conditionId = {$conditionId}");
    		$query->show('value');
    		return $query->fetch()->value;
    	} else {
    		$query->show('conditionId,value');
    		$recs = array();
    		while($rec = $query->fetch()){
    			$recs[$rec->conditionId] = $rec->value;
    		}
    		return $recs;
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
       if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
       		if(empty($rec->cClass) || empty($rec->cId)){
       			$res = 'no_one';
       		} elseif(!cls::get($rec->cClass)->haveRightFor('edit', $rec->cId)){
       			$res = 'no_one';
       		} else{
       			if(!haveRole('sales,purchase,ceo')){
       				$res = 'no_one';
       			}
       		}
       }
       
       if($action == 'add' && isset($rec->cClass) && isset($rec->cId)){
       		if($res != 'no_one'){
       			if (!count($mvc::getRemainingOptions($rec->cClass, $rec->cId))) {
       				$res = 'no_one';
       			}
       		}
       }
       
       // Ако има указани роли за параметъра, потребителя трябва да ги има за редакция/изтриване
       if(($action == 'edit' || $action == 'delete') && $res != 'no_one' && isset($rec)){
       		$roles = cond_Parameters::fetchField($rec->conditionId, 'roles');
       		if(!empty($roles) && !haveRole($roles, $userId)){
       			$res = 'no_one';
       		}
       }
    }
    
    
    /**
     * Добавяне на свойтвата към обекта
     */
    public static function getFeatures($class, $objectId, $features)
    {
    	$classId = cls::get($class)->getClassId();
    	$query = static::getQuery();
    	
    	$query->where("#cClass = '{$classId}' AND #cId = '{$objectId}'");
    	$query->EXT('isFeature', 'cond_Parameters', 'externalName=isFeature,externalKey=conditionId');
    	$query->where("#isFeature = 'yes'");
    	
    	while($rec = $query->fetch()){
    		$row = static::recToVerbal($rec, 'conditionId,value');
    		$features[$row->conditionId] = $row->value;
    	}
    	
    	return $features;
    }
    
    
	/**
     * След запис се обновяват свойствата на перата
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if(cond_Parameters::fetchField("#id='{$rec->conditionId}'", 'isFeature') == 'yes'){
    		acc_Features::syncFeatures($rec->cClass, $rec->cId);
    	}
    }
    
    
	/**
     * Преди изтриване се обновяват свойствата на перата
     */
    protected static function on_AfterDelete($mvc, &$res, $query)
    {
        foreach ($query->getDeletedRecs() as $rec) {
        	if(cond_Parameters::fetchField("#id='{$rec->conditionId}'", 'isFeature') == 'yes'){
        		acc_Features::syncFeatures($rec->cClass, $rec->cId);
        	}
        }
    }
    
    
    /**
     * Форсира(ако няма създава, ако има го обновява) търговско условие към клиент
     * 
     * @param mixed $class     - клас на контрагента
     * @param int $objectId    - ид на контрагента
     * @param int $conditionId - ид на параметъра
     * @param mixed $value     - стойност на параметъра
     * @return int             - създадения/обновения запис
     */
    public static function force($class, $objectId, $conditionId, $value)
    {
    	expect($Class = cls::get($class));
    	expect(cls::haveInterface('crm_ContragentAccRegIntf', $Class));
    	expect($pRec = cond_Parameters::fetch($conditionId));
    	$Type = cond_Parameters::getTypeInstance($pRec, $class, $objectId, $value);
    	expect($value = $Type->fromVerbal($value));
    	
    	// Новия запис
    	$rec = (object)array('cClass' => $Class->getClassId(), 'cId' => $objectId, 'conditionId' => $conditionId, 'value' => $value);
    	
    	// Имали стар запис, ако има се обновява
    	$exRec = self::fetch("#cClass = {$rec->cClass} AND #cId = {$rec->cId} AND #conditionId = {$rec->conditionId}");
    	if(is_object($exRec)){
    		$rec->id = $exRec->id;
    	}
    	
    	// създаване/обновяване на записа
    	return self::save($rec);
    }
}