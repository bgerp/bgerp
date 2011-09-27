<?php

/**
 *  Ценовия аспект на каталога - себестойности и ценоразписи
 *
 */
class catpr_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'catpr_Prices';
    
    
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
            'catpr_Prices',
            'catpr_Pricelists',
            'catpr_Pricelists_Details',
        );
        
        // Роля за power-user на този модул
        $role = 'catpr';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(1, 'Продукти', 'Цени', 'catpr_Prices', 'default', "{$role}, admin");
        
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