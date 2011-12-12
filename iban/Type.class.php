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
        $value = trim($value);

        if (empty($value)) {
            $res->error = 'Липсващ IBAN';
        } elseif($value{0} == '#') {
            $res->value = $value;

         } else {
        
            // $res->value = iban_to_machine_format($value);
            
            if (!empty($res->value) && !verify_iban($res->value)) {
                $res->error = 'Невалиден IBAN';
            }
         }
        
        return (array)$res;
    }
    
    
    /**
     * Връща двубуквеното означение на държавата от където е този IBAN
     */
    static function getCountryPart($iban) 
    {
    	$validIban = self::isValid($iban);
 
    	expect(!$validIban['error']);
    	
    	$country = iban_get_country_part($validIban['value']);
    	
    	return $country;
    } 
    
    
    /**
     * Връща кода на банката от IBAN номера
     */
    static function getBankPart($iban)
    {
    	$validIban = self::isValid($iban);
 
    	expect(!$validIban['error']);
    	
    	$bank = iban_get_bank_part($iban);
    	
    	return $bank;
    }


    /**
     * Рендира input-a за IBAN-a
     */
    function renderInput_($name, $value="", $attr = array())
    {
        setIfNot($attr['size'], 35);
        setIfNot($attr['title'], tr('За номер извън IBAN стандарта, започнете със знака "#"'));

        return parent::renderInput_($name, $value, $attr);
    }


    /**
     *
     */
    function toVerbal($value)
    {
        if($value{0} == '#') {
            $value = substr($value, 1);
        }

        return parent::toVerbal_($value);
    }
    
}