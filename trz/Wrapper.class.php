<?php



/**
 * ТРЗ - опаковка
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trz_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('trz_Salaries', 'Заплати');
        $tabs->TAB('trz_Bonuses', 'Бонуси');
        $tabs->TAB('trz_Sickdays', 'Болнични');
        $tabs->TAB('trz_Leaves', 'Отпуски');
        $tabs->TAB('trz_Fines', 'Премии и глоби');
        $tabs->TAB('trz_Payrolls', 'Ведомости (за заплати)');
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab) ? $invoker->className : $invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " « ", 'PAGE_TITLE');
        
        $invoker->menuPage = 'Персонал:ТРЗ';
    }
}