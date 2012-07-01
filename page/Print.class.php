<?php



/**
 * Клас 'page_Print' - Шаблон за страница за печат
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
class page_Print extends page_Html {
    
    
    /**
     * @todo Чака за документация...
     */
    function page_Print()
    {
    	$conf = core_Packs::getConfig('core');
    	
        $this->page_Html();
        $this->replace("UTF-8", 'ENCODING');
        
        $this->push(Mode::is('screenMode', 'narrow') ? "css/narrowCommon.css" : 'css/wideCommon.css', 'CSS');
        
        $this->push(Mode::is('screenMode', 'narrow') ? "css/narrowApplication.css" : 'css/wideApplication.css', 'CSS');
        $this->append("window.print();", 'ON_LOAD');
        
        $this->append("
         * {
             background-color: white !important;
             DIV.background-image: none !important;
           }
         ", "STYLES");
        
        $this->push('js/efCommon.js', 'JS');
        
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico") . ">", "HEAD");
        
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        $this->replace(new ET("<div class='printing'>[#PAGE_CONTENT#]</div>"), "PAGE_CONTENT");
    }
}