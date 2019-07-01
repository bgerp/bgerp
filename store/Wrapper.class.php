<?php


/**
 * Клас 'store_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'store'
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class store_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('store_Products', 'Наличности', 'ceo,storeWorker');
        $this->TAB('store_Stores', 'Складове', 'ceo,storeWorker');
        $this->TAB(array('deals_OpenDeals', 'show' => 'store'), 'Документи->Чакащи', 'store,ceo');
        $this->TAB('store_ShipmentOrders', 'Документи->Експедиции', 'store,ceo');
        $this->TAB('store_Receipts', 'Документи->Получавания', 'store,ceo');
        $this->TAB('store_Transfers', 'Документи->Междускладови трансфери', 'store,ceo');
        $this->TAB('store_ConsignmentProtocols', 'Документи->Отговорно пазене', 'store,ceo');
        $this->TAB('store_InventoryNotes', 'Документи->Инвентаризация', 'store,ceo');
        
        $this->title = 'Склад';
    }
}
