<?php

/**
 * Колко цифри след запетаята да се показват
 */
defIfNot('EF_TEMPERATURETYPE_DECIMALS', 2);


/**
 * Мерната единица по подразбиране
 */
defIfNot('EF_DEFAULT_UNIT', '°C');


/**
 * Клас  'physics_TemperatureType' - Тип за температура
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Yusein Yuseinov
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class physics_TemperatureType extends type_Double
{
	
	
	/**
     * Инициализиране на типа
     */
	function init($params)
	{
		parent::init($params);
		setIfNot($this->params['decimals'], EF_TEMPERATURETYPE_DECIMALS);
		setIfNot($this->params['default_unit'], EF_DEFAULT_UNIT);
	}
	
	
	/**
	 * Преобразуване от Фаренхайт в Целзий
	 */
	function farToCels($far)
	{
		$cels = ($far - 32) / 1.8;
		
		return $cels;
	}
	
	
	/**
	 * Преобразуване от Целзий във Фаренхайт
	 */
	function celsToFar($cels)
	{
		$far = ($cels * 1.8) + 32;
		
		return $far;
	}
	
	
	/**
	 * Преобразуване от Келвин в Целзий
	 */
	function kelvToCels($kelv)
	{
		$cels = $kelv - 273.15;
		
		return $cels;
	}
	
	
	/**
	 * Преобразуване от Целзий в Келвин
	 */
	function celsToKelv($cels)
	{
		$kelv = $cels + 273.15;
		
		return $kelv;
	}
	
	
	/**
	 * Преобразуване от Келвин във Фаренхайт
	 */
	function kelvToFar($kelv)
	{
		$far = ($kelv*1.8) - 459.67;
		
		return $far;
	}
	
	
	/**
	 * Преобразуване от Фаренхайт в Келвин
	 */
	function farToKelv($far)
	{
		$kelv = ($far + 459.67) / 1.8;
		
		return $kelv;
	}
	
	/**
	 * Преобразуване от Целзий в Целзий
	 */
	function celsToCels($cels)
	{
		return $cels;
	}
	
	
	/**
	 * Преобразуване от Фаренхайт във Фаренхайт
	 */
	function farToFar($far)
	{
		return $far;
	}
	
	
	/**
	 * Преобразуване от Келвин в Келвин
	 */
	function kelvToKelv($kelv)
	{
		return $kelv;
	}
	
	
	/**
     *  Преобразуване от вътрешно представяне към вербална стойност
     */
	function toVerbal($value) 
	{
		if(!isset($value)) return NULL;
		
		return parent::toVerbal($value) . ' ' . $this->params['default_unit'];
	}
	
	
	/**
	 *  Преобразуване от вербална стойност, към вътрешно представяне
	 */
	function fromVerbal($value)
	{
		//Проверява единицата на въведената стойност
  		if (stristr($value, 'F') == TRUE) {
  			$str = 'far';
  		} elseif (stristr($value, 'K') == TRUE) {
  			$str = 'kelv';
  		} else {
  			$str = 'cels';
  		}
  		
  		$str .= 'To';
  		
  		//Проверява единицата на стойността по подразбиране
		if (stristr($this->params['default_unit'], 'F') == TRUE) {
  			$str .= 'Far';
  		} elseif (stristr($this->params['default_unit'], 'K') == TRUE) {
  			$str .= 'Kelv';
  		} else {
  			$str .= 'Cels';
  		}
  		
  		//Премахва единиците за температура и праща данните за обработка
		$valForReplace = array('°', 'C', 'K', 'F');
		$value = str_ireplace($valForReplace, '', $value);
		$value = parent::fromVerbal($value);
		return $this->$str($value);
	}
	
	
	/**
	 *Преобразуване от вътрешно представяне към вербална стойност
	 */
	function renderInput_($name, $value="", $attr = array())
	{	
		if (!is_numeric($value)) $value='';
		
		$value = parent::toVerbal($value) . ' ' . $this->params['default_unit'];
		
		return parent::renderInput_($name, $value, $attr);
	}

}











