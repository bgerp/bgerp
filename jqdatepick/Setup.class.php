<?php


/**
 * @todo Чака за документация...
 */
defIfNot('JQDATEPICKER_VERSION', 'v4.0.6');

/**
 * Клас 'jqdatepick_Setup' -
 *
 *
 * @category  vendors
 * @package   jqdatepick
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class jqdatepick_Setup extends core_Manager {
    
    
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
    var $info = "Самопоказващ се календар в полетата за дата";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
           'JQDATEPICKER_VERSION' => array ('varchar', 'mandatory')
    
             );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('Избор на дата', 'jqdatepick_Plugin', 'type_Date', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
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