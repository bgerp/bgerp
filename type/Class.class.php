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
        if (is_numeric($value)) {
            $value = parent::toVerbal($value);
        } else {
            
            if(is_string($value)) {
                $valId = core_Classes::getId($value);
            }
            
            if($valId) {
                $value = parent::toVerbal($valId);
            } else {
                $value = '??????????';
            }
        }
        
        return $value;
    }
    
    
    /**
     * 
     * 
     * @param string|integer $value
     */
    function fromVerbal($value)
    {
        if (!isset($value)) return $value;
        
        $error = FALSE;
        
        $interface = $this->params['interface'];
        $mvc = cls::get($this->params['mvc']);
        $this->options = $mvc->getOptionsByInterface($interface, $this->params['select']);
        
        $classNameOptions = $mvc->getOptionsByInterface($interface, 'name');

        $value = parent::fromVerbal($value);
 
        // Възможно е $value да е името на класа
        if (is_numeric($value)) {
            if (!$this->options[$value]) {
                $error = TRUE;
            }
        } elseif (isset($value)) {
            
            $v = $value;

            if (!(($value = array_search($v, $this->options)) || ($value = array_search($v, $classNameOptions)) )) {
                $error = TRUE;
            }
        }
        
        if ($error) {
            $this->error = 'Несъществуващ клас';
        }
        
        return $value;
    }
    
    
    /**
     * 
     * 
     * @param string $value
     * 
     * @return object
     * 
     * @see type_Key::fetchVal()
     */
    protected function fetchVal(&$value)
    {
        if (is_numeric($value)) {
            $mvc = &cls::get($this->params['mvc']);
            $rec = $mvc->fetch((int)$value);
        } else {
            
            // Ако е подадено името на класа
            $rec = core_Classes::fetch(array("#name = '[#1#]'", $value));
        }
        
        return $rec;
    }
}
