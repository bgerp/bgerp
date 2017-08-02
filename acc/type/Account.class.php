<?php



/**
 * Клас acc_type_Account, за избиране на счетоводна сметка
 *
 * Ако е зададен параметър 'root' - може да се избират само
 * сметките започващи с този номер
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_type_Account extends type_Key
{
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        $params['params']['mvc'] = 'acc_Accounts';
        
        setIfNot($params['params']['select'], 'title');
        setIfNot($params['params']['root'], '');
        setIfNot($params['params']['regInterfaces'], '');
        
        parent::init($params);
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     *
     * `$this->params['root']` е префикс, който трябва да имат номерата на всички сметки-опции
     */
    public function prepareOptions($value = NULL)
    {
    	if(isset($this->options)) {
    		
    		return $this->options;
    	}
    	
    	$mvc = cls::get($this->params['mvc']);
        $root = $this->params['root'];
        $select = $this->params['select'];
        $regInterfaces = $this->params['regInterfaces'];
       
        $options = $mvc->makeArray4Select($select, array("#num LIKE '[#1#]%' AND #state NOT IN ('closed')", $root));
        
        // Ако има зададени интерфейси на аналитичностите
        if($regInterfaces){
            static::filterSuggestions($regInterfaces, $options);
        }
        
        $this->options = $options;
        
        $this->handler = md5($this->getSelectFld() . $this->params['mvc']);
        
        $this->options = parent::prepareOptions();
        
        return $this->options;
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function toVerbal_($value)
    {
    	$row = acc_Accounts::recToVerbal($value, 'title,num');
    	
    	return $row->num . "." . $row->title;
    }
    
    
    /**
     * Помощна ф-я филтрираща опциите на модела, така че аналитичностите на
     * сметките да отговарят на някакви интерфейси. Подредбата на итнерфейсите
     * трябва да отговаря на тази на аналитичностите. Остават само тези сметки които имат всички посочени
     * интерфейси. Ако за интерфейси е посочено 'none', остават само сметките без разбивки
     *
     * @param string $list - имената на интерфейсите разделени с "|"
     * @param array $suggestions - подадените предложения
     */
    public static function filterSuggestions($list, &$suggestions)
    {
        $arr = explode('|', $list);
        expect(count($arr) <= 3, 'Най-много могат да са зададени 3 интерфейса');
       
        foreach ($arr as $index => $el){
        	if($el == 'none') continue;
            expect($arr[$index] = core_Interfaces::fetchField("#name = '{$el}'", 'id'), "Няма интерфейс '{$el}'");
        }
        
        if(count($suggestions)){
           
            // За всяка сметка
            foreach ($suggestions as $id => $sug){

            	if(is_object($sug)) continue;
                
                // Извличане на записа на сметката
                $rec = acc_Accounts::fetch($id);
                
                
                foreach (range(0, 2) as $i){
                	$fld = "groupId" . ($i + 1);
                	
                	if(isset($arr[$i]) && $arr[$i] != 'none' && !isset($rec->{$fld})){
                		unset($suggestions[$id]);
                		break;
                	}
                	
                	if(empty($rec->{$fld})) continue;
                	
                	// Ако има аналитичност, се извлича интерфейса, който поддържа
                	$listIntf = acc_Lists::fetchField($rec->{$fld}, 'regInterfaceId');
                	
                	if($listIntf != $arr[$i]){
                		unset($suggestions[$id]);
                		break;
                	}
                }
            }
        }
        
        if(is_array($suggestions)){
        	$resetArr = array_values($suggestions);
        	$map = array_combine(array_keys($resetArr), array_keys($suggestions));
        	
        	// От опциите махаме групите на сметките, ако в тях не са останали сметки
        	foreach ($resetArr as $i => $v){
        		$vNext = $resetArr[$i+1];
        		
        		// Ако текущото предложение е група и след нея следва друга група, я махаме
        		if(is_object($v) && (is_object($vNext) || !$vNext)){
        			$unsetKey = $map[$i];
        			unset($suggestions[$unsetKey]);
        		}
        	}
        }
    }
}