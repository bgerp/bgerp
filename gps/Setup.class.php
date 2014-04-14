<?php


/**
 * Адрес на който ще се слуша за данни
 */
defIfNot('LOCAL_IP', '127.0.0.1');

/**
 * Порт
 */
defIfNot('PORT', '8500');

/**
 * Протокол
 */
defIfNot('PROTOCOL', 'udp');

/**
 * IP на хост от който се приемат данни // IP на демона, от където праща данните
 */
defIfNot('DATA_SENDER', '127.0.0.1');

/**
 * Домейн на системата
 */
defIfNot('DOMAIN', 'bgerp.local');

/**
 * Клас 'gps_Setup'
 *
 * @category  vendors
 * @package   gps
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class gps_Setup extends core_ProtoSetup 
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'gps_Log';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = "Точки от GPS данни от тракери";
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
            'LOCAL_IP' => array ('ip', 'mandatory, caption=IP от което ще се четат данните'),
            'PORT' => array ('int', 'mandatory, caption=Порт'),
            'PROTOCOL' => array ('enum(udp=udp, tcp=tcp)', 'mandatory, caption=Протокол'),
            'DATA_SENDER' => array ('ip', 'mandatory, caption=Адрес на изпращач'),
            'DOMAIN' => array ('varchar(255)', 'mandatory, caption=Домейн')
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'gps_Log',
            'gps_ListenerControl'
        );

    /**
     * Роли за достъп до модула
     */
    public $roles = 'gps';
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
            array(3.4, 'Мониторинг', 'GPS', 'gps_Log', 'default', "gps,ceo,admin"),
    );
    

    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}