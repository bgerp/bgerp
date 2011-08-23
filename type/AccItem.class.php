<?php

class type_AccItem extends type_Key
{
	const MAX_SUGGESTIONS = 100;
	
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
    	expect($listNum = $this->params['listNum']);
    	
    	$mvc    = cls::get($this->params['mvc']);
    	$select = $this->params['select'];
    	
    	$this->options = $mvc->makeArray4Select($select, array("#lists LIKE '%|[#1#]|%'", $listNum));
    }
    

    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value="", $attr = array())
    {
		$this->prepareOptions();
    	
		return parent::renderInput_($name, $value, $attr);
    }
    

    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
	function fromVerbal_($value)
	{
		$this->prepareOptions();

		return parent::fromVerbal($value);
	}
}