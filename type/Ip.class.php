<?php


/**
 * Клас 'type_Ip' - тип за съхранение и обработка на IP v4 адрес
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
 */
class type_Ip extends type_Varchar
{
    /**
     * Дължина на полето в mySql таблица
     */
    public $dbFieldLen = 15;
    
    
    /**
     * Инициализиране на типа
     */
    public function init($params = array())
    {
        $params['params']['size'] = 15;
        
        parent::init($params);
    }
    
    
    /**
     * Приема вербална стойност
     */
    public function fromVerbal_($value)
    {
        $value = trim($value);
        
        if (empty($value)) {
            
            return;
        }
        
        if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->error = 'Некоректен IP адрес';
            
            return false;
        }
        
        return $value;
    }
    
    
    /**
     * Преобразува имейл-а в човешки вид
     */
    public function toVerbal_($value)
    {
        if (empty($value)) {
            
            return;
        }
        
        $time = $this->params['time'];
        $coloring = $this->params['coloring'] != 'no';
        
        $value = self::decorateIp($value, $time, $coloring);
        
        return $value;
    }
    
    
    /**
     * Декорира ip адреса
     *
     * @param string    $ip
     * @param string  $time
     * @param array $attr
     */
    public static function decorateIp($ip, $time = null, $coloring = true)
    {
        $res = $ip;
        
        $me = cls::get(get_called_class());
        
        $me->invoke('AfterDecorateIp', array(&$res, $ip, $time, $coloring));
        
        return $res;
    }
    
    
    /**
     * Връща последното публично IP намерено в даден стринг
     */
    public function getLastIp($str)
    {
        preg_match_all('/((?:\d{1,3}\.){3})\d{1,3}/', $str, $matches);
        
        for ($ipCount = countR($matches[0]) - 1; $ipCount >= 0; $ipCount--) {
            $ip = $matches[0][$ipCount];
            
            if (type_Ip::isPublic($ip)) {
                
                return $ip;
            }
        }
    }
    
    
    /**
     * Дали посоченото IP е частно (запазено за частна употреба от организации)?
     *
     * @param string $ip
     *
     * @return bool
     */
    public static function isPrivate($ip)
    {
        if (strpos($ip, '10.') === 0) {
            
            return true;
        }
        
        if (strpos($ip, '127.0.0.') === 0) {
            
            return true;
        }
        
        if ($ip == '::1') {
            
            return true;
        }
        
        if (strpos($ip, '192.168.') === 0) {
            
            return true;
        }
        
        for ($i = 16; $i < 32; $i++) {
            if (strpos($ip, "172.{$i}.") === 0) {
                
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща TRUE, ако IP-то на потребителя е от локалния компютър
     */
    public static function isLocal()
    {
        $localIpArr = array('::1', '127.0.0.1');
        $isLocal = in_array($_SERVER['REMOTE_ADDR'], $localIpArr);
        
        return $isLocal;
    }


    /**
     * Дали посоченото IP е публично
     */
    public static function isPublic($ip)
    {
        return !type_Ip::isPrivate($ip);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE)) {
            expect(!type_Ip::isPrivate($ip), $ip);     // @todo: да се махне
            
            return true;
        }
        
        expect(type_Ip::isPrivate($ip));     // @todo: да се махне
        
        return false;
    }

    
    /**
     * Извлича реално ИП зад прокси сървър
     * 
     * @return string $ip
     */
    public static function getRealIp() {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            list($ip) = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']); // може да има повече IP-та разделени със запетайка
            $ip = trim($ip);
        }

        return $ip;
    }


    /**
     * Извлича ип-та от стринг
     *
     * @param string $input - стринг с ип-та
     * @return array
     *           - 'ips'     => списък от ip2long() на чистите IP-та
     *           - 'ipsRaw'  => списък от оригиналните IP-та (string)
     *           - 'nets'    => списък от [net => ip2long(мрежа), mask => bitmask]
     *           - 'netsRaw' => списък от [net => '1.2.3.0', prefix => 24]
     */
    public static function extractIps($input): array
    {
        $lines = preg_split('/[\r\n,;]+/', trim($input));
        $ips = $ipsRaw = $nets = $netsRaw = array();

        foreach ($lines as $line) {
            $entry = trim($line);
            if (empty($entry)) continue;

            // Мрежа в CIDR нотация?
            if (strpos($entry, '/') !== false) {
                list($net, $prefix) = explode('/', $entry, 2);
                $prefix = (int)$prefix;

                // Валидация
                if (filter_var($net, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $prefix >= 0 && $prefix <= 32) {
                    $netLong  = ip2long($net);
                    $maskLong = (~0) << (32 - $prefix);

                    $nets[]    = ['net' => $netLong, 'mask' => $maskLong];
                    $netsRaw[] = ['net' => $net, 'prefix' => $prefix];
                }
            }
            // Чист IP
            elseif (filter_var($entry, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ips[]    = ip2long($entry);
                $ipsRaw[] = $entry;
            }
            // иначе прескачаме негодни записи
        }

        return array(
            'ips'     => $ips,
            'ipsRaw'  => $ipsRaw,
            'nets'    => $nets,
            'netsRaw' => $netsRaw,
        );
    }



    /**
     * Проверява дали даден IP (string) е в списъка на фирмените IP-та/мрежи.
     *
     * @param string $ip - IP
     * @param array $parsed - масив с парсирани ип-та от extractIps
     * @return bool
     */
    public static function isInIps($ip, $parsed)
    {
        $ipLong = ip2long($ip);
        if ($ipLong === false) return false;

        // 1) директно съвпадение с чист IP
        if (in_array($ipLong, $parsed['ips'], true)) return true;

        // 2) попада ли в някоя от мрежите?
        foreach ($parsed['nets'] as $net) {
            if (($ipLong & $net['mask']) === ($net['net'] & $net['mask'])) {
                return true;
            }
        }

        return false;
    }
}
