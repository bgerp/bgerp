<?php



/**
 * class dma_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с DMA
 *
 *
 * @category  all
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Setup
{
    
    
    /**
     * Версия на компонента
     */
    var $version = '0.1';
    
    
    /**
     * Стартов контролер за връзката в системното меню
     */
    var $startCtr = 'store_Stores';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Палетно складово стопанство";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'store_Stores',
            'store_Movements',
            'store_Pallets',
            'store_Racks',
            'store_RackDetails',
            'store_Products',
            'store_Documents',
            'store_DocumentDetails',
            'store_Zones',
            'store_PalletTypes'
        );
        
        // Роля за power-user на този модул
        $role = 'store';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        core_Classes::add('store_ArrangeStrategyTop');
        core_Classes::add('store_ArrangeStrategyBottom');
        core_Classes::add('store_ArrangeStrategyMain');
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(3, 'Логистика', 'Складове', 'store_Stores', 'default', "{$role}, admin");
        
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