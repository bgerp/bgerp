<?php
/**
 * Ключ към регистриран интерфейс
 *
 * @see core_Interfaces
 *
 * @category   Experta Framework
 * @package    type
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class type_Interface extends type_Key
{
	/**
     *  Инициализиране на обекта
     */
    function init($params)
    {
        $params['params']['mvc'] = 'core_Interfaces';
        setIfNot($params['params']['select'], 'info');
    	
        parent::init($params);
    }
    
    private function prepareOptions()
    {
        if (empty($this->params['root'])) {
        	return;
        }
        
    	$mvc = cls::get($this->params['mvc']);
    	
    	$allIntf = $mvc->makeArray4Select('name');
    	$this->options = array();
    	foreach ($allIntf as $id=>$name) {
    		if (cls::isSubinterfaceOf($name, $this->params['root'])) {
    			$this->options[$id] = $mvc->fetchField($id, 'info');
    		}
    	}
    }
    
    function renderInput_($name, $value="", $attr = array())
    {
		$this->prepareOptions();
    	
		return parent::renderInput_($name, $value, $attr);
    }
    
	function fromVerbal_($value)
	{
		$this->prepareOptions();

		return parent::fromVerbal($value);
	}
    
}