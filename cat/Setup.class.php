<?php


/**
 * class cat_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Setup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cat_Products';
    
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    
    /**
     * Описание на модула
     */
    var $info = "Каталог на стандартни продукти";
    
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'cat_UoM',
            'cat_Groups',
            'cat_Products',
            'cat_products_Params',
            'cat_products_Packagings',
            'cat_products_Files',
            'cat_Categories',
            'cat_Params',
            'cat_Packagings',
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
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}