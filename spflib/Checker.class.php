<?php


use SPFLib\Checker;
use SPFLib\Check\Environment;

/**
 * Проверява SPF DNS записи
 *
 * @category  vendors
 * @package   spflib
 *
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class spflib_Checker
{
    /**
     * Проверява SPF запис
     *
     * @param string $ip  - IP адрес на изпращача
     * @param string $heloDomain - домейн от helo командата
     * @param string $senderDomain - домейн на изпращача
     *
     * @return string - резултат от проверката
     */
    public static function check($IP = '', $heloDomain = '', $senderDomain = '')
    {
        
        if (core_Composer::isInUse()) {
            try {
                // Инстанция на класа
                $checker = new Checker();
            } catch (Exception $e) {
                reportException($e);
            }
        }
        $res = $checker->check(new Environment($IP, $heloDomain, $senderDomain));
        
        return $res;
    }
}
