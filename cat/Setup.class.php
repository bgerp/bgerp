<?php

/**
 *  class cat_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани с продуктите
 *
 */
class cat_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'cat_Products';
    
    
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
            'cat_Attributes',
            'cat_Groups',
            'cat_PriceListDetails',
            'cat_PriceLists',
            'cat_ProductDetails',
            'cat_Products',
            'cat_Prices'
        );
        
        // Роля за power-user на този модул
        $role = 'cat';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(1, 'Продукти', 'Каталог', 'cat_Products', 'default', "{$role}, admin");
        
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