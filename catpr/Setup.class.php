<?php



/**
 * Ценовия аспект на каталога - себестойности и ценоразписи
 *
 *
 * @category  bgerp
 * @package   catpr
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catpr_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'catpr_Costs';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Цени и себестойност на стандартните продукти";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'catpr_Costs',
            'catpr_Pricegroups',
            'catpr_Discounts',
            'catpr_discounts_Details',
            'catpr_Pricelists',
            'catpr_pricelists_Details',
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
        $html .= $Menu->addItem(1, 'Продукти', 'Цени', 'catpr_Costs', 'default', "{$role}, admin");
        
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