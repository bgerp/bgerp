<?php

/**
 *  class acc_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани с rfid
 *
 */
class rfid_Setup
{
    /**
     *  Версия
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'rfid_Events';
    
    
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
            'rfid_Readers',
            'rfid_Events',
            'rfid_Tags',
            'rfid_Holders',
            'rfid_Ownerships'
        );
        
        // Роля за power-user на този модул
        $role = 'rfid';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
		
        // Добавяне на класовете за различните драйвери за четците
        // core_Classes::add('rfid_driver_RfidNrj');
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3, 'Мониторинг', 'RFID', 'rfid_Events', 'default', "{$role}, admin");
        
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