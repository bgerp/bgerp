<?php

/**
 * Клас  'type_Device' - Ключ към запис в мениджъра core_Devices
 *
 * може да се селектира по име на адаптер
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Device extends type_Key {
    
    
    /**
     *  Инициализиране на типа
     */
    function init($params)
    {
        parent::init($params);
        
        $this->params['mvc'] = 'core_Devices';
        setIfNot($this->params['select'], 'name');
    }
    
    
    /**
     * Рендира INPUT-a
     */
    function renderInput_($name, $value="", $attr = array())
    {
        expect($this->params['mvc'], $this);
        
        $mvc = cls::get($this->params['mvc']);
        
        if(!$value) {
            $value = $attr['value'];
        }
        
        $adapter = $this->params['adapter'];
        
        $options = $mvc->getOptionsByAdapter($adapter, $this->params['select']);
        
        if($this->params['allowEmpty']) {
            $options = arr::combine( array(NULL => ''), $options);
        }
        
        $tpl = ht::createSmartSelect($options, $name, $value, $attr,
        $this->params['maxRadio'],
        $this->params['maxColumns'],
        $this->params['columns']);
        
        return $tpl;
    }
    
    
    /**
     * Връща вътрешното представяне на вербалната стойност
     */
    function fromVerbal($value)
    {
        if(!$value) return NULL;
        
        $value = (int) $value;
        
        $adapter = $this->params['adapter'];
        
        $mvc = cls::get($this->params['mvc']);
        
        $options = $mvc->getOptionsByAdapter($adapter, $this->params['select']);
        
        if(!$options[$value]) {
            $this->error = 'Несъщесвуващо устройство';
            
            return FALSE;
        } else {
            
            return $value;
        }
    }
}