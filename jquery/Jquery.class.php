<?php

DEFINE('JQUERY_VER', '1.4.2');


/**
 *  @todo Чака за документация...
 */
DEFINE('JQUERY_UI_VER', '1.8.2');


/**
 * Клас 'jquery_Jquery' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    jquery
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class jquery_Jquery {
    
    
    /**
     *  @todo Чака за документация...
     */
    function jquery_Jquery()
    {
        $this->path = sbf("jquery", '');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function enable(&$tpl)
    {
        $tpl->push("jquery/" . JQUERY_VER . "/jquery-" . JQUERY_VER . ".min.js", "JS");
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function enableUI(&$tpl)
    {
        $this->enable($tpl);
        
        // $tpl->push("jquery/ui-" . JQUERY_UI_VER . "/js/jquery-ui-1.8.2.custom.min.js", "JS");
        $tpl->push("http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js", "JS");
        $tpl->push("jquery/ui-1.8.2/css/custom-theme/jquery-ui-1.8.2.custom.css", "CSS");
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function enableDatePicker(&$tpl)
    {
        $this->enableUI($tpl);
        $tpl->push("jquery/ui/i18n/jquery.ui.i18n.all.js", "JS");
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function enableTableSorter(&$tpl)
    {
        $this->enable($tpl);
        
        $tpl->push("jquery/tablesorter/themes/blue/style.css", "CSS");
        
        if(isDebug()) {
            $tpl->push("jquery/tablesorter/jquery.tablesorter.js", "JS");
        } else {
            $tpl->push("jquery/tablesorter/jquery.tablesorter.min.js", "JS");
        }
        $tpl->push("jquery/jquery.uitableedit.js", "JS");
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function run(&$tpl, $code, $once=FALSE)
    {
        $tpl->appendOnce(new ET("\n$(document).ready(function(){ \n[#JQUERY_CODE#]\n });\n"), "JQRUN");
        
        if($once) {
            $tpl->appendOnce($code, 'JQUERY_CODE');
        } else {
            $tpl->append($code, 'JQUERY_CODE');
        }
    }
}