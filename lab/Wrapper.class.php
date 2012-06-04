<?php



/**
 * Клас 'lab_Wrapper'
 *
 *
 * @category  bgerp
 * @package   lab
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class lab_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'lab'));
        
        $tabs->TAB('lab_Tests', 'Тестове');
        $tabs->TAB('lab_Methods', 'Методи');
        $tabs->TAB('lab_Parameters', 'Параметри');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->prepend(tr($invoker->title) . ' « ', 'PAGE_TITLE');
    }
}