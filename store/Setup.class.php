<?php

/**
 *  class dma_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани с DMA
 *
 */
class store_Setup
{
    /**
     *  Версия на компонента
     */
    var $version = '0.1';
    
    
    /**
     *  Стартов контролер за връзката в системното меню
     */
    var $startCtr = 'store_Stores';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    

    /**
     * Описание на модула
     */
    var $info = "Палетно складово стопанство";

    /**
     *  Инсталиране на пакета
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
            //'store_Zones'
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
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);

        return $res;
    }
}