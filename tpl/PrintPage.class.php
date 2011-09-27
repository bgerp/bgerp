<?php

/**
 * Клас 'tpl_PrintPage' - Шаблон за страница за печат
 *
 *
 * @category   Experta Framework
 * @package    tpl
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class tpl_PrintPage extends tpl_HtmlPage {
    
    
    /**
     *  @todo Чака за документация...
     */
    function tpl_PrintPage()
    {
        $this->tpl_HtmlPage();
        $this->replace("UTF-8", 'ENCODING');
        
        $this->push(Mode::is('screenMode', 'narrow')?"css/narrowCommon.css":'css/wideCommon.css', 'CSS');
        
        $this->push(Mode::is('screenMode', 'narrow')?"css/narrowApplication.css":'css/wideApplication.css', 'CSS');
        $this->append("window.print();", 'ON_LOAD');
        
        
        $this->append("
         * {
             background-color: white !important;
             background-image: none !important;
         }", "STYLES");
        
        $this->push( 'js/efCommon.js', 'JS');
        
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico") . ">", "HEAD");
        
        $this->prepend(EF_APP_TITLE, 'PAGE_TITLE');
    }
}