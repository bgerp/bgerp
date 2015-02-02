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
class type_Class  extends type_Key {
    
    
    /**
     * Инициализиране на типа
     */
    function init($params = array())
    {
        parent::init($params);
        
        $this->params['mvc'] = 'core_Classes';
        setIfNot($this->params['select'], 'name');
    }


    public function prepareOptions()
    {
        Mode::push('text', 'plain');
        
        expect($this->params['mvc'], $this);
        
        $mvc = cls::get($this->params['mvc']);
        
        $interface = $this->params['interface'];
        
        if(is_array($this->options)) {
            $options = $this->options;
        } else {
            $options = $mvc->getOptionsByInterface($interface, $this->params['select']);
        }
        
        Mode::pop('text');
        
        $this->options = $options;
        
        $this->options = parent::prepareOptions();
        
        return $this->options;
    }
    
    
    /**
     * Рендира INPUT-a
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        if(!$value) {
            $value = $attr['value'];
        }

        if(!is_numeric($value)) {
            $value = $this->fromVerbal($value);
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Конвертира текстова или числова (id от core_Classes) стойност
     * за име на клас към вербална (текстова)
     */
    function toVerbal($value)
    {
        if(is_numeric($value)) {
            $value = parent::toVerbal($value);
        }
        
        return $value;
    }
}