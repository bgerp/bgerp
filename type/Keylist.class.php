<?php


/**
 * Броя на всички записи, над които групите ще са отворени по подразбиране
 */
defIfNot(CORE_MAX_OPT_FOR_OPEN_GROUPS, 10);


/**
 * Клас  'type_Keylist' - Списък от ключове към редове от MVC модел
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
class type_Keylist extends core_Type {
    
    
    /**
     * MySQL тип на полето в базата данни
     */
    var $dbFieldType = 'text';
    
    
	/**
     * Конструктор. Дава възможност за инициализация
     */
    function init($params = array())
    {
        parent::init($params);
        
        // Ако не е зададен параметъра
        setIfNot($this->params['maxOptForOpenGroups'], CORE_MAX_OPT_FOR_OPEN_GROUPS);
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function toVerbal_($value)
    {
        if(empty($value)) return NULL;
        
        $vals = explode($value{0}, $value);
        
        foreach($vals as $v) {
            if($v) {
                $res .= ($res ? ", " : '') . $this->getVerbal($v);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща вербалната стойност на k
     */
    function getVerbal($k)
    {
        if(! round($k) > 0) return '';
        
        if($this->params['mvc']) {
            
            $mvc = &cls::get($this->params['mvc']);
            
            if(($part = $this->params['select']) && $part != '*') {
                
                if(!$rec = $mvc->fetch($k)) {
 
                    return '???';
                }
                
                if ($this->params['translate']) {
                    $rec->{$part} = tr($rec->{$part});
                }
                
                if ($this->params['transliterate']) {
                    $rec->{$part} = transliterate($rec->{$part});
                }
                
                $res = $mvc->getVerbal($rec, $part);
                
                return $res;
            } else {
                $value = $mvc->getTitleById($k);
                
                if ($this->params['translate']) {
                    $value = tr($value);
                }
                
                if ($this->params['transliterate']) {
                    $value = transliterate($value);
                }
            }
        } elseif($this->params['function']) {
        
        } elseif($this->suggestions) {
            
            $value = $this->suggestions[$k];
            
            if ($this->params['translate']) {
                $value = tr($value);
            }
            
            if ($this->params['transliterate']) {
                $value = transliterate($value);
            }
        }
        
        return $value;
    }
    
    
    /**
     * Ако получи списък, вместо keylist, и в същото време
     * има select = конкретно поле от и mvc
     * @todo: да се направи конвертирането
     */
    public function toMysql($value, $db, $notNull, $defValue)
    {
        $value = parent::toMysql($value, $db, $notNull, $defValue);
        
        return $value;
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $attrCB = array();
        
        if(is_array($value)) {
            $value = static::fromArray($value);
        }
        
        // Ако няма списък с предложения - установяваме го
        if(!$this->suggestions) {
            $this->prepareSuggestions();
        }
        
        if(!$value) {
            $values = array();
        } else {
            $values = explode($value{0}, trim($value, $value{0}));
        }
        
        $attrCB['type'] = 'checkbox';
        $attrCB['class'] .= ' checkbox';
        
        // Определяме броя на колоните, ако не са зададени.
        $col = $this->params['columns'] ? $this->params['columns'] :
        min(($this->params['maxColumns'] ? $this->params['maxColumns'] : 4),
            round(sqrt(max(0, count($this->suggestions) + 1))));
        
        $i = 0; $html = ''; $trOpen = TRUE;
        $j = 0; //за конструиране на row-1,row-2 и т.н.
        
        if(count($this->suggestions)) {
            foreach($this->suggestions as $key => $v) {
                
                // Ако имаме група, правим ред и пишем името на групата
                if(is_object($v) && $v->group) {
                	$j++;
                	
                    if($trOpen) {
                        while($i > 0) {
                            $html .= "\n    <td></td>";
                            $i++;
                            $i = $i % $col;
                        }
                        $html .= '</tr>';
                    }
                    
                    if ($this->params['translate']) {
                        $v->title = tr($v->title);
                    }
                    
                    if ($this->params['transliterate']) {
                        $v->title = transliterate($v->title);
                    }
                    
                    if ($groupOpen){
                    	$html .= "</table></td>";
                    }
                   
                    $minusUrl = sbf("img/16/toggle2.png", "");
                    $minusImg =  ht::createElement("img", array('src' => $minusUrl,  'class' => 'btns-icon minus'));
                    
                    $plusUrl = sbf("img/16/toggle1.png", "");
                    $plusImg =  ht::createElement("img", array('src' => $plusUrl, 'class' => 'btns-icon plus'));
                    
                    // Класа за групите
                    $class = 'keylistCategory';
                    
                    // Ако е вдигнат флага, за отваряне на група
                    if ($v->autoOpen) {
                    
                        // Добавяме класа за отворена група
                        $class .= ' group-autoOpen';
                    }
                    
                    $html .= "\n<tr id='row-". $j . "' class='{$class}' ><td class='keylist-group' colspan='" . 
                        $col . "'><div onclick='toggleKeylistGroups(this)'>". $plusImg . $minusImg . $v->title . "</div></td></tr>" .
                        "<tr><td><table class='inner-keylist'>";
                    
                    $groupOpen = 1;
                    $haveChecked = FALSE;
                    $i = 0;
                } else {
                    $attrCB['id'] = $name . "_" . $key;
                    $attrCB['name'] = $name . "[{$key}]";
                    $attrCB['value'] = $key;
                    
                    if(in_array($key, $values)) {
                        $attrCB['checked'] = 'checked';
                        $haveChecked = TRUE;
                    } else {
                        unset($attrCB['checked']);
                    }
                    
                    $v = type_Key::getOptionTitle($v);

                    if ($this->params['translate']) {
                        $v = tr($v);
                    }
                    
                    if ($this->params['transliterate']) {
                        $v = transliterate($v);
                    }
                    
                    $cb = ht::createElement('input', $attrCB);
                    $cb->append("<label  for=\"" . $attrCB['id'] . "\">{$v}</label>");
                    
                    if($i == 0 && $j>0) {
                        $html .= "\n<tr class='row-" .$j . "'>";
                        $trOpen = TRUE;
                    }
                    $html .= "\n    <td>" . $cb->getContent() . "</td>";
                    
                    if($i == $col -1) {
                        $html .= "</tr>";
                        $trOpen = FALSE;
                    }
                    
                    $i++;
                    $i = $i % $col;
                }
 
            }  
            if ($groupOpen){
            	$html .= "</table></td>";
            } 
        } else {
            $html = '<tr><td></td></tr>';
        }
        
        $attr['class'] .= ' keylist';
        $tpl = HT::createElement('table', $attr, $html);
        jquery_Jquery::enable($tpl);
        $tpl->push('js/keylist.js', 'JS');
        jquery_Jquery::run($tpl, "checkForHiddenGroups();", TRUE);
        return $tpl;
    }


    /**
     * Връща масив със всички предложения за този списък
     */
    function getSuggestions()
    {
        if(!$this->suggestions) {
            $this->prepareSuggestions();
        }

        return $this->suggestions;
    }


    /**
     * Подготвя предложенията за списъка
     */
    private function prepareSuggestions()
    {
        if($select = $this->params['select']) {
            $mvc = &cls::get($this->params['mvc']);
            $query = $mvc->getQuery();
                
            if($groupBy = $this->params['groupBy']) {
                $query->orderBy("#{$groupBy}")
                ->show($groupBy);
            }
                
            if($select != "*") {
                $query->show($select)
                ->show('id')
                ->orderBy($select);
            }
                
            // Ако имаме метод, за подготвяне на заявката - задействаме го
            if($onPrepareQuery = $this->params['prepareQuery']) {
                cls::callFunctArr($onPrepareQuery, array($this, $query));
            }
                
            // Ако имаме where клауза за сортиране
            if($where = $this->params['where']) {
                $query->where($where);
            }
            
            // Ако е зададено да се групира
            if ($groupBy) {
                
                // Броя на групите
                $cnt = $query->count();
                
                // Ако броя е под максимално допустимите
                if ($cnt < $this->params['maxOptForOpenGroups']) {
                    
                    // Отваряме всички групи
                    $openAllGroups = TRUE;
                } else {
                    
                    // Ако е зададена, коя група да се отвори
                    if ($this->params['autoOpenGroups']) {
                        
                        // Ако е зададено да се отворят всичките
                        if (trim($this->params['autoOpenGroups']) == '*') {
                            
                            // Вдигаме флага
                            $openAllGroups = TRUE;
                        } else {
                            
                            // Вземаме всички групи, които са зададени да се отворят
                            $autoOpenGroupsArr = type_Keylist::toArray($this->params['autoOpenGroups']);
                        }
                    }
                }
            }
            
            while($rec = $query->fetch()) {
                
                // Ако е групирано
                if($groupBy) {
                    
                    // Флаг, указващ дали да се отвори групата
                    $openGroup = FALSE;
                    
                    if($group != $rec->{$groupBy}) {
                        $key = $rec->id . '_group';
                        $this->suggestions[$key] = new stdClass();
                        $this->suggestions[$key]->title = $mvc->getVerbal($rec, $groupBy);
                        $this->suggestions[$key]->group = TRUE;
                        
                        // Ако е зададено да се отворят всички групи
                        if ($openAllGroups) {
                            
                            // Да се отвори групата
                            $openGroup = TRUE;
                        } else {
                            
                            // Ако е зададено да се отвори текущата група
                            if ($autoOpenGroupsArr[$rec->$groupBy]) {
                                
                                // Вдигаме флага
                                $openGroup = TRUE;
                            }
                        }
                        
                        // Ако е вдигнат флага
                        if ($openGroup) {
                            
                            // Вдигаме флага
                            $this->suggestions[$key]->autoOpen = TRUE;
                        }
                        
                        $group = $rec->{$groupBy};
                    }
                }
                    
                if($select != "*") {
                    $this->suggestions[$rec->id] = $mvc->getVerbal($rec, $select);
                } else {
                    $this->suggestions[$rec->id] = $mvc->getTitleById($rec->id);
                }
            }
        }
    }
    
    
    /**
     * Конвертира стойността от вербална към (int)  
     */
    function fromVerbal_($value)
    {
        if(!is_array($value)) return NULL;
        
        try {
            $res = self::fromArray($value);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $res = FALSE;
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува от масив с индекси ключовете към keylist
     */
    static function fromArray($value)
    {
        if(count($value)) {

            // Сортираме ключовете на масива, за да има
            // стринга винаги нормализиран вид - от по-малките към по-големите
            ksort($value);

            foreach($value as $id => $val)
            {
                if(empty($id) && empty($val)) continue;
                
                if(!ctype_digit(trim($id))) {
                    throw new Exception("Некоректен списък '{$id}' => '{$val}', '{$res}'");
                }
                
                $res .= "|" . $id;
            }
            $res = $res . "|";
        }
        
        return $res;
    }
    
    
    /**
     * Преобразува keylist към масив
     */
    static function toArray($klist)
    {
        if (is_array($klist)) {
            return $klist;
        }
        
        if (empty($klist)) {
            return array();
        }
        
        $kArr = explode('|', $klist);
        
        $resArr = array();
        
        foreach($kArr as $key) {
            if($key !== '') {
                $resArr[$key] = $key;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Проверява дали ключът присъства в дадения keylist
     * Ако ключът е масив, проверява се дали поне един негов елемент
     * присъства в дадения keylist
     */
    static function isIn($key, $list)
    {
        if(is_array($key)) {
            foreach($key as $k) {
                if(self::isIn($k, $list)) {

                    return TRUE;
                }
            }
        } else {
            return strpos($list, '|' . $key . '|') !== FALSE;
        }

        return FALSE;
    }
    
    
    /**
     * Добавя нов ключ към keylist
     *
     * @param mixed $klist масив ([`key`] => `key`) или стринг (`|key1|key2|...|`)
     * @param int $key ключ за добавяне
     * @return string `|key1|key2| ... |key|`
     */
    static function addKey($klist, $key)
    {
        $klist = self::toArray($klist);
        $klist[$key] = $key;
        $klist = self::fromArray($klist);
        
        return $klist;
    }
    
    
    /**
     * Съединяваме два keylist стринга
     * 
     * @param type_Keylist $klist1
     * @param type_Keylist $klist2
     * 
     * @return type_Keylist $newKlist
     */
    static function merge($klist1, $klist2)
    {
        $klist1Arr = self::toArray($klist1);
        $klist2Arr = self::toArray($klist2);
        
        $newArr = $klist1Arr + $klist2Arr;
        
        $newKlist = self::fromArray($newArr);
        
        return $newKlist;
    }
    
    
    /**
     * Премахва от първия кейлист ключовете на вторив
     * 
     * @param type_Keylist $klist1
     * @param type_Keylist $klist2
     * 
     * @return type_Keylist $newKlist
     */
    static function diff($klist1, $klist2)
    {
        $klist1Arr = self::toArray($klist1);
        $klist2Arr = self::toArray($klist2);
        
        $newArr = array_diff($klist1Arr, $klist2Arr);
        
        $newKlist = self::fromArray($newArr);
        
        return $newKlist;
    }

    
    /**
     * Премахва ключ от keylist
     *
     * @param mixed $klist масив ([`key`] => `key`) или стринг (`|key1|key2|...|`)
     * @param int $key ключ за премахване
     * @return string `|key1|key2| ... |key|`
     */
    static function removeKey($klist, $key)
    {
        $klist = self::toArray($klist);
        
        if (isset($klist[$key])) {
            unset($klist[$key]);
        }
        $klist = self::fromArray($klist);
        
        return $klist;
    }


    /**
     * Връща истина или лъжа за това дали дадения стринг отговаря за синтаксиса на keylist
     */
    static function isKeylist($str)
    {
        if(is_string($str) && preg_match("/^\\|[\\-0-9\\|]+\\|$/", $str)) {
            $res = TRUE;
        } else {
            $res = FALSE;
        }

        return $res;
    }
}