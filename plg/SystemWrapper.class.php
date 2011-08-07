<?php

/**
 * Клас 'plg_SystemWrapper' - Поддържа системното меню и табовете на пакета 'Core'
 *
 *
 * @category   Experta Framework
 * @package    plg
 * @author     Milen Georgiev
 * @copyright  2006-2009 Experta Ltd.
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class plg_SystemWrapper extends core_Plugin
{
    
    
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
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
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . ' » ' . tr('Ядро') . ' » ', 'PAGE_TITLE');
    }
}