<?php

/**
 * Клас 'page_Dialog' - Страница за диалогови прозорци
 * 
 * @category  ef
 * @package   page
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class page_Dialog extends page_Html
{
    
    
    /**
     * Изпраща към изхода съдържанието, като преди това го опакова
     */
    function output($content = '', $place = 'CONTENT')
    {
        $this->replace("UTF-8", 'ENCODING');
        
        $this->append("<link rel=\"stylesheet\" type=\"text/css\" href=" . sbf("css/common.css") . "/>\n", "HEAD");
        
        $this->append("<link rel=\"stylesheet\" type=\"text/css\" href=" . sbf("css/dialog.css") . "/>\n", "HEAD");
        
        $this->append("<script type=\"text/javascript\" src=" . sbf("js/efCommon.js") . "></script>\n", "HEAD");
        
        parent::output($content, 'PAGE_CONTENT');
    }
}
