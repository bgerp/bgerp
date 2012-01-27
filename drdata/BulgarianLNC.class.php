<?php



/**
 * Клас 'drdata_BulgarianLNC' - Проверка за валиден личен номер на чужденец
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class drdata_BulgarianLNC
{
    
    
    /**
     * Тегловности на числата
     */
    var $weights = array(21, 19, 17, 13, 11, 9, 7, 3, 1, 0);
    
    
    /**
     * Проверява за валиден номер на чужденец
     */
    function isLnc($value)
    {
        if (!isset($value)) {
            
            return FALSE;
        }
        
        if (!preg_match('/^[0-9]{10}$/', $value)) {
            
            return FALSE;
        }
        
        $valArr = str_split($value);
        $sum = 0;
        
        foreach ($this->weights as $key => $weight) {
            $sum += $valArr[$key] * $weight;
        }
        
        $rest = $sum % 10;
        
        if ($rest == $valArr[9]) {
            
            return TRUE;
        }
        
        return "<br />Не е валидно ЛНЧ.";
    }
}
