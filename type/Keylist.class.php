<?php


/**
 * Броя на всички записи, над които групите ще са отворени по подразбиране
 */
defIfNot('CORE_MAX_OPT_FOR_OPEN_GROUPS', 10);


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
        if($this->params['makeLinks'] && $mvc = $this->params['mvc']) {
            $mvc = cls::get($mvc);
        }
        
        foreach($vals as $v) {
            if($v) {
                $name = $this->getVerbal($v);
                if((!Mode::is('text', 'plain')) && (!Mode::is('printing')) && $mvc instanceof core_Master && $mvc->haveRightFor('single', $v)) {
                	if($this->params['makeLinks'] === 'short'){
                		$name = ht::createLinkRef($name, array($mvc, 'Single', $v));
                	} else {
                		$name = ht::createLink($name, array($mvc, 'Single', $v));
                	}
                }
                $res .= ($res ? ", " : '') . $name;
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
                
                $res = $mvc->getVerbal($rec, $part);
                
                return $res;
            } else {
                $value = $mvc->getTitleById($k);
            }
        } elseif($this->params['function']) {
        
        } elseif($this->suggestions) {
            
            $value = $this->suggestions[$k];
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
        min(($this->params['maxColumns'] ? $this->params['maxColumns'] : ((Mode::is('screenMode', 'wide')) ? 4 : 2)),
            round(sqrt(max(0, count($this->suggestions) + 1))));
        
        $i = 0; $html = ''; $trOpen = FALSE;
        $j = 0; //за конструиране на row-1,row-2 и т.н.
        
        $keyListClass = 'keylist';
        
        $suggCnt = count($this->suggestions);
        
        if($suggCnt) {
        	if($suggCnt < 4 ) {
        		$keyListClass .= ' shrinked';
        	}
        	
        	$groupOpen = 0;
        	$addKeylistWide = FALSE;
        	
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
                    
                    if ($groupOpen){
                    	$html .= "</table></td>";
                    }
                   
                    $minusUrl = sbf("img/16/toggle2.png", "");
                    $minusImg =  ht::createElement("img", array('src' => $minusUrl,  'class' => 'btns-icon minus'));
                    
                    $plusUrl = sbf("img/16/toggle1.png", "");
                    $plusImg =  ht::createElement("img", array('src' => $plusUrl, 'class' => 'btns-icon plus'));
                    
                    $checkedUrl = sbf("img/16/checked.png", "");
                    $checkImg =  ht::createElement("img", array('src' => $checkedUrl, 'class' => 'btns-icon invert-checkbox hidden'));
                    
                    $uncheckedUrl = sbf("img/16/unchecked.png", "");
                    $uncheckImg =  ht::createElement("img", array('src' => $uncheckedUrl, 'class' => 'btns-icon invert-checkbox '));
                    
                    // Класа за групите
                    $class = 'keylistCategory';
                    
                    // Ако е вдигнат флага, за отваряне на група
                    if ($v->autoOpen) {
                    
                        // Добавяме класа за отворена група
                        $class .= ' group-autoOpen';
                    }
                    
                    $addKeylistWide = TRUE;
                    
                    $html .= "\n<tr id='row-". $j . "' class='{$class}' ><td class='keylist-group'><div>". $plusImg . $minusImg . $v->title . $checkImg  . $uncheckImg."</div></td></tr>" .
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
                    
                    $cb = ht::createElement('input', $attrCB);
                    
                    if($this->params['maxCaptionLen'] &&  $this->params['maxCaptionLen'] < mb_strlen($v)) {
                    	$title = " title=" . ht::escapeAttr($v);
                    	$v = str::limitLen($v, $this->params['maxCaptionLen']);
                    } else {
                    	$title = "";
                    }
                    $cb->append("<label {$title} data-colsInRow='" .$col   . "' for=\"" . $attrCB['id'] . "\">{$v}</label>");
                    
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
                while($i < $col) {
                    $html .= "<td></td>";
                    $i++;
                }
            	$html .= "</tr></table></td>";
            } 
        } else {
            $html = "<tr><td><i style='color:grey;'>" . tr('Няма записи за избор') . "</i></td></tr>";
        }
        
        if ($addKeylistWide) {
            $keyListClass .= ' keylist-wide';
        }
        
        $attr['class'] .= " " . $keyListClass ;
        $tpl = HT::createElement('table', $attr, $html);
        jquery_Jquery::run($tpl, "keylistActions();", TRUE);
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
        $mvc = cls::get($this->params['mvc']);
        
        $mvc->invoke('BeforePrepareSuggestions', array(&$this->suggestions, $this));
        
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
        
        $mvc->invoke('AfterPrepareSuggestions', array(&$this->suggestions, $this));
    }
    
    
    /**
     * Конвертира стойността от вербална към (int)  
     */
    function fromVerbal_($value)
    {
        if(!is_array($value)) return NULL;
        
        try {
            $res = self::fromArray($value);
        } catch (core_exception_Expect $e) {
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
                    throw new core_exception_Expect("Некоректен списък '{$id}' => '{$val}', '{$res}'");
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
    
    
    
    /**
     * Проверява дали kelist-а/масива е празен
     * 
     * @param mixed $klist - Масив или klist, който да се проверява
     * 
     * @return boolean - Ако е празен, връщаме истина
     */
    static function isEmpty($klist) 
    {
        // Преобразуваме в масив
        $klist = self::toArray($klist);
        
        // Ако е празен
        if (!$klist) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща масив с различията между хранилищата
     * 
     * @param string $fRepos - Първият масив/keylist
     * @param string $lRepos - Вторият масив/keylist
     * @param boolean $useKey - Дали да се използват ключовете за сравнение
     * 
     * @return array $arr - Масив с различията
     * $arr['same'] - без промяна
     * $arr['delete'] - изтрити от първия
     * $arr['add'] - добавени към първия
     */
    static function getDiffArr($fRepos, $lRepos, $useKey=FALSE)
    {
        // Вземаме масива на първия
        $fReposArr = type_Keylist::toArray($fRepos);
        
        // Вземаме масива на втория
        $lReposArr = type_Keylist::toArray($lRepos);
        
        // Ако е сетнат флага
        if ($useKey) {
            
            // Задаваме ключовете, като стойности
            $fReposArr = array_keys($fReposArr);
            $lReposArr = array_keys($lReposArr);
        }
        
        // Изчисляваме различията
        $arr['same'] = array_intersect($fReposArr, $lReposArr);
        $arr['delete'] = array_diff($fReposArr, $lReposArr);
        $arr['add'] = array_diff($lReposArr, $fReposArr);
        
        return $arr;
    }
}
