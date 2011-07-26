<?php


/**
 * Клас 'jqdatepick_Plugin' - избор на дата
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    jqdatepick
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class jqdatepick_Plugin extends core_Plugin {
    
    
    /**
     * Изпълнява се преди рендирането на input
     */
    function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr = array())
    {
        if(Mode::is('screenMode', 'narrow')) return;
        ht::setUniqId($attr);
    }
    
    
    /**
     * Изпълнява се след рендирането на input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr= array())
    {
        if(Mode::is('screenMode', 'narrow')) return;
        
        $JQuery = cls::get('jquery_Jquery');
        $JQuery->enable($tpl);
        $tpl->push("jqdatepick/v4.0.5/flora.datepick.css", "CSS");
        $tpl->push("jqdatepick/v4.0.5/jquery.datepick.js", "JS");
        $tpl->push("jqdatepick/v4.0.5/jquery.datepick-" . core_Lg::getCurrent() . ".js", "JS");
        
        $JQuery->run($tpl, "$('#" . $attr['id'] . "').datepick({dateFormat: 'dd-mm-yyyy'});");
    }
}