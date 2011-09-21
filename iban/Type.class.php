<?php

cls::load('type_Varchar');

require_once 'php-iban-1.1.2/php-iban.php';


/**
 * Клас 'iban_Type' - Въвеждане на IBAN номера
 * 
 * Клас за работа с IBAN полета
 *
 * @category   Experta Framework
 * @package    iban
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class iban_Type extends type_Varchar
{
    
    
    /**
     *  Максималната дължина на полето
     */
    var $dbFieldLen = 35;
    
    
    /**
     *  Проверява дали въведения IBAN е коректен
     */
    function isValid($value)
    {
        if (empty($value)) {
            return;
        }
        
        $res->value = iban_to_machine_format($value);
        
        if (!empty($res->value) && !verify_iban($res->value)) {
            $res->error = 'Невалиден IBAN';
        }
        
        return (array)$res;
    }
    
    
    /**
     * Връща двубуквеното означение на държавата от където е този IBAN
     */
    static function getCountryPart($iban) 
    {
    	
    	$validIban = self::isValid($iban);
    	if (!isset($validIban)) {
    		
    		return 'Не сте въвели IBAN номер.';
    	}
    	
    	if (isset($validIban['error'])) {
    			
    		return $validIban['error'];
    	}
    	
    	$country = iban_get_country_part($validIban['value']);
    	
    	return $country;
    	
    } 
    
    
    /**
     * Връща кода на банката от IBAN номера
     */
    static function getBankPart($iban)
    {
    	$validIban = self::isValid($iban);
    	if (!isset($validIban)) {
    		
    		return 'Не сте въвели IBAN номер.';
    	}
    	
    	if (isset($validIban['error'])) {
    			
    		return $validIban['error'];
    	}
    	
    	$bank = iban_get_bank_part($iban);
    	
    	return $bank;
    }
    
}