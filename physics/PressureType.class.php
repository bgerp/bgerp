<?php

/**
 * Колко цифри след запетаята да се показват
 */
defIfNot('EF_PRESSURETYPE_DECIMALS', 0);


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
		setIfNot($this->params['default_unit'], EF_DEFAULT_UNIT_PRESSURE);
	}
	
	
	/**
	 *Преобразуване от вътрешно представяне към вербална стойност
	 */
	function renderInput_($name, $value="", $attr = array())
	{	
		if (!is_numeric($value)) $value=0;
		
		$value = parent::toVerbal($value) . ' ' . $this->params['default_unit'];
		
		return parent::renderInput_($name, $value, $attr);
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
		//Преобразува първата в главна, а останалите в малка, ако е подаден такъв параметър
		
			
		$convertFrom = $this->checkUnit($value);
		$convertFrom = ucfirst(strtolower($convertFrom));
  		$convertTo = $this->checkUnit($this->params['default_unit']);
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
		
		$convert = "convert".$convertFrom;
		$val_converted = $this->$convert($value, $convertTo) * $prefix;
		
		return $val_converted;
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
  				$str = $this->checkUnit($this->params['default_unit'], FALSE);	
  			}
  		}
  			
		return $str;
	}
	
	
	/**
	 * Конвертира от Паскал в избраната стойност
	 */
	function convertPa($value, $to) {
		if ($to == 'bar') {
			$converted = $value/100000;
		} elseif ($to == 'atm') {
			$converted = $value*0.000009869;
		} elseif ($to == 'at') {
			$converted = $value*0.000010197;
		} elseif ($to == 'torr') {
			$converted = $value*0.0075006;
		}elseif ($to == 'psi') {
			$converted = $value*0.00014504;
		} else {
			
			return $value;
		}
		
		return $converted;
	}
	
	
	/**
	 * Конвертира от bar в избраната стойност
	 */
	function convertBar($value, $to) {
		if ($to == 'pa') {
			$converted = $value*100000;
		} elseif ($to == 'atm') {
			$converted = $value*0.98692;
		} elseif ($to == 'at') {
			$converted = $value*1.0197;
		} elseif ($to == 'torr') {
			$converted = $value*750.06;
		}elseif ($to == 'psi') {
			$converted = $value*14.5037744;
		} else {
			
			return $value;
		}
		
		return $converted;
	}
	
	
	/**
	 * Конвертира от at в избраната стойност
	 */
	function convertAt($value, $to) {
		if ($to == 'pa') {
			$converted = $value*98066.5;
		} elseif ($to == 'atm') {
			$converted = $value*0.96784;
		} elseif ($to == 'bar') {
			$converted = $value*0.980665;
		} elseif ($to == 'torr') {
			$converted = $value*735.56;
		}elseif ($to == 'psi') {
			$converted = $value*14.223;
		} else {
			
			return $value;
		}
		
		return $converted;
	}
	
	
	/**
	 * Конвертира от atm в избраната стойност
	 */
	function convertAtm($value, $to) {
		if ($to == 'pa') {
			$converted = $value*101325;
		} elseif ($to == 'at') {
			$converted = $value*1.0332;
		} elseif ($to == 'bar') {
			$converted = $value*1.01325;
		} elseif ($to == 'torr') {
			$converted = $value*760;
		}elseif ($to == 'psi') {
			$converted = $value*14.696;
		} else {
			
			return $value;
		}
		
		return $converted;
	}
	
	
	/**
	 * Конвертира от torr в избраната стойност
	 */
	function convertTorr($value, $to) {
		if ($to == 'pa') {
			$converted = $value*133.322;
		} elseif ($to == 'at') {
			$converted = $value*0.0013595;
		} elseif ($to == 'bar') {
			$converted = $value*0.0013332;
		} elseif ($to == 'atm') {
			$converted = $value*0.0013158;
		}elseif ($to == 'psi') {
			$converted = $value*0.019337;
		} else {
			
			return $value;
		}
		
		return $converted;
	}
	
	
	/**
	 * Конвертира от psi в избраната стойност
	 */
	function convertPsi($value, $to) {
		if ($to == 'pa') {
			$converted = $value*6895;
		} elseif ($to == 'at') {
			$converted = $value*0.070307;
		} elseif ($to == 'bar') {
			$converted = $value*0.068948;
		} elseif ($to == 'atm') {
			$converted = $value*0.068046;
		}elseif ($to == 'torr') {
			$converted = $value*51.715;
		} else {
			
			return $value;
		}
		
		return $converted;
	}
	
}