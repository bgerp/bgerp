<?php

/**
 *  class acc_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани със счетоводството
 *
 */
class cams_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'cams_Cameras';
    
    
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
            'cams_Cameras',
            'cams_Records',
            'cams_Positions'
        );
        
        // Роля за power-user на този модул
        $role = 'cams';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        core_Classes::add('cams_driver_UIC');
        core_Classes::add('cams_driver_UICmockup');
        core_Classes::add('cams_driver_Edimax');
        
        $Menu = cls::get('bgerp_Menu');
        $Menu->addItem(3, 'Мониторинг', 'Камери', 'cams_Cameras', 'default', "{$role}, admin");
        
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