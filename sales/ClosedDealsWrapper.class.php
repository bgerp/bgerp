<?php
class sales_ClosedDealsWrapper extends sales_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		$tabs->TAB('sales_ClosedDealsDebit', 'Приключени с излишък');
        $tabs->TAB('sales_ClosedDealsCredit', 'Приключени с остатък');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        
        $mvc->currentTab = 'Приключени сделки';
    }
}