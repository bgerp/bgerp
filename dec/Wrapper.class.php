<?php



/**
 * Клас 'dec_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'dec'
 *
 *
 * @category  bgerp
 * @package   dec
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class dec_Wrapper extends sales_Wrapper
{
    
	function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        if(Request::get('Act') == 'add' || Request::get('Act') == 'edit'){
        	 $tabs;
        } else {
	        $tabs->TAB('dec_Declarations', 'Списък');
	        $tabs->TAB('dec_Statements', 'Твърдения');
	        $tabs->TAB('dec_DeclarationTypes', 'Бланки');
        }

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Декларации';
        
        Mode::set('pageMenu', 'Търговия');
		Mode::set('pageSubMenu', 'Продажби'); 

    }
}