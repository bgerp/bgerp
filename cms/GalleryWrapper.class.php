<?php
class cms_GalleryWrapper extends cms_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        $tabs->TAB('cms_GalleryImages', 'Картинки');
        $tabs->TAB('cms_GalleryGroups', 'Групи');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        
        $mvc->currentTab = 'Галерия';
    }
}