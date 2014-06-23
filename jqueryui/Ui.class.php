<?php


/**
 * Версията на JQueryUI, която се използва
 */
defIfNot(JQUERYUI_VERSION, '1.8.2');



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
     * Пътя спрямо версията
     * 
     * @return string
     */
    static function getPath()
    {
        
        $uiPath = 'jqueryui/' . JQUERYUI_VERSION;
        
        return $uiPath;
    }
    
    
    /**
     * Активира JS
     * 
     * @param core_ET $tpl
     */
    static function enableJS(&$tpl)
    {
        $jsPath = static::getPath() . '/js/jquery-ui-1.8.2.custom.min.js';
        
        $tpl->push($jsPath, "JS");
    }
    
    
    /**
     * Активира CSS
     * 
     * @param core_ET $tpl
     */
    static function enableCSS(&$tpl)
    {
        $cssPath = static::getPath() . '/css/custom-theme/jquery-ui-1.8.2.custom.css';
        
        $tpl->push($cssPath, "CSS");
        
    }
}
