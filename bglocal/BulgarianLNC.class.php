<?php


/**
 * Клас 'drdata_BulgarianLNC' - Проверка за валиден личен номер на чужденец
 *
 *
 * @category  bgerp
 * @package   bglocal
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bglocal_BulgarianLNC
{
    /**
     * Тегла на числата
     */
    public $weights = array(21, 19, 17, 13, 11, 9, 7, 3, 1, 0);
    
    
    /**
     * Проверява за валиден номер на чужденец
     */
    public function isLnc($value)
    {
        if (!isset($value)) {
            
            return false;
        }
        
        if (!preg_match('/^[0-9]{10}$/', $value)) {
            
            return false;
        }
        
        $valArr = str_split($value);
        $sum = 0;
        
        foreach ($this->weights as $key => $weight) {
            $sum += $valArr[$key] * $weight;
        }
        
        $rest = $sum % 10;
        
        if ($rest == $valArr[9]) {
            
            return true;
        }
        
        return '|*<br>|Не е валидно ЛНЧ.';
    }
}
