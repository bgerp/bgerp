<?php

/**
 * Клас  'type_Email' - Тип за емейл
 *
 * Има валидираща функция
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Email extends type_Varchar {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $dbFieldLen = 80;
    
    
    /**
     * Валидира е-маила
     */
    function fromVerbal($value)
    {
        $value = strtolower(trim($value));
        
        $from = array('<at>', '[at]', '(at)', '{at}', ' at ', ' <at> ',
            ' [at] ', ' (at) ', ' {at} ');
        $to = array('@', '@', '@', '@', '@', '@', '@', '@', '@');
        
        $value = str_replace($from, $to, $value);
        
        $from = array('<dot>', '[dot]', '(dot)', '{dot}', ' dot ',
            ' <dot> ', ' [dot] ', ' (dot) ', ' {dot} ');
        $to = array('.', '.', '.', '.', '.', '.', '.', '.', '.');
        
        $value = str_replace($from, $to, $value);
        
        if(!$value) return NULL;
        
        if(!$this->validEmail($value)) {
            $this->error = 'Некоректен е-мейл';
            
            return FALSE;
        } else {
            
            return $value;
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function validEmail($email)
    {
        if (!$email) return NULL;
        
        if (preg_match("/[\\000-\\037]/", $email)) {
            
            return FALSE;
        }
        
        $pattern = "/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])" .
        "[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD";
        
        if(!preg_match($pattern, $email)){
            
            return FALSE;
        }
        
        return TRUE;
    }
}