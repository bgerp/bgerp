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
 * @category  vendors
 * @package   physics
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class physics_PressureType extends type_Double
{
    
    
    /**
     * Инициализиране на типа
     */
    public function init($params = array())
    {
        parent::init($params);
        setIfNot($this->params['decimals'], EF_PRESSURETYPE_DECIMALS);
        setIfNot($this->params['defaultUnit'], EF_DEFAULT_UNIT_PRESSURE);
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if (!is_numeric($value)) {
            $value = 0;
        }
        
        $value = parent::toVerbal($value) . ' ' . $this->params['defaultUnit'];
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност
     */
    public function toVerbal($value)
    {
        if (!isset($value)) {
            return;
        }
        
        return parent::toVerbal($value) . ' ' . $this->params['defaultUnit'];
    }
    
    
    /**
     * Преобразуване от вербална стойност, към вътрешно представяне
     */
    public function fromVerbal($value)
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
        
        //Премахва всички стойности различни от: "числа-.,аритметични знаци"
        $pattern = '/[^0-9\-\.\,\/\*\+]/';
        $value = preg_replace($pattern, '', $value);
        
        $value = parent::fromVerbal($value);
        
        $valConverted = $this->convertToBar($value, $convertFrom, $convertTo);
        
        return $valConverted;
    }
    
    
    /**
     * Проверява стойността на представката
     */
    public function checkUnitPrefix($valueForCheck)
    {
        $prefix = 1;
        $searh = array('atm', 'атм', 'mmHg', 'милиметри');
        $valueForCheck = str_ireplace($searh, '', $valueForCheck);
        
        if ((mb_stristr($valueForCheck, 'milli') == true) ||
            (mb_stristr($valueForCheck, 'мили') == true) ||
            (mb_strstr($valueForCheck, 'm') == true) ||
            (mb_strstr($valueForCheck, 'м') == true)) {
            $prefix = 0.001;
        } elseif ((mb_stristr($valueForCheck, 'mega') == true) ||
            (mb_stristr($valueForCheck, 'мега') == true) ||
            (mb_strstr($valueForCheck, 'M') == true) ||
            (mb_strstr($valueForCheck, 'М') == true)) {
            $prefix = 1000000;
        } elseif ((mb_stristr($valueForCheck, 'kilo') == true) ||
            (mb_stristr($valueForCheck, 'кило') == true) ||
            (mb_stristr($valueForCheck, 'k') == true) ||
            (mb_stristr($valueForCheck, 'к') == true)) {
            $prefix = 1000;
        } elseif ((mb_stristr($valueForCheck, 'hecto') == true) ||
            (mb_stristr($valueForCheck, 'хекто') == true) ||
            (mb_stristr($valueForCheck, 'h') == true) ||
            (mb_stristr($valueForCheck, 'Х') == true)) {
            $prefix = 100;
        }
        
        return $prefix;
    }
    
    
    /**
     * Проверява единицата на въведената стойност
     */
    public function checkUnit($valueForCheck, $searchAgain = true)
    {
        if ((mb_stristr($valueForCheck, 'ps') == true) ||
            (mb_stristr($valueForCheck, 'пс') == true)) {
            $str = 'psi';
        } elseif ((mb_stristr($valueForCheck, 'bar') == true) ||
            (mb_stristr($valueForCheck, 'бар')) == true) {
            $str = 'bar';
        } elseif ((mb_stristr($valueForCheck, 'atm') == true) ||
            (mb_stristr($valueForCheck, 'атм') == true) ||
            (mb_stristr($valueForCheck, 'физ') == true) ||
            (mb_stristr($valueForCheck, 'ysi') == true)) {
            $str = 'atm';
        } elseif ((mb_stristr($valueForCheck, 'at') == true) ||
            (mb_stristr($valueForCheck, 'ат') == true) ||
            (mb_stristr($valueForCheck, 'техн') == true) ||
            (mb_stristr($valueForCheck, 'te') == true)) {
            $str = 'at';
        } elseif ((mb_stristr($valueForCheck, 'mmHg') == true) ||
            (mb_stristr($valueForCheck, 'жив') == true) ||
            (mb_stristr($valueForCheck, 'mercury') == true) ||
            (mb_stristr($valueForCheck, 'tor') == true) ||
            (mb_stristr($valueForCheck, 'тор') == true)) {
            $str = 'torr';
        } elseif ((mb_stristr($valueForCheck, 'Pa') == true) ||
            (mb_stristr($valueForCheck, 'Па') == true) ||
            (mb_stristr($valueForCheck, 'P') == true) ||
            (mb_stristr($valueForCheck, 'П') == true)) {
            $str = 'pa';
        } else {
            $str = 'bar';
            
            //Проверява дали е въведена стойност по подразбиране, за да я използва, ако няма добавена
            if ($searchAgain) {
                $str = $this->checkUnit($this->params['defaultUnit'], false);
            }
        }
        
        return $str;
    }
    
    
    /**
     * Конвертира всички стойности в bar
     * @param $value     double - Стойността за обработване
     * @param $valueUnit string - Единицата на въведената стойност
     * @param $defUnit   string - Желаната стойност
     */
    public function convertToBar($value, $valueUnit, $defUnit)
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
     * Конвертира всички стойности от bar в избраната стойност
     * @param $value     double - Стойността за обработване
     * @param $defUnit   string - Желаната стойност
     */
    public function convertToDef($value, $defUnit)
    {
        if ($defUnit == 'pa') {
            $converted = $value * 100000;
        } elseif ($defUnit == 'atm') {
            $converted = $value / 1.01325;
        } elseif ($defUnit == 'at') {
            $converted = $value / 0.980665;
        } elseif ($defUnit == 'torr') {
            $converted = $value / 0.0013332;
        } elseif ($defUnit == 'psi') {
            $converted = $value / 0.068948;
        } else {
            $converted = $value;
        }
        
        return $converted;
    }
}
