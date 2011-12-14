<?php

/**
 * Клас 'bank_Wrapper'
 */
class bank_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'bank') );
        
        $tabs->TAB('bank_Accounts',      'Банкови сметки');
        $tabs->TAB('bank_OwnAccounts',  'Наши сметки');
        $tabs->TAB('bank_Documents',    'Банкови документи');        	
        
        $tabs->TAB('bank_AccountTypes',  'Типове банкови сметки');
        $tabs->TAB('bank_PaymentMethods','Начини на плащане');

 
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}