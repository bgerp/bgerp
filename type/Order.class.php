<?php


/**
 * Клас  'type_Order' - Тип за задаване на подредба с три нива
 *
 *
 * @category  ef
 * @package   type
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class type_Order extends type_Varchar
{
    /**
     * Клас за <td> елемент, който показва данни от този тип
     */
    public $tdClass = '';
    
    
    /**
     * Конвертира от вербална стойност
     */
    public function fromVerbal_($val)
    {
        $val = trim($val);
        
        // Празна стойност се приема за NULL
        if ($val === '') {
            
            return;
        }
        
        $vArr = explode('.', $val);
        
        if (!isset($vArr[1])) {
            $vArr[1] = 0;
        }
        
        if (!isset($vArr[2])) {
            $vArr[2] = 0;
        }
        
        $verb = str_pad((int) $vArr[0], 3, '0', STR_PAD_LEFT) .
                str_pad((int) $vArr[1], 3, '0', STR_PAD_LEFT) .
                str_pad((int) $vArr[2], 3, '0', STR_PAD_LEFT);
        
        if (strlen($verb) != 9) {
            $this->error = 'Не е в допустимия формат - XXX.XXX.XXX. Може да се въведат до 3 групи, по 3 цифри.';
            
            return false;
        }
        
        return $verb;
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if (strlen($value)) {
            $value = $this->toVerbal_($value);
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    public function toVerbal_($value)
    {
        $value = trim($value);
        
        if (!($len = strlen($value))) {
            
            return;
        }
        
        if ($len != 9) {
            
            return $value;
        }
        
        $l2 = substr($value, $len - 3, 3);
        $l1 = substr($value, $len - 6, 3);
        $l0 = substr($value, 0, $len - 6);
        
        $res = $l0;
        
        if ($l1 > 0) {
            $res .= '.' . round($l1);
        }
        
        if ($l2 > 0) {
            $res .= '.' . round($l2);
        }
        
        $res = ltrim($res, '0');
        
        return $res;
    }
}
