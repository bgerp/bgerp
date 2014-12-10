<?php





/**
 * Клас  'type_Key' - Ключ към ред от MVC модел
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Key extends type_Int {
    
    
    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    var $tdClass = '';
    
    
    /**
     * Хендлър на класа
     * 
     * @var string
     */
    public $handler;
    
    
    /**
     * Инициализиране на типа
     */
    function getSelectFld()
    {
        if(core_Lg::getCurrent() == 'bg' && $this->params['selectBg']) {
            
            return $this->params['selectBg'];
        } else {

            return $this->params['select'];
        }
    }

    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function toVerbal_($value)
    {
        
        if(empty($value)) return NULL;

        
        if($this->params['mvc']) {
            $mvc = &cls::get($this->params['mvc']);
            
            if(($part = $this->getSelectFld()) && $part != '*') {

                $rec = $mvc->fetch($value);
                
                if(!$rec) return '??????????????';
                
                $v = $mvc->getVerbal($rec, $part);
                
                return $v;
            } else {
                if($this->params['title']) {
                    $field = $this->params['title'];
                    $value = $mvc->fetchField($value, $field);
                    
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
    	$conf = core_Packs::getConfig('core');
    	
        if(empty($value)) return NULL;
        
        $mvc = &cls::get($this->params['mvc']);
        
        setIfNot($maxSuggestions, $this->params['maxSuggestions'], $conf->TYPE_KEY_MAX_SUGGESTIONS);
        
        $options = $this->options;
        
        if(($field = $this->getSelectFld()) && (!count($options))) {
            $options = $this->prepareOptions();
        }
        
        if(!is_numeric($value) && count($options)) {
            foreach($options as $id => $v) {
                if (!is_string($v)) {
                    if(!$v->group) {
                        $optionsR[trim($v->title)] = $id;
                    }
                } else {
                    
                    /**
                     * $v (косвено) се сравнява с субмитнатата чрез HTML `<select>` елемент
                     * стойност $value. Оказа се, че (поне при някои браузъри) специалните HTML
                     * символи (`&amp;`, `&nbsp` и пр.) биват декодирани при такъв субмит. Така
                     * ако $v съдържа такива символи, сравнението ще пропадне, въпреки, че
                     * стойностите може да изглеждат визуално еднакви. Напр. ако
                     *
                     *  $v = "Тестов&nbsp;пример"; $value = "Тестов пример"
                     *
                     *  очевидно $v != $value
                     *
                     *  По тази причина декодираме специалните символи предварително.
                     *
                     */
                    //$v = html_entity_decode($v, ENT_NOQUOTES, 'UTF-8');
                    $optionsR[trim($v)] = $id;
                }
            }
            
            $value = $optionsR[trim($value)];
        }
        
        $value = (int) $value;
        
        $rec = $mvc->fetch($value);
        
        if(!$rec) {
            $this->error = 'Несъществуващ обект';
            
            return FALSE;
        } else {
            
            return $value;
        }
    }
    

    public function prepareOptions()
    {   
        Mode::push('text', 'plain');
        
        // Ако опциите вече са генерирани - не ги подготвяме отново
        if(!is_array($this->options) || !count($this->options)) {
        
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

            if(!count($options)) {
                
                if (!is_array($this->options)) {
                    foreach($mvc->makeArray4select($field, $where) as $id => $v) {
                        $options[$id] = $v;
                    }
                    $this->handler = md5($field . $where . $this->params['mvc']);
                } else {
                    foreach($this->options as $id => $v) {
                        $options[$id] = $v;
                    }
                }
            }

            
            // Правим титлите на опциите да са уникални и изчисляваме най-дългото заглавие
            $this->maxFieldSize = 0;
            if(is_array($options)) {
                foreach($options as $id => &$title) {
                    if(is_object($title)) continue;
                    if($titles[$title]) {
                        $title .= " ({$id})";
                    }
                    $titles[$title] = TRUE;
                    $this->maxFieldSize = max($this->maxFieldSize, mb_strlen($title));
                }
            }
      
            $this->options = &$options;

            $mvc->invoke('AfterPrepareKeyOptions', array(&$this->options, $this));
        }
        
        setIfNot($this->handler, md5(json_encode($this->options)));
        Debug::stopTimer('prepareOPT ' . $this->params['mvc']);
        
        Mode::pop('text');
        
        return $this->options;
    }
    
    /**
     * Рендира HTML поле за въвеждане на данни чрез форма
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $conf = core_Packs::getConfig('core');
        
        expect($this->params['mvc']);
        
        $mvc = cls::get($this->params['mvc']);
        
        if(!$value) {
            $value = $attr['value'];
        }
        
        if($this->getSelectFld() || count($this->options)) {
            
            $options = $this->prepareOptions();
            
            if(!is_array($options)) {
                $options = $this->options;
            }

            if($this->params['allowEmpty']) {
                $placeHolder = array('' => (object) array('title' => $attr['placeholder'] ? $attr['placeholder'] : ' ', 'attr' => 
                    array('style' => 'color:#777;')));
                $options = arr::combine($placeHolder, $options);
            } elseif($attr['placeholder']) {
                $placeHolder = array('' => (object) array('title' => $attr['placeholder'], 'attr' => 
                    array('style' => 'color:#777;', 'disabled' => 'disabled')));
                $options = arr::combine($placeHolder, $options);
            }

            setIfNot($maxSuggestions, $this->params['maxSuggestions'], $conf->TYPE_KEY_MAX_SUGGESTIONS);

            parent::setFieldWidth($attr);

            // Ако трябва да показваме combo-box
            if(count($options) > $maxSuggestions) {

                if(is_object($options[''])) {
                    $options['']->title = '';
                }
                
                // Генериране на cacheOpt ако не са в кеша
            	$cacheOpt = core_Cache::get('SelectOpt', $this->handler, 20, array($this->params['mvc']));
            	  
                if(FALSE === $cacheOpt || 1) {
                    
                    $cacheOpt = array();

                    foreach($options as $key => $v) {
                        
                        $title = self::getOptionTitle($v);
                        
                        if ($title && $key) {
                            $title = "{$title} ({$key})";
                        } 
                            
                        $vNorm = strtolower(str::utf2ascii(trim($title)));
                        
                        if(is_object($v)) {
                            $v->title = $title;
                        } else {
                            $v = $title;
                        }
                        
                        $cacheOpt[$vNorm] = $v;
                    }
 
                    core_Cache::set('SelectOpt', $this->handler, serialize($cacheOpt), 20, array($this->params['mvc']));
                } else {
                	$cacheOpt = (array) unserialize($cacheOpt);
                }
                

                
                if($this->suggestions) {
                    $suggestions = $this->suggestions;
                } else {
                    $suggestions = array_slice($cacheOpt, 0, $maxSuggestions, TRUE);
                }
               
                foreach($suggestions as $key => $v) {
                   
                    $key = self::getOptionTitle($v);
                    
                    
                    
                    $selOpt[trim($key)] = $v;
                }

                $title = self::getOptionTitle($options[$value]);

                $selOpt[$title] =  $options[$value];
                 
                $this->options = $selOpt;

                $attr['ajaxAutoRefreshOptions'] = "{Ctr:\"type_Key\"" .
                ", Act:\"ajax_GetOptions\", hnd:\"{$this->handler}\", maxSugg:\"{$maxSuggestions}\", ajax_mode:1}";
                
                // Ако е id определяме стойността която ще се показва, като вербализираме
                // Иначе - запазваме предходния вариянт. Работил ли е някога?
                $setVal = self::getOptionTitle($options[$value]);

                if(!$setVal && is_numeric($value)) {
                    $setVal = $this->toVerbal($value); 
                }  

                $tpl = ht::createCombo($name, $setVal, $attr, $selOpt);  
            } else {
                
                if(count($options) == 0 && $mvc->haveRightFor('list')) {
                    $msg = "Липсва избор за |* \"" . $mvc->title . "\".";
                    
                    if(!$mvc->fetch("1=1")) {
                        $msg .= " Моля въведете началните данни.";
                    }
                    
                    return new Redirect(array($mvc, 'list'), tr($msg));
                }
                
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

        $conf = core_Packs::getConfig('core');
        
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
       
        setIfNot($maxSuggestions, Request::get('maxSugg', 'int'), $conf->TYPE_KEY_MAX_SUGGESTIONS);
        
        $options = (array) unserialize(core_Cache::get('SelectOpt', $hnd));
        
        if ($options['']) {
            $select = new ET('');
        } else {
            $select = new ET('<option value="">&nbsp;</option>');
        }
        
        $cnt = 0;
 
        if (is_array($options)) {
            
            foreach ($options as $id => $title) {
                
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
                
                if ($attr['value'] == $selected) {
                    $attr['selected'] = 'selected';
                }
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
