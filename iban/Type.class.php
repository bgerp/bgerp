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
    public static function loadCode()
    {
        require_once('php-iban-' . iban_Setup::get('CODE_VERSION') . '/php-iban.php');
    }
    
    
    /**
     * Максималната дължина на полето
     */
    public $dbFieldLen = 35;
    /**
     *  Параметър определящ максималната широчина на полето
     */
    public $maxFieldSize = 35;
    
    /**
     * Проверява дали въведения IBAN е коректен
     */
    public function isValid($value)
    {
        self::loadCode();

        $value = trim($value);
        
        $res = new stdClass();
        
        // Допускане на записване на непопълнен ИБАН
        if ($value === '') {
            return;
        }
        
        if (empty($value)) {
            $res->error = 'Липсващ IBAN';
        } elseif ($value{0} == '#') {
            $res->value = $value;
        } else {
            if (!verify_iban($value)) {
                $res->error = 'Невалиден IBAN! За сметка извън IBAN стандарта започнете със знака "#"';
            }
        }
        
        return (array) $res;
    }
    
    
    /**
     * Връща двубуквеното означение на държавата от където е този IBAN
     */
    public static function getCountryPart($iban)
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
    public static function getBankPart($iban)
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
    public static function getParts($iban)
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
    public function renderInput_($name, $value = '', &$attr = array())
    {
        setIfNot($attr['size'], intval($this->dbFieldLen * 1.3));
        setIfNot($attr['maxlength'], $this->dbFieldLen);
        setIfNot($attr['title'], 'За номер извън IBAN стандарта започнете със знака "#"');
      
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Връща вербалната стойност на IBAN номера
     */
    public function toVerbal($value)
    {
        if (empty($value)) {
            return;
        }
        
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
    public static function removeDs($value)
    {
        if ($value{0} == '#') {
            $value = substr($value, 1);
        }
        
        return $value;
    }
    
    
    /**
     * Връща каноническа форма на IBAN номера
     */
    public function canonize($iban)
    {
        self::loadCode();

        if ($iban{0} == '#') {
            
            return trim(str_replace(array(' ', '-'), array('', ''), $iban));
        }

        return iban_to_machine_format($iban);
    }
}
