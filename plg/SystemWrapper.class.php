<?php



/**
 * Клас 'plg_SystemWrapper' - Поддържа системното меню и табове-те на пакета 'Core'
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_SystemWrapper extends plg_ProtoWrapper
{
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->pageMenu = 'Система';
        
        $this->TAB('core_Packs', 'Пакети', 'admin');
        $this->TAB('core_Users', 'Потребители', 'admin');
        $this->TAB('core_Roles', 'Роли', 'admin');
        $this->TAB('core_Classes', 'Класове', 'admin');
        $this->TAB('core_Interfaces', 'Интерфейси', 'admin');
        $this->TAB('core_Lg', 'Превод', 'admin');
        $this->TAB('core_Logs', 'Логове', 'admin');
        $this->TAB('core_Cron', 'Крон');
        $this->TAB('core_Plugins', 'Плъгини', 'admin');
        $this->TAB('core_Cache', 'Кеш', 'admin');
        $this->TAB('core_Locks', 'Заключвания', 'admin');
    }
}