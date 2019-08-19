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
 * @category  vendors
 * @package   physics
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class physics_TemperatureType extends type_Double
{
    /**
     * Инициализиране на типа
     */
    public function init($params = array())
    {
        parent::init($params);
        setIfNot($this->params['decimals'], EF_TEMPERATURETYPE_DECIMALS);
        setIfNot($this->params['defaultUnit'], EF_DEFAULT_UNIT);
    }
    
    
    /**
     * Преобразуване от Фаренхайт в Целзий
     */
    public function farToCels($far)
    {
        $cels = ($far - 32) / 1.8;
        
        return $cels;
    }
    
    
    /**
     * Преобразуване от Целзий във Фаренхайт
     */
    public function celsToFar($cels)
    {
        $far = ($cels * 1.8) + 32;
        
        return $far;
    }
    
    
    /**
     * Преобразуване от Келвин в Целзий
     */
    public function kelvToCels($kelv)
    {
        $cels = $kelv - 273.15;
        
        return $cels;
    }
    
    
    /**
     * Преобразуване от Целзий в Келвин
     */
    public function celsToKelv($cels)
    {
        $kelv = $cels + 273.15;
        
        return $kelv;
    }
    
    
    /**
     * Преобразуване от Келвин във Фаренхайт
     */
    public function kelvToFar($kelv)
    {
        $far = ($kelv * 1.8) - 459.67;
        
        return $far;
    }
    
    
    /**
     * Преобразуване от Фаренхайт в Келвин
     */
    public function farToKelv($far)
    {
        $kelv = ($far + 459.67) / 1.8;
        
        return $kelv;
    }
    
    
    /**
     * Преобразуване от Целзий в Целзий
     */
    public function celsToCels($cels)
    {
        return $cels;
    }
    
    
    /**
     * Преобразуване от Фаренхайт във Фаренхайт
     */
    public function farToFar($far)
    {
        return $far;
    }
    
    
    /**
     * Преобразуване от Келвин в Келвин
     */
    public function kelvToKelv($kelv)
    {
        return $kelv;
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
     * Проверява единицата за въведената стойност
     */
    public function checkUnit($valueForCheck, $firstLetter = false, $searchAgain = true)
    {
        if ((mb_stristr($valueForCheck, 'F') == true) ||
            (mb_stristr($valueForCheck, 'Ф') == true)) {
            $str = 'far';
        } elseif ((mb_stristr($valueForCheck, 'K') == true) ||
            (mb_stristr($valueForCheck, 'К')) == true) {
            $str = 'kelv';
        } elseif ((mb_stristr($valueForCheck, 'C') == true) ||
            (mb_stristr($valueForCheck, 'С') == true) ||
            (mb_stristr($valueForCheck, 'Ц') == true)) {
            $str = 'cels';
        } else {
            $str = 'cels';
            
            if (!$firstLetter) {
                //Проверява дали е въведена стойност по подразбиране, за да я използва, ако няма добавена
                if (isset($this->params['defaultUnit'])) {
                    if ($searchAgain) {
                        $str = $this->checkUnit($this->params['defaultUnit'], $firstLetter, false);
                    }
                }
            }
        }
        
        //Преобразува първата в главна, а останалите в малка, ако е подаден такъв параметър
        if ($firstLetter) {
            $str = ucfirst(strtolower($str));
        }
        
        return $str;
    }
    
    
    /**
     * Преобразуване от вербална стойност, към вътрешно представяне
     */
    public function fromVerbal($value)
    {
        $str = $this->checkUnit($value);
        $str .= 'To';
        $str .= $this->checkUnit($this->params['defaultUnit'], true);
        
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
        
        return $this->$str($value);
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
}
