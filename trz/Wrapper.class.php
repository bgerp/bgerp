<?php
/**
 * ТРЗ - опаковка
 *
 * @category   BGERP
 * @package    trz
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class trz_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
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
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab)?$invoker->className:$invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');

        $invoker->menuPage = 'Персонал:ТРЗ';
    }
}