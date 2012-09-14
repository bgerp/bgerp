<?php

class cal_View extends core_Plugin
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $from = Request::get('from', 'date');
        
        if(!$from) {
            $from = dt::verbal2mysql();
        }

        $act = strtolower(Request::get('Act'));
         
        if(!$act || $act == 'default') {
            $act = 'list';
        }

        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet', 'maxTabsNarrow' => 1000));
        
 
        $tabs->TAB('list', 'Списък',  array($mvc, 'list',  'from' => $from));
        $tabs->TAB('day',  'Ден',     array($mvc, 'day',   'from' => $from));
        $tabs->TAB('week', 'Седмица', array($mvc, 'week',  'from' => $from));
        $tabs->TAB('month', 'Месец',  array($mvc, 'month', 'from' => $from));
        $tabs->TAB('year', 'Година',  array($mvc, 'year',  'from' => $from));

        $tpl = $tabs->renderHtml($tpl, $act);
        
        $mvc->currentTab = 'Календар';
    }
}