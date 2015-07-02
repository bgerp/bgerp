<?php



/**
 * Клас 'context_Plugin' - контекстно меню за бутоните от втория ред на тулбара
 *
 *
 * @category  vendors
 * @package   context
 * @author    Nevena Georgiva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class context_Plugin extends core_Plugin {
    
    
    /**
     * Изпълнява се преди добавянето на бутона за показване на втория ред бутони
     */
    function on_BeforeAppendSecondRow($mvc, &$res, &$toolbar, $rowId)
    {
     	if (!is_object($toolbar)) return ;
     	
    	$toolbar->prepend(ht::createFnBtn(' ', NULL, NULL, array('class'=>'more-btn', 'title'=>'Други действия с този документ')), "ROW0");
    	
        $toolbar->push('context/lib/contextMenu.css', "CSS");
        $toolbar->push('context/lib/contextMenu.js', "JS");
        jquery_Jquery::run($toolbar,'prepareContextMenu();', TRUE);
        jquery_Jquery::runAfterAjax($toolbar, 'prepareContextMenu');
        
        return FALSE;
    }
    
    
    /**
     * Изпълнява се преди връщането на лейаута на тулбара
     */
    function on_BeforeGetToolbarLayout($mvc, &$layout, $rowId)
    {
    	if(count($mvc->buttons) > 5 && !Mode::is('screenMode', 'narrow') ||
    	count($mvc->buttons) > 3 && Mode::is('screenMode', 'narrow')){
    		$layout = new ET("<div class='clearfix21 toolbar'><div class='toolbar-first'>[#ROW0#][#ROW1#]" .
    		"<!--ET_BEGIN ROW2--><div class='modal-toolbar' data-position='auto' id='Row2_{$rowId}'>[#ROW2#]</div><!--ET_END ROW2--></div></div>");
    	}
    	else{
    		$layout = new ET("<div class='clearfix21 toolbar' style='margin-bottom: 8px;'>[#ROW1#][#ROW2#]</div>");
    	}
   
        return FALSE;
    }
}
