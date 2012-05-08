<?php



/**
 * Клас 'page_Internal' - Шаблон за страница на приложението, видима за вътрешни потребители
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
class page_Internal extends page_Html {
    
    
    /**
     * Конструктор за страницата по подразбиране
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function page_Internal()
    {
        $this->page_Html();

        $this->replace("UTF-8", 'ENCODING');
        
        $this->push(array(Mode::is('screenMode', 'narrow') ? "css/narrowCommon.css" : 'css/wideCommon.css',
                Mode::is('screenMode', 'narrow') ? "css/narrowApplication.css" : 'css/wideApplication.css'), 'CSS');
        $this->push('js/efCommon.js', 'JS');
        
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico") . " type=\"image/x-icon\">", "HEAD");
        $this->appendOnce("\n<link  rel=\"icon\" href=" . sbf("img/favicon.ico") . " type=\"image/x-icon\">", "HEAD");
        
        $this->prepend(EF_APP_TITLE, 'PAGE_TITLE');
        
        $this->replace(cls::get('page_InternalLayout'), 'PAGE_CONTENT');
        
        $navBar = cls::get('page_Navbar');
        $navBar = $navBar->getContent();
        
        if(!empty($navBar)) {
            $this->replace($navBar, 'NAV_BAR');
        }
        
        // Вкарваме хедър-а и футъра
        $this->replace(cls::get('page_InternalHeader'), 'PAGE_HEADER');
        $this->replace(cls::get('page_InternalFooter'), 'PAGE_FOOTER');
    }

    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output($invoker)
    {
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