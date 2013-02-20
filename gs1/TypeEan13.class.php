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
    var $dbFieldLen = 18;
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        $this->params['size'] = $this->params[0] = 18;
    }
    
    
    /**
     * Към 12-цифрен номер, добавя 13-та цифра за да го направи EAN13 код
     */
    function ean13CheckDigit($digits, $n = 13)
    {
        $digits = (string)$digits;
        $oddSum = $evenSum = 0;
        foreach(array('even'=>'1', 'odd'=>'0') as $k=>$v) {
	        foreach (range($v, $n, 2) as ${"{$k}Num"}) {
	        	${"{$k}Sum"} += $digits[${"{$k}Num"}];
			}
        }
		
        $evenSumThree = $evenSum * 3;
		$totalSum = $evenSumThree + $oddSum;
        $nextTen = (ceil($totalSum / 10)) * 10;
        $checkDigit = $nextTen - $totalSum;
        
        return $digits . $checkDigit;
    }
    
    
    /**
     * Проверка за валидност на EAN13 код
     */
    function isValidEan($value, $n = 13)
    {
        $digits12 = substr($value, 0, $n-1);
        $digits13 = $this->ean13CheckDigit($digits12);
        
        $res = ($digits13 == $value);
        
        return $res;
    }
    
    
    /**
     * Връща верен EAN 13 + 2/5, ако е подаден такъв
     * @param string $value - 15 или 18 цифрен баркод
     * @param int $n - колко цифри са допълнителните към EAN13
     */
    function ean13SCheckDigit($value, $n)
    {
    	$digits12 = substr($value, 0, 12);
    	$supDigits = substr($value, 13, $n);
    	$res = $this->ean13CheckDigit($digits12);
    	$res .= $supDigits;
    	
    	return $res;
    }
    
    
    /**
     * Проверка за валидност на първите 13 цифри от 15 или 18 
     * цифрен баркод код, дали са валиден EAN13 код
     * @param string $value - EAN код с повече от 13 цифри
     */
    function isValidEanS($value)
    {
    	$digits13 = substr($value, 0, 13);
    	if($this->isValidEan($digits13, 13)) {
    		return TRUE;
    	} else {
    		return FALSE;
    	}
    }
    
    
    /**
     * Дефиниция на виртуалния метод на типа, който служи за проверка на данните
     */
    function isValid($value)
    {
        if(!trim($value)) return array('value' => '');
        
        $res = new stdClass();
        
        if(preg_match("/^[0-9]{7}$/", $value)) {
        	$res->value = $this->ean13CheckDigit($value, 8);
            $res->warning = "Въвели сте само 7 цифри. Пълният EAN8 код {$res->value} ли е?";
        } elseif(preg_match("/^[0-9]{8}$/", $value)) {
        	if (!$this->isValidEan($value, 8)){
                $res->error = "Невалиден EAN8 номер.";
            }
        } else if(preg_match("/^[0-9]{13}$/", $value)){
            if (!$this->isValidEan($value)){
                $res->error = "Невалиден EAN13 номер.";
            }
        } elseif(preg_match("/^[0-9]{15}$/", $value)) {
        	if (!$this->isValidEanS($value)){
        		$res->value = $this->ean13SCheckDigit($value, 2);
        		$res->error = "Невалиден EAN13+2 номер. Пълният EAN13+2 код {$res->value} ли е?";
            } 
        } elseif(preg_match("/^[0-9]{18}$/", $value)) {
        	if (!$this->isValidEanS($value)){
        		$res->value = $this->ean13SCheckDigit($value, 5);
                $res->error = "Невалиден EAN13+5 номер. Пълният EAN13+5 код {$res->value} ли е?";
            }
        } elseif (preg_match("/^[0-9]{12}$/", $value)){
            $res->value = $this->ean13CheckDigit($value);
            $res->warning = "Въвели сте само 12 цифри. Пълният EAN13 код {$res->value} ли е?";
        } else {
            $res->error = "Невалиден EAN13 номер. ";
            
            if (preg_match("/[^0-9]/", $value)) {
                $res->error .= "Полето приема само цифри.";    
            } else {
                $len = mb_strlen($value);
                $res->error .= "Въведения номер има |*{$len}| цифри.";  
            }
        }
        
        return (array) $res;
    }
}