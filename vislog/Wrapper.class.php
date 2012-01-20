<?php


/**
 * Клас 'vislog_Wrapper'
 *
 * Поддържа табовете на пакета 'vislog'
 *
 *
 * @category  vendors
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class vislog_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        $tabs->TAB('vislog_History', 'История');
        $tabs->TAB('vislog_Refferer', 'Рефериране');
        $tabs->TAB('vislog_HistoryResources', 'Ресурси');
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}