<?php

/**
 * Клас 'store_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'store'
 *
 * @category   bgERP 2.0
 * @package    store
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      0.1
 */
class store_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
    	$tabs = cls::get('core_Tabs');
        
        // проверка за избран склад
        $selectedStoreId = store_Stores::getCurrent();

        if ($selectedStoreId) {
	        $tabs->TAB('store_Movements',       'Движения');
	        $tabs->TAB('store_Pallets',         'Палети');
	        $tabs->TAB('store_Racks',           'Стелажи');
	        $tabs->TAB('store_Zones',           'Зони');
	        $tabs->TAB('store_Products',        'Продукти');
	        $tabs->TAB('store_Stores',          'Складове');
	        $tabs->TAB('store_PalletTypes',     'Видове палети');
	        $tabs->TAB('store_Documents',       'Документи');        	
        } else {
        	$tabs->TAB('store_Stores',          'Складове');
        }
        
        // $tpl = $tabs->renderHtml($tpl, $invoker->className);
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab)?$invoker->className:$invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}