<?php



/**
 * Клас 'core_Type' - Прототип на класовете за типове
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Type extends core_BaseClass
{
    
    
	/**
	 * Параметрите на типа
	 *
	 * @var array
	 */
	public $params;
	
	
	/**
	 * Опциите на типа
	 *
	 * @var array
	 */
	public $options;
	
	
	/**
	 * Предложенията с опции на типа
	 *
	 * @var array
	 */
	public $suggestions;
	
	
	/**
	 * 
	 */
	public $tdClass = '';
	
	
	/**
	 * 
	 */
	public $error = '';
	
	
    /**
     * Конструктор. Дава възможност за инициализация
     */
    function __construct($params = array())
    {
        if(is_array($params) && count($params)) {
            $this->params = $params;
        }
    }
    
    
    /**
     * Премахваме HTML елементите при визуализацията на всички типове,
     * които не предефинират тази функция
     */
    function toVerbal_($value)
    {
        if ($value === NULL) return NULL;
        
        $value = self::escape($value);
        
        if ($this->params['truncate'] && mb_strlen($value) > $this->params['truncate']) {
            $value = mb_substr($value, 0, $this->params['truncate']);
            $value .= "...";
        }
        
        if ($this->params['wordwrap'] && strlen($value)) {
            $value = wordwrap($value, $this->params['wordwrap'], "<br />\n");
        }
        
        return $value;
    }
    
    
    /**
     * Ескейпване на HTML таговете
     */
    static function escape($value)
    {
        $value = str_replace(array("&", "<", '&amp;lt;', '&amp;amp;'), array('&amp;', '&lt;', '&lt;', '&amp;'), $value);
        
        return $value;
    }
    
    
    /**
     * Връща стойността по подразбиране за съответния тип
     */
    function defVal()
    {
        return isset($this->defaultValue) ? $this->defaultValue : NULL;
    }
    
    
    /**
     * Връща атрибутите на елемента TD необходими при таблично
     * представяне на стойността
     */
    function getTdClass()
    {
        return $this->tdClass;
    }
    
    
    /**
     * Добавяне атрибута към стринга
     * 
     * @param string $class
     */
    function appendTdClass($class)
    {
        $this->tdClass .= ($this->tdClass) ? ' ' . $class : $class;
    }
    
    
    /**
     * Този метод трябва да конвертира от вербално към вътрешно
     * представяне дадената стойност
     * 
     * @param mixed $verbalValue
     * 
     * @return mixed
     */
    function fromVerbal_($verbalValue)
    {
        return $verbalValue;
    }
    
    
    /**
     * Този метод трябва генерира хHTML код, който да представлява
     * полето за въвеждане на конкретния формат информация
     * 
     * @param string $name
     * @param string $value
     * @param array|NULL $attr
     * 
     * @return core_ET
     */
    function renderInput_($name, $value = '', &$attr = array())
    {
        $value = $this->toVerbal($value);
        
        return ht::createTextInput($name, $value, $attr);
    }
    
    
    /**
     * Връща размера на полето в базата данни
     */
    function getDbFieldSize()
    {
        setIfNot($size, $this->params['size'], $this->params[0], $this->dbFieldLen);
        
        return $size;
    }
    
    
    protected function _baseGetMysqlAttr()
    {
        $res = new stdClass();
        
        $res->size = $this->getDbFieldSize();
        
        $res->type = strtoupper($this->dbFieldType);
        
        // Ключовете на опциите на типа, са опциите в MySQL
        if(count($this->options)) {
            foreach($this->options as $key => $val) {
                $res->options[] = $key;
            }
        }
        
        if (is_array($this->params) && in_array('unsigned', array_map('strtolower', $this->params))) {
            $res->unsigned = TRUE;
        }
        
        if($this->params['collate']) {
            $res->collation = $this->params['collate'];
        } elseif($this->params['ci']) {
            $res->collation = 'ci';
        } elseif($this->collation) {
            $res->collation = $this->collation;
        }
        
        setIfNot($res->indexPrefix, $this->params['indexPrefix']);
        
        return $res;
    }

    
    /**
     * Връща атрибутите на MySQL полето
     */
    public function getMysqlAttr()
    {
        return $this->_baseGetMysqlAttr();
    }
    
    
    /**
     * Връща MySQL-ската стойност на стойността, така обезопасена,
     * че да може да участва в заявки
     */
    public function toMysql($value, $db, $notNull, $defValue)
    {
        if($value === NULL) {
            if(!$notNull) {
                $mysqlVal = 'NULL';
            } else {
                if($defValue === NULL) {
                    $mysqlVal = "''";
                } else {
                    $mysqlVal = "'" . $db->escape($defValue) . "'";
                }
            }
        } else {
            $mysqlVal = "'" . $db->escape($value) . "'";
        }
        
        return $mysqlVal;
    }
    
    
    /**
     * Въртрешно PHP представяне на произволна стойност, сериализирана в MySQL поле.
     * 
     * @param string $value
     * @return mixed stdClass, array, string, ...
     */
    public function fromMysql($value)
    {
        return $value;
    }
    
    
    /**
     * Проверява зададената стойност дали е допустима за този тип.
     * Стойността е във вътрешен формат (MySQL)
     * Връща масив с ключове 'warning', 'error' и 'value'.
     * Ако стойността е съмнителна 'warning' съдържа предупреждение
     * Ако стойността е невалидна 'error' съдържа съобщение за грешка
     * Ако стойността е валидна или съмнителна във 'value' може да се
     * съдържа 'нормализирана' стойност
     */
    function isValid($value)
    {
        if ($value !== NULL) {
            
            $res = array();
            
            // Проверка за максимална дължина
            $size = $this->getDbFieldSize();
            
            if ($size && mb_strlen($value) > $size) {
                $res['error'] = "Текстът е над допустимите|* {$size} |символа";
            }
            
            // Използваме валидираща функция, ако е зададена
            if (isset($this->params['valid'])) {
                cls::callFunctArr($this->params['valid'], array($value, &$res));
            }
            
            // Проверяваме дали отговаря на регулярен израз, ако е зададен
            if (!$res['error'] && isset($this->params['regexp'])) {
                if (!preg_match($this->params['regexp'], $value)) {
                    $res['error'] = 'Неправилен формат на данните';
                }
            }
            
            // Проверяваме дали не е под минималната стойност, ако е зададена
            if (!$res['error'] && isset($this->params['min'])) {
                if($value < $this->params['min']) {
                    $res['error'] = 'Под допустимото' . "|* - '" .
                    $this->toVerbal($this->params['min']) . "'";
                }
            }
            
            // Проверяваме дали е над недостижимия минимум, ако е зададен
            if (!$res['error'] && isset($this->params['Min'])) {
                if($value <= $this->params['Min']) {
                    $res['error'] = 'Не е над' . "|* - '" .
                    $this->toVerbal($this->params['Min']) . "'";
                }
            }
            
            // Проверяваме дали не е над максималната стойност, ако е зададена
            if (!$res['error'] && isset($this->params['max'])) {
                if($value > $this->params['max']) {
                    $res['error'] = 'Над допустимото' . "|* - '" .
                    $this->toVerbal($this->params['max']) . "'";
                }
            }
            
            // Проверяваме дали е под недостижимия максимум, ако е зададен
            if (!$res['error'] && isset($this->params['Max'])) {
                if($value >= $this->params['Max']) {
                    $res['error'] = 'Не е под' . "|* - '" .
                    $this->toVerbal($this->params['Max']) . "'";
                }
            }
            
            if($res['error']) {
            	$this->error = TRUE;
            }
           
            
            return $res;
        }
    }
    
    
    /**
     * Създава input поле или комбо-бокс
     */
    function createInput($name, $value, $attr)
    {   
        $this->setFieldWidth($attr);

    	setIfNot($attr['type'], 'text');
        if(count($this->suggestions)) {
            $tpl = ht::createCombo($name, $value, $attr, $this->suggestions);
        } else {
            $tpl = ht::createTextInput($name, $value, $attr);
        }
        
        return $tpl;
    }


    /**
     * Подреждане на предложенията по вътрешните им стойности и добавяне на $value
     * Приема се, че предложенията са зададени с вербални стойности
     */
    function fromVerbalSuggestions($value)
    {   
        if(is_array($this->suggestions) && count($this->suggestions)) {
            Mode::push('text', 'plain');
            
            if (isset($this->suggestions[''])) {
                $emptySuggestions = $this->suggestions[''];
                unset($this->suggestions['']);
            }
            

            if($this->error) {
                $opt[$this->fromVerbal($value)] = $value;
            } else {
                $opt[$value] = $this->toVerbal($value);
            }
            foreach($this->suggestions as $s) {
                $opt[$this->fromVerbal($s)] = $s;
            }
            ksort($opt);
            $this->suggestions = array();
            
            if (isset($emptySuggestions)) {
                $this->suggestions[''] = $emptySuggestions;
            }
            
            foreach($opt as $o => $s) {
                $v = $this->toVerbal_($o);
                if (!isset($v)) continue;
                $this->suggestions[$v] = $v;
            }
            Mode::pop('text');
        }
    }
    
    
    /**
     * Метод-фабрика за създаване на обекти-форматъри. Освен името на класа-тип
     * '$name' може да съдържа в скоби и параметри на форматъра, като size,syntax,max,min
     */
    static function getByName($name)
    {
        if (is_object($name) && cls::isSubclass($name, "core_Type")) {

            return $name;
        }
        
        $leftBracketPos = strpos($name, "(");
        
        if ($leftBracketPos > 0) {
            $typeName = substr($name, 0, $leftBracketPos);
        } else {
            $typeName = $name;
        }
        
        // Ако няма долна черта в името на типа - 
        // значи е базов тип и се намира в папката 'type'
        if (!strpos($typeName, '_')) {
            $typeName = 'type_' . ucfirst($typeName);
        }
        
        $p = array();
        $typeName = trim($typeName);
        
        // Ескейп на \( \) \, \" \=
        $fromArr = array("\\\\", "\\(", "\\)", "\\,", '\\"', "\\=", "\\'");
        $fromArr2 = array("\\", "(", ")", ",", '"', "=", "'");
        $toArr   = array('&aaa;', '&bbb;', '&ccc;', '&ddd;', '&eee;', '&fff;', '&ggg;');
        
        $name = str_replace($fromArr, $toArr, $name);

        if ($leftBracketPos > 0) {
            $rightBracketPos = strrpos($name, ")");
            
            if ($rightBracketPos > $leftBracketPos) {
                $params = substr($name, $leftBracketPos + 1,
                    $rightBracketPos - $leftBracketPos - 1);
                $params = explode(",", $params);
                
                foreach ($params as $index => $value) {
                    
                    $value = trim($value);
                    
                    if (strpos($value, "=") > 0) {
                        list($key, $val) = explode("=", $value);
                        $val = str_replace($toArr, $fromArr2, $val);
                        $p[trim($key)] = trim($val);
                    } else { 
                        $value = str_replace($toArr, $fromArr2, $value);
                        if (count($p) == 0 && is_numeric($value) && ($typeName != 'type_Enum')) {
                            $p[] = $value;
                        } else {
                            $p[$value] = $value;
                        }
                    }
                }
            } else {
                error("Грешка в описанието на типа", $name);
            }
        }
        
        if ($typeName == 'type_Enum') {
            return cls::get($typeName, array(
                    'options' => $p
                ));
        } elseif($typeName == 'type_Set') {
            return cls::get($typeName, array(
                    'suggestions' =>  $p
                ));
        } else {
            return cls::get($typeName, array(
                    'params' => $p
                ));
        }
    }
    
    
    /**
     * Преобразува различни променливи в стринг
     * 
     * @param mixed $o - Масив, обект, стринг, боолеан и др.
     * 
     * @return string $r - Параметъра преобразуван в стринг
     */
    static function mixedToString($o)
    {
        static $i = 0;

        $i++;

        if ($i > 4) {
            $i--;

            return "...";
        }

        $r = gettype($o);

        if (is_object($o)) {
            $r = get_class($o);
            $o = get_object_vars($o);
        }

        if (is_array($o)) {
            if ($r != 'array') {
                $openBracket = '{';
                $closeBracket = '}';
            } else {
                $openBracket = '[';
                $closeBracket = ']';
            }
            $r = "($r) {$openBracket}";

            if (count($o)) {
                
                // Променлива, с която отбелязваме, че обикаляме за първи път масива
                $firstTime = TRUE;
                
                // Обхождаме масива
                foreach ($o as $name => $value) {
                    
                    // Ако сме за първи път
                    if ($firstTime) {
                        
                        // Променяме стойността
                        $firstTime = FALSE;
                    } else {
                        
                        // Добавяме в началото
                        $r .= ", ";
                    }
                    
                    $r .= "|{$name}| : " . static::mixedToString($value);
                }
            }
            $r .= "{$closeBracket}";
        } elseif (is_string($o)) {
            $r = "($r) " . $o;
        } elseif (is_bool($o)) {
            $r = "($r) " . ($o ? 'TRUE' : 'FALSE');
        } else {
            $r = "($r) " . $o;
        }
        $i--;

        return $r;
    }

    /**
     * Определя и задава широчината на полето
     */
    function setFieldWidth(&$attr, $size = NULL, $options = NULL)
    {
        if($options === NULL) {
            $options = $this->options;
        }

        if(!$size && !$this->maxFieldSize && is_array($options)) {
            $this->maxFieldSize = 1;
            $i = 1;
            foreach($options as $opt) {
                if(is_object($opt)) {
                    $title = $opt->title;
                } else {
                    $title = $opt;
                }
                list($title,) = explode('||', $title);

                $this->maxFieldSize = max($this->maxFieldSize, mb_strlen($title));
                if($i++ > 100) break;
            }
            $this->maxFieldSize = max($this->maxFieldSize, mb_strlen($attr['placeholder']));
        }
 
        // Определяме размера на най-дългия възможен стринг, като най-дългата опция
        if(!$size && $this->maxFieldSize > 0) {
            $size = $this->maxFieldSize;
        }

        if(!$size && $this->params['size']) {
            $size =  $this->params['size'];
        }

        if(!$size && $this->params[0]) {
            $size =  $this->params[0];
        }

        if(is_array($this->options)) $size *= 1.1;
        
        if(!preg_match("/(w25|w50|w75|w100)/", $attr['class'])) {
            if($size > 0 && $size <= 13) {
                $wClass = 'w25';
            } elseif($size > 0 && $size <= 35) {
                $wClass = 'w50';
            } elseif($size > 0 && $size <= 55) {
                $wClass = 'w75';
            } else {
                $wClass = 'w100';
            }

            $attr['class'] .= ($attr['class'] ? ' ' : '') . $wClass;
        }
    }
}
