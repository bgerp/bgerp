<?php



/**
 * Клас 'keyboard_Setup' -
 *
 *
 * @category  vendors
 * @package   keyboard
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class keyboard_Setup extends core_Manager {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Виртуална клавиатура";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Зареждаме мениджъра на плъгините
                $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
                $Plugins->installPlugin('Pass VKB', 'keyboard_Plugin', 'type_Password', 'private');
        $html .= "<li>Закачане към полетата за пароли - type_Password (Активно)";
        
        // Инсталиране към всички полета, но без активиране
                $Plugins->installPlugin('All VKB', 'keyboard_Plugin', 'core_Type', 'family', 'stopped');
        $html .= "<li>Закачане към всички инпут полета - type_Password (Спряно)";
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
                $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
                if($delCnt = $Plugins->deinstallPlugin('keyboard_Plugin')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'keyboard_Plugin'";
        } else {
            $html .= "<li>Не са премахнати закачания на плъгина";
        }
        
        return $html;
    }
}