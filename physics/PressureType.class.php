<?php

/**
 * Колко цифри след запетаята да се показват
 */
defIfNot('EF_PRESSURETYPE_DECIMALS', 1);


/**
 * Мерната единица по подразбиране
 */
defIfNot('EF_DEFAULT_UNIT_PRESSURE', 'bar');


/**
 * Клас  'physics_PressureType' - Тип за температура
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
class physics_PressureType extends type_Double
{
	
	
	/**
     * Инициализиране на типа
     */
	function init($params)
	{
		parent::init($params);
		setIfNot($this->params['decimals'], EF_PRESSURETYPE_DECIMALS);
		setIfNot($this->params['defaultUnit'], EF_DEFAULT_UNIT_PRESSURE);
	}
	
	
	/**
	 *Преобразуване от вътрешно представяне към вербална стойност
	 */
	function renderInput_($name, $value="", $attr = array())
	{	
		if (!is_numeric($value)) $value=0;
		
		$value = parent::toVerbal($value) . ' ' . $this->params['defaultUnit'];
		
		return parent::renderInput_($name, $value, $attr);
	}
	
	
	/**
     *  Преобразуване от вътрешно представяне към вербална стойност
     */
	function toVerbal($value) 
	{
		if(!isset($value)) return NULL;
		return parent::toVerbal($value) . ' ' . $this->params['defaultUnit'];
	}
	
	
	/**
	 *  Преобразуване от вербална стойност, към вътрешно представяне
	 */
	function fromVerbal($value)
	{	
		$convertFrom = $this->checkUnit($value);
  		$convertTo = $this->checkUnit($this->params['defaultUnit']);
  		$prefix = $this->checkUnitPrefix($value);
  		//Преобразува в невербална стойност
  		$from = array('<dot>', '[dot]', '(dot)', '{dot}', ' dot ',
            ' <dot> ', ' [dot] ', ' (dot) ', ' {dot} ');
        $to = array('.', '.', '.', '.', '.', '.', '.', '.', '.');
        $value = str_ireplace($from, $to, $value);
        
        $from = array('<comma>', '[comma]', '(comma)', '{comma}', ' comma ',
            ' <comma> ', ' [comma] ', ' (comma) ', ' {comma} ');
        $to = array(',', ',', ',', ',', ',', ',', ',', ',', ',');
        $value = str_ireplace($from, $to, $value);
        
        $from = array('<minus>', '[minus]', '(minus)', '{minus}', ' minus ',
            ' <minus> ', ' [minus] ', ' (minus) ', ' {minus} ');
        $to = array('-', '-', '-', '-', '-', '-', '-', '-', '-');
        $value = str_ireplace($from, $to, $value);
        
        //Премахва всички стойности различни от: "числа-.,"
		$pattern = '/[^0-9\-\.\,]/';
		$value = preg_replace($pattern, '' ,$value);
  		
		$value = parent::fromVerbal($value);
		
		$valConverted = $this->convertToBar($value, $convertFrom, $convertTo);
		
		return $valConverted;
	}
	
	
	/**
	 * Проверява стойността на представката
	 */
	function checkUnitPrefix($valueForCheck) {
		$prefix = 1;
		$searh = array("atm", "атм", "mmHg", "милиметри");
		$valueForCheck = str_ireplace($searh, "", $valueForCheck);
		
		if ((mb_stristr($valueForCheck, 'milli') == TRUE) ||
			(mb_stristr($valueForCheck, 'мили') == TRUE) ||
			(mb_strstr($valueForCheck, 'm') == TRUE) ||
			(mb_strstr($valueForCheck, 'м') == TRUE)) 
		{
			$prefix = 0.001;
		} elseif ((mb_stristr($valueForCheck, 'mega') == TRUE) ||
			(mb_stristr($valueForCheck, 'мега') == TRUE) ||
			(mb_strstr($valueForCheck, 'M') == TRUE) ||
			(mb_strstr($valueForCheck, 'М') == TRUE))
		{
			$prefix = 1000000;
		} elseif ((mb_stristr($valueForCheck, 'kilo') == TRUE) ||
			(mb_stristr($valueForCheck, 'кило') == TRUE) ||
			(mb_stristr($valueForCheck, 'k') == TRUE) ||
			(mb_stristr($valueForCheck, 'к') == TRUE))
		{
			$prefix = 1000;
		} elseif ((mb_stristr($valueForCheck, 'hecto') == TRUE) ||
			(mb_stristr($valueForCheck, 'хекто') == TRUE) ||
			(mb_stristr($valueForCheck, 'h') == TRUE) ||
			(mb_stristr($valueForCheck, 'Х') == TRUE))
		{
			$prefix = 100;	
		}
		
		return $prefix;
	}
	
	
	/**
	 * Проверява единицата на въведената стойност
	 */
	function checkUnit($valueForCheck, $searchAgain = TRUE)
	{
		if ((mb_stristr($valueForCheck, 'ps') == TRUE) || 
		  		(mb_stristr($valueForCheck, 'пс') == TRUE)) {
  			$str = 'psi';
  		}elseif ((mb_stristr($valueForCheck, 'bar') == TRUE) || 
  				(mb_stristr($valueForCheck, 'бар')) == TRUE) {
  			$str = 'bar';
  		} elseif ((mb_stristr($valueForCheck, 'atm') == TRUE) || 
		  		(mb_stristr($valueForCheck, 'атм') == TRUE) || 
		  		(mb_stristr($valueForCheck, 'физ') == TRUE) ||
		  		(mb_stristr($valueForCheck, 'ysi') == TRUE)) {
  			$str = 'atm';
  		} elseif ((mb_stristr($valueForCheck, 'at') == TRUE) || 
		  		(mb_stristr($valueForCheck, 'ат') == TRUE) || 
		  		(mb_stristr($valueForCheck, 'техн') == TRUE) ||
		  		(mb_stristr($valueForCheck, 'te') == TRUE)) {
  			$str = 'at';
  		} elseif ((mb_stristr($valueForCheck, 'mmHg') == TRUE) || 
		  		(mb_stristr($valueForCheck, 'жив') == TRUE) || 
		  		(mb_stristr($valueForCheck, 'mercury') == TRUE) || 
		  		(mb_stristr($valueForCheck, 'tor') == TRUE) ||
		  		(mb_stristr($valueForCheck, 'тор') == TRUE)) {
  			$str = 'torr';
  		} elseif ((mb_stristr($valueForCheck, 'Pa') == TRUE) || 
				(mb_stristr($valueForCheck, 'Па') == TRUE) ||
				(mb_stristr($valueForCheck, 'P') == TRUE) ||
				(mb_stristr($valueForCheck, 'П') == TRUE)) {
  			$str = 'pa';
  		} else {
  			$str = 'bar';
  			//Проверява дали е въведена стойност по подразбиране, за да я използва, ако няма добавена
  			if ($searchAgain) {
  				$str = $this->checkUnit($this->params['defaultUnit'], FALSE);	
  			}
  		}
  			
		return $str;
	}
	
	
	/**
	 * 
	 * Конвертира всички стойности в bar
	 * @param $value     double - Стойността за обработване
	 * @param $valueUnit string - Единицата на въведената стойност
	 * @param $defUnit   string - Желаната стойност
	 */
	function convertToBar($value, $valueUnit, $defUnit)
	{
		if ($valueUnit == 'pa') {
			$bar = $value / 100000;
		} elseif ($valueUnit == 'atm') {
			$bar = $value / 0.98692;
		} elseif ($valueUnit == 'at') {
			$bar = $value / 1.0197;
		} elseif ($valueUnit == 'torr') {
			$bar = $value / 750.06;
		} elseif ($valueUnit == 'psi') {
			$bar = $value / 14.5037744;
		} else {
			$bar = $value;
		}
		$converted = $this->convertToDef($bar, $defUnit);
		
		return $converted;
	}
	
	
	/**
	 * 
	 * Конвертира всички стойности от bar в избраната стойност
	 * @param $value     double - Стойността за обработване
	 * @param $defUnit   string - Желаната стойност
	 */
	function convertToDef($value, $defUnit) 
	{
		if ($defUnit == 'pa') {
			$converted = $value*100000;
		} elseif ($defUnit == 'atm') {
			$converted = $value/1.01325;
		} elseif ($defUnit == 'at') {
			$converted = $value/0.980665;
		} elseif ($defUnit == 'torr') {
			$converted = $value/0.0013332;
		} elseif ($defUnit == 'psi') {
			$converted = $value/0.068948;
		} else {
			$converted = $value;
		}
		
		return $converted;
	}
	
}