<?php
class bank_DocumentWrapper extends bank_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
		$tabs->TAB('bank_IncomeDocument', 'Приходни Банкови Документи');
		$tabs->TAB('bank_CostDocument', 'Разходни Банкови Документи');
        $tabs->TAB('bank_InternalMoneyTransfer', 'Вътрешно Парични Трансфери');
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Документи';
    }
}