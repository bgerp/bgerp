<?php



/**
 * Ключ към запис от core_Interfaces
 *
 *
 * @category  ef
 * @package   type
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       core_Interfaces
 */
class type_Interface extends type_Key
{
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        $params['params']['mvc'] = 'core_Interfaces';
        
        setIfNot($params['params']['select'], 'title');
        
        parent::init($params);
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     * Ако е посочен суфикс, извеждате се само интерфейсите
     * чието име завършва на този суфикс
     */
    public function prepareOptions($value = NULL)
    {
        Mode::push('text', 'plain');
        
    	$this->invoke('BeforePrepareKeyOptions', array(&$this->options, $this));
    	
    	if (!isset($this->options)) {
    	    
        	$mvc = cls::get($this->params['mvc']);
            
            $allInterfaces = $mvc->makeArray4Select('name');
            
            $this->options = array();
            
            $suffix = $this->params['suffix'];
            
            $lenSuffix = strlen($suffix);
            
            if(count($allInterfaces)) {
                foreach ($allInterfaces as $id => $name) {
                    if ((!$suffix) || (strrpos($name, $suffix) == (strlen($name) - $lenSuffix))) {
                        $mvc->fetchByName($name);
                        $this->options[$id] = $mvc->fetchField($id, $this->params['select']);
                    }
                }
            }
    	}
        
        Mode::pop('text');
        
        $this->options = parent::prepareOptions();
        
        return $this->options;
    }
}
