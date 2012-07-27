<?php
/**
 * Шаблон за страница заредена от не-идентифициран посетител
 *
 * @category  bgerp
 * @package   page
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class page_External extends page_Html
{
    /**
     * Конструктор
     */
    function __construct()
    {
    	$conf = core_Packs::getConfig('core');
    	
        parent::__construct();

        $this->replace("UTF-8", 'ENCODING');
        
        $this->push(Mode::is('screenMode', 'narrow') ? "css/narrowCommon.css" : 'css/wideCommon.css', 'CSS');
        $this->push(Mode::is('screenMode', 'narrow') ? "css/narrowApplication.css" : 'css/wideApplication.css', 'CSS');
        $this->push('js/efCommon.js', 'JS');
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf("img/favicon.ico", '"', TRUE) . ">", "HEAD");
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        $this->replace(new ET("<div class='external'>[#PAGE_CONTENT#]</div>"), "PAGE_CONTENT");
    }
}