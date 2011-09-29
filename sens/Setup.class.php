<?php

/**
 *  class acc_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани със сензорите
 *
 */
class sens_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    

    /**
     *  От кои други пакети зависи
     */
    var $depends = 'permanent=0.1';
    
    
    /**
     *  Начален контролер на пакета за връзката в core_Packs
     */
    var $startCtr = 'sens_Sensors';
    
    
    /**
     *  Начален екшън на пакета за връзката в core_Packs
     */
    var $startAct = 'default';
    

    /**
     * Описание на модула
     */
    var $info = "Мониторинг на сензори и оборудване";
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'sens_Sensors',
            'sens_SensorLogs',
            'sens_Limits',
            'sens_Params',
            'sens_Overviews',
            'sens_OverviewDetails'
        );
        
        // Роля за power-user на този модул
        $role = 'sens';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        core_Classes::add('sens_driver_SensorMockup');
        core_Classes::add('sens_driver_HWgSTE');
        core_Classes::add('sens_driver_TSM');
        core_Classes::add('sens_driver_SATEC');
        core_Classes::add('sens_driver_TCW121');
                
        $Menu = cls::get('bgerp_Menu');
        $Menu->addItem(3, 'Мониторинг', 'MOM', 'sens_Sensors', 'default', "{$role}, admin");
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "Пакета 'sens' е де-инсталиран";
    }
}