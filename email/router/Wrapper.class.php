<?php
class email_router_Wrapper extends email_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        $tabs->TAB('email_Filters', 'Потребителски правила');
        $tabs->TAB('email_Router', 'Автоматично рутиране');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        
        $mvc->currentTab = 'Рутиране';
    }
}