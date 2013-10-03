<?php
class trz_LeavesWrapper extends trz_Wrapper
{
function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        // ако имаме форма за добавяне или редактирване
        // скриваме вътрешните бутони
        if(Request::get('Act') == 'add' || Request::get('Act') == 'edit'){
        	 $tabs;
        } else {
        	 $tabs->TAB('trz_Requests', 'Молби');
        	 $tabs->TAB('trz_Orders', 'Заповеди');
        }
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Отпуски >> Молби';
    }
}