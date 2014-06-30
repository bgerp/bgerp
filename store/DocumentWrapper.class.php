<?php
class store_DocumentWrapper extends store_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
        
        $tabs->TAB('acc_OpenDeals', 'Чакащи', array('acc_OpenDeals', 'show' => 'store'), 'store,ceo');
		$tabs->TAB('store_ShipmentOrders', 'Експедиции');
        $tabs->TAB('store_Receipts', 'Получавания');
		$tabs->TAB('store_Transfers', 'Трансфери');
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Документи';
    }
}