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
        
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico", '"', TRUE) . " type=\"image/x-icon\">", "HEAD");
       
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        $this->replace(new ET(
        "<div class='clearfix21' id='all'>
            <div id=\"cmsTop\"> 
                [#PAGE_HEADER#]
            </div>
            <div id=\"cmsMenu\" class='menuRow'>
                [#CMS_MENU#]
            </div>
            <div id=\"maincontent\" {$minHeighStyle}>
                <div class='statuses' id='statuses' style='margin: 0 auto;'>
                    [#STATUSES#]
                </div>
                 [#CMS_LAYOUT#]
             </div>
             <div id=\"cmsBottom\">
                [#FEED#]
                [#cms_Content::getFooter#]
              </div>
         </div>"), 
        'PAGE_CONTENT');

        // Добавка за разпознаване на браузъра
        $Browser = cls::get('core_Browser');
        $this->append($Browser->renderBrowserDetectingCode(), 'BROWSER_DETECT');

        // Добавяме основното меню
        $this->replace(cms_Content::getMenu(), 'CMS_MENU');
        
        // Добавяме лейаута
        $this->replace(cms_Content::getLayout(), 'CMS_LAYOUT');

    }

    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output(&$invoker)
    {
        // Генерираме хедъра и Линка към хедъра
        $invoker->appendOnce(cms_Feeds::generateHeaders(), 'HEAD');
        $invoker->replace(cms_Feeds::generateFeedLink(), 'FEED');
        
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
