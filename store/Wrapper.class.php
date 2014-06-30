<?php



/**
 * Клас 'store_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'store'
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
		$this->TAB('store_ShipmentOrders', 'Документи');
        $this->TAB('store_Movements', 'Подреждане');
        $this->TAB('store_Stores', 'Складове', 'ceo,storeWorker');
        
        $this->title = 'Склад';
    }
}