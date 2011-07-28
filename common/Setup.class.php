<?php

/**
 *  class common_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  админ. мениджъри с общо предназначение
 *
 */

class common_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'common_Units';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $depends = 'crm=0.1';
    
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'common_BankAccounts',
            'common_BankAccountTypes',
            'common_Currencies',
            'common_CurrencyRates',
            'common_CurrencyGroups',
            'common_CurrencyGroupContent',
            'common_Units',
            'common_Locations',
            'common_LocationTypes',
            'common_DocumentTypes',
            'common_PaymentMethods',
            'common_PaymentMethodsNew',
            'common_PaymentMethodDetails',
            'common_PaymentTerms',
            'common_Mvr',
            'common_DistrictCourts'
        );
        
        // Роля за power-user на този модул
        $role = 'common';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3, 'Общи', 'Локации', 'common_Locations', 'default', "{$role}, admin");
        $html .= $Menu->addItem(3, 'Общи', 'Валути', 'common_Currencies', 'default', "{$role}, admin");
        $html .= $Menu->addItem(3, 'Общи', 'Данни', 'drdata_Countries', 'default', "{$role}, admin");
        
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