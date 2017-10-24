<?php



/**
 * Клас acc_type_Accounts, за избиране на счетоводни сметки
 *
 * Ако е зададен параметър 'root' - може да се избират само
 * сметките започващи с този номер
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_type_Accounts extends type_Keylist
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
        if (isset($this->options)) {
            
            return $this->options;
        }
        $mvc = cls::get($this->params['mvc']);
        $root = $this->params['root'];
        $select = $this->params['select'];
        $regInterfaces = $this->params['regInterfaces'];
        
        $suggestions = $mvc->makeArray4Select($select, array("#num LIKE '[#1#]%' AND #state NOT IN ('closed')", $root));
        
        // Ако има зададени интерфейси на аналитичностите
        if($regInterfaces){
            acc_type_Account::filterSuggestions($regInterfaces, $suggestions);
        }
        
        $this->suggestions = $suggestions;
        
        return $this->options;
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
}