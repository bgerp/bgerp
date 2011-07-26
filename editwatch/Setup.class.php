<?php


/**
 * Клас 'editwatch_Setup' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    editwatch
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class editwatch_Setup extends core_Manager {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'editwatch_Editors';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    
    
    /**
     *  Инсталиране на пакета
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
     *  Де-инсталиране на пакета
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