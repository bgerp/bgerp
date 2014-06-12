<?php


/**
 * Клас 'deals_WrapperDocuments'
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_WrapperDocuments extends deals_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		
		$tabs->TAB('deals_DebitDocuments', 'Вземания');
        $tabs->TAB('deals_CreditDocuments', 'Задължения');
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Прехвърления';
    }
}