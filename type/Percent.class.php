<?php



/**
 * @todo Чака за документация...
 */
defIfNot('EF_PERCENT_DECIMALS', 2);


/**
 * Клас  'type_Percent' - Тип за проценти
 *
 *
 * @category  ef
 * @package   type
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Percent extends type_Double {
    
    
    /**
     * Инициализиране на типа
     */
    function init($params = array())
    {
        parent::init($params);
        setIfNot($this->params['decimals'], EF_PERCENT_DECIMALS);
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност за проценти (0 - 100%)
     */
    function toVerbal($value)
    {
        if(!isset($value)) return NULL;
        
        $value = $value * 100;
        
        return parent::toVerbal($value) . '&nbsp;%';
    }
    
    
    /**
     * Преобразуване от вербална стойност, към вътрешно представяне за процент (0 - 1)
     */
    function fromVerbal($value)
    {
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
        
        $from = array('<percent>', '[percent]', '(percent)', '{percent}', ' percent ',
            ' <percent> ', ' [percent] ', ' (percent) ', ' {percent} ');
        $to = array('%', '%', '%', '%', '%', '%', '%', '%', '%');
        $value = str_ireplace($from, $to, $value);
        
        $from = array('<процент>', '[процент]', '(процент)', '{процент}', ' процент ',
            ' <процент> ', ' [процент] ', ' (процент) ', ' {процент} ');
        $to = array('%', '%', '%', '%', '%', '%', '%', '%', '%');
        $value = str_ireplace($from, $to, $value);
        
        //Премахва всички стойности различни от: "числа-.,%аритметични знаци"
        $pattern = '/[^0-9\-\.\,\/\*\+\%]/';
        $value = preg_replace($pattern, '' , $value);
        
        $value = str_replace('%', '', $value);
        $value = parent::fromVerbal($value);
        $value = $value / 100;
        
        return $value;
    }
    
    
    /**
     * Преобразуване от вътрешно представяне към вербална стойност за проценти при рендиране (0 - 100%)
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        if (!($this->error)) {
            $value = (100 * $value) . ' %';
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
}