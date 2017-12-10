<?php




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
    public $dbFieldType = 'text';
    

    /**
     * Тук записваме само числа
     */
    public $collation = 'ascii_bin';
    
    
    /**
     * Хендлър на класа
     *
     * @var string
     */
    public $handler;
    

	/**
     * Конструктор. Дава възможност за инициализация
     */
    function init($params = array())
    {
        parent::init($params);
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function toVerbal_($value)
    {
        static $cache;

        if(empty($value)) return NULL;
        
        $value = trim($value);

        // Очакваме валиден keylist
        if(preg_match("/^[0-9\\|]*$/", $value)) {
            $div = '|';
        } elseif(preg_match("/^[0-9\\,]*$/", $value)) {
            $div = ',';
        } else {
            error('500 Очакваме валиден keylist');
        }
        
        $value = trim($value, $div);

        $vals = explode($div, $value);
        
        $mvc = cls::get($this->params['mvc']);
       
        $ids = str_replace($div, ',', $value);
        
        if($ids) {  
            if(($res = $cache[$mvc->className][$ids]) === NULL) {
                $query = $mvc->getQuery();
                //$query->where("#id IN ($ids)");
                //while($query->fetchAndCache()) {}
 
                foreach($vals as $v) {
                    if($v) { 
                        $name = $this->getVerbal($v);
                        if((!Mode::is('text', 'xhtml')) && (!Mode::is('text', 'plain')) && (!Mode::is('printing')) && $mvc instanceof core_Master && $mvc->haveRightFor('single', $v)) {
                            $attr = array();
                            if(isset($this->params['classLink'])){
                                $attr = array('class' => $this->params['classLink']);
                            }
                            
                            if($this->params['makeLinks'] === 'short'){
                                $name = ht::createLinkRef($name, array($mvc, 'Single', $v), FALSE, $attr);
                            } else {
                                $name = ht::createLink($name, array($mvc, 'Single', $v), FALSE, $attr);
                            }
                        }
                        if(Mode::is('text-export', 'csv')) {
                            $delimeter = '|';
                        } else {
                            $delimeter = (isset($this->params['classLink']) && !Mode::is('text', 'plain')) ? " " : ", ";
                        }
                        $res .= ($res ? $delimeter : '') . $name;
                    }
                }

                $cache[$mvc->className][$ids] = $res;
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
     * 
     * @param string $name
     * @param string $value
     * @param array|NULL $attr
     * 
     * @see core_Type::renderInput_()
     * 
     * @return core_ET
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $attrCB = array();
        
        if(is_array($value)) {
            $value = static::fromArray($value);
        }
        
        // Ако няма списък с предложения - установяваме го
        if(!isset($this->suggestions)) {
            $this->prepareSuggestions();
        }

        if($value === NULL) {
            $emptyValue = TRUE;
        }
        
        if(!$value) {
            $values = array();
        } else {
            $values = explode($value{0}, trim($value, $value{0}));
        }
        
        $attrCB['type'] = 'checkbox';
        $attrCB['class'] .= ' checkbox';
        
        // Определяме броя на колоните, ако не са зададени.
        $maxChars = $this->params['maxChars'];
        $col = self::getCol((array)$this->suggestions, $maxChars);
      
        $i = 0; $html = ''; $trOpen = FALSE;
        static $j = 0; //за конструиране на row-1,row-2 и т.н.
        
        $keyListClass = 'keylist';
        
        $suggCnt = count($this->suggestions);
        
        if($suggCnt) {
        	if($suggCnt < 4 ) {
        		$keyListClass .= ' shrinked';
        	}
        	
        	$groupOpen = 0;
        	$addKeylistWide = FALSE;

            if(count($this->suggestions) == 1 && $this->params['mandatory'] && $emptyValue) {
                $key = key($this->suggestions);
                $values[$key] = $key;
            }
        	
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
                    $checkImg =  ht::createElement("img", array('src' => $checkedUrl, 'class' => 'btns-icon invert-checkbox checked'));
                    
                    $uncheckedUrl = sbf("img/16/unchecked.png", "");
                    $uncheckImg =  ht::createElement("img", array('src' => $uncheckedUrl, 'class' => 'btns-icon invert-checkbox unchecked'));
                    
                    // Класа за групите
                    $class = 'keylistCategory';
                    
                    // Ако е вдигнат флага, за отваряне на група
                    if ($v->autoOpen) {
                    
                        // Добавяме класа за отворена група
                        $class .= ' group-autoOpen';
                    }
                    
                    $addKeylistWide = TRUE;
                    
                    $html .= "\n<tr id='row-". $j . "' class='{$class}' ><td class='keylist-group noSelect'><div>" . $checkImg  . $uncheckImg . "<span class='invertTitle'>". $v->title . "</span>" .  $plusImg . $minusImg . "</div></td></tr>" .
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
                    
                    if(0.9 * $maxChars < mb_strlen($v)) {
                    	$title = " title=\"" . ht::escapeAttr($v) . "\"";
                    	$v = str::limitLen($v, $maxChars * 1.08);
                    } else {
                    	$title = "";
                    }
                    
                    $v = type_Varchar::escape($v);

                    list(, $uId)=explode("_", $key);
                    if($this->info[$uId]) {
                        $v = "<span class='profile-state'>" . $v . "</span>";
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
            $mvc = cls::get($this->params['mvc']);
            $msg = tr('Липсва избор за');
            $title = tr($mvc->title);
            if($mvc->haveRightFor('list')) {
                $url = array($mvc, 'list');
                $title = ht::createLink($title, $url, FALSE, 'style=font-weight:bold;');
            }

            $cssClass = $this->params['mandatory'] ? 'inputLackOfChoiceMandatory' : 'inputLackOfChoice';

            $html = "<span class='{$cssClass}'>{$msg} {$title}</div>";
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
     * Определяне на броя колонки за чексбоксчетата
     * 
     * @param array $options    Всички опции
     * @param int   $maxChars   Максимален брой символи в опция
     *
     * @return int              Брой колонки
     */
    public static function getCol($options, &$maxChars)
    {
        $options = (array)$options;
        if(!$maxChars) {
            $maxChars = Mode::is('screenMode', 'wide') ? 100 : 50;
            if(count($options) < 6) {
                $maxChars = $maxChars / 2;
            }
        }
 
        // Разпределяме опциите в 2,3 и 4 групи и гледаме при всяко разпределение, колко е максималния брой опции
        $i = 0;
        foreach($options as $key => $v) {
            if($v->group) {
                $i = 0;
                continue;
            }
            for($j = 2; $j <= 4; $j++) {
                $max[$j][$i % $j] = max($max[$j][$i % $j], min($maxChars * 0.9, mb_strlen(type_Key::getOptionTitle($v))));
                $res[] = type_Key::getOptionTitle($v);
            }
            $i++;
        }
        
        $max2 = $max[2][0] + $max[2][1] + 4;
        $max3 = $max[3][0] + $max[3][1] + $max[3][2] + 8;
        $max4 = $max[4][0] + $max[4][1] + $max[4][2] + $max[4][3] + 12;

        if($max2 > $maxChars) {
            $col = 1;
        } elseif($max3 > $maxChars) {
            $col = 2;
        } elseif($max4 > $maxChars) {
            $col = 3;
        } else {
            $col = 4;
        }
        
        return $col;
    }


    /**
     * Връща масив със всички предложения за този списък
     */
    function getSuggestions()
    {
        if(!isset($this->suggestions)) {
            $this->prepareSuggestions();
        }

        return $this->suggestions;
    }


    /**
     * Подготвя предложенията за списъка
     * 
     * @return array
     */
    public function prepareSuggestions($ids = NULL)
    {
        $mvc = cls::get($this->params['mvc']);
        
        // Ако не е зададен параметъра
        if(!isset($this->params['maxOptForOpenGroups'])) {
            $conf = core_Setup::getConfig();
            $maxOpt = $conf->_data['CORE_MAX_OPT_FOR_OPEN_GROUPS'];
            if(!isset($maxOpt)) {
                $maxOpt = CORE_MAX_OPT_FOR_OPEN_GROUPS;
            } 
            setIfNot($this->params['maxOptForOpenGroups'], $maxOpt);
        }

        if (!isset($this->suggestions)) {
            $this->suggestions = array();
        }
        
        $mvc->invoke('BeforePrepareSuggestions', array(&$this->suggestions, $this));
        
        if($select = $this->params['select']) {
            $mvc = &cls::get($this->params['mvc']);
            $query = $mvc->getQuery();
                
            if($groupBy = $this->params['groupBy']) {
                $query->orderBy("#{$groupBy}")
                ->show($groupBy);
            }

            if($where = $this->params['where']) {
                $query->where("{$where}");
            }
            
            if($orderBy = $this->params['orderBy']) {
                $query->orderBy("#{$orderBy}", NULL, TRUE); 
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
        
        return $this->suggestions;
    }
    
    
    /**
     * Конвертира стойността от вербална към (int)  
     * 
     * @param mixed $value
     * 
     * @see core_Type::fromVerbal_()
     * 
     * @return mixed
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
     * 
     * @param array $value
     * 
     * @return string
     */
    static function fromArray($value)
    {
        $res = '';
        
        if (is_array($value) && !empty($value)) {

            // Сортираме ключовете на масива, за да има
            // стринга винаги нормализиран вид - от по-малките към по-големите
            ksort($value);

            foreach($value as $id => $val)
            {
                if(empty($id) && empty($val)) continue;
                
                if(!is_numeric(trim($id))) {
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
    static function merge($klist1, $klist2, $klist3 = NULL, $klist4 = NULL)
    {
        $klist1Arr = self::toArray($klist1);
        $klist2Arr = self::toArray($klist2);
        
        $newArr = $klist1Arr + $klist2Arr;

        if($klist3) {
            $newArr += self::toArray($klist3);
        }
        
        if($klist4) {
            $newArr += self::toArray($klist4);
        }
      
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
     * @param string|array $fArr - Първият масив/keylist
     * @param string|array $sArr - Вторият масив/keylist
     * @param boolean $useKey - Дали да се използват ключовете за сравнение
     * 
     * @return array $arr - Масив с различията
     * $arr['same'] - без промяна
     * $arr['delete'] - изтрити от първия
     * $arr['add'] - добавени към първия
     */
    static function getDiffArr($fArr, $sArr, $useKey=FALSE)
    {
        // Вземаме масива на първия
        $fArr = type_Keylist::toArray($fArr);
        
        // Вземаме масива на втория
        $sArr = type_Keylist::toArray($sArr);
        
        // Ако е сетнат флага
        if ($useKey) {
            
            // Задаваме ключовете, като стойности
            $fArr = array_keys($fArr);
            $sArr = array_keys($sArr);
        }
        
        // Изчисляваме различията
        $arr['same'] = array_intersect($fArr, $sArr);
        $arr['delete'] = array_diff($fArr, $sArr);
        $arr['add'] = array_diff($sArr, $fArr);
        
        return $arr;
    }


    /**
     * Нормализира записа на keylist
     */
    static function normalize($list)
    {
        $arr = explode('|', trim($list, '|'));
        asort($arr);
        $list = '|' . implode('|', $arr) . '|';

        return $list;
    }
}
