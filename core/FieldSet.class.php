<?php



/**
 * Клас 'core_FieldSet' - Абстрактен клас за работа с полета
 *
 *
 * @category  all
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_FieldSet extends core_BaseClass
{
    // Атрибути на полетата:
    //
    // kind - вид на полето 
    //    - FLD: физическо поле;
    //    - EXT: поле от друг модел; 
    //    - XPR: SQL изчислимо поле;
    //    - FNC: функционално поле.
    // 
    // type - тип на полето, име на типов клас
    //
    // name - име на полето, идентификатор до 64 символа 
    //
    // mvc  - модел, собственик на полето, идентификатор до 64 символа 
    //
    // caption - вербално наименование на полето
    // 
    // notNull - флаг, че полето не може да има NULL стойност. 
    //    По подразбиране този флаг е FALSE
    // 
    // value - стойност по подразбиране. По подразбиране тази стойност е
    //    NULL, ако полето може да бъде празно или съвпада с  defVal(), ако
    //    полето не може да бъде празно
    //
    // externalClass
    //
    // externalName
    //
    // expression
    //
    // dependFromFields
    //
    
    
    
    /**
     * Данни за полетата
     */
    var $fields = array();
    
    
    /**
     * Добавя поле в описанието на таблицата
     */
    function FLD($name, $type, $params = array(), $moreParams = array())
    {
        $fieldType = core_Type::getByName($type);

        $params = arr::make($params, TRUE);

        $value = ($params['notNull'] && !isset($params['value'])) ? $fieldType->defVal() : NULL;
        $this->setField($name, arr::combine(array(
                    'kind' => 'FLD',
                    'value' => $value,
                    'type' => $fieldType
                ), $params, $moreParams), TRUE);
    }
    
    
    /**
     * Добавя външно поле от друг MVC, което може да участва в релационни заявки
     */
    function EXT($name, $externalClass, $params = array(), $moreParams = array())
    {
        $mvc = cls::get($externalClass);
        $params = arr::combine($params, $moreParams);
        
        // Установяваме името на полето от външния модел
        setIfNot($params['externalName'], $name);
        
        if (!$params['externalKey']) {
            $key = strToLower($externalClass) . 'Id';
            
            if ($this->fields[$key]) {
                $params['externalKey'] = $key;
            } elseif (substr($externalClass, -1) == 's') {
                $key = strToLower(substr($externalClass, 0, strlen($externalClass) - 1)) . 'Id';
                
                if ($this->fields[$key]) {
                    $params['externalKey'] = $key;
                }
            }
        }
        
        $fieldType = $mvc->fields[$params['externalName']]->type;
        
        $this->setField($name, arr::combine(array(
                    'kind' => 'EXT',
                    'externalClass' => $externalClass,
                    'type' => $fieldType
                ), $params), TRUE);
    }
    
    
    /**
     * Добавя външно поле- mySQL израз, което може да участва в релационни заявки
     */
    function XPR($name, $type, $expr, $params = array(), $moreParams = array())
    {
        $fieldType = core_Type::getByName($type);
        $this->setField($name, arr::combine(array(
                    'kind' => 'XPR',
                    'type' => $fieldType,
                    'expression' => $expr
                ), $params, $moreParams), TRUE);
    }
    
    
    /**
     * Добавя поле - свойство, което може да участва във вътрешни изчисления
     * и входно-изходни операции, но не се записва в базата
     * За всяко едно такова поле в MVC класа трябва да се дефинират две функции:
     * ->readName($rec);
     * ->writeName(&$rec, $value);
     */
    function FNC($name, $type, $params = array(), $moreParams = array())
    {
        $fieldType = core_Type::getByName($type);
        $this->setField($name, arr::combine(array(
                    'kind' => 'FNC',
                    'type' => $fieldType
                ), $params, $moreParams), TRUE);
    }
    
    
    /**
     * Задава параметри на едно поле от модела.
     * Ако $forse е FALSE, тогава задаването на параметрите не се извършва,
     * ако те вече са зададени
     */
    function setField($names, $params, $newField = FALSE)
    {
        $params = arr::make($params, TRUE);
        $names = arr::make($names, TRUE);
        
        foreach ($names as $name => $caption) {
            
            if ($newField && isset($this->fields[$name])) {
                error("Дублирано име на поле", "'{$name}'");
            } elseif (!$newField && !isset($this->fields[$name])) {
                error("Несъществуващо поле", "'{$name}'", $this->fields);
            } elseif(!isset($this->fields[$name])) {
                $this->fields[$name] = new stdClass();
            }
            
            foreach ($params as $member => $value) {
                if($member)
                $this->fields[$name]->{$member} = $value;
            }
            
            $this->fields[$name]->caption = $this->fields[$name]->caption ? $this->fields[$name]->caption : $name;
            $this->fields[$name]->name = $name;
        }
    }
    
    
    /**
     * Създава ново поле
     */
    function newField($name, $params)
    {
        if (isset($this->fields[$name])) {
            error("Дублирано име на поле", "'{$name}'");
        }
        $this->fields[$name]->name = $name;
        $this->setField($name, $params);
    }
    
    
    /**
     * Вкарва  множество от стойности-предложения за дадено поле
     */
    function setSuggestions($name, $suggestions)
    {
        if(is_string($suggestions)) {
            $suggestions = arr::make($suggestions, TRUE);
        }
        $this->fields[$name]->type->suggestions = $suggestions;
    }
    
    
    /**
     * Вкарва данни за изброимо множество от стойности за дадено поле
     */
    function setOptions($name, $options)
    {
        $this->setField($name, array('options' => $options));
    }
    
    
    /**
     * Добавя опции в края
     */
    function appendOptions($name, $options)
    {
        if (count($options)) {
            foreach ($options as $key => $value) {
                $this->fields[$name]->options[$key] = $value;
            }
        }
    }
    
    
    /**
     * Добавя опции в началото
     */
    function prependOptions($name, $options)
    {
        if (count($this->fields[$name]->options)) {
            foreach ($this->fields[$name]->options as $key => $value) {
                $options[$key] = $value;
            }
        }
        $this->fields[$name]->options = $options;
    }
    
    
    /**
     * Ако има зададени връща масив със стойности
     * val => verbal за даденото поле
     */
    function getOptions($name)
    {
        return $this->fields[$name]->options;
    }
    
    
    /**
     * Връща структурата на посоченото поле. Ако полето
     * липсва, а $strict е истина, генерира се грешка
     */
    function getField($name, $strict = TRUE)
    {
        if($name{0} == '#') {
            $name = substr($name, 1);
        }
        
        if ($this->fields[$name]) {
            return $this->fields[$name];
        } else {
            if ($strict) {
                error("Липсващо поле", "'{$name}'" . ($strict ? ' (strict)' : ''));
            }
        }
    }
    
    
    /**
     * Връща масив с елементи име_на_поле => структура - описание
     * $where е условие, с PHP синтаксис, като имената на атрибутите на
     * полето са предхождани от #
     * $fieldsArr е масив от полета, върху който се прави избирането
     */
    function selectFields($where = "", $fieldsArr = NULL)
    {
        if ($fieldsArr) {
            $fArr = arr::make($fieldsArr, TRUE);
        } else {
            $fArr = $this->fields ? $this->fields : array();
        }
        
        $cond = str::prepareExpression($where, array(
                &$this,
                'makePhpFieldName'
            ));
        
        foreach ($fArr as $name => $caption) {
            if (!$where || eval("return $cond;")) {
                $res[$name] = $this->fields[$name];
            }
        }
        
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function makePhpFieldName($name)
    {
        return '$this->fields[$name]->' . $name;
    }
}