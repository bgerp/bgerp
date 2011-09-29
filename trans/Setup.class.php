<?php

/**
 *  class common_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  админ. мениджъри с общо предназначение
 *
 */

class trans_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'trans_DeliveryTerms';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $depends = 'crm=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Транспортни операции";

    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'trans_DeliveryTerms',
        );
        
        // Роля за power-user на този модул
        $role = 'trans';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3, 'Логистика', 'Транспорт', 'trans_DeliveryTerms', 'default', "{$role}, admin");
         
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "Пакетът 'trans' е де-инсталиран";
    }
}