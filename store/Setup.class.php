<?php



/**
 * class dma_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с DMA
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Setup extends core_ProtoSetup
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
     * Списък с мениджърите, които съдържа пакета
     */
    var  $managers = array(
            'store_Stores',
            'store_Movements',
            'store_Pallets',
            'store_PalletTypes',
            'store_Racks',
            'store_RackDetails',
            'store_Products',
            'store_Zones',
            'store_ShipmentOrders',
            'store_ShipmentOrderDetails',
    		'store_Receipts',
    		'store_ReceiptDetails',
    		'store_Transfers',
    		'store_TransfersDetails',
        );
    

    /**
     * Роли за достъп до модула
     */
    var $roles = 'store';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.3, 'Логистика', 'Складове', 'store_Stores', 'default', "store, ceo"),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
                   
        core_Classes::add('store_ArrangeStrategyTop');
        core_Classes::add('store_ArrangeStrategyBottom');
        core_Classes::add('store_ArrangeStrategyMain');
        
    	if($roleRec = core_Roles::fetch("#role = 'masterStore'")){
    		core_Roles::delete("#role = 'masterStore'");
    	}
    	
        // Добавяне на роля за старши складажия
        $html .= core_Roles::addRole('storeMaster', 'store') ? "<li style='color:green'>Добавена е роля <b>storeMaster</b></li>" : '';
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