<?php



/**
 * Клас 'editwatch_Setup' -
 *
 *
 * @category  vendors
 * @package   editwatch
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class editwatch_Setup extends core_Manager {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'editwatch_Editors';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Предупреждение за паралелна работа с един запис";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Установяваме страните;
                $Editors = cls::get('editwatch_Editors');
        $html .= $Editors->setupMVC();
        
        // Зареждаме мениджъра на плъгините
                $Plugins = cls::get('core_Plugins');
        
        // Инсталиране към всички полета, но без активиране
                $Plugins->installPlugin('Editwatch', 'editwatch_Plugin', 'core_Manager', 'family', 'active');
        $html .= "<li>Закачане към всички core_Manager към формата за редактиране (Спряно)";
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
                $Plugins = cls::get('core_Plugins');
        
        if($delCnt = $Plugins->deinstallPlugin('editwatch_Plugin')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на плъгина";
        } else {
            $html .= "<li>Не са премахнати закачания на плъгина";
        }
        
        return $html;
    }
}