<?php



/**
 * Клас  'type_Identifier' - Тип за идентификатор
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Identifier extends type_Varchar {
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal($value)
    {
        $value = parent::fromVerbal(str::trim($value));
        
        if($value === '') return NULL;
        
        if (!self::isValid($value)) {
            $this->error = 'Некоректен идентификатор|* ' . $value;
            
            return FALSE;
        }
        
        return $value;
    }
    
    
    /**
     * Проверява дали е валиден
     */
    function isValid($value)
    {
        $len = $this->params[0] ? '0,' . ($this->params[0]-1) : '0,63';
        $pattern = "/^[a-zA-Z_]{1}[a-zA-Z0-9_]{" . $len . "}$/i";
        
        if(!preg_match($pattern, $value)) {
            
            return FALSE;
        }
        
        return TRUE;
    }
}