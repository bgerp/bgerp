<?php

/**
 *  class currency_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъра Currency
 *
 */
class currency_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'currency_Currencies';
    
    
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
    var $info = "Валути и техните курсове";
   
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'currency_Currencies',
            'currency_CurrencyGroups',
            'currency_CurrencyRates'            
        );
        
        // Роля за power-user на този модул
        $role = 'currency';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2, 'Финанси', 'Валути',  'currency_Currencies', 'default', "{$role}, admin");
        
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