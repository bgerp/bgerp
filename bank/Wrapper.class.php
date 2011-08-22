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
        
        $tabs->TAB('bank_BankAccounts',     'Банкови сметки');
        $tabs->TAB('bank_BankAccountTypes', 'Типове банкови сметки');
        $tabs->TAB('bank_BankOwnAccounts',  'Банкови сметки на фирмата');
       
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}