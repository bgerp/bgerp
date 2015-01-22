<?php


/**
 * "Подправка" за кодиране на CRC за ключа
 */
defIfNot('KEY_CRC_SALT', md5(EF_SALT . '_KEY_CRC'));


/**
 * Клас  'type_Key' - Ключ към ред от MVC модел
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class type_Key extends type_Int
{
    
    
    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    public $tdClass = '';
    
    
    /**
     * Хендлър на класа
     * 
     * @var string
     */
    public $handler;
    
    
    /**
     * Параметър определящ максималната широчина на полето
     */
    public $maxFieldSize = 0;
    
    
    /**
     * Разделител при генериране на ключ
     */
    protected static $keyDelimiter = '|';
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function toVerbal_($value)
    {
        if ($value === NULL || $value === '') return NULL;
        
        if ($this->params['mvc']) {
            $mvc = &cls::get($this->params['mvc']);
            
            if(($part = $this->getSelectFld()) && $part != '*') {
                
                $rec = $this->fetchVal($value);
                
                if (!$rec && $value == 0) return NULL;
                
                if(!$rec) return '??????????????';
                
                $v = $mvc->getVerbal($rec, $part);
                
                return $v;
            } else {
                if($this->params['title']) {
                    $field = $this->params['title'];
                    $value = $mvc->fetch($value)->{$field};
                    
                    if(!$value) return '??????????????';
                    
                    $value = $mvc->fields[$field]->type->toVerbal($value);
                } else {
                    $value = $mvc->getTitleById($value);
                }
            }
        }
        
        return $value;
    }
    
    
    /**
     * Връща вътрешното представяне на вербалната стойност
     */
    function fromVerbal_($value)
    {
        if(empty($value)) return NULL;
        
        $key = self::getKeyFromCrc($value);
        
        $oValue = $value;
        
        if (!isset($key)) {
            
            $mvc = &cls::get($this->params['mvc']);
            
            $maxSuggestions = $this->getMaxSuggestions();
            
            $options = $this->options;
            
            $selOptCache = unserialize(core_Cache::get('SelectOpt', $this->handler));
            
            if ($selOptCache === FALSE) {
                $options = $this->prepareOptions();
                $selOptCache = unserialize(core_Cache::get('SelectOpt', $this->handler));
            }
            
            if (($field = $this->getSelectFld()) && (!count($options))) {
                $options = $this->prepareOptions();
            }
            
            if (($selOptCache !== FALSE) && count((array)$selOptCache)) {
                foreach((array)$selOptCache as $id => $titleArr) {
                    
                    if ($value == $titleArr['title']) {
                        $value = $id;
                        break;
                    }
                }
            }
        } else {
            $value = $key;
        }
        
        $rec = $this->fetchVal($value);
        
        if (!$rec) {
            if (($this->params['allowEmpty']) && ($oValue == ' ')) {
                
                return $value;
            } else {
                $this->error = 'Несъществуващ обект';
            }
            
            return FALSE;
        } else {
            
            return $value;
        }
    }
    
    
    /**
     * Инициализиране на типа
     */
    protected function getSelectFld()
    {
        if(core_Lg::getCurrent() == 'bg' && $this->params['selectBg']) {
            
            return $this->params['selectBg'];
        } else {

            return $this->params['select'];
        }
    }
    
    
    /**
     * 
     * 
     * @param string $value
     * 
     * @return object
     */
    protected function fetchVal($value)
    {
        $mvc = &cls::get($this->params['mvc']);
        
        $rec = $mvc->fetch((int)$value);
        
        return $rec;
    }
    
    
    /**
     * 
     */
    public function prepareOptions()
    {
        Mode::push('text', 'plain');
        
        // Ако опциите вече са генерирани - не ги подготвяме отново
        if (!is_array($this->options) || !count($this->options)) {
        
            $mvc = cls::get($this->params['mvc']);

            if($this->getSelectFld() == '*') {
                $field = NULL;
            } else {
                $field = $this->getSelectFld();
            }
            
            if ($this->params['where']) {
                $where = $this->params['where'];
            }
            
            // Ако е зададено поле group='sysId'
            if ($this->params['group']) {
                $where = $this->filterByGroup($mvc);
            }
            
            Debug::startTimer('prepareOPT ' . $this->params['mvc']);
            
            $options = array();
            
            $mvc->invoke('BeforePrepareKeyOptions', array(&$options, $this));

            if (!count($options)) {
                
                if (!is_array($this->options)) {
                    
                    $keyIndex = $this->getKeyField();
                    
                    $arrForSelect = (array) $mvc->makeArray4select($field, $where, $keyIndex);
                    foreach($arrForSelect as $id => $v) {
                        $options[$id] = $v;
                    }
                    $this->handler = md5($field . $where . $this->params['mvc'] . $keyIndex);
                } else {
                    foreach($this->options as $id => $v) {
                        $options[$id] = $v;
                    }
                }
            }
            
            // Правим титлите на опциите да са уникални и изчисляваме най-дългото заглавие
            if(is_array($options)) {
                
                $titles = array();
                
                foreach($options as $id => $title) {
                    
                    if(is_object($title)) continue;
                    
                    if ($titles[$title]) {
                        $title = self::getUniqTitle($title, $id);
                    }
                    
                    $titles[$title] = TRUE;
                    $this->maxFieldSize = max($this->maxFieldSize, mb_strlen($title));
                    $options[$id] = $title;
                }
            }
            
            $this->options = &$options;

            $mvc->invoke('AfterPrepareKeyOptions', array(&$this->options, $this));
        } else {
            $options = $this->options;
        }
        
        setIfNot($this->handler, md5(json_encode($this->options)));
        
        $maxSuggestions = $this->getMaxSuggestions();
        
        // Ако трябва да показваме combo-box
        if(count($options) > $maxSuggestions) {
 
            if(is_object($options[''])) {
                $options['']->title = '';
            }
            
            $cacheOpt = array();
            
            $titles = array();
            
            foreach($options as $key => $v) {
                
                $title = self::getOptionTitle($v);
                if ($titles[$title]) {
                    $title = self::getUniqTitle($title, $key);
                }
                
                $titles[$title] = TRUE;
                
                $vNorm = self::normalizeKey($title);
                
                if (is_object($v)) {
                    $v->title = $title;
                } else {
                    $v = $title;
                }
                
                $cacheOpt[$key]['title'] = $v;
                $cacheOpt[$key]['id'] = $vNorm;
            }
            
            core_Cache::set('SelectOpt', $this->handler, serialize($cacheOpt), 20, array($this->params['mvc']));
        } else {
            $nArrOpt = array();
            
            foreach ($options as $key => $v) {
                
                $key = self::prepareOptKey($key);
                $nArrOpt[$key] = $v;
            }
            
            $options = $nArrOpt;
        }
        
        Debug::stopTimer('prepareOPT ' . $this->params['mvc']);
        
        Mode::pop('text');
        
        return $options;
    }
    
    
    /**
     * Подготвя подадения ключ, като добавя crc сумата след него
     * 
     * @param string $key
     * 
     * @return string
     */
    public static function prepareOptKey($key)
    {
        if (!trim($key)) return $key;
        
        $crc = self::calcCrc($key);
        
        $newKey = $key . self::$keyDelimiter . $crc;
        
        return $newKey;
    }
    
    
    /**
     * Връща ключа от стринга, като проверява дали е коректен CRC сумата
     * 
     * @param string $str
     * 
     * @return integer|string|NULL
     */
    public static function getKeyFromCrc($str)
    {
        if (strpos($str, self::$keyDelimiter) === FALSE) return ;
        
        list($key, $crc) = explode(self::$keyDelimiter, $str);
        
        if (self::calcCrc($key) != $crc) return ;
        
        return $key;
    }
    
    
    /**
     * Изчислява CRC сумата за ключа
     * 
     * @param string $val
     * 
     * @return integer
     */
    public static function calcCrc($val)
    {
        $crc =  crc32($val . '_' . KEY_CRC_SALT);
        
        $crc = substr($crc, 0, 4);
        
        return $crc;
    }
    
    
    /**
     * 
     * 
     * @return string
     */
    protected function getKeyField()
    {
        $keyField = 'id';
        
        if (!empty($this->params['key'])) {
            $keyField = $this->params['key'];
        }
        
        return $keyField;
    }
    
    
    /**
     * Връща броя на максимално допуситимите опции за показване
     * 
     * @return integer
     */
    public function getMaxSuggestions()
    {
        $conf = core_Packs::getConfig('core');
        
        $maxSuggestions = $this->params['maxSuggestions'] ? $this->params['maxSuggestions'] : $conf->TYPE_KEY_MAX_SUGGESTIONS;
        
        return $maxSuggestions;
    }
    
    
    /**
     * 
     * 
     * @param string $title
     * @param integer $id
     * 
     * @return string
     */
    protected static function getUniqTitle($title, $id)
    {
        
        return $title . " ({$id})";
    }
    
    
    /**
     * 
     * 
     * @param string $val
     * 
     * @return string
     */
    protected static function normalizeKey($val)
    {
        $val = trim(strtolower(str::utf2ascii(trim($val))));
        
        return $val;
    }
    
    
    /**
     * Рендира HTML поле за въвеждане на данни чрез форма
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        expect($this->params['mvc']);
        $selOpt = array();
        $mvc = cls::get($this->params['mvc']);
        
        if (!$value) {
            $value = $attr['value'];
        }
        
        $options = $this->options;
        
        $pOptions = $this->prepareOptions();
        
        if ($this->getSelectFld() || count($options)) {
            
            $options = $pOptions;
            
            
            if(!is_array($options)) {
                $options = $this->options;
            }
            
            $optionsCnt = count($options);
            
            if($this->params['allowEmpty']) {
                $placeHolder = array('' => (object) array('title' => $attr['placeholder'] ? $attr['placeholder'] : ' ', 'attr' => 
                    array('style' => 'color:#777;')));
                $options = arr::combine($placeHolder, $options);
            } elseif($attr['placeholder']) {
                $placeHolder = array('' => (object) array('title' => $attr['placeholder'], 'attr' => 
                    array('style' => 'color:#777;', 'disabled' => 'disabled')));
                $options = arr::combine($placeHolder, $options);
            }
            
            $maxSuggestions = $this->getMaxSuggestions();
            
            parent::setFieldWidth($attr);
            
            if ($optionsCnt > $maxSuggestions) {
                
                $selOptCache = (array) unserialize(core_Cache::get('SelectOpt', $this->handler));
                
                if($this->suggestions) {
                    $suggestions = $this->suggestions;
                } else {
                    $suggestions = array_slice($this->options, 0, $maxSuggestions, TRUE);
                }
                
                foreach((array)$suggestions as $key => $v) {
                   
                    $key = self::getOptionTitle($v);
                    
                    $selOpt[trim($key)] = $v;
                }
                
                $this->options = $selOpt;
                
                $attr['ajaxAutoRefreshOptions'] = "{Ctr:\"type_Key\"" .
                ", Act:\"ajax_GetOptions\", hnd:\"{$this->handler}\", maxSugg:\"{$maxSuggestions}\", ajax_mode:1}";
                
                // Ако е id определяме стойността която ще се показва, като вербализираме
                // Иначе - запазваме предходния вариянт. Работил ли е някога?
                $setVal = self::getOptionTitle($selOptCache[$value]['title']);
                
                if(!$setVal && is_numeric($value)) {
                    $setVal = $this->toVerbal($value); 
                }
                
                // Най-отгоре да е стойността по подразбиране
                unset($selOpt[$setVal]);
                $selOpt = array($setVal => $setVal) + $selOpt;
                
                $tpl = ht::createCombo($name, $setVal, $attr, $selOpt);
            } else {
                if (count($options) == 0 && $mvc->haveRightFor('list')) {
                    $msg = '|Липсва избор за|* "' . $mvc->title . '".';
                    
                    if (!$mvc->fetch("1=1")) {
                        $msg .= " |Моля въведете началните данни.";
                    }
                    
                    return new Redirect(array($mvc, 'list'), $msg);
                }
                
                $value = self::prepareOptKey($value);
                
                $tpl = ht::createSmartSelect($options, $name, $value, $attr,
                    $this->params['maxRadio'],
                    $this->params['maxColumns'],
                    $this->params['columns']);
            }
        } else {
            
            error(NULL, $this);
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща списък е елементи <option> при ajax заявка
     */
    function act_ajax_GetOptions()
    {    
        // Приключваме, ако няма заявка за търсене
        $hnd = Request::get('hnd');
        
        $q = Request::get('q');
        
        $q = plg_Search::normalizeText($q);
        
        $q = '/[ \"\'\(\[\-\s]' . str_replace(' ', '.* ', $q) . '/';
 
        core_Logs::add('type_Key', NULL, "ajaxGetOptions|{$hnd}|{$q}", 1);
        
        if (!$hnd) {
            return array(
                'error' => 'Липсват допълнителни опции'
            );
        }
        
        if (!($maxSuggestions = Request::get('maxSugg', 'int'))) {
            $maxSuggestions = $this->getMaxSuggestions();
        }
        
        $options = (array) unserialize(core_Cache::get('SelectOpt', $hnd));
        
        if ($options['']) {
            $select = new ET('');
        } else {
            $select = new ET('<option value="">&nbsp;</option>');
        }
        
        $cnt = 0;
        
        if (is_array($options)) {
            
            $openGroup = FALSE;
            
            foreach ($options as $key => $titleArr) {
                
                $title = $titleArr['title'];
                $id = $titleArr['id'];
                
                $attr = array();
                
                if((!is_object($title) && !isset($title->group)) && $q && (!preg_match($q, ' ' . $id)) ) continue;
                
                $element = 'option';
                
                if (is_object($title)) {
                    if ($title->group) {
                        if ($openGroup) {
                            // затваряме групата                
                            $select->append('</optgroup>');
                        }
                        $element = 'optgroup';
                        $attr = $title->attr;
                        $attr['label'] = $title->title;
                        $newGroup = ht::createElement($element, $attr);
                        continue;
                    } else {
                        if($newGroup) {
                            $select->append($newGroup);
                            $newGroup = NULL;
                            $openGroup = TRUE;
                        }
                        $attr = $title->attr;
                        $title = $title->title;
                    }
                } else {
                    if($newGroup) {
                        $select->append($newGroup);
                        $newGroup = NULL;
                        $openGroup = TRUE;
                    }
                }
                
                $attr['value'] = self::getOptionTitle($title);
                
                $option = ht::createElement($element, $attr, $title);
                $select->append($option);
                
                $cnt++;
                
                if($cnt >= $maxSuggestions) break;
            }
        }
        
        $res = array(
            'content' => $select->getContent()
        );
       
        echo json_encode($res);
        
        die;
    }
    
    
    /**
     * Добавя филтриране на резултатите по група зададена с нейно sysId
     * @param core_Mvc $mvc - мениджър на ключа
     * @return string - 'where' клауза за филтриране по Ид на група
     */
    private function filterByGroup(core_Mvc $mvc)
    {
        // Ако не е посочено 'groupsField', приемаме че то се казва "groups"
        setIfNot($mvc->groupsField, 'groups');
		$fieldParams = $mvc->getField($mvc->groupsField)->type->params;
        $GroupManager = cls::get($fieldParams['mvc']);

        // Проверяваме дали мениджъра има поле sysId или systemId
        $groupQuery = $GroupManager->getQuery();
        
        if($sysIdField = $GroupManager->fields['sysId']){
            $sysIdField = 'sysId';
        } elseif($GroupManager->fields['systemId']) {
            $sysIdField = 'systemId';
        }
            	
        // Очакваме мениджъра да поддържа или sysId или systemId
        expect($sysIdField, 'Мениджъра не поддържа sysId-та');
        $groupQuery->where("#{$sysIdField} = '{$this->params['group']}'");
            	
        // Очакваме да има запис зад това sysId
        expect($groupRec = $groupQuery->fetch(), 'Няма група с това sysId');
            	
        // Модифицираме заявката като добавяме филтриране по група, която
        // е зададена с нейно Id - отговарящо на посоченото systemId
        return "#{$mvc->groupsField} LIKE '%|{$groupRec->id}|%'";
    }


    /**
     * Връща заглавието на опцията, независимо от това дали тя е стринг или обект
     */
    static function getOptionTitle($v)
    {
        if($v == NULL || is_string($v)) {
            $title = $v;
        } else {
            $title = $v->title;
        } 

        return $title;
    }


    /**
     * Транслитерира масив с опции, като запазва възможността някои от тях да са обекти
     */
    static function transliterateOptions($options)
    {
        foreach($options as &$opt) {
            if(is_object($opt)) {
                $opt->title = transliterate($opt->title);
            } else {
                $opt = transliterate($opt);
            }
        }

        return $options;
    }
    
    
	/**
     * Превежда масив с опции, като запазва възможността някои от тях да са обекти
     */
    static function translateOptions($options)
    {
        foreach($options as &$opt) {
            if(is_object($opt)) {
                $opt->title = tr($opt->title);
            } else {
                $opt = tr($opt);
            }
        }

        return $options;
    }
}