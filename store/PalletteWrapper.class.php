<?php
class store_PalletteWrapper extends store_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
		$tabs->TAB('store_Pallets', 'Палети');
		$tabs->TAB('store_PalletTypes', 'Видове');
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Палети';
    }
}