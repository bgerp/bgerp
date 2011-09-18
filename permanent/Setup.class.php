<?php


/**
 * Клас 'permanent_Setup' - Съхранява параметри и показания на обекти
 *
 * @category   Experta Framework
 * @package    permanent
 * @author	   Димитър Минеков
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n 
 * @since      v 0.1
 */
class permanent_Setup {
    
    
    /**
     *  Версия
     */
    var $version = '0.1';
    
    /**
     *  Контролер на връзката от менюто core_Packs
     */
    var $startCtr = 'permanent_Data';
    

    /**
     *  Екшън на връзката от менюто core_Packs
     */
    var $startAct = 'default';

    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'permanent_Data'
        );
                
    	$instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
    	        
        return "";
    }
}