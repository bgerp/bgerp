<?php

/**
 * Имейли - опаковка
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('email_Messages', 'Входящи');
        $tabs->TAB('email_Sent', 'Изходящи');
        
        $tabs->TAB('email_Inboxes', 'Кутии');
        
        if(haveRole('admin')) {
            $tabs->TAB('email_Router', 'Рутиране');
        }
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab)?$invoker->className:$invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
        
        $invoker->menuPage = 'Имейли';
    }
}
