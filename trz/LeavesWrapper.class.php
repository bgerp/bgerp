<?php
class trz_LeavesWrapper extends trz_Wrapper
{
function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        $tabs->TAB('trz_Requests', 'Молби');
        $tabs->TAB('trz_Orders', 'Заповеди');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Отпуски >> Молби';
    }
}