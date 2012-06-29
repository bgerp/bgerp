<?php


/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_SERVER', 'talk.google.com');

/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_PORT', '5222');

/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_USER', '');

/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_PASSWORD', '');

/**
 * @todo Чака за документация...
 */
defIfNot('XMPPHP_DOMAIN', 'gmail.com');


/**
 * class xmpphp_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със чат съобщенията
 *
 *
 * @category  vendors
 * @package   xmpphp
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class xmpphp_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'xmpphp_Sender';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "XMPP известяване";


    /**
     * Необходими пакети
     */
    var $depends = '';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
               
           'XMPPHP_SERVER'   => array ('varchar'),
    
           'XMPPHP_PORT'   => array ('int'),
     
           'XMPPHP_DOMAIN'   => array ('varchar'),
    
           'XMPPHP_USER'   => array ('identifier', 'mandatory'),
    
           'XMPPHP_PASSWORD'   => array ('password', 'mandatory')
    
    
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'xmpphp_Sender'
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
                
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    }
}