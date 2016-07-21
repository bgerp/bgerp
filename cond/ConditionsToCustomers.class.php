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
    public $loadList = 'plg_RowTools2, crm_Wrapper';
    
    
    /**
     * Поле за показване лентата с инструменти
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Кой може да вижда списъчния изглед
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,cond';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,cond';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'ceo,cond';
    
    
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
        $this->FLD('conditionId', 'key(mvc=cond_Parameters,select=name,allowEmpty)', 'input,caption=Условие,mandatory,silent,removeAndRefreshForm=value');
        $this->FLD('value', 'varchar(255)', 'caption=Стойност, mandatory');
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
    		$form->setOptions("conditionId", static::getRemainingOptions($rec->cClass, $rec->cId));
    	} else {
    		$form->setReadOnly('conditionId');
    	}
    	
    	if($form->rec->conditionId){
        	if($Driver = cond_Parameters::getDriver($form->rec->conditionId)){
        		$form->setField('value', 'input');
        		$pRec = cond_Parameters::fetch($form->rec->conditionId);
        		if($Type = $Driver->getType($pRec)){
        			$form->setFieldType('value', $Type);
        		}
        	} else {
        		$form->setError('conditionId', 'Има проблем при зареждането на типа');
        	}
        }
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	$data->form->title = core_Detail::getEditTitle($rec->cClass, $rec->cId, $mvc->singleTitle, $rec->id, 'за');
    }
    
    
	/**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     */
    private static function getRemainingOptions($cClass, $cId)
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
    public function prepareCustomerSalecond(&$data)
    {
        expect($data->cClass = core_Classes::getId($data->masterMvc));
        expect($data->masterId);
        $query = static::getQuery();
        $query->where("#cClass = {$data->cClass} AND #cId = {$data->masterId}");
    	
        while($rec = $query->fetch()) {
        	
        	// Според параметарът, се променя вербалното представяне на стойността
            $data->recs[$rec->id] = $rec;
            $row = static::recToVerbal($rec);
            core_RowToolbar::createIfNotExists($row->_rowTools);
            
            $data->rows[$rec->id] = $row; 
        }
        
    	if($data->masterMvc->haveRightFor('edit', $data->masterId) && static::haveRightFor('add')){
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
    	
    	if($ParamType = cond_Parameters::getTypeInstance($paramRec)){
    		$row->value = $ParamType->toVerbal(trim($rec->value));
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
			foreach($data->rows as $id => $row) {
				$tpl->append("<div style='white-space:normal;font-size:0.9em;'>");
				$toolsHtml = $row->_rowTools->renderHtml();
				$tpl->append($row->conditionId . " - {$row->value}<span style='position:relative;top:4px'>{$toolsHtml}</span>");
				$tpl->append("</div>");
			}
	    } else {
	    	$tpl->append(tr("Все още няма условия"));
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
    	expect(cls::haveInterface('crm_ContragentAccRegIntf', $cClass));
    	
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
    protected static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
       if ($action == 'add' && isset($rec) && (empty($rec->cClass) || empty($rec->cId))) {
        	$res = 'no_one';
       }
       
       if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
       		
       		$cState = cls::get($rec->cClass)->fetchField($rec->cId, 'state');
       		if($cState == 'rejected'){
       			$res = 'no_one';
       		} else {
       			if(!cls::get($rec->cClass)->haveRightFor('single', $rec->cId)){
       				$res = 'no_one';
       			}
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
}