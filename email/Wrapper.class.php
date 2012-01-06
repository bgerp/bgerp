<?php
/**
 * Имейли - опаковка
 *
 * @category   BGERP
 * @package    rip
 * @author     Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class email_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('email_Messages', 'Входящи');
        $tabs->TAB('email_Sent', 'Изходящи');

        $tabs->TAB('email_Inboxes', 'Кутии');
        $tabs->TAB('email_PublicDomains', 'Пуб. домейни');
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab)?$invoker->className:$invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');

        $invoker->menuPage = 'Имейли';
    }
}