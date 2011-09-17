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
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'permanent_Data'
        );
        
        // Роля за power-user на този модул
        $role = 'every_one';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
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