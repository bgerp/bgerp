<?php



/**
 * Клас  'type_Class' - Ключ към запис в мениджъра core_Classes
 *
 * Може да се избира по име на интерфейс
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class type_Class extends type_Key {
    
    
    /**
     * Инициализиране на типа
     */
    function init($params = array())
    {
        parent::init($params);
        
        $this->params['mvc'] = 'core_Classes';
        setIfNot($this->params['select'], 'name');
    }
    
    
    /**
     * Рендира INPUT-a
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        expect($this->params['mvc'], $this);
        
        $mvc = cls::get($this->params['mvc']);
        
        if(!$value) {
            $value = $attr['value'];
        }
        
        $interface = $this->params['interface'];
        
        $options = $mvc->getOptionsByInterface($interface, $this->params['select']);
        
        if($this->params['allowEmpty']) {
            $options = arr::combine(array(NULL => ''), $options);
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
        if(empty($value)) return NULL;
        
        $value = core_Classes::getId($value);
        
        $interface = $this->params['interface'];
        
        $mvc = cls::get($this->params['mvc']);
        
        $options = $mvc->getOptionsByInterface($interface, $this->params['select']);
        
        if(!$options[$value]) {
            $this->error = 'Несъществуващ клас';
            
            return FALSE;
        } else {
            
            return $value;
        }
    }
}