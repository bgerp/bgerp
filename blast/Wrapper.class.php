<?php
/**
 * Масово разпращане - опаковка
 *
 * @category   bgERP
 * @package    blast
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 */
class blast_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('blast_Lists', 'Списъци');
        $tabs->TAB('blast_Emails', 'Е-мейли');
        $tabs->TAB('blast_Sms', 'SMS-и');
        $tabs->TAB('blast_Faxes', 'Факсове');
        $tabs->TAB('blast_Letters', 'Писма');
        $tabs->TAB('blast_Labels', 'Етикети');
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab)?$invoker->className:$invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');

        $invoker->menuPage = 'Разпращане';
    }
}