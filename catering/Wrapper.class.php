<?php



/**
 * Клас 'catering_Wrapper'
 *
 *
 * @category  bgerp
 * @package   catering
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class catering_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'catering'));
        
        $tabs->TAB('catering_Menu', 'Меню');
        $tabs->TAB('catering_Companies', 'Фирми');
        $tabs->TAB('catering_EmployeesList', 'Столуващи');
        $tabs->TAB('catering_Requests', 'Заявки');
        $tabs->TAB('catering_Orders', 'Поръчки');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->prepend(tr($invoker->title) . " « ", 'PAGE_TITLE');
    }
}