<?php



/**
 * Клас 'type_IP' - тип за съхранение и обработка на IP v4 адрес
 *
 *
 * @category  all
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class type_IP extends type_Varchar {
    
    
    /**
     * Дължина на полето в mySql таблица
     */
    var $dbFieldLen = 15;
    
    
    /**
     * Инициализиране на типа
     */
    function init($params)
    {
        $params['params']['size'] = 15;
        
        parent::init($params);
    }
    
    
    /**
     * Приема вербална стойност
     */
    function fromVerbal_($value)
    {
        $value = trim($value);
        
        if(empty($value)) return NULL;
        
        if(!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->error = 'Некоректен IP адрес';
            
            return FALSE;
        }
        
        return $value;
    }
    
    
    /**
     * Преобразува имейл-а в човешки вид
     */
    function toVerbal_($value)
    {
        if(empty($value)) return NULL;
        
        return $value;
    }
    
    
    /**
     * Връща последното публично IP намерено в даден стринг
     */
    function getLastIp($str)
    {
        preg_match_all('/((?:\d{1,3}\.){3})\d{1,3}/', $str, $matches);
        
        for ($ipCount = count($matches[0])-1; $ipCount >= 0; $ipCount--) {
            
            $ip = $matches[0][$ipCount];
            
            if (type_Ip::isPublic($ip)) return $ip;
        }
    }
    
    
    /**
     * Дали посоченото IP е частно (запазено за частна употреба от организации)?
     */
    static function isPrivate($ip)
    {
        if(strpos($ip, '10.') === 0) return TRUE;
        
        if(strpos($ip, '127.0.0.') === 0) return TRUE;
        
        if(strpos($ip, '192.168.') === 0) return TRUE;
        
        for($i = 16; $i < 32; $i++) {
            if(strpos($ip, "172.{$i}.") === 0) return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Дали посоченото IP е публично
     */
    static function isPublic($ip)
    {
        return !type_Ip::isPrivate($ip);
        
        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE)) {
            
            expect(!type_Ip::isPrivate($ip), $ip);    // @todo: да се махне
            return TRUE;
        } else {
            
            expect(type_Ip::isPrivate($ip));    // @todo: да се махне
            return FALSE;
        }
    }
}