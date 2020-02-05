<?php


/**
 * Клас  'type_Classes' - Ключове към запис в мениджъра core_Classes
 *
 * Може да се избира по име на интерфейс и ограничава до наследниците на даден клас
 *
 *
 * @category  bgerp
 * @package   type
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class type_Classes extends type_Keylist
{
    /**
     * Инициализиране на типа
     */
    public function init($params = array())
    {
        parent::init($params);
        
        $this->params['mvc'] = 'core_Classes';
        setIfNot($this->params['select'], 'name');
    }
    
    
    /**
     * Подготвя масив с опции за показване в падащия списък
     */
    public function getSuggestions()
    {
        $this->suggestions = self::getOptionsByInterfaceAndParent($this->params['interface'], $this->params['select'], $this->params['parent'], $this->option);
        
        return $this->suggestions;
    }
    

    /**
     * Подготвя масив с опции за показване в падащия списък
     */
    public function prepareSuggestions($ids = null)
    {
        $this->suggestions = self::getOptionsByInterfaceAndParent($this->params['interface'], $this->params['select'], $this->params['parent'], $this->option);
        
        return $this->suggestions;
    }
    
    
    /**
     * Рендира INPUT-a
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if (!$value) {
            $value = $attr['value'];
        }
        
        if (!keylist::isKeylist($value)) {
            $value = $this->fromVerbal($value);
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Конвертира текстова или числова (id от core_Classes) стойност
     * за име на клас към вербална (текстова)
     */
    public function toVerbal($value)
    {
        $arr = keylist::fromArray($value);
        foreach($arr as $v) {
            if (is_numeric($value)) {
                $v = parent::toVerbal($v);
                
                if (strpos($v, '||') !== false) {
                    $v = tr($v);
                }
            }

            $res[] = $v;
        }

        $value = implode(', ', $value);

        return $value;;
    }
    
    
    /**
     *
     *
     * @param string|int $value
     */
    public function fromVerbal($value)
    {
        if (!isset($value)) {
            
            return $value;
        }
        
        $error = false;
        
        $options = self::getOptionsByInterfaceAndParent($this->params['interface'], $this->params['select'], $this->params['parent'], $this->option);
        
        $classNameOptions = core_Classes::getOptionsByInterface($interface, 'name');
        
        $value = parent::fromVerbal($value);
        
        $arr = explode(',', str_replace('|', ',', trim($value, '|')));
        $res = array();

        foreach($arr as $class) {
            $classId = core_Classes::getId($class, true);
            if(!$classId || !isset($options[$classId])) {
                $error = true;
                $errCls[] = $class;
                continue;
            }
            $res[$classId] = $classId;
        }
        
        if ($error) {
            $this->error = 'Несъществуващ клас|*: ' . implode(', ', $errCls);
        }

        $value = keylist::fromArray($res);
                
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
            $rec = $mvc->fetch((int) $value);
        } else {
            
            // Ако е подадено името на класа
            $rec = core_Classes::fetch(array("#name = '[#1#]'", $value));
        }
        
        return $rec;
    }


    /**
     * Връща масив от опции за даден интерфейс и евентуално родителски клас
     */
    public static function getOptionsByInterfaceAndParent($interface, $titleField, $parent = null, $exOptions = null)
    {
        // Извличаме опциите
        Mode::push('text', 'plain');
        $options = core_Classes::getOptionsByInterface($interface, $titleField);
        Mode::pop('text');
        
        $flagRaw = false;
        foreach($options as $id => $title) {
            if(is_object($title)) {
                $resOpt[$id] = $title;
                $flagRaw = true;
                continue;
            }
            if(strpos($title, '||')) {
                    $title = tr($title);
            } 
            if(is_array($exOptions) && count($exOptions) && !isset($exOptions[$id])) continue;
            if($parent && !cls::isSubclass($id, $parent)) continue;
            if(!$flagRaw) {
                list($group, $name) = explode('»', $title);
                $name = trim($name);
                
                $groupedRes[trim($group)][$id] = $name;
            }
            $resOpt[$id] = $title;
        }

        if(!$flagRaw) {
            ksort($groupedRes);
            $resOpt = array();
            foreach($groupedRes as $gName => $gArr) {
                if($gName) {
                    $resOpt[$gName] = (object) array(
                            'title' => $gName,
                            'group' => true,);
                    foreach($gArr as $id => $cName) {
                        $resOpt[$id] = $cName;
                    }
                }
            }
        }
 
        return $resOpt;
    }
}
