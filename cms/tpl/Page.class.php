<?php



/**
 * Клас 'cms_tpl_Page' - Шаблон за публична страница
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cms_tpl_Page extends page_Html {
    
    
    /**
     * Конструктор за страницата по подразбиране
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function cms_tpl_Page()
    {
    	$conf = core_Packs::getConfig('core');
    	
        $this->page_Html();

        $this->replace("UTF-8", 'ENCODING');
        
        $this->push(array(Mode::is('screenMode', 'narrow') ? "css/narrowCommon.css" : 'css/wideCommon.css',
                Mode::is('screenMode', 'narrow') ? "css/narrowApplication.css" : 'css/wideApplication.css'), 'CSS');
        $this->push( 'cms/css/Wide.css', 'CSS');
        $this->push('js/efCommon.js', 'JS');
        
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico") . " type=\"image/x-icon\">", "HEAD");
        $this->appendOnce("\n<link  rel=\"icon\" href=" . sbf("img/favicon.ico") . " type=\"image/x-icon\">", "HEAD");
        
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');

        // $bgImg = sbf('cms/img/bgerp_header.png', '');
        $bgImg = 'http://printed-bags.net/uploads/bgERP_header_644.jpg';
        
        $this->replace(new ET(
        "<div class='clearfix21' id='all'>
            <div id=\"framecontentTop\"  class=\"container\" style=\"background-image:url('{$bgImg}');\"> 
                [#PAGE_HEADER#]
            </div>
            <div id=\"menu\" class='menuRow'>
                [#cms_Content::getMenu#]
            </div>
            <div id=\"maincontent\" {$minHeighStyle}>
                <div class='statuses' id='statuses' style='margin: 0 auto;'>
                    [#STATUSES#]
                </div>
                <div class='row'>
                    <div class='fourcol' id='navigation' style='padding-top:20px;padding-left:20px;'>
                        [#NAVIGATION#]
                    </div>
                    <div class='sevencol'  style='padding-top:20px;'>
                        [#PAGE_CONTENT#]                    
                     </div>
                </div>
             </div>
            [#cms_Content::getFooter#] 
         </div>"), 
        'PAGE_CONTENT');
        $Browser = cls::get('core_Browser');
        $this->append($Browser->renderBrowserDetectingCode());
    }

    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output(&$invoker)
    {
        if (!Mode::get('lastNotificationTime')) {
            Mode::setPermanent('lastNotificationTime', time());    
        }
        $invoker->append(core_Statuses::show(), 'STATUSES');
        
        $Nid = Request::get('Nid', 'int');
        
        if($Nid && $msg = Mode::get('Notification_' . $Nid)) {
            
            $msgType = Mode::get('NotificationType_' . $Nid);
            
            if($msgType) {
                $invoker->append("<div class='notification-{$msgType}'>", 'NOTIFICATION');
            }
            
            $invoker->append($msg, 'NOTIFICATION');
            
            if($msgType) {
                $invoker->append("</div>", 'NOTIFICATION');
            }
            
            Mode::setPermanent('Notification_' . $Nid, NULL);
            
            Mode::setPermanent('NotificationType_' . $Nid, NULL);
        }
    }
}