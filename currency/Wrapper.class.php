<?php



/**
 * Клас 'currency_Wrapper'
 *
 *
 * @category  bgerp
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class currency_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'currency'));
        
        $tabs->TAB('currency_Currencies', 'Списък');
        $tabs->TAB('currency_CurrencyGroups', 'Групи валути');
        $tabs->TAB('currency_CurrencyRates', 'Валутни курсове');
        $tabs->TAB('currency_FinIndexes',    'Индекси');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " « ", 'PAGE_TITLE');
    }
}