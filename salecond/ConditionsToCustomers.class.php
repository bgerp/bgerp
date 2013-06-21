<?php

/**
 * Клас 'salecond_ConditionsToCustomers'
 *
 *
 * @category  bgerp
 * @package   salecond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class salecond_ConditionsToCustomers extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Други условия';
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Друго условие';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools';
    
    
    /**
     * Поле за показване лентата с инструменти
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да вижда списъчния изглед
     */
    var $canList = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('cClass', 'class(interface=doc_ContragentDataIntf)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('cId', 'int', 'caption=Клиент->Обект,input=hidden,silent');
        $this->FLD('conditionId', 'key(mvc=salecond_Parameters,select=name,allowEmpty)', 'input,caption=Условие,mandatory');
        $this->FLD('value', 'varchar(255)', 'caption=Стойност');
    }
    
    
    /**
     * Извиква се след подготовка на формата
     */
    static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	/*$form = &$data->form;
    	$rec = &$form->rec;
    	
    	if(!$rec->id){
    		$form->addAttr('conditionId', array('onchange' => "addCmdRefresh(this.form); this.form.submit();"));
    	}
    	
    	if($form->cmd == 'refresh'){
    		$form->setField('value', 'input=hidden');
    	} */
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
    	/*if($form->cmd == 'refresh'){
    		$condRec = salecond_Parameters::fetch($form->rec->conditionId);
    		
    		if($condRec->options){
    			$vArr = explode(",", $condRec->options);
    			$options = array_combine($vArr, $vArr);
    			
	    		if($condRec->type == 'enum'){
	    			//$form->FNC('enumValues',  cls::get('type_Enum', array('options' => $options)), 'input,caption=Опции');
	    			$form->setField('value', 'disabled');
	    			$form->setOptions('value', $options);
	    		} else {
	    			$vArr = explode(',', $condRec->values);
	    			$form->setSuggestions('value', array_combine($vArr, $vArr));
	    		}
    		}
    	}*/
    	
        if($form->isSubmitted()) {
            static::isValueValid($form);
        }
    }
    

    /**
     * Подготвя данните за екстеншъна с условия на офертата
     */
    public static function prepareCustomerSalecond(&$data)
    {
        expect($data->cClass = core_Classes::fetchIdByName($data->masterMvc));
        expect($data->masterId);
        $query = static::getQuery();
        $query->where("#cClass = {$data->cClass} AND #cId = {$data->masterId}");
    	
        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = static::recToVerbal($rec);
        }
        
        $data->TabCaption = 'Условия';
	}
    

    /**
     * Рендира екстеншъна с условия на офертата
     */
    public static function renderCustomerSalecond($data)
    {
      	$tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        $tpl->append(tr('Условия на продажба'), 'title');
        
        $img = sbf('img/16/add.png');
	    $addUrl = array('salecond_ConditionsToCustomers', 'add', 'cClass' => $data->cClass, 'cId' => $data->masterId, 'ret_url' => TRUE);
	    $addBtn = ht::createLink(' ', $addUrl, NULL, array('style' => "background-image:url({$img})", 'class' => 'linkWithIcon addSalecond')); 
	    $tpl->append($addBtn, 'title');
        
	    if(count($data->rows)) {
			foreach($data->rows as $id => $row) {
				$tpl->append("<div style='white-space:normal;font-size:0.9em;'>", 'content');
				 $tpl->append($row->conditionId . " - " . $row->value . "<span style='position:relative;top:4px'>" . $row->tools . "</span>", 'content');
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
     * Проверка дали въведената стойност отговаря на типа
     * на параметъра
     * @param core_Form $form - формата
     */
    static function isValueValid(core_Form &$form)
    {
    	$rec = &$form->rec;
    	expect($paramType = salecond_Parameters::fetchField($rec->conditionId, 'type'));
            
        // взависимост от избрания параметър проверяваме дали 
        // стойността му е във валиден формат за неговия тип
        switch($paramType){
            case 'double':
            	if(!is_numeric($rec->value)){
            		$form->setError('value', "Невалидна стойност за параметър. Трябва да е число");
            	}
            	break;
            case 'int':
            	if(!ctype_digit($rec->value)){
            		$form->setError('value', "Невалидна стойност за параметър. Трябва да е цяло число");
            	}
            	break;
            case 'date':
            	$date = cls::get('type_Date');
            	if(!$date->fromVerbal($rec->value)){
            		$form->setError('value', "Невалидна стойност за параметър. Трябва да е валидна дата");
            	}
            	break;
            case 'enum':
            	break;
            }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
       if ($action == 'add' && (empty($rec->cClass) || empty($rec->cId))) {
        	$res = 'no_one';
        }
    }
}