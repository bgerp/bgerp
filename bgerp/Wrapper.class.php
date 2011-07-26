<?php

/**
 * Клас 'bgerp_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'bgerp'
 *
 * @category   Experta Framework
 * @package    bgerp
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id: $
 * @link
 * @since
 */
class bgerp_Wrapper extends core_Plugin
{
    
    
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('bgerp_Menu', 'Меню');
        $tabs->TAB('bgerp_Portal', 'Портал');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » " , 'PAGE_TITLE');
    }
}