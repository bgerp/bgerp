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
        $this->pageMenu = 'Настройки';
        
        $this->TAB('core_Packs', 'Код->Пакети', 'admin');
        $this->TAB('core_Updates', 'Код->Обновяване', 'admin');
        $this->TAB('core_Maintenance', 'Код->Миграции', 'admin');
        $this->TAB('core_Users', 'Потребители', 'admin');
        $this->TAB('core_Roles', 'Роли', 'admin');
        $this->TAB('core_Lg', 'Превод', 'admin');
        
        $this->TAB('log_Data', 'Логове->Потребителски', 'admin');
        $this->TAB('log_System', 'Логове->Системен', 'admin, debug');
        $this->TAB('log_Actions', 'Логове->Действия', 'admin');
        $this->TAB('log_Browsers', 'Логове->Браузъри', 'admin');
        $this->TAB('log_Classes', 'Логове->Класове', 'admin');
        $this->TAB('log_Ips', 'Логове->IP-та', 'admin');
        $this->TAB('log_Referer', 'Логове->Реферери', 'admin');
        $this->TAB('core_LoginLog', 'Логове->Логин', 'admin');
        
        $this->TAB('core_Cron', 'Крон');
        $this->TAB('core_Plugins', 'Плъгини', 'admin');
        $this->TAB('core_Cache', 'Вътрешни->Кеш', 'debug');
        $this->TAB('core_Permanent', 'Вътрешни->Постоянен кеш', 'debug');
        
        $this->TAB('core_Classes', 'Вътрешни->Класове', 'debug');
        $this->TAB('core_Interfaces', 'Вътрешни->Интерфейси', 'debug');
        $this->TAB('core_Locks', 'Вътрешни->Заключвания', 'debug');
        $this->TAB('core_Settings', 'Вътрешни->Персонализация', 'debug');
        $this->TAB('core_Forwards', 'Вътрешни->Пренасочвания', 'debug');
        $this->TAB('core_CallOnTime', 'Вътрешни->Отложени', 'debug');

        Mode::set('pageSubMenu', 'Админ');
    }
}