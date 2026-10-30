<?php


defIfNot('ICAL_VERSION', '2.0.1');


/**
 * Настройки на библиотеката за iCalendar
 *
 * @category  vendors
 * @package   ical
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class ical_Setup extends core_ProtoSetup
{
    /** Версия на пакета. */
    public $version = '0.1';


    /** Описание на модула. */
    public $info = 'Преглед на събития в iCalendar файлове';


    /** Пакет без инсталация. */
    public $noInstall = true;


    /** Изборът се пази за конкретната инсталация. */
    public $configDescription = array(
        'ICAL_VERSION' => array('enum(2.0.1,3.6.0)', 'mandatory,caption=ICS Parser->Версия'),
    );


    /** Показва липсващото разширение по стандартния начин. */
    public function checkConfig()
    {
        if (self::get('VERSION') === '3.6.0' && !extension_loaded('mbstring')) {
            return 'ICS Parser 3.6.0 изисква PHP разширението mbstring.';
        }
    }
}
