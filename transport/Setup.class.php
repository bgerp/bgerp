<?php



/**
 * Транспорт - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   transport
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class transport_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'transport_Requests';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Транспортни операции";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
        	'transport_Requests', 
        	'transport_Shipment', 
       		'transport_Claims', 
       		'transport_Registers', 
            
        );
        
        // Роля за power-user на този модул
        $role = 'transport';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        
        $html .= $Menu->addItem(3.6, 'Логистика', 'Транспорт', 'transport_Requests', 'default', "{$role}, ceo");
        
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