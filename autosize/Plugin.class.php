<?php



/**
 * Клас 'ckeditor_Plugin' -
 *
 *
 * @category  vendors
 * @package   ckeditor
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class autosize_Plugin extends core_Plugin {
    
    
    /**
     * Изпълнява се преди рендирането на input
     */
    function on_BeforeRenderInput(&$invoker, &$ret, $name, $value, &$attr = array())
    {
       
        ht::setUniqId($attr);
        $attr['class'] .= ' autosize';
    }
    
    
    /**
     * Изпълнява се след рендирането на input
     */
    function on_AfterRenderInput(&$invoker, &$tpl, $name, $value, $attr = array())
    {
    	$conf = core_Packs::getConfig('autosize');
    	        
        $JQuery = cls::get('jquery_Jquery');
        $JQuery->enable($tpl);
        $tpl->push("autosize/" . $conf->AUTOSIZE_VERSION . "/jquery.autosize.min.js", "JS");
        
        $maxRows = Mode::is('screenMode', 'narrow') ? $conf->AUTOSIZE_MAX_ROWS_NARROW : $conf->AUTOSIZE_MAX_ROWS_WIDE;

        //$tpl->append("\n    .autosize {max-height:{$maxRows}em;}", "STYLES");

        $JQuery->run($tpl, "$('.autosize').autosize({maxHeight:{$maxRows}});");
    }
} 