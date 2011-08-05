<?php
/**
 * Ключ към запис от core_Adapters
 *
 * @see core_Adapters
 *
 * @category   Experta Framework
 * @package    type
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class type_Adapter extends type_Key
{
	/**
     *  Инициализиране на обекта
     */
    function init($params)
    {
        $params['params']['mvc'] = 'core_Adapters';

        setIfNot($params['params']['select'], 'title');
    	
        parent::init($params);
    }
    

    /**
     * Подготвя опциите според зададените параметри
     * Ако е посочен суфикс, извеждате се само адаптерите
     * чуето име завършва на този суфикс
     */
    private function prepareOptions()
    {
        if (empty($this->params['suffix'])) {
        	return;
        }
        
    	$mvc = cls::get($this->params['mvc']);
    	
    	$allAdapters = $mvc->makeArray4Select('name');

    	$this->options = array();
        
        $suffix = $this->params['suffix'];
        $lenSuffix = strlen($suffix);

    	foreach ($allAdapters as $id => $name) {
            if ((!$suffix) || (strrpos($name, $suffix) == (strlen($name) - $lenSuffix)) ) {
    			$this->options[$id] = $mvc->fetchField($id, $params['params']['select']);
    		}
    	}
    }
    

    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value="", $attr = array())
    {
		$this->prepareOptions();
    	
		return parent::renderInput_($name, $value, $attr);
    }
    

    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Adapters
     */
	function fromVerbal_($value)
	{
		$this->prepareOptions();

		return parent::fromVerbal($value);
	}
    
}