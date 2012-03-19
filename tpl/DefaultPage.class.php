<?php



/**
 * Клас 'tpl_DefaultPage' - Шаблон за страница на приложението
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  all
 * @package   tpl
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class tpl_DefaultPage extends tpl_HtmlPage {
    
    
    /**
     * @todo Чака за документация...
     */
    function tpl_DefaultPage()
    {
        $this->tpl_HtmlPage();
        $this->replace("UTF-8", 'ENCODING');
        
        $this->push(array(Mode::is('screenMode', 'narrow') ? "css/narrowCommon.css" : 'css/wideCommon.css',
                Mode::is('screenMode', 'narrow') ? "css/narrowApplication.css" : 'css/wideApplication.css'), 'CSS');
        $this->push('js/efCommon.js', 'JS');
        
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico") . " type=\"image/x-icon\">", "HEAD");
        $this->appendOnce("\n<link  rel=\"icon\" href=" . sbf("img/favicon.ico") . " type=\"image/x-icon\">", "HEAD");
        
        $this->prepend(EF_APP_TITLE, 'PAGE_TITLE');
        
        $this->replace(cls::get('tpl_PageLayout'), 'PAGE_CONTENT');
        
        $navBar = cls::get('tpl_PageNavbar');
        $navBar = $navBar->getContent();
        
        if(!empty($navBar)) {
            $this->replace($navBar, 'NAV_BAR');
        }
        
        // Вкарваме хедър-а и футъра
        $this->replace(cls::get('tpl_PageHeader'), 'PAGE_HEADER');
        $this->replace(cls::get('tpl_PageFooter'), 'PAGE_FOOTER');
    }
}