<?php
class store_ArrangementWrapper extends store_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
        
 		$tabs->TAB('store_Movements', 'Движения');
        $tabs->TAB('store_Pallets', 'Палети');
		$tabs->TAB('store_Products', 'Продукти');
        $tabs->TAB('store_Racks', 'Стелажи');
        $tabs->TAB('store_Zones', 'Зони');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Подредба';
    }
}