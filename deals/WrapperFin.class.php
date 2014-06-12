<?php


/**
 * Клас 'deals_WrapperFin'
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_WrapperFin extends deals_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
        if(haveRole('ceo,dealsMaster')){
        	$tabs->TAB('deals_Deals', 'Сделки');
        	$tabs->TAB('deals_ClosedDeals', 'Приключвания');
        }
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Финансови';
    }
}