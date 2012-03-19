<?php



/**
 * class catering_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра на кетъринга
 *
 *
 * @category  all
 * @package   catering
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catering_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'catering_Menu';
    
    
    /**
     * Екшън - входна точка в пакета.
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Кетъринг за служителите";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'catering_Menu',
            'catering_MenuDetails',
            'catering_Companies',
            'catering_EmployeesList',
            'catering_Requests',
            'catering_RequestDetails',
            'catering_Orders'
        );
        
        // Роля за power-user на този модул
        $role = 'catering';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2, 'Персонал', 'Кетъринг', 'catering_Menu', 'default', "{$role}, admin");
        
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