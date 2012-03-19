<?php



/**
 * Масово разпращане - опаковка
 *
 *
 * @category  all
 * @package   blast
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('blast_Lists', 'Списъци');
        $tabs->TAB('blast_Emails', 'Имейли');
        $tabs->TAB('blast_Sms', 'SMS-и');
        $tabs->TAB('blast_Faxes', 'Факсове');
        $tabs->TAB('blast_Letters', 'Писма');
        $tabs->TAB('blast_Labels', 'Етикети');
        
        //$tabs->TAB('blast_ListSend', 'Лог на изпращаните писма');
        $tabs->TAB('blast_Blocked', 'Блокирани');
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab) ? $invoker->className : $invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
        
        $invoker->menuPage = 'Разпращане';
    }
}