<?php
class pos_FavouritesWrapper extends pos_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
		$tabs->TAB('pos_Favourites', 'Продукти');
        $tabs->TAB('pos_FavouritesCategories', 'Категории');
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'PoS Продукти';
    }
}