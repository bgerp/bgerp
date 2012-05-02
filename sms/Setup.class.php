<?php



/**
 * class sms_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със СМС-и
 *
 *
 * @category  vendors
 * @package   sms
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sms_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'sms_Sender';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "SMS известяване";


    /**
     * Необходими пакети
     */
    var $depends = 'mobio=0.1';
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'sms_Sender'
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