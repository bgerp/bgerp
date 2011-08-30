<?php

/**
 * Клас 'currency_Wrapper'
 */
class currency_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'currency') );
        
        $tabs->TAB('currency_Currencies',     'Валути');
        $tabs->TAB('currency_CurrencyGroups', 'Групи валути');
        $tabs->TAB('currency_CurrencyRates',  'Валутни курсове');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}