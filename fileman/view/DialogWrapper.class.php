<?php

cls::load('tpl_HtmlPage');


/**
 * Клас 'fileman_view_DialogWrapper' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_view_DialogWrapper extends tpl_HtmlPage {
    
    
    /**
     * @todo Чака за документация...
     */
    function output($content)
    {
        $this->replace("UTF-8", 'ENCODING');
        
        $this->append("<link rel=\"stylesheet\" type=\"text/css\" href=" . sbf("fileman/css/default.css") . "/>\n", "HEAD");
        
        if(Mode::is('screenMode', 'narrow')) {
            $this->append("<link rel=\"stylesheet\" type=\"text/css\" href=" . sbf("css/narrowCommon.css") . "/>\n", "HEAD");
        } else {
            $this->append("<link rel=\"stylesheet\" type=\"text/css\" href=" . sbf("css/wideCommon.css") . "/>\n", "HEAD");
        }
        
        $this->append("<script type=\"text/javascript\" src=" . sbf("js/efCommon.js") . "></script>\n", "HEAD");
        
        parent::output($content, 'PAGE_CONTENT');
    }
}