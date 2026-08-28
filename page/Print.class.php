<?php


/**
 * Клас 'page_Print' - Шаблон за страница за печат
 *
 *
 * @category  ef
 * @package   page
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class page_Print extends page_Html
{
    /**
     * @todo Чака за документация...
     */
    public function __construct()
    {
        $conf = core_Packs::getConfig('core');
        
        parent::__construct();
        
        $this->append("\n<meta name=\"robots\" content=\"noindex,nofollow\">", 'HEAD');
        
        $this->replace('UTF-8', 'ENCODING');
        
        $this->push('css/common.css', 'CSS');
        $this->push('css/Application.css', 'CSS');
        $this->push('css/internalTheme.css', 'CSS');

        if (Mode::get('runPrinting') !== false) {
            
            // Печатът се стартира и при възстановяване от кеша на браузъра (bfcache),
            // за да излиза диалогът при всяко показване на страницата
            $printJs = '
                var bgerpPrintStarted = false;
                
                function bgerpRunPrinting()
                {
                    if (bgerpPrintStarted) {
                        return;
                    }
                    
                    bgerpPrintStarted = true;
                    
                    setTimeout(function(){
                        window.print();
                        bgerpPrintStarted = false;
                    }, 200);
                }
                
                if (document.readyState === "complete") {
                    bgerpRunPrinting();
                } else {
                    $(window).on("load", function(){
                        bgerpRunPrinting();
                    });
                }
                
                $(window).on("pageshow", function(e){
                    if (e.originalEvent && e.originalEvent.persisted) {
                        bgerpRunPrinting();
                    }
                });
            ';
            
            jquery_Jquery::run($this, $printJs);
        }

        $this->append('
         * {
             background-color: none !important;
           }
	       	#statuses, .toast-container{
			   display: none !important;
			}
         ', 'STYLES');
        
        jquery_Jquery::enable($this);
        $this->push('js/efCommon.js', 'JS');
        
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=\"" . getBoot(true, true, true) . '/favicon.ico"' . ' type="image/x-icon">', 'HEAD');
        
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        $this->replace(new ET("<div class='printing'>[#PAGE_CONTENT#]</div>"), 'PAGE_CONTENT');
    }
}
