<?php


/**
 * Клас 'jqueryui_Ui' - Работа с JQuery UI библиотеката
 *
 *
 * @category  vendors
 * @package   jqueryui
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class jqueryui_Ui
{
    

	/**
	 * 
	 * 
	 * @param core_ET $tpl
	 */
    static function enable(&$tpl)
    {
        // Активираме JQUERY (ако не е активен)
        jquery_Jquery::enable($tpl);
        
        // Добавяме JS пакета
        static::enableJS($tpl);
        
        // Добавяме CSS пакета
        static::enableCSS($tpl);
    }
    
    
    /**
     * Активира JS
     * 
     * @param core_ET $tpl
     */
    static function enableJS(&$tpl)
    {
        $conf = core_Packs::getConfig('jqueryui');
        
        $jsPath = "jqueryui/" . $conf->JQUERYUI_VERSION . "/jquery-ui.js";
        
        $tpl->push($jsPath, "JS");
    }
    
    
    /**
     * Активира CSS
     * 
     * @param core_ET $tpl
     */
    static function enableCSS(&$tpl)
    {
        $conf = core_Packs::getConfig('jqueryui');
        
        $cssPath = "jqueryui/" . $conf->JQUERYUI_VERSION . "/jquery-ui.min.css";
        
        $tpl->push($cssPath, "CSS");
        
    }
}
