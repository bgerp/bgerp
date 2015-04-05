<?php

/**
 * Клас 'cond_ConditionsToCustomers'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class cond_ConditionsToCustomers extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Други условия';
    
    
    /**
     * Старо име на класа
     */
    var $oldClassName = 'salecond_ConditionsToCustomers';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Друго условие';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, cond_Wrapper';
    
    
    /**
     * Поле за показване лентата с инструменти
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да вижда списъчния изглед
     */
    var $canList = 'no_one';
    
    
    /**
     * Кой може да вижда списъчния изглед
     */
    var $canAdd = 'ceo,cond';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    var $fetchFieldsBeforeDelete = 'id, cClass, cId, conditionId';
    
    
    /**
     * Активен таб
     */
    var $currentTab = 'Параметри';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('cClass', 'class(interface=doc_ContragentDataIntf)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('cId', 'int', 'caption=Клиент->Обект,input=hidden,silent');
        $this->FLD('conditionId', 'key(mvc=cond_Parameters,select=name,allowEmpty)', 'input,caption=Условие,mandatory,silent');
        $this->FLD('value', 'varchar(255)', 'caption=Стойност, mandatory');
    }
    
    
    /**
     * Извиква се след подготовка на формата
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	
    	$form->setOptions("conditionId", static::getRemainingOptions($rec->cClass, $rec->cId));
    	if(!$rec->id){
    		$form->addAttr('conditionId', array('onchange' => "addCmdRefresh(this.form); document.forms['{$form->formAttr['id']}'].elements['value'].value ='';this.form.submit();"));
    	} else {
    		$form->setReadOnly('conditionId');
    	}
    	
    	if($rec->conditionId){
    		$condType = cond_Parameters::fetchField($rec->conditionId, 'type');
    		
    		if($condType == 'delCond'){
    			$form->fields['value']->type = cls::get('type_Key',array('params' => array('mvc' => 'cond_DeliveryTerms', 'select' => 'codeName', 'allowEmpty' => 'allowEmpty')));
    		} elseif($condType == 'payMethod'){
    			$form->fields['value']->type = cls::get('type_Key', array('params' => array('mvc' => 'cond_paymentMethods', 'select' => 'description', 'allowEmpty' => 'allowEmpty')));
    		} else {
    			$form->fields['value']->type = cat_Params::getParamTypeClass($form->rec->conditionId, 'cond_Parameters');
    		}
    	} else {
    		$form->setField('value', 'input=hidden');
    	}
    }
    
	
	/**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     */
    static function getRemainingOptions($cClass, $cId)
    {
        $options = cond_Parameters::makeArray4Select();
        if(count($options)) {
            $query = self::getQuery();

            while($rec = $query->fetch("#cClass = {$cClass} AND #cId = {$cId}")) {
               unset($options[$rec->conditionId]);
            }
        } else {
            $options = array();
        }

        return $options;
    }
    
    
    /**
     * Подготвя данните за екстеншъна с условия на офертата
     */
    public static function prepareCustomerSalecond(&$data)
    {
        expect($data->cClass = core_Classes::getId($data->masterMvc));
        expect($data->masterId);
        $query = static::getQuery();
        $query->where("#cClass = {$data->cClass} AND #cId = {$data->masterId}");
    	
        while($rec = $query->fetch()) {
        	
        	// Според параметарът, се променя вербалното представяне на стойността
            $data->recs[$rec->id] = $rec;
            $row = static::recToVerbal($rec);
            $data->rows[$rec->id] = $row; 
        }
        
    	if($data->masterMvc->haveRightFor('edit', $data->masterId) && static::haveRightFor('add')){
        	$img = sbf('img/16/add.png');
		    $addUrl = array('cond_ConditionsToCustomers', 'add', 'cClass' => $data->cClass, 'cId' => $data->masterId, 'ret_url' => TRUE);
		    $data->addBtn = ht::createLink('', $addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon addSalecond', 'title' => 'Добавяне на ново търговско условие')); 
        }
        
        $data->TabCaption = 'Условия';
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
    	$type = cond_Parameters::fetchField($rec->conditionId, 'type');
        if($type != 'enum' && $type != 'delCond' && $type != 'payMethod'){
        	try{
        		$Type = cls::get("type_{$type}");
        		$row->value = $Type->toVerbal($rec->value);
        	} catch(core_exception_Expect $e){
        		$row->value = "??????????????";
        	}
            
        } elseif($type == 'delCond'){
            $row->value = cond_DeliveryTerms::recToVerbal($rec->value, 'codeName')->codeName;
        } elseif($type == 'payMethod'){
            $row->value = cond_paymentMethods::getTitleById($rec->value);
        }
    }
    
    
    /**
     * Рендира екстеншъна с условия на офертата
     */
    public static function renderCustomerSalecond($data)
    {
      	$tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->append(tr('Търговски условия'), 'title');
        
        if(isset($data->addBtn)){
        	$tpl->append($data->addBtn, 'title');
        }
        
	    if(count($data->rows)) {
			foreach($data->rows as $id => $row) {
				$tpl->append("<div style='white-space:normal;font-size:0.9em;'>", 'content');
				$tpl->append($row->conditionId . " - " . $row->value . "<span style='position:relative;top:4px'> &nbsp;" . $row->tools . "</span>", 'content');
				$tpl->append("</div>", 'content');
				
			}
	    } else {
	    	$tpl->append(tr("Все още няма условия"), 'content');
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
    	expect(cls::haveInterface('doc_ContragentDataIntf', $cClass));
    	
    	$query = static::getQuery();
    	$query->where("#cClass = {$cClass}");
    	$query->where("#cId = {$cId}");
    	if($conditionId){
    		$query->where("#conditionId = {$conditionId}");
    		return $query->fetch()->value;
    	} else {
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
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
       if ($action == 'add' && isset($rec) && (empty($rec->cClass) || empty($rec->cId))) {
        	$res = 'no_one';
       }
       
       if(($action == 'edit' || $action == 'delete') && isset($rec)){
       		
       		$cState = cls::get($rec->cClass)->fetchField($rec->cId, 'state');
       		if($cState == 'rejected'){
       			$res = 'no_one';
       		}
       }
    }
    
    
    /**
     * Добавяне на свойтвата към обекта
     */
    public function getFeatures($class, $objectId, $features)
    {
    	$classId = cls::get($class)->getClassId();
    	$query = $this->getQuery();
    	
    	$query->where("#cClass = '{$classId}' AND #cId = '{$objectId}'");
    	$query->EXT('isFeature', 'cond_Parameters', 'externalName=isFeature,externalKey=conditionId');
    	$query->where("#isFeature = 'yes'");
    	
    	while($rec = $query->fetch()){
    		$row = $this->recToVerbal($rec, 'conditionId,value');
    		$features[$row->conditionId] = $row->value;
    	}
    	
    	return $features;
    }
    
    
	/**
     * След запис се обновяват свойствата на перата
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if(cond_Parameters::fetchField("#id='{$rec->conditionId}'", 'isFeature') == 'yes'){
    		acc_Features::syncFeatures($rec->cClass, $rec->cId);
    	}
    }
    
    
	/**
     * Преди изтриване се обновяват свойствата на перата
     */
    public static function on_AfterDelete($mvc, &$res, $query)
    {
        foreach ($query->getDeletedRecs() as $rec) {
        	if(cond_Parameters::fetchField("#id='{$rec->conditionId}'", 'isFeature') == 'yes'){
        		acc_Features::syncFeatures($rec->cClass, $rec->cId);
        	}
        }
    }
}