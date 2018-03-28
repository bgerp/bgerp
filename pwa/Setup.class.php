<?php

/**
 * Клас 'pwa_Setup' -  bgERP progressive web application
 *
 *
 * @package   pwa
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pwa_Setup extends core_ProtoSetup
{
	var $info = "bgERP progressive web application";
	
	
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина към страницата
        $html .= $Plugins->installPlugin('bgERP PWA', 'pwa_Plugin', 'core_page_Active', 'family');
        
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
        
        $Plugins->deinstallPlugin('pwa_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'bgERP PWA'";
        
        return $html;
    }
}
