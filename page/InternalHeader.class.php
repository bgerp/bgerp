<?php


/**
 * Клас 'page_InternalHeader' - Горна част на страницата
 *
 *
 * @category  bgerp
 * @package   tpl
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class page_InternalHeader extends core_ET {
    
    
    /**
     * Конструктор на шаблона
     */
    function page_InternalHeader()
    {
        if(Mode::is('screenMode', 'narrow')) {
            $this->header = new ET("
                <div id='mainMenu'>
                     <div class=\"menuRow\" class='clearfix21;'>[#MENU_ROW#]<!--ET_BEGIN NOTIFICATIONS_CNT--><a id='notificationsCnt' style='margin-right:5px;float:right;' href='" . toUrl(array('bgerp_Portal', 'Show')) . "'>[#NOTIFICATIONS_CNT#]</a><!--ET_END NOTIFICATIONS_CNT--></div>
                </div>
                <!--ET_BEGIN SUB_MENU--><div id=\"subMenu\">[#SUB_MENU#]</div>\n<!--ET_END SUB_MENU-->");
        } else {
            $this->header = new ET("
                <div id='mainMenu'>
                    <div style='float:right;'><!--ET_BEGIN NOTIFICATIONS_CNT--><a id='notificationsCnt' style='position:absolute;margin-left:22px;' href='" . toUrl(array('bgerp_Portal', 'Show')) . "'>[#NOTIFICATIONS_CNT#]</a><!--ET_END NOTIFICATIONS_CNT-->[#logo#]</div>
                    <div class=\"menuRow\">[#MENU_ROW1#]</div>
                    <div class=\"menuRow\" style=\"margin-top:3px; margin-bottom:3px;\">[#MENU_ROW2#]</div>
                    <div class=\"menuRow\">[#MENU_ROW3#]</div>                   

                </div> <div class='clearfix'></div>
                <!--ET_BEGIN SUB_MENU--><div id=\"subMenu\">[#SUB_MENU#]</div>\n<!--ET_END SUB_MENU-->");
            
            $logo = ht::createLink("<IMG  SRC=" .
                sbf('img/bgerp.png') . "  BORDER=\"0\" ALT=\"\" style='border-top:5px solid transparent;'>",
                array('bgerp_Portal', 'Show'));
            
            $this->header->replace($logo, 'logo');
        }
        
        $Menu = cls::get('bgerp_Menu');
        
        $Menu->place($this->header);
        
        $this->core_Et($this->header);
        
        $this->prepend("\n<meta name=\"robots\" content=\"noindex,nofollow\">", 'HEAD');
        $this->prepend("\n<meta name=\"format-detection\" content=\"telephone=no\">", 'HEAD');
    }
}