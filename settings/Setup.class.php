<?php

/**
 *  class registry_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани със Settings
 *
 */
class settings_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'settings_Settings';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'settings_Settings',
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