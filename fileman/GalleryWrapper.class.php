<?php


/**
 * 
 * 
 * 
 * @author developer
 *
 */
class fileman_GalleryWrapper extends cms_Wrapper
{
    
    
    /**
     * 
     * @see plg_ProtoWrapper::on_AfterRenderWrapping()
     */
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        $tabs->TAB('fileman_GalleryImages', 'Картинки');
        $tabs->TAB('fileman_GalleryGroups', 'Групи');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        
        $mvc->currentTab = 'Галерия';
    }
}