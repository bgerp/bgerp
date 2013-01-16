<?php
class bank_TemplateWrapper extends bank_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		$tabs->TAB('bank_PaymentOrders', 'Платежни Нареждания');
        $tabs->TAB('bank_CashWithdrawOrders', 'Нареждане Разписка');
        $tabs->TAB('bank_DepositSlips', 'Вносни Бележки');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        
        $mvc->currentTab = 'Бланки';
    }
}