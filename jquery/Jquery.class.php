<?php


/**
 * Клас 'jquery_Jquery' - Работа с JQuery библиотеката
 *
 *
 * @category  ef
 * @package   jquery
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class jquery_Jquery
{
    
    
    /**
     * Пътя до JQuery библиотеката
     * 
     * @return string
     */
    static function getPath()
    {
        $conf = core_Packs::getConfig('jquery');
        $jQueryPath = 'jquery/' . $conf->JQUERY_VERSION . '/jquery.min.js';
        
        return $jQueryPath;
    }
    
    
    /**
     * Добавя JQuery библиотеката към шаблона
     * 
     * @param core_ET $tpl
     */
    static function enable(&$tpl)
    {
        // Ако не е подаден обект, създаваме празен шаблон
        if (!is_object($tpl)) $tpl = new ET();
        
        // Ако не е шаблон
        if (!($tpl instanceof core_ET)) return FALSE;
        
        // Пътя до библиотеката
        $jQueryPath = static::getPath();
        
        // Добавяме библиотеката
        $tpl->push($jQueryPath, "JS");
    }
    
    
    /**
     * Добавя подадения код във функция, на JQuery, която се вика след зареждане на страницата
     * 
     * @param core_ET $tpl
     * @param string $code
     * @param boolean $once
     */
    static function run(&$tpl, $code, $once = FALSE)
    {
        $tpl->appendOnce(static::getCodeTpl(), "JQRUN");
        
        if($once) {
            $tpl->appendOnce($code, 'JQUERY_CODE');
        } else {
            $tpl->append($code, 'JQUERY_CODE');
        }
    }
    
    
    /**
     * Връща шаблон в който да се изпълни кода след зареждане на страницата
     * 
     * @return core_ET
     */
    static function getCodeTpl()
    {
        $runTpl = new ET("\n$(document).ready(function(){ \n[#JQUERY_CODE#]\n });\n");
        
        return $runTpl;
    }
    
    
    /**
     * Функция, която да се изпълни след получаване на резултата по AJAX
     * 
     * @param core_ET $tpl
     * @param string $func
     * @param boolean $once
     */
    static function runAfterAjax(&$tpl, $func, $once = TRUE)
    {
        if (!is_object($tpl)) {
            $tpl = new ET();
        }
        
        $tpl->push($func, 'JQUERY_RUN_AFTER_AJAX', $once);
    }
}
