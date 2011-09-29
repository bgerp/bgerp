<?php


/**
 * Клас 'keyboard_Setup' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    keyboard
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class keyboard_Setup extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = '';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Виртуална клавиатура";
    
    
    /**
     *  Инсталиране на пакета
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
     *  Де-инсталиране на пакета
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