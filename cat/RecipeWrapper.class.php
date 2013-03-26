<?php
class cat_RecipeWrapper extends cat_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
		$tabs->TAB('cat_Recipes', 'Рецептурник');
        $tabs->TAB('cat_RecipeGroups', 'Групи');
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Рецепти';
    }
}