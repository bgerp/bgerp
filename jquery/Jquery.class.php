<?php


/**
 * Клас 'jquery_Jquery' - Работа с JQuery библиотеката
 *
 *
 * @category  ef
 * @package   jquery
 *
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class jquery_Jquery
{
    /**
     * Пътя до JQuery библиотеката
     *
     * @return string
     */
    public static function getPath()
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
    public static function enable(&$tpl)
    {
        // Ако не е подаден обект, създаваме празен шаблон
        if (!is_object($tpl)) {
            $tpl = new ET();
        }
        
        // Ако не е шаблон
        if (!($tpl instanceof core_ET)) {
            
            return false;
        }
        
        // Пътя до библиотеката
        $jQueryPath = static::getPath();
        
        // Добавяме библиотеката
        $tpl->push($jQueryPath, 'JS');
    }
    
    
    /**
     * Добавя подадения код във функция, на JQuery, която се вика след зареждане на страницата
     *
     * @param core_ET $tpl
     * @param string  $code
     * @param bool    $once
     */
    public static function run(&$tpl, $code, $once = false)
    {
        $code = trim($code);
        
        if ($once) {
            $tpl->appendOnce("\n$(document).ready(function(){ {$code} });", 'JQRUN');
        } else {
            $tpl->append("\n$(document).ready(function(){ {$code} });", 'JQRUN');
        }
    }
    
    
    /**
     * Функция, която да се изпълни след получаване на резултата по AJAX
     *
     * @param core_ET $tpl
     * @param string  $func
     * @param bool    $once
     */
    public static function runAfterAjax(&$tpl, $func, $once = true)
    {
        if (!is_object($tpl)) {
            $tpl = new ET();
        }
        
        $tpl->push($func, 'JQUERY_RUN_AFTER_AJAX', $once);
    }
}
