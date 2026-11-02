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
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class spflib_Checker
{
    /**
     * Проверява SPF запис
     *
     * @param string $IP - IP адрес на изпращача
     * @param string $heloDomain - домейн от helo командата
     * @param string $senderDomain - адресът от MAIL FROM (историческо име на аргумента)
     *
     * @return \SPFLib\Check\Result - резултат от проверката, включително getCode()
     */
    public static function check($IP = '', $heloDomain = '', $senderDomain = '')
    {
        expect(core_Composer::isInUse() && class_exists(Checker::class),
            'Липсва SPF Library. Инициализирайте пакета spflib.');
        $checker = new Checker();

        return $checker->check(new Environment($IP, $heloDomain, $senderDomain));
    }
}
