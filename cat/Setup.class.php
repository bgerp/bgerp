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
        $html .= $Menu->addItem(1, 'Каталог', 'Продукти', 'cat_Products', 'default', "{$role}, admin");
        
        return $html;
    }
    
    private function setupRoles()
    {
        $html = '';
        
        $Roles = &cls::get('core_Roles');
        $catRoleId = $Roles->save(
        (object)array(
            'role' => 'cat'
        ),
        NULL, 'ignore'
        );
        
        if ($catRoleId === 0) {
            $html .= '<li>OK, вече съществува роля `cat`</li>';
        } elseif ($catRoleId) {
            $html .= '<li style="color: green;">Добавена роля `cat`</li>';
        } else {
            $html .= '<li style="color: red;">Грешка при добавяне на роля `cat`</li>';
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