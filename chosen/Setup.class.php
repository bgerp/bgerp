<?php


/**
 * Клас 'chosen_Setup' - Предава по добър изглед на keylist полетата
 *
 * @category   Experta Framework
 * @package    chosen
 * @author	   Yusein Yuseinov
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n 
 * @link       http://harvesthq.github.com/chosen/
 * @since      v 0.1
 */
class chosen_Setup extends core_Manager {
    
    
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
        
        // Инсталираме
        $Plugins->installPlugin('Chosen', 'chosen_Plugin', 'type_Keylist', 'private');
        $html .= "<li>Закачане към полетата за данни - type_Keylist (Активно)";
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Keylist полета
        $Plugins->deinstallPlugin('chosen_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'chosen_Plugin'";
        
        return $html;
    }
}