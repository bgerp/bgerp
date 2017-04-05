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
        $this->TAB('store_Stores', 'Складове', 'ceo,storeWorker');
        $this->TAB('store_Products', 'Наличности');
        $this->TAB('store_ShipmentOrders', 'Документи->Експедиции');
        $this->TAB('store_Receipts', 'Документи->Получавания');
		$this->TAB('store_Transfers', 'Документи->Трансфери');
		$this->TAB('store_ConsignmentProtocols', 'Документи->Отговорно пазене');
		$this->TAB('store_InventoryNotes', 'Документи->Инвентаризация');
		
        $this->title = 'Склад';
    }
}