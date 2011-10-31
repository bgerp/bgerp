<?php
/**
 * Емейли - опаковка
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
        
        $tabs->TAB('email_Messages', 'Съобщения');

        $tabs->TAB('email_Accounts', 'Акаунти');
        
        $tabs->TAB('email_Unsorted', 'Несортирани');
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab)?$invoker->className:$invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');

        $invoker->menuPage = 'Емейли';
    }
}