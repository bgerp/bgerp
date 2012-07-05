<?php
class email_router_Wrapper extends email_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphavit'));

        $tabs->TAB('email_Router', 'Автоматично');
        $tabs->TAB('email_Filters', 'Ръчно (филтри)');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        
        $mvc->currentTab = 'Рутиране';
    }
}