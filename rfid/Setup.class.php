<?php


/**
 * class acc_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с rfid
 *
 *
 * @category  bgerp
 * @package   rfid
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class rfid_Setup
{
    
    
    /**
     * Версия
     */
    var $version = '0.1';
    
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'rfid_Events';
    
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    
    /**
     * Описание на модула
     */
    var $info = "RFID отчитане на раб. време";
    
    
    
    /**
     * Инсталиране на пакета
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
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}