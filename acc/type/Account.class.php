<?php

class acc_type_Account extends type_Key
{
    const MAX_SUGGESTIONS = 1000;
    
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params)
    {
        $params['params']['mvc'] = 'acc_Accounts';
        
        setIfNot($params['params']['select'], 'title');
        setIfNot($params['params']['root'], '');
        setIfNot($params['params']['maxSuggestions'], self::MAX_SUGGESTIONS);
        
        parent::init($params);
    }
    
    
    
    /**
     * Подготвя опциите според зададените параметри.
     *
     * `$this->params['root']` е префикс, който трябва да имат номерата на всички сметки-опции
     */
    private function prepareOptions()
    {
        if (isset($this->options)) {
            return;
        }
        $mvc = cls::get($this->params['mvc']);
        $root = $this->params['root'];
        $select = $this->params['select'];
        
        $this->options = $mvc->makeArray4Select($select, array("#num LIKE '[#1#]%' AND state NOT IN ('closed')", $root));
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
        
        return parent::fromVerbal_($value);
    }
}