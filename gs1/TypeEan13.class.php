<?php

cls::load('type_Varchar');


/**
 * Клас 'gs1_TypeEan13' -
 *
 *
 * @category  vendors
 * @package   gs1
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class gs1_TypeEan13 extends type_Varchar
{
    
    
    /**
     * Колко символа е дълго полето в базата
     */
    var $dbFieldLen = 13;
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params)
    {
        
        parent::init($params);
        $this->params['size'] = $this->params[0] = 13;
    }
    
    
    /**
     * Към 12-цифрен номер, добавя 13-та цифра за да го направи EAN13 код
     */
    function ean13CheckDigit($digits)
    {
        $digits = (string)$digits;
        $even_sum = $digits{1} + $digits{3} + $digits{5} + $digits{7} + $digits{9} + $digits{11};
        $even_sum_three = $even_sum * 3;
        $odd_sum = $digits{0} + $digits{2} + $digits{4} + $digits{6} + $digits{8} + $digits{10};
        $total_sum = $even_sum_three + $odd_sum;
        $next_ten = (ceil($total_sum / 10)) * 10;
        $check_digit = $next_ten - $total_sum;
        
        return $digits . $check_digit;
    }
    
    
    /**
     * Проверка за валидност на EAN13 код
     */
    function isValidEan13($value)
    {
        $digits12 = substr($value, 0, 12);
        $digits13 = $this->ean13CheckDigit($digits12);
        
        $res = ($digits13 == $value);
        
        return $res;
    }
    
    
    /**
     * Дефиниция на виртуалния метод на типа, който служи за проверка на данните
     */
    function isValid($value)
    {
        if(!trim($value)) return array('value' => '');
        
        $res = new stdClass();
        
        if(preg_match("/^[0-9]{13}$/", $value)){
            if (!$this->isValidEan13($value)){
                $res->error = "Невалиден EAN13 номер";
            }
        }
        elseif (preg_match("/^[0-9]{12}$/", $value)){
            $res->value = $this->ean13CheckDigit($value);
        } else {
            $len = strlen($value);
            $res->error = "Невалиден EAN13 номер. Въведения номер има |*{$len}| цифри.";
        }
        
        return (array) $res;
    }
}