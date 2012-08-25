<?php

/**
 * Константи за изпращане на СМС-и през Pro-SMS
 */

/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_URL', '');

/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_USER', '');


/**
 * @todo Чака за документация...
 */
defIfNot('PROSMS_PASS', '');


/**
 * class prosms_Setup
 *
 * Инсталиране/Деинсталиране на плъгина за изпращане на SMS-и чрез prosms
 *
 *
 * @category  vendors
 * @package   prosms
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class prosms_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "SMS изпращане чрез prosms";
    

    var $configDescription = array (
        'PROSMS_URL' => array('url', 'mandatory'),
        'PROSMS_USER' => array('identifier', 'mandatory'),
        'PROSMS_PASS' => array('password', 'mandatory'),
        );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {   
        $managers = array(
            'prosms_Dlr',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }

        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
     
        // Инсталираме плъгина и за изпращане през prosms - по подразбиране
        $html .= $Plugins->forcePlugin('SMS изпращане', 'prosms_Plugin', 'sms_Sender', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Date полета
        $Plugins->deinstallPlugin('prosms_Plugin');
        $html .= "<li>Премахване на prosms като изпращач на SMS-и";
        
        return $html;

    }
}