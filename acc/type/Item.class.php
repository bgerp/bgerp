<?php

class acc_type_Item extends type_Key
{
	const MAX_SUGGESTIONS = 50;
	
	/**
     *  Инициализиране на обекта
     */
    function init($params)
    {
        $params['params']['mvc'] = 'acc_Items';

        setIfNot($params['params']['select'], 'title');
        setIfNot($params['params']['maxSuggestions'], self::MAX_SUGGESTIONS);
        
        parent::init($params);
    }
	

    /**
     * Подготвя опциите според зададените параметри.
     * 
     * `$this->params['root']` е префикс, който трябва да имат номерата на всички опции
     */
    private function prepareOptions()
    {
    	expect($lists = $this->params['lists'], $this);

    	$mvc    = cls::get($this->params['mvc']);
    	$select = $this->params['select'];

    	if (!is_array($lists)) {
    		$lists = explode('|', $lists);
    	}
    	
    	$this->options = array();
    	
    	$cleanQuery = $mvc->getQuery();
    	$cleanQuery->show("id, {$select}");
    	
    	// За всяка от зададените в `lists` номенклатури, извличаме заглавието и принадлежащите 
    	// й пера. Заглавието става <OPTGROUP> елемент, перата - <OPTION> елементи
    	foreach ($lists as $list) {
    		$byField = is_numeric($list) ? 'num' : 'systemId';
    		$listRec = acc_Lists::fetch(
    			array("#{$byField} = '[#1#]'", $list), 
    			'id, num, name, caption'
    		);
    		
    		// Създаваме <OPTGROUP> елемента (само ако листваме повече от една номенклатура)
    		if (count($lists) > 1) {
	    		$this->options["x{$listRec->id}"] = (object)array(
	    			'title' => $listRec->caption,
	    			'group' => TRUE,
//	    			'attr'  => array('class' => 'list'),
	    		);
    		}
    		
    		// Извличаме перата на текущата номенклатура
    		$query = clone($cleanQuery);
    		$query->where("#lists LIKE '%|{$listRec->id}|%'");
    		while ($itemRec = $query->fetch()) {
    			$this->options["{$itemRec->id}.{$listRec->id}"] = strip_tags($itemRec->{$select});
    		}
    	}
    }
    

    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value="", $attr = array())
    {
		$this->prepareOptions();
		
		foreach ($this->options as $key => $val) {
			if (!is_object($val) && intval($key) == $value) {
				$value = $key;
				break;
			}
		}
		
		return parent::renderInput_($name, $value, $attr);
    }
    

    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
	function fromVerbal_($value)
	{
		$this->prepareOptions();
		
		if ($result = parent::fromVerbal_($value)) {
			$result = intval($result);
		}
		
		return $result;
	}
}
