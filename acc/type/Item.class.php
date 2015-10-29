<?php



/**
 * Клас acc_type_Item
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_type_Item extends type_Key
{
    
    
    /**
     * Параметър определящ максималната широчина на полето
     */
    var $maxFieldSize = 30;
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        $params['params']['mvc'] = 'acc_Items';
        
        setIfNot($params['params']['select'], 'title');
        
        parent::init($params);
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     *
     * `$this->params['root']` е префикс, който трябва да имат номерата на всички опции
     */
    public function prepareOptions()
    {
        expect($lists = $this->params['lists'], $this);
        
        if (isset($this->options)) {
            
            return $this->options;
        }
        
        $mvc = cls::get($this->params['mvc']);
        $select = $this->params['select'];
        
        if (!is_array($lists)) {
            $lists = explode('|', $lists);
        }
        
        $this->options = array();
        
        $cleanQuery = $mvc->getQuery();
        $cleanQuery->show("id, {$select}, state");
        
        // За всяка от зададените в `lists` номенклатури, извличаме заглавието и принадлежащите 
        // й пера. Заглавието става <OPTGROUP> елемент, перата - <OPTION> елементи
        foreach ($lists as $list) {
            $byField = is_numeric($list) ? 'num' : 'systemId';
            $listRec = acc_Lists::fetch(
                array("#{$byField} = '[#1#]'", $list),
                'id, num, name, caption'
            );
            
            // Създаваме <OPTGROUP> елемента (само ако листваме повече от една номенклатура)
            if (count($lists) > 1) {
                $this->options["x{$listRec->id}"] = (object)array(
                    'title' => $listRec->caption,
                    'group' => TRUE,
                );
            }
            
            // Извличаме перата на текущата номенклатура
            $query = clone($cleanQuery);
            $query->where("#lists LIKE '%|{$listRec->id}|%'");
            
            // Показваме само активните, само ако е не е зададено в типа 'showAll'
            if(empty($this->params['showAll'])){
                $query->where("#state = 'active'");
            }
            
            while ($itemRec = $query->fetch()) {
                $title = $itemRec->{$select};
                
                // Ако перото е затворено, указваме го в името му
                if($itemRec->state == 'closed'){
                    $title .= " (" . tr('затворено') . ")";
                }
                
                // Слагаме вербалното име на перата, и за всеки случай премахваме html таговете ако има
                $this->options["{$itemRec->id}.{$listRec->id}"] = $title;
            }
            
            $where .= ($query->where) ? $query->getWhereAndHaving()->w : ' ';
        }
        
        $this->handler = md5($this->getSelectFld() . $where . $this->params['mvc']);
        
        $this->options = parent::prepareOptions();
        
        return $this->options;
    }
    
    
    /**
     * Връща възможните стойности за ключа
     * 
     * @param string $value
     * 
     * @return array
     */
    function getAllowedKeyVal($id, $listId = NULL)
    {
        $inst = cls::get($this->params['mvc']);
        
        $rec = $inst->fetch($id);
        $listArr = type_Keylist::toArray($rec->lists);
        
        $resArr = array();
        
        foreach ($listArr as $listId) {
            $resArr[] = $id . '.' . $listId;
        }
        
        return $resArr;
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $this->prepareOptions();
        
        $conf = core_Packs::getConfig('core');
        setIfNot($maxSuggestions, $this->params['maxSuggestions'], $conf->TYPE_KEY_MAX_SUGGESTIONS);
        
        foreach ($this->options as $key => $val) {
            if (!is_object($val) && intval($key) == $value) {
                
                $value = $key;
                
                break;
            }
        }

        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * 
     * 
     * @see type_Key::fromVerbal_()
     */
    function fromVerbal_($value)
    {
        $value = parent::fromVerbal_($value);
        
        if(isset($value)){
        	$value = intval($value);
        }
        
        return $value;
    }
    
    
    /**
     * 
     * 
     * @param mixed $key
     * 
     * @return mixed
     */
    public function prepareKey($key)
    {
        // Позволените са латински букви, цифри и .
        $key = preg_replace('/[^A-Z0-9\.]/i', '', $key);
        
        return $key;
    }
    
    
    /**
     * 
     * 
     * @param string $value
     * 
     * @return object
     */
    protected function fetchVal(&$value)
    {
        $mvc = &cls::get($this->params['mvc']);
        
        $rec = $mvc->fetch(intval($value));
        
        return $rec;
    }
}
