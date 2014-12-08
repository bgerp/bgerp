<?php


/**
 * Плъгин за SMS
 * 
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_SMSPlg extends core_Plugin
{
    
    
    /**
     * Прихваща извикването на интерфейсния метод prepareNumberStr
     * 
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $number
     */
    public static function on_AfterPrepareNumberStr($mvc, &$res, $number)
    {
        $res = drdata_PhoneType::getNumberStr($number, 0);
    }
}