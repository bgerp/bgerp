<?php

/**
 * Клас 'catering_Wrapper'
 */
class catering_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'catering') );
        
        $tabs->TAB('catering_Menu', 'Меню');
        $tabs->TAB('catering_Companies', 'Фирми');
        $tabs->TAB('catering_EmployeesList', 'Столуващи');
        $tabs->TAB('catering_Requests', 'Заявки');
        $tabs->TAB('catering_Orders', 'Поръчки');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->tabName ? $invoker->tabName : $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}