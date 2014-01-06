<?php
class bank_DocumentWrapper extends bank_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
		$tabs->TAB('bank_IncomeDocuments', 'Приходни документи');
		$tabs->TAB('bank_SpendingDocuments', 'Разходни документи');
        $tabs->TAB('bank_InternalMoneyTransfer', 'Вътрешни трансфери');
        $tabs->TAB('bank_ExchangeDocument', 'Обмени на валути');
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Документи';
    }
}