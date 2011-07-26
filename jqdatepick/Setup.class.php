<?php


/**
 * Клас 'jqdatepick_Setup' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    calendarpicker
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class jqdatepick_Setup extends core_Manager {
    
    
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
     *  Инсталиране на пакета
     */
    function install()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $Plugins->installPlugin('Date Picker', 'jqdatepick_Plugin', 'type_Date', 'private');
        $html .= "<li>Закачане към полетата за дати - type_Date (Активно)";
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Date полета
        $Plugins->deinstallPlugin('jqdatepick_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'calendarpicker_Plugin'";
        
        return $html;
    }
}