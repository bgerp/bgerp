<?php


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
     * Дали да се подготвят SelectOpt
     */
    protected $prepareSelOpt = TRUE;
    
    
    /**
     * Името на selectOpt
     */
    public $selectOpt = 'SelectOpt';
    
    
    /**
     * Инициализиране на типа
     */
    function init($params = array())
    {
        parent::init($params);
        
        if (Mode::is('keyStopAutocomplete')) {
            $this->params['autocomplete'] = 'off';
        }
    }
    
    
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
                
                // Ако е указано - правим превод
                if($this->params['translate']) {
                    $v = tr($v); 
                }

                if(isset($this->params['makeLink'])){
                	if(method_exists($mvc, 'getSingleUrlArray')){
                		$v = ht::createLink($v, $mvc->getSingleUrlArray($rec->id), FALSE, "ef_icon={$mvc->singleIcon}");
                	}
                }
                
                return $v;
            } else {
                if($this->params['title']) {
                    $field = $this->params['title'];
                    $value = $mvc->fetch($value)->{$field};
                    
                    if(!$value) return '??????????????';
                    
                    $value = $mvc->fields[$field]->type->toVerbal($value);
                } else {
                	if(isset($this->params['makeLink'])){
                		if(method_exists($mvc, 'getHyperlink')){
                			return $mvc->getHyperlink($value, TRUE);
                		}
                	}
                	
                    $value = $mvc->getTitleById($value);
                }
            }
        }
        
        // Ако е указано - правим превод  
        if($this->params['translate']) {
            $value = tr($value);
        }

        return $value;
    }
    
    
    /**
     * Връща вътрешното представяне на вербалната стойност
     */
    function fromVerbal_($value)
    {
        if(empty($value)) return NULL;
        
        $key = self::getKeyFromTitle($value);
        
        $oValue = $value;
        
        if (!isset($key)) {
         
            $mvc = &cls::get($this->params['mvc']);
            
            $maxSuggestions = $this->getMaxSuggestions();
            
            $options = $this->options;
            
            $selOptCache = unserialize(core_Cache::get($this->selectOpt, $this->handler));
            
            if ($selOptCache === FALSE) {
                $options = $this->prepareOptions();
                $selOptCache = unserialize(core_Cache::get($this->selectOpt, $this->handler));
            }
            
            if (($field = $this->getSelectFld()) && (!count($options))) {
                $options = $this->prepareOptions();
            }
            
            $value = NULL;

            if (($selOptCache !== FALSE) && count((array)$selOptCache)) {
                foreach((array)$selOptCache as $id => $titleArr) {
                    
                    if ($value == $titleArr['title']) {
                        $value = $id;
                        break;
                    }
                }
            }
        } else {
            $value = $this->prepareKey($key);
        }
        
        $rec = $this->fetchVal($value);
        
        if (!$rec) {
            if (($this->params['allowEmpty']) && ($oValue == ' ')) {
                
                return $value;
            } else {
                
                Mode::setPermanent('keyStopAutocomplete', TRUE);
                
                $this->error = 'Несъществуващ обект';
            }
            
            return FALSE;
        } else {
 
            return $value;
        }
    }
    
    
    /**
     * 
     * 
     * @param string|int|NULL $key
     * 
     * @return string
     */
    public function prepareKey($key)
    {
        // Само числа
        $key = (int) $key;
        
        return $key;
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
     * @param integer $value
     * 
     * @return object
     */
    protected function fetchVal(&$value)
    {
        $mvc = &cls::get($this->params['mvc']);
        
        $rec = $mvc->fetch((int)$value);
        
        return $rec;
    }
    
    
    /**
     * Връща възможните стойности за ключа
     * 
     * @param string $value
     * 
     * @return array
     */
    function getAllowedKeyVal($id)
    {
        
        return array($id => $id);
    }
    
    
    /**
     * Подготвя масив с опциите
     */
    public function prepareOptions($value = NULL)
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
            
            $where = '';
            
            if ($this->params['where']) {
                $where = $this->params['where'];
            }
            
            // Ако е зададено поле group='sysId'
            if ($this->params['group']) {
                
                $fWhere = $this->filterByGroup($mvc);
                
                if ($fWhere) {
                    $where = empty($where) ? $fWhere : "({$where}) AND ({$fWhere})" ;
                }
            }
            
            Debug::startTimer('prepareOPT ' . $this->params['mvc']);
            
            $options = array();
            
            $mvc->invoke('BeforePrepareKeyOptions', array(&$options, $this, $where));
 
            if (!count($options)) {
                
                if (!is_array($this->options)) {
                    
                    $keyIndex = $this->getKeyField();
                    
                    $arrForSelect = (array) $mvc->makeArray4select($field, $where, $keyIndex, $this->params['orderBy']);  
                    foreach($arrForSelect as $id => $v) {
                        $options[$id] = $v;
                    }
                    $this->handler = md5($field . $where . $this->params['mvc'] . $keyIndex . '|' . core_Lg::getCurrent());  
                } else {
                    foreach($this->options as $id => $v) {
                        $options[$id] = $v;
                    }  
                }
            }
            
            // Правим титлите на опциите да са уникални и изчисляваме най-дългото заглавие
            if(is_array($options)) {
                
                $titles = array();
                
                $i = 1;

                foreach($options as $id => $title) {
                    
                    if(is_object($title)) continue;
                    
                    if ($titles[$title]) {
                        $title = self::getUniqTitle($title, $id);
                    }
                    $titles[$title] = TRUE;
                    $options[$id] = $title;

                    list($title1, ) = explode('||', $title);
                    $this->maxFieldSize = max($this->maxFieldSize, mb_strlen($title1));

                    if($i++ > 100) break;
                }
            }
            
            $this->options = &$options;
            
            $mvc->invoke('AfterPrepareKeyOptions', array(&$this->options, $this, $where));
        } else {
            $options = $this->options;
        }
        
        if(!$this->handler) {
            $this->handler = md5(implode(',', array_keys($this->options)) . '|' . core_Lg::getCurrent());
        }
        
        if($optSz = core_Cache::get($this->selectOpt, $this->handler, 60, array($this->params['mvc']))) {
            $cacheOpt = unserialize($optSz);
            $options = array();
            foreach($cacheOpt as $id => $obj) {
                $options[$id] = $obj['title'];
            }
        } else {
            $this->prepareSelectOpt($options);
        }

        Debug::stopTimer('prepareOPT ' . $this->params['mvc']);
        
        Mode::pop('text');

        if($this->params['translate']) { 
            $options = self::translateOptions($options);  
        }
        
        $this->options = $options;
        
        return $options;
    }
    
    
    /**
     * Подготвя опциите за селект, ако условията са изпълнени
     * 
     * @param array $options
     */
    protected function prepareSelectOpt(&$options)
    {
        if (!$this->prepareSelOpt) return ;
        
        $maxSuggestions = $this->getMaxSuggestions();
        
        // Ако трябва да показваме combo-box
        if (count($options) <= $maxSuggestions) return ;
        
        if(is_object($options[''])) {
            $options['']->title = '';
        }
        
        $cacheOpt = array();
        
        $titles = array();
        
        $isInstalled = core_Packs::isInstalled('select2');
        
        foreach($options as $key => $v) {
            
            $title = self::getOptionTitle($v);
            
            // Ако вече е добавено id-то след края на текста, да не се добавя повторвно
            if (!self::haveId($title, $key)) {
                if (!$isInstalled) {
                    $title = self::getUniqTitle($title, $key);
                }
                
                if (is_object($v)) {
                    $v->title = $title;
                    $options[$key] = $v;
                } else {
                    $options[$key] = $title;
                }
            }
            
            if ($titles[$title]) {
                $title = self::getUniqTitle($title, $key);
            }
            
            $titles[$title] = TRUE;
        
            $vNorm = trim(preg_replace('/[^a-z0-9\*]+/', ' ', strtolower(str::utf2ascii($title))));
            
            if (is_object($v)) {
                $v->title = $title;
            } else {
                $v = $title;
            }
            
            $cacheOpt[$key]['title'] = $v;
            $cacheOpt[$key]['id'] = $vNorm;
        }

        core_Cache::set($this->selectOpt, $this->handler, serialize($cacheOpt), 60, array($this->params['mvc']));
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
     * Проверява дали има 'id' в края на стринга
     * 
     * @param string $title
     * @param integer $id
     * 
     * @return NULL|boolean
     */
    protected static function haveId($title, $id)
    {
        $nKey = " ({$id})";
        $pos = mb_strrpos($title, $nKey);
        
        if ($pos === FALSE) return FALSE;
        
        $len = mb_strlen($title);
        $keyLen = mb_strlen($nKey);
        
        if (($pos+$keyLen) == $len) return TRUE;
    }
    
    
    /**
     * Опитва се да извлече ключа от текста
     * 
     * @param string $title
     * 
     * return integer|NULL
     */
    protected static function getKeyFromTitle($title)
    {
        if (is_numeric($title) || !isset($title)) return $title;
        
        $len = mb_strlen($title);
        
        $lastCloseBracketPos = mb_strrpos($title, ')');
        
        if (!$lastCloseBracketPos) return $title;
        
        if ($len != ($lastCloseBracketPos+1)) return $title;
        
        $lastOpenBracketPos = mb_strrpos($title, ' (');
        
        if (!$lastOpenBracketPos) return $title;
        
        $lastOpenBracketPos += 2;
        
        $key = mb_substr($title, $lastOpenBracketPos, $lastCloseBracketPos-$lastOpenBracketPos);
        
        return $key;
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
        $val = plg_Search::normalizeText($val);
        
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

        if(!is_array($options) || !count($options)) {
            $options = $this->prepareOptions();
        }
        
        if(($div = $this->params['groupByDiv'])) {
            $options = ht::groupOptions($options, $div);
        }

        if ($this->getSelectFld() || count($options)) {
            
            $optionsCnt = count($options);

            if($this->params['allowEmpty']) {
                $placeHolder = array('' => (object) array('title' => $attr['placeholder'] ? $attr['placeholder'] : ' ', 'attr' => 
                    array('style' => 'color:#777;')));
                $options = arr::combine($placeHolder, $options);
            } elseif($attr['placeholder'] && $optionsCnt != 1) {
                $placeHolder = array('' => (object) array('title' => $attr['placeholder'], 'attr' => 
                    array('style' => 'color:#777;', 'disabled' => 'disabled')));
                $options = arr::combine($placeHolder, $options);
            }
            
            $maxSuggestions = $this->getMaxSuggestions();
            
            parent::setFieldWidth($attr);
        
            if (($optionsCnt > $maxSuggestions) && (!core_Packs::isInstalled('select2'))) {
                
                if ($this->params['autocomplete']) {
                    $attr['autocomplete'] = $this->params['autocomplete'];
                }
                
                $selOptCache = (array) unserialize(core_Cache::get($this->selectOpt, $this->handler));
                
                if($this->suggestions) {
                    $suggestions = $this->suggestions;
                } else {
                    $suggestions = array_slice($options, 0, $maxSuggestions, TRUE);
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
                
                if ($selOpt['']) {
                    $selOpt = array('' => $selOpt['']) + $selOpt;
                }
                
                $tpl = ht::createCombo($name, $setVal, $attr, $selOpt);
            } else {
                
                $optionsCnt = count($options);
                
                if (($optionsCnt == 0 || ($optionsCnt == 1 && isset($options['']) && $this->params['mandatory']))) {
                    
                    $msg = tr('Липсва избор за');
                    
                    $title = tr($mvc->title);

                    if($mvc->haveRightFor('list')) {
                        $url = array($mvc, 'list');
                        $title = ht::createLink($title, $url, FALSE, 'style=font-weight:bold;');
                    }

                    $cssClass = $this->params['mandatory'] ? 'inputLackOfChoiceMandatory' : 'inputLackOfChoice';

                    $tpl = new ET("<span class='{$cssClass}'>[#1#] [#2#]</div>", $msg, $title);

                } else {
                
                    // Ако полето е задължително и имаме само една не-празна опция - тя да е по подразбиране
                    if($this->params['mandatory'] && $optionsCnt == 2 && empty($value) && $options[key($options)] === '') {
                        list($o1, $o2) = array_keys($options);
                        if(!empty($o2)) {
                            $value = $o2;
                        } elseif(!empty($o1)) {
                            $value = $o1;
                        }
                    }
                    
                    $tpl = ht::createSmartSelect($options, $name, $value, $attr,
                        $this->params['maxRadio'],
                        $this->params['maxColumns'],
                        $this->params['columns']);
                }
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
        
        if (!$hnd) {
            return array(
                'error' => 'Липсват допълнителни опции'
            );
        }
        
        if (!($maxSuggestions = Request::get('maxSugg', 'int'))) {
            $maxSuggestions = $this->getMaxSuggestions();
        }
        
        $options = unserialize(core_Cache::get($this->selectOpt, $hnd));
        
        $select = new ET('<option value="">&nbsp;</option>');
        
        $cnt = 0;
        
        if (is_array($options)) {
            
            $openGroup = FALSE;
            
            foreach ($options as $key => $titleArr) {
                
                $title = $titleArr['title'];
                $id = $titleArr['id'];
                
                $attr = array();
                
                if ($key == '') continue;
                
                if(!isset($title->group) && $q && (!preg_match($q, ' ' . $id)) ) continue;
                
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
                
                if (!is_object($title)) {
                    $cnt++;
                }
                
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