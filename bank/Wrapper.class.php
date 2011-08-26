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
        
        // проверка за избрана с-ка
        $selectedAccountId = Mode::get('selectedAccountId');
        
        if ($selectedAccountId) {
	        $tabs->TAB('bank_BankAccounts',     'Банкови сметки');
	        $tabs->TAB('bank_BankAccountTypes', 'Типове банкови сметки');
	        $tabs->TAB('bank_BankOwnAccounts',  'Банкови сметки на фирмата');
	        $tabs->TAB('bank_BankDocuments',    'Банкови документи');        	
        } else {
            $tabs->TAB('bank_BankOwnAccounts',  'Банкови сметки на фирмата');
        }

        $tabs->TAB('bank_Currencies',     'Валути');
        $tabs->TAB('bank_CurrencyGroups', 'Групи валути');
        $tabs->TAB('bank_CurrencyRates',  'Валутни курсове');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}