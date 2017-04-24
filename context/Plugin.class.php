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

    	$toolbar->prepend(ht::createFnBtn(' ', NULL, NULL, array('class'=>'more-btn arrowDown', 'title'=>tr('Други действия с този документ'), "data-id" => "Row2_{$rowId}")), "ROW0");
        $toolbar->append(ht::createFnBtn(' ', NULL, NULL, array('class'=>'more-btn task', 'title'=>tr('Избор на задача'), 'data-id' => "task_{$rowId}" )), "TASK");
        $goBtn = ht::createFnBtn('>>', NULL, NULL, array('class'=>'button linkWithIcon no-icon small', 'title'=>tr('Добави към избраната задача')));
        $toolbar->append("<div class='modal-toolbar twoColsContext' style = 'padding: 5px' data-position='auto' id='task_{$rowId}'> <select><option></option><option>Среща с фирма 1</option><option>Среща с фирма 2</option><option>Среща с фирма 3</option></select>{$goBtn}</div>", "TASK");

        $toolbar->push('context/'. context_Setup::get('VERSION') . '/contextMenu.css', "CSS");
        $toolbar->push('context/'. context_Setup::get('VERSION') . '/contextMenu.js', "JS");
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
            $link = ht::createFnBtn("Още", "toggleDisplay('hidden_{$rowId}'); var trigger = $(this).closest('.toolbar-first').find('.more-btn'); $(this).remove(); $(trigger).contextMenu('destroy'); prepareContextMenu(); $(trigger).contextMenu('open'); $(trigger).contextMenu('open');", NULL, array('ef_icon'=>'img/16/dots.png', 'class' => "linkWithIcon"));

    		$layout = new ET("<div class='clearfix21 toolbar'><div class='toolbar-first'>[#ROW0#][#TASK#][#ROW1#]" .
    		"<!--ET_BEGIN ROW2--><div class='modal-toolbar twoColsContext' data-position='auto' id='Row2_{$rowId}'><span class='context-column'>[#ROW2#]</span>" .
            "<!--ET_BEGIN HIDDEN--><span class='context-column sideBorder'>[#HIDDEN#]</span><!--ET_END HIDDEN-->" .
    		"</div><!--ET_END ROW2-->" .
    		"</div></div>");
    	}
    	else{
    		$layout = new ET("<div class='clearfix21 toolbar' style='margin-bottom: 8px;'>[#ROW1#][#ROW2#][#HIDDEN#]</div>");
    	}
   
        return FALSE;
    }
}
