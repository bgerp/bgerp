<?php
class hr_DataWrapper extends hr_Wrapper
{
    public function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        $tabs->TAB('hr_ContractTypes', 'Шаблони');
      

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Данни';
    }
}
