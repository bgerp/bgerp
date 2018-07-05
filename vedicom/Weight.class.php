<?php



/**
 * @todo Чака за документация...
 */
define('MSG_LENGTH', 10);
// Броя на повторените данни при което се приема, че теглото е стабилно.
define('PRECISION', 2);
// Регулярен израз който мачва при стабилно състояние на везната. Пример: 'P+02.290'
define('STABLE_EXP', '/[pP]\+[0-9]{2}\.[0-9]{3}$/');




/**
 * Прочитане тегло от VEDIA VDI серии
 *
 *
 * @category  vendors
 * @package   vedicom
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Vedicom
 */
class vedicom_Weight extends core_Manager
{
    
    
    /**
     * Взима теглото от VEDIA VDI везна
     */
    public static function get($ip, $port = 2002, $timeout = 10)
    {
        set_time_limit($timeout);
        
        $fp = fsockopen($ip, $port, $errno, $errstr, $timeout);
        
        $result = false;
        $precision = 1;
        
        while (false !== ($c = fgetc($fp)) && $result !== true) {
            // $res .= $c;
            // echo ("$res <br>"); flush;
            if (preg_match(STABLE_EXP, $res, $matches)) {
                $match = substr($matches[0], 2);
                if ($prev != $match) {
                    $prev = $match;
                } else {
                    $precision++;
                }
                if ($precision == PRECISION) {
                    $result = true;
                }
            }
            //echo ($i++ .' - $res ->'.$res." : ". strlen($res)."<br>"); flush();
        }
        // echo ($match . "<--->" . $precision);
        if ($result) {
            
            return $match;
        }

        return false;
    }
}
