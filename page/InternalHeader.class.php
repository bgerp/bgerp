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
                     <div class='menuRow clearfix21'><div style='width:100%; border:solid 1px red;'>[#MENU_ROW#]<!--ET_BEGIN NOTIFICATIONS_CNT--><div id='notificationsCnt'>[#NOTIFICATIONS_CNT#]</div><!--ET_END NOTIFICATIONS_CNT--></div></div>
                </div>
                <!--ET_BEGIN SUB_MENU--><div id=\"subMenu\">[#SUB_MENU#]</div>\n<!--ET_END SUB_MENU-->");
        } else {
            $this->header = new ET("
                <div id='mainMenu'>
                    <div style='float:right;'><!--ET_BEGIN NOTIFICATIONS_CNT--><div id='notificationCnt'>[#NOTIFICATIONS_CNT#]</div><!--ET_END NOTIFICATIONS_CNT-->[#logo#]</div>
                    <div class=\"menuRow\">[#MENU_ROW1#]</div>
                    <div class=\"menuRow\" style=\"margin-top:3px; margin-bottom:3px;\">[#MENU_ROW2#]</div>
                    <div class=\"menuRow\">[#MENU_ROW3#]</div>                   

                </div> <div class='clearfix'></div>
                <!--ET_BEGIN SUB_MENU--><div id=\"subMenu\">[#SUB_MENU#]</div>\n<!--ET_END SUB_MENU-->");
            
            $img = ht::createElement('img', array('src' => sbf('img/bgerp.png', ''), 'alt' => '', 'style' => 'border:0; border-top:5px solid transparent;'));

            $logo = ht::createLink($img, array('bgerp_Portal', 'Show'));
            
            $this->header->replace($logo, 'logo');
        }
        
        $Menu = cls::get('bgerp_Menu');
        
        $Menu->place($this->header);
        
        $this->core_Et($this->header);
        
        $this->prepend("\n<meta name=\"robots\" content=\"noindex,nofollow\">", 'HEAD');
        $this->prepend("\n<meta name=\"format-detection\" content=\"telephone=no\">", 'HEAD');
    }
}