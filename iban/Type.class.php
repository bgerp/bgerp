<?php




/**
 * Клас 'iban_Type' - Въвеждане на IBAN номера
 *
 * Клас за работа с IBAN полета
 *
 *
 * @category  vendors
 * @package   iban
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class iban_Type extends type_Varchar
{
    
    static function loadCode()
    {
        require_once('php-iban-' . iban_Setup::get('CODE_VERSION') . '/php-iban.php');
    }
    
    
    /**
     * Максималната дължина на полето
     */
    var $dbFieldLen = 35;
    /**
     *  Параметър определящ максималната широчина на полето
     */ 
     var $maxFieldSize = 35;
    
    /**
     * Проверява дали въведения IBAN е коректен
     */
    function isValid($value)
    {
        self::loadCode();

        $value = trim($value);
        
        $res = new stdClass();
        
        // Допускане на записване на непопълнен ИБАН
        if($value === '') return NULL;
        
        if (empty($value)) {
            $res->error = 'Липсващ IBAN';
        } elseif($value{0} == '#') {
            $res->value = $value;
        } else {
            
            if (!verify_iban($value)) {
                $res->error = 'Невалиден IBAN! За сметка извън IBAN стандарта започнете със знака "#"';
            }
        }
        
        return (array)$res;
    }
    
    
    /**
     * Връща двубуквеното означение на държавата от където е този IBAN
     */
    static function getCountryPart($iban)
    {   
        self::loadCode();

        $self = cls::get(get_called_class());
        
    	$validIban = $self->isValid($iban);
        
        expect(!$validIban['error']);
        
        $country = iban_get_country_part($iban);
        
        return $country;
    }
    
    
    /**
     * Връща кода на банката от IBAN номера
     */
    static function getBankPart($iban)
    {   
        self::loadCode();

        $self = cls::get(get_called_class());
        
    	$validIban = $self->isValid($iban);
        
        expect(!$validIban['error']);
        
        $bank = iban_get_bank_part($iban);
        
        return $bank;
    }
    
    
    /**
     * Връща кода на банката от IBAN номера
     */
    static function getParts($iban)
    {
        self::loadCode();

        $self = cls::get(get_called_class());
        
    	$validIban = $self->isValid($iban);
        
        expect(!$validIban['error']);
        
        $parts = iban_get_parts($iban);
        
        return $parts;
    }
    
    
    /**
     * Рендира input-a за IBAN-a
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        setIfNot($attr['size'], intval($this->dbFieldLen * 1.3));
        setIfNot($attr['maxlength'], $this->dbFieldLen);
        setIfNot($attr['title'], tr('За номер извън IBAN стандарта започнете със знака "#"'));
      
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Връща вербалната стойност на IBAN номера
     */
    function toVerbal($value)
    {
        if(empty($value)) return NULL;
        
    	$value = $this->removeDs($value);
        
        return type_Varchar::escape($value);
    }
    
    
    /**
     * Премахва първия # в IBAN' a
     * 
     * @param string $value - IBAN
     * 
     * @return string $value
     */
    static function removeDs($value)
    {
        if($value{0} == '#') {
            $value = substr($value, 1);
        }
        
        return $value;
    }
    
    
    /**
     * Връща каноническа форма на IBAN номера
     */
    function canonize($iban)
    {   
        self::loadCode();

        if($iban{0} == '#') {
            return trim(str_replace(array(' ', '-'), array('', ''), $iban));
        } else {
            return iban_to_machine_format($iban);
        }
    }
}
