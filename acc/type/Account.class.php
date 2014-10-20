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
    public function prepareOptions()
    {
        if (isset($this->options)) {
            return;
        }
        $mvc = cls::get($this->params['mvc']);
        $root = $this->params['root'];
        $select = $this->params['select'];
        $regInterfaces = $this->params['regInterfaces'];
        
        $options = $mvc->makeArray4Select($select, array("#num LIKE '[#1#]%' AND state NOT IN ('closed')", $root));
        
        // Ако има зададени интерфейси на аналитичностите
        if($regInterfaces){
            static::filterSuggestions($regInterfaces, $options);
        }
        
        $this->options = $options;
        
        $this->handler = md5($this->getSelectFld() . $this->params['mvc']);
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $this->prepareOptions();
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function fromVerbal_($value)
    {
        $this->prepareOptions();
        
        return parent::fromVerbal_($value);
    }
    
    
    /**
     * Помощна ф-я филтрираща опциите на модела, така че аналитичностите на
     * сметките да отговарят на някакви интерфейси. Подредбата на итнерфейсите
     * трябва да отговаря на тази на аналитичностите
     *
     * @param string $list - имената на интерфейсите разделени с "|"
     * @param array $suggestions - подадените предложения
     */
    public static function filterSuggestions($list, &$suggestions)
    {
        $arr = explode('|', $list);
        expect(count($arr) <= 3, 'Най-много могат да са зададени 3 интерфейса');
        
        foreach ($arr as $index => $el){
            expect($arr[$index] = core_Interfaces::fetchField("#name = '{$el}'", 'id'), "Няма интерфейс '{$el}'");
        }
        
        if(count($suggestions)){
            
            // За всяка сметка
            foreach ($suggestions as $id => $sug){
                
                // Извличане на записа на сметката
                $rec = acc_Accounts::fetch($id);
                
                // За всеки итнерфейс
                foreach ($arr as $index => $el){
                    
                    // Ако съответния запис няма аналитичност се премахва
                    $fld = "groupId" . ++$index;
                    
                    if(!isset($rec->$fld)) {
                        unset($suggestions[$id]);
                        break;
                    }
                    
                    // Ако има аналитичност, се извлича интерфейса, който поддържа
                    $listIntf = acc_Lists::fetchField($rec->$fld, 'regInterfaceId');
                    
                    // Ако интерфейса не съвпада с подадения, записа се премахва
                    if($listIntf != $el){
                        unset($suggestions[$id]);
                    }
                }
            }
        }
    }
}