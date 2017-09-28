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
    function __construct()
    {
    	$conf = core_Packs::getConfig('core');
    	
        parent::__construct();
        
        $this->append("\n<meta name=\"robots\" content=\"noindex,nofollow\">", 'HEAD');
        
        $this->replace("UTF-8", 'ENCODING');
        
        $this->push('css/common.css','CSS');
        $this->push('css/Application.css','CSS');
        $this->push('css/internalTheme.css','CSS');
        
        jquery_Jquery::run($this, "window.print();");
        
        $this->append("
         * {
             background-color: none !important;
           }
	       	#statuses, .toast-container{
			   display: none !important;
			}
         ", "STYLES");
        
        jquery_Jquery::enable($this);
        $this->push('js/efCommon.js', 'JS');
    
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=\"" . getBoot(TRUE) . '/favicon.ico"' . " type=\"image/x-icon\">", "HEAD");
        
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        $this->replace(new ET("<div class='printing'>[#PAGE_CONTENT#]</div>"), "PAGE_CONTENT");
    }
}