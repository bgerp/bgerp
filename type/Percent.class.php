<?php

defIfNot('EF_PERCENT_DECIMALS', 2);

/**
 * Клас  'type_Percent' - Тип за проценти
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Yusein YUseinov
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Percent extends type_Double {
    
    /**
     * Инициализиране на типа
     */
	function init($params)
	{
		parent::init($params);
		setIfNot($this->params['decimals'], EF_PERCENT_DECIMALS);
	}
	
	
    /**
     *  @todo Чака за документация...
     */
	function toVerbal($value) 
	{
		if(!isset($value)) return NULL;
		$value = $value * 100;
		
		return parent::toVerbal($value) . '&nbsp;%';
	}
	
	
	/**
	 *  Преобразуване от вербална стойност, към вътрешно представяне за процент (0-1)
	 */
	function fromVerbal($value)
	{
		$value = str_replace('%', '', $value);
		$value = parent::fromVerbal($value);
		$value = $value/100;
		
		return $value;
	}
	
	function renderInput_($name, $value="", $attr = array())
	{
		$value = (100 * $value) . ' %';
		
		return parent::renderInput_($name, $value, $attr);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}