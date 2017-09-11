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


    /**
     * Подготвя масив с опции за показване в падащия списък
     */
    public function prepareOptions($value = NULL)
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
        
        if(count($this->options) > 1){
        	$optionsWithoutGroup = $newOptions = array();
        	
        	// За всяка опция
        	foreach ($this->options as $index => $opt){
        		if(!is_object($opt)){
        			
        			// Ако в името на класа има '->' то приемаме, че стринга преди знака е името на групата
        			$optArr = explode('»', $opt);
        			
        			// Ако стринга е разделен на точно две части (име на група и име на клас)
        			if(count($optArr) == 2){
        				
        				// Добавяме името като OPTGROUP
        				$newOptions[$optArr[0]] = (object)array(
        						'title' => trim($optArr[0]),
        						'group' => TRUE,
        				);
        				$newOptions[$index] = trim($optArr[1]);
        			} else {
        				
        				// Ако няма група запомняме го като такъв
        				$optionsWithoutGroup[$index] = $opt;
        			}
        		}
        	}
        	
        	// Ако има поне една намерена OPTGROUP на класовете, Иначе не правим нищо
        	if(count($newOptions)){
        		
        		// Ако все пак има класове без група, добавяме ги в началото на опциите
        		if(count($optionsWithoutGroup)){
        			$newOptions = $optionsWithoutGroup + $newOptions;
        		}
        		
        		// Заместваме старите опции със новите, ако има поне една OPTGROUP
        		$this->options = $newOptions;
        	}
        }
        
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
        	
        	if(strpos($value, '||') !== FALSE){
        		$value = tr($value);
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

        $savedOpt = $this->options;

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

        $this->options = $savedOpt;

        return $value;
    }
    
    
    /**
     * 
     * 
     * @param mixed $key
     * 
     * @return string
     */
    public function prepareKey($key)
    {
        // Позволените са латински букви, цифри и _ - \W
        $key = preg_replace('/[^A-Z0-9\_]/i', '', $key);
        
        return $key;
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
