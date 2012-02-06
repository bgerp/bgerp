<?php



/**
 * Рендер за хипервръзка към калкулатора
 *
 *
 * @category  vendors
 * @package   calculator
 * @author    Milen Georgiev
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class calculator_View 
{
    
    /**
     * Връща хипервръзка с икона за стартиране на калкулатора
     */
    function getBtn()
    {
        $tpl = new ET("<a onclick=\"window.open('[#url#]','Calculator','width=484,height=303,resizable=yes,scrollbars=status=0')\" href=\"#\">[#icon#]</a>");

        $url = sbf('calculator/html/calculator.html', '');

        $icon = ht::createElement('img', array('src' => sbf('calculator/img/calc.png', ''), 'width' => 32, 'height' => 16, 'style' => 'vertical-align:middle;'));

        $tpl->replace($url, 'url');

        $tpl->replace($icon, 'icon');

        return $tpl;
    }
    
}