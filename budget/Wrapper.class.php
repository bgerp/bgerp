<?php



/**
 * Бюджетиране - опаковка
 *
 *
 * @category  bgerp
 * @package   budget
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class budget_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('budget_Assets', 'Парични средства');
        $tabs->TAB('budget_IncomeExpenses', 'Приходи / Разходи');
        $tabs->TAB('budget_Balances', 'Баланс');
        $tabs->TAB('budget_Reports', 'По подразделения / Дейности');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->currentTab ? : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " « ", 'PAGE_TITLE');
        
        $invoker->menuPage = 'Финанси:Бюджетиране';
    }
}