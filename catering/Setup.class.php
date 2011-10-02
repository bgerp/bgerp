<?php

/**
 *  class catering_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъра на кетъринга
 *
 */
class catering_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'catering_Menu';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $depends = 'drdata=0.1';
    

    /**
     * Описание на модула
     */
    var $info = "Кетъринг за служителите";
   

    /**
     *  Инсталиране на пакета
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
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);

        return $res;
    }
}