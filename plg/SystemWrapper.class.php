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
        $this->TAB('core_Lg', 'Превод', 'admin');
        $this->TAB('core_Logs', 'Логове->Общ', 'admin');
        
        $this->TAB('logs_Data', 'Логове 2->Данни', 'admin');
        $this->TAB('logs_Actions', 'Логове 2->Действия', 'admin');
        $this->TAB('logs_Browsers', 'Логове 2->Браузъри', 'admin');
        $this->TAB('logs_Classes', 'Логове 2->Класове', 'admin');
        $this->TAB('logs_Ips', 'Логове 2->IP-та', 'admin');
        $this->TAB('logs_Referer', 'Логове 2->Реферери', 'admin');
        
        $this->TAB('core_LoginLog', 'Логове->Логин', 'admin');
        $this->TAB('core_Cron', 'Крон');
        $this->TAB('core_Plugins', 'Плъгини', 'admin');
        $this->TAB('core_Cache', 'Вътрешни->Кеш', 'debug');
        $this->TAB('core_Classes', 'Вътрешни->Класове', 'debug');
        $this->TAB('core_Interfaces', 'Вътрешни->Интерфейси', 'debug');
        $this->TAB('core_Locks', 'Вътрешни->Заключвания', 'debug');
        $this->TAB('core_Settings', 'Вътрешни->Персонализация', 'debug');
        $this->TAB('core_Forwards', 'Вътрешни->Пренасочвания', 'debug');
        $this->TAB('core_CallOnTime', 'Вътрешни->Отложени', 'debug');
    }
}