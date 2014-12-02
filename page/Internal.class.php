<?php



/**
 * Клас 'page_Internal' - Шаблон за страница на приложението, видима за вътрешни потребители
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  bgerp
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
    	$conf = core_Packs::getConfig('core');
    	
        $this->page_Html();
        
        $this->appendOnce(new ET('[#bgerp_Notifications::subscribeCounter#]'));
        
        $this->replace("UTF-8", 'ENCODING');
        
        $this->push('css/common.css','CSS');
        $this->push('css/Application.css','CSS');
        $this->push('css/default-theme.css','CSS');

        $browserInfo = Mode::get("getUserAgent");
        
		//добавяне на стил само за дефоултния андроидски браузър
        if(strPos($browserInfo, 'Mozilla/5.0') !== FALSE && strPos($browserInfo,'Android') !== FALSE && 
        strPos($browserInfo, 'AppleWebKit') !== FALSE && strPos($browserInfo,'Chrome') === FALSE){
        	  $this->append("
		       select {padding-left: 0.2em !important;}
		         ", "STYLES");
        }
        
        jquery_Jquery::enable($this);
        $this->push('js/efCommon.js', 'JS');
        
        $this->push('Cache-Control: private, max-age=0', 'HTTP_HEADER');
        //$this->push('Pragma: no-cache', 'HTTP_HEADER');
        $this->push('Expires: -1', 'HTTP_HEADER');

        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico", '"', TRUE) . " type=\"image/x-icon\">", "HEAD");
         
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        $this->replace(cls::get('page_InternalLayout'), 'PAGE_CONTENT');
        
        $navBar = cls::get('page_Navbar');
        $navBar = $navBar->getContent();
        
        if(!empty($navBar)) {
            $this->replace($navBar, 'NAV_BAR');
        }
        
        // Вкарваме хедър-а и футъра
        // $this->replace(cls::get('page_InternalHeader'), 'PAGE_HEADER');
        $this->replace(cls::get('page_InternalFooter'), 'PAGE_FOOTER');
    }

    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output(&$invoker)
    {
        if (!Mode::get('lastNotificationTime')) {
            Mode::setPermanent('lastNotificationTime', time());
        }
    }
} 