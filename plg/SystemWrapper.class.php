<?php



/**
 * Клас 'plg_SystemWrapper' - Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  all
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_SystemWrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        Mode::set('pageMenu', 'Система');
        
        $tabs = CLS::get('core_Tabs');
        
        $tabs->TAB('core_Packs', 'Пакети');
        $tabs->TAB('core_Users', 'Потребители');
        $tabs->TAB('core_Roles', 'Роли');
        $tabs->TAB('core_Classes', 'Класове');
        $tabs->TAB('core_Interfaces', 'Интерфейси');
        $tabs->TAB('core_Lg', 'Превод');
        $tabs->TAB('core_Logs', 'Логове');
        $tabs->TAB('core_Cron', 'Крон');
        $tabs->TAB('core_Plugins', 'Плъгини');
        $tabs->TAB('core_Cache', 'Кеш');
        $tabs->TAB('core_Locks', 'Заключвания');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . ' » ' . tr('Ядро') . ' » ', 'PAGE_TITLE');
    }
}