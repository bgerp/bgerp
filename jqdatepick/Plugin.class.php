<?php


/**
 * @todo Чака за документация...
 */
defIfNot('JQDATEPICKER_VERSION', 'v4.0.6');


/**
 * Клас 'jqdatepick_Plugin' - избор на дата
 *
 *
 * @category  vendors
 * @package   jqdatepick
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
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
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {
        if(Mode::is('screenMode', 'narrow')) return;
        
        $JQuery = cls::get('jquery_Jquery');
        $JQuery->enable($tpl);
        $tpl->push("jqdatepick/" . JQDATEPICKER_VERSION . "/jquery.datepick.css", "CSS");
        $tpl->push("jqdatepick/" . JQDATEPICKER_VERSION . "/jquery.datepick.js", "JS");
        $tpl->push("jqdatepick/" . JQDATEPICKER_VERSION . "/jquery.datepick-" . core_Lg::getCurrent() . ".js", "JS");
        
        $JQuery->run($tpl, "$('#" . $attr['id'] . "').datepick({dateFormat: 'dd-mm-yyyy'});");
    }
}