<?php


/**
 * class recently_Setup
 *
 * Инсталиране/Деинсталиране на
 * Подсказки за инпут полетата
 *
 *
 * @category  vendors
 * @package   recently
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class recently_Setup {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'recently_Values';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Запомняне в избрани полета на последно въведените данни";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Установяваме мениджъра;
                $Values = cls::get('recently_Values');
        $html .= $Values->setupMVC();
        
        // Зареждаме мениджъра на плъгините
                $Plugins = cls::get('core_Plugins');
        
        // Инсталираме recently към формите
                $Plugins->installPlugin('Recently', 'recently_Plugin', 'core_Form', 'private');
        $html .= "<li>Закачане към формите (Активно)";
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "";
    }
}