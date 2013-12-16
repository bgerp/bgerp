<?php



/**
 * Клас 'cms_Page' - Шаблон за публична страница
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
class cms_Page extends page_Html {
    
    
    /**
     * Конструктор за страницата по подразбиране
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function cms_Page()
    {
        // Конструктора на родителския клас
        $this->page_Html();
    	
        // Параметри от конфигурацията
        $conf = core_Packs::getConfig('core');
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');
    	
        // Кодировка - UTF-8
        $this->replace("UTF-8", 'ENCODING');
        
        $this->push('css/common.css','CSS');
        $this->push('css/Application.css','CSS');
        $this->push('cms/css/Wide.css', 'CSS');
        $this->push('js/efCommon.js', 'JS');
        
        $this->push('Cache-Control: public', 'HTTP_HEADER');
        $this->push('Expires: ' . gmdate("D, d M Y H:i:s", time() + (haveRole('powerUser') ? 10 : 3600)) . ' GMT', 'HTTP_HEADER');
        $this->push('-Pragma', 'HTTP_HEADER');
        
        // Добавяне на включвания външен код
        cms_Includes::insert($this);

        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico", '"', TRUE) . " type=\"image/x-icon\">", "HEAD");
        
        $this->replace(new ET(getFileContent('cms/tpl/Page.shtml')), 'PAGE_CONTENT');
        
        // Скрипт за генериране на min-height, според устройството
        $this->append("runOnLoad(setMinHeightExt);", "JQRUN");
        
        // Падинг за логин формата
        $this->append("runOnLoad(loginFormPadding);", "JQRUN");
        
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
        //$invoker->replace(cms_Feeds::generateFeedLink(), 'FEED');
        
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


    /**
     * Връща картинката за главата на публичната страница
     */
    static function getHeaderImg() 
    {
        if(Mode::is('screenMode', 'wide')) {
      	 	$headerImg = sbf("cms/img/bgERP.jpg", '');
        } else {
      	 	$headerImg = sbf("cms/img/bgERP-small.jpg", '');
        }
        
        return $headerImg;
    }

    
}
