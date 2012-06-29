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
        
        $tabs->TAB('email_Incomings', 'Входящи');
        
        //Показва таба за постинги, само ако имаме права за листване
        if (email_Outgoings::haveRightFor('list', core_Users::getCurrent())) {
            $tabs->TAB('email_Outgoings', 'Изходящи');
        }
        
        $tabs->TAB('email_Inboxes', 'Кутии');
        
        if(haveRole('admin')) {
            $tabs->TAB('email_Router', 'Рутиране');
        }
        
        $tabs->TAB('email_Sent', 'Изпращания');

        $tabs->TAB('email_Services', 'Услуги');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->currentTab ? : $invoker->className);
        
        $tpl->prepend(tr($invoker->title) . " « ", 'PAGE_TITLE');
        
        $invoker->menuPage = 'Имейли';
    }
}
