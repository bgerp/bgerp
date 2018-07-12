<?php
/**
 * Шаблон за страница заредена от не-идентифициран посетител
 *
 * @category  bgerp
 * @package   page
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class page_External extends page_Html
{
    /**
     * Конструктор
     */
    public function __construct()
    {
        $conf = core_Packs::getConfig('core');
        
        parent::__construct();
        
        $this->replace('UTF-8', 'ENCODING');
        
        $this->push('css/common.css', 'CSS');
        $this->push('css/Application.css', 'CSS');
        jquery_Jquery::enable($this);
        $this->push('js/efCommon.js', 'JS');
        $this->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf('img/favicon.ico', '"', true) . '>', 'HEAD');
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        $this->replace(new ET("<div class='external'>[#PAGE_CONTENT#]</div>"), 'PAGE_CONTENT');
    }
}
