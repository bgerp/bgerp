<?php



/**
 * Клас 'core_FieldSet' - Абстрактен клас за работа с полета
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
    public $fields = array();
    
    
    /**
     * Добавя поле в описанието на таблицата
     *
     * @param string       $name
     * @param string       $type
     * @param string|array $params
     * @param array        $moreParams
     */
    public function FLD($name, $type, $params = array(), $moreParams = array())
    {
        $fieldType = core_Type::getByName($type);
        
        $params = arr::make($params, true);
        
        $value = (isset($params['notNull']) && !isset($params['value'])) ? $fieldType->defVal() : null;
        $this->setField($name, arr::combine(array(
                    'kind' => 'FLD',
                    'value' => $value,
                    'type' => $fieldType
                ), $params, $moreParams), true);
    }
    
    
    /**
     * Добавя външно поле от друг MVC, което може да участва в релационни заявки
     *
     * @param string       $name
     * @param string       $externalClass
     * @param string|array $params
     * @param array        $moreParams
     */
    public function EXT($name, $externalClass, $params = array(), $moreParams = array())
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
        
        expect(isset($fieldType));
        
        $this->setField($name, arr::combine(array(
                    'kind' => 'EXT',
                    'externalClass' => $externalClass,
                    'type' => $fieldType
                ), $params), true);
    }
    
    
    /**
     * Добавя външно поле- mySQL израз, което може да участва в релационни заявки
     *
     * @param string       $name
     * @param string       $type
     * @param string       $expr
     * @param string|array $params
     * @param array        $moreParams
     */
    public function XPR($name, $type, $expr, $params = array(), $moreParams = array())
    {
        $fieldType = core_Type::getByName($type);
        $this->setField($name, arr::combine(array(
                    'kind' => 'XPR',
                    'type' => $fieldType,
                    'expression' => $expr
                ), $params, $moreParams), true);
    }
    
    
    /**
     * Добавя поле - свойство, което може да участва във вътрешни изчисления
     * и входно-изходни операции, но не се записва в базата
     * За всяко едно такова поле в MVC класа трябва да се дефинират две функции:
     * ->readName($rec);
     * ->writeName(&$rec, $value);
     *
     * @param string       $name
     * @param string       $type
     * @param string|array $params
     * @param string|array $moreParams
     */
    public function FNC($name, $type, $params = array(), $moreParams = array())
    {
        $fieldType = core_Type::getByName($type);
        $this->setField($name, arr::combine(array(
                    'kind' => 'FNC',
                    'type' => $fieldType
                ), $params, $moreParams), true);
    }
    
    
    /**
     * Задава параметри на едно поле от модела.
     */
    public function setField($names, $params, $newField = false)
    {
        $params = arr::make($params, true);
        $names = arr::make($names, true);
        
        if ($params['after'] && !is_array($params['after'])) {
            $params['after'] = explode('|', $params['after']);
        }

        if (isset($params['before']) && !is_array($params['before'])) {
            $params['before'] = explode('|', $params['before']);
        }
        
        $paramsS = $params;

        foreach ($names as $name => $caption) {
            $params = $paramsS;
            
            $mustOrder = $params['mustOrder'];

            if ($newField && isset($this->fields[$name])) {
                if ($params['forceField']) {
                    return;
                }

                error('@Дублирано име на поле', "'{$name}'");
            } elseif (!$newField && !isset($this->fields[$name])) {
                error('@Несъществуващо поле', "'{$name}'", $this->fields);
            } elseif (!isset($this->fields[$name])) {
                $this->fields[$name] = new stdClass();
                $mustOrder = true;
            }

            foreach ($params as $member => $value) {
                // Ако има - задаваме suggestions (предложенията в падащото меню)
                if ($member == 'suggestions') {
                    if (is_scalar($value)) {
                        $value = array('' => '') + arr::make($value, true, '|');
                    }
                    $this->fields[$name]->type->suggestions = $value;
                    continue;
                }
                
                // Ако са зададени да се вземат опциите от функция
                if ($member == 'optionsFunc') {
                    $this->fields[$name]->optionsFunc = $value;
                    continue;
                }
                if ($member) {
                    if ($value == 'unsetValue') {
                        unset($this->fields[$name]->{$member});
                    } else {
                        $this->fields[$name]->{$member} = $value;
                    }
                }
            }
            
            $this->fields[$name]->caption = $this->fields[$name]->caption ? $this->fields[$name]->caption : $name;
            $this->fields[$name]->name = $name;

            // Параметри, които се предават на типа
            $typeParams = array('maxRadio' => 0, 'maxColumns' => 0, 'columns' => 0, 'mandatory' => 0, 'groupByDiv' => 0, 'maxCaptionLen' => 1, 'options' => 1);
            foreach ($typeParams as $pName => $force) {
                if (isset($this->fields[$name]->{$pName}) && ($force || !isset($this->fields[$name]->type->params[$pName]))) {
                    if ($pName == 'options') {
                        $this->fields[$name]->type->{$pName} = $this->fields[$name]->{$pName};
                    } else {
                        $this->fields[$name]->type->params[$pName] = $this->fields[$name]->{$pName};
                    }
                }
            }

            // Слага полета с еднаква група последователно, независимо от реда на постъпването им
            if (strpos($this->fields[$name]->caption, '->')) {
                list($group, $caption) = explode('->', $this->fields[$name]->caption);
                
                if (strpos($group, '||')) {
                    list($group, $en) = explode('||', $group);
                }
 
                if (isset($this->lastFroGroup[$group])) {
                    $params['after'][] = $this->lastFroGroup[$group];
                }
                $this->lastFroGroup[$group] = $name;
            }
        
            if ((count($params['before']) || count($params['after'])) && $mustOrder) {
                $newFields = array();
                $isSet = false;
                foreach ($this->fields as $exName => $exFld) {
                    if (is_array($params['before']) && in_array($exName, $params['before'])) {
                        $isSet = true;
                        $newFields[$name] = &$this->fields[$name];
                    }
                    
                    if (!$isSet || ($exName != $name)) {
                        $newFields[$exName] = &$this->fields[$exName];
                    }

                    if (is_array($params['after']) && in_array($exName, $params['after'])) {
                        $newFields[$name] = &$this->fields[$name];
                        $isSet = true;
                    }
                }
                $this->fields = $newFields;
            }

            // Проверяваме дали има предишни полета, които трябва да се подредят преди или след това поле
            if ($mustOrder) {
                $firstArr = $secondArr = $before = $after = array();
                $second = false;
                foreach ($this->fields as $exName => $exFld) {
                    if ($name == $exName) {
                        $second = true;
                        continue;
                    }
                    if (is_array($exFld->before) && in_array($name, $exFld->before)) {
                        $before[$exName] = &$this->fields[$exName];
                        continue;
                    }
                    if (is_array($exFld->after) && in_array($name, $exFld->after)) {
                        $after[$exName] = &$this->fields[$exName];
                        continue;
                    }
                    if (!$second) {
                        $firstArr[$exName] = &$this->fields[$exName];
                    } else {
                        $secondArr[$exName] = &$this->fields[$exName];
                    }
                }

                if (count($before) || count($after)) {
                    $me = array($name => $this->fields[$name]);
                    $this->fields = $firstArr + $before + $me + $after + $secondArr;
                }
            }
        }

        // Преподреждаме формата, така, че поле, което има секция, която преди се е срещала, но
        // текущата е различна - то полето отива към края на последното срещане
        $lastGroup = '';
        $lastField = array();
        $flagChange = false;
        foreach ($this->fields as $name => $fld) {
            if (strpos($fld->caption, '->')) {
                list($group, $caption) = explode('->', $fld->caption);
                
                if (strpos($group, '||')) {
                    list($group, $en) = explode('||', $group);
                }

                if ($lastGroup && $lastGroup != $group && $lastField[$group]) {
                    $flagChange = true;
                    arr::insert($res, $lastField[$group], array($name => $fld), true);
                } else {
                    $res[$name] = $fld;
                }
          
                $lastField[$group] = $name;
                $lastGroup = $group;
            } else {
                $res[$name] = $fld;
            }
        }
        if ($flagChange) {
            $this->fields = $res;
        }
    }

    
    /**
     * Прави подредбата на полетата във формата
     */
    public function orderField()
    {
        foreach ($this->fields as $name => $field) {
            if (isset($field->before)) {
                $before = arr::make($before);
                foreach ($before as $fName) {
                    if (isset($this->fields[$fName])) {
                        $this->fields[$fName]->_insertBefore[] = $name;
                    }
                }
            }

            if (isset($field->after)) {
                $after = arr::make($after);
                foreach ($after as $fName) {
                    if (isset($this->fields[$fName])) {
                        $this->fields[$fName]->_insertAfter[] = $name;
                    }
                }
            }
        }
        
        $newFields = array();

        foreach ($this->fields as $name => $field) {
            if (is_array($field->_insertBefore)) {
                foreach ($field->_insertBefore as $fName) {
                    if (!isset($newField[$fName])) {
                        $newFields[$fName] = $this->fields[$fName];
                    }
                }
            }

            if (!isset($newField[$name])) {
                $newFields[$name] = $this->fields[$name];
            }
            
            if (is_array($field->_insertAfter)) {
                foreach ($field->_insertAfter as $fName) {
                    if (!isset($newField[$fName])) {
                        $newFields[$fName] = $this->fields[$fName];
                    }
                }
            }
        }

        $this->fields = $newFields;
    }
    
     
    
    /**
     * Създава ново поле
     */
    public function newField($name, $params)
    {
        if (isset($this->fields[$name])) {
            error('@Дублирано име на поле', "'{$name}'");
        }
        $this->fields[$name]->name = $name;
        $this->setField($name, $params);
    }
    
    
    /**
     * Вкарва множество от стойности-предложения за дадено поле
     */
    public function setSuggestions($name, $suggestions)
    {
        if (is_string($suggestions)) {
            $suggestions = arr::make($suggestions, true);
        }
        
        if (!isset($this->fields[$name])) {
            error('@Несъществуващо поле', "'{$name}'", $this->fields);
        }
        
        $this->fields[$name]->type->suggestions = $suggestions;
    }
    
    
    /**
     * Добавя множество от стойности-предложения за дадено поле в края
     */
    public function appendSuggestions($name, $suggestions)
    {
        if (count($suggestions)) {
            foreach ($suggestions as $key => $value) {
                $this->fields[$name]->type->suggestions[$key] = $value;
            }
        }
    }
    
    
    /**
     * Добавя множество от стойности-предложения за дадено поле в началото
     */
    public function prependSuggestions($name, $suggestions)
    {
        $getSuggestions = $this->getSuggestions($name);
        if (count($getSuggestions)) {
            foreach ($getSuggestions as $key => $value) {
                $suggestions[$key] = $value;
            }
        }
        $this->fields[$name]->type->suggestions = $suggestions;
    }
    
    
    /**
     * Ако има зададени връща масив със стойности
     * val => verbal за даденото поле
     */
    public function getSuggestions($name)
    {
        return $this->fields[$name]->type->suggestions;
    }
    
    
    /**
     * Вкарва данни за изброимо множество от стойности за дадено поле
     */
    public function setOptions($name, $options)
    {
        $this->setField($name, array('options' => $options));
    }
    
    
    /**
     * Добавя опции в края
     */
    public function appendOptions($name, $options)
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
    public function prependOptions($name, $options)
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
    public function getOptions($name)
    {
        return $this->fields[$name]->options;
    }
    
    
    /**
     * Връща структурата на посоченото поле. Ако полето
     * липсва, а $strict е истина, генерира се грешка
     */
    public function getField($name, $strict = true)
    {
        if ($name{0} == '#') {
            $name = substr($name, 1);
        }
        
        if ($this->fields[$name]) {
            
            return $this->fields[$name];
        }
        if ($strict) {
            error('@Липсващо поле', "'{$name}'" . ($strict ? ' (strict)' : ''));
        }
    }
    
    
    /**
     * Връща всички полета, които са зададени в $fileds
     *
     * @param $rec
     * @return array
     */
    public function getAllFields_($rec = null)
    {
        return $this->fields;
    }
    
    
    /**
     * Връща типа на посоченото поле. Ако полето
     * липсва, а $strict е истина, генерира се грешка
     */
    public function getFieldType($name, $strict = true)
    {
        // Ако има такова поле в модела
        if ($this->fields[$name]) {
            
            // Връщаме му типа
            return $this->fields[$name]->type;
        }
            
        // Ако го няма и $strict е TRUE, предизвикваме грешка
        if ($strict) {
            error('@Липсващо поле', "'{$name}'" . ($strict ? ' (strict)' : ''));
        }
    }
    
    
    /**
     * Връща масив с елементи име_на_поле => структура - описание
     * $where е условие, с PHP синтаксис, като имената на атрибутите на
     * полето са предхождани от #
     * $fieldsArr е масив от полета, върху който се прави избирането
     */
    public function selectFields($where = '', $fieldsArr = null)
    {
        $res = array();

        if ($fieldsArr) {
            $fArr = arr::make($fieldsArr, true);
        } else {
            $fArr = $this->fields ? $this->fields : array();
        }
        
        $cond = str::prepareExpression($where, array(
                &$this,
                'makePhpFieldName'
            ));
        
        foreach ($fArr as $name => $caption) {
            if (!$where || @eval("return ${cond};")) {
                $res[$name] = $this->fields[$name];
            }
        }
        
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function makePhpFieldName($name)
    {
        return '$this->fields[$name]->' . $name;
    }
    
    
    /**
     * Връща параметър на полето
     *
     * @param string $name      - име на полето
     * @param string $paramName - име на параметъра
     *
     * @return mixed - стойността на параметъра ако е зададена
     */
    public function getFieldParam($name, $paramName)
    {
        $field = $this->getField($name);
        
        return $field->$paramName;
    }
    
    
    /**
     * Задава подадените параметри към полето
     *
     * @param string $name
     * @param array  $params
     */
    public function setParams($name, $params = array())
    {
        $params = arr::make($params, true);
        
        $field = $this->getField($name);
        
        foreach ($params as $param => $value) {
            $field->$param = $value;
        }
    }
    
    
    /**
     * Връща параметър на типа на полето
     *
     * @param string $name
     * @param array  $params
     */
    public function getFieldTypeParam($name, $paramName)
    {
        $fieldType = $this->getFieldType($name);
        
        return $fieldType->params[$paramName];
    }
    
    
    /**
     * Добавя атрибути към поле
     *
     * @param string $name - име на полето
     * @param mixed  $arr  - масив от атрибут -> стойност
     */
    public function setFieldAttr($name, $arr)
    {
        $arr = arr::make($arr, true);
        
        $this->setField($name, array('attr' => $arr));
    }
    
    
    /**
     * Задава/Подменя тип на полето
     *
     * @param  string $name - име на полето
     * @param  mixed  $Type - инстанция или име на тип
     * @return void
     */
    public function setFieldType($name, $type)
    {
        $fieldType = core_Type::getByName($type);
        
        $this->getField($name)->type = $fieldType;
    }
    
    
    /**
     * Задава подадените параметри към типа на полето
     *
     * @param string $name
     * @param array  $params
     */
    public function setFieldTypeParams($name, $params = array())
    {
        $params = arr::make($params, true);
    
        $fieldType = $this->getFieldType($name);
    
        if (count($params)) {
            foreach ($params as $param => $value) {
                $fieldType->params[$param] = $value;
            }
        }
    }
    
    
    /**
     * Връща масив от имената на полетата с техните кепшъни
     *
     * @param  string $where
     * @return array  $fieldsArr
     */
    public function getFieldArr($where = '')
    {
        $fields = $this->selectFields($where);
        $fieldsArr = array();
        foreach ($fields as $name => $fld) {
            $fieldsArr[$name] = $fld->caption;
        }
        
        return $fieldsArr;
    }
}
