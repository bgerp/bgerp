<?php
class cash_DocumentWrapper extends cash_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
		$tabs->TAB('cash_InternalMoneyTransfer', 'Вътрешни парични трансфери');
        $tabs->TAB('cash_ExchangeDocument', 'Обмени на валути');
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Вътрешни трансфери';
    }
}