<?php


/**
 * Клас 'content_Setup' - контекстно меню за бутоните от втория ред на тулбара
 *
 *
 * @category  vendors
 * @package   context
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class context_Setup extends core_ProtoSetup 
{
    /**
     * контекстно меню за бутоните
     */
    var $info = "Контекстно меню за бутоните от тулбара";
    

    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonJs()
    {
        return 'context/lib/contextMenu.js';
    }
    
    
    /**
     * Връща JS файлове, които са подходящи за компактиране
     */
    public function getCommonCss()
    {
        return "context/lib/contextMenu.css";
    }

    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме контекстното меню към тулбара
        $html .= $Plugins->installPlugin('Контекстно меню', 'context_Plugin', 'core_Toolbar', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deinstall();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Date полета
        $Plugins->deinstallPlugin('context_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'context_Plugin'";
        
        return $html;
    }
}
