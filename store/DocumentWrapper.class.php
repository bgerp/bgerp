<?php
class store_DocumentWrapper extends store_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
		$tabs->TAB('store_Transfers', 'Трансфери');
		$tabs->TAB('store_ShipmentOrders', 'Експедиция');
        $tabs->TAB('store_Receipts', 'Разписки');
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Документи';
    }
}