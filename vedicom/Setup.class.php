<?php



/**
 * class vedicom_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с vedicom везни
 *
 *
 * @category  vendors
 * @package   vedicom
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class vedicom_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'vedicom_Weight';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Чете тегло от Vedicom - VEDIA VDI везни";


    /**
     * Необходими пакети
     */
    var $depends = '';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array();
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'vedicom_Weight'
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