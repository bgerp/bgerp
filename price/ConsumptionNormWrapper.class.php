<?php
class price_ConsumptionNormWrapper extends price_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
		$tabs->TAB('price_ConsumptionNorms', 'Разходни норми');
        $tabs->TAB('price_ConsumptionNormGroups', 'Групи');
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Разх. норми';
    }
}