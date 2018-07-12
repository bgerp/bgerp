<?php


/**
 * Рендер за хипервръзка към калкулатора
 *
 *
 * @category  vendors
 * @package   calculator
 *
 * @author    Milen Georgiev
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class calculator_View
{
    /**
     * Връща хипервръзка с икона за стартиране на калкулатора
     */
    public static function getBtn()
    {
        $tpl = new ET("<a style='cursor:pointer;' title='" . tr('Калкулатор') . "' onclick=\"w = window.open('[#url#]','Calculator','width=484,height=303,resizable=no,scrollbars=no,location=0,status=no,menubar=0,resizable=0,status=0'); if(w) w.focus();\"  target='Calculator'>[#icon#]</a>");
        
        $url = sbf('calculator/html/calculator.html', '');
        
        $icon = ht::createElement('img', array('src' => sbf('calculator/img/calc.png', ''), 'width' => 32, 'height' => 16, 'style' => 'vertical-align:middle;margin-bottom:2px;', 'alt' => 'calculator'));
        
        $tpl->replace($url, 'url');
        
        $tpl->replace($icon, 'icon');
        
        return $tpl;
    }
}
