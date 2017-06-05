<?php



/**
 * Клас 'store_plg_BalanceSync'
 * Плъгин даващ възможноста на документи да генерират документ за резервиране на складови наличности
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_plg_ReserveStockSource extends core_Plugin
{
	
	
	/**
	 * След подготовка на тулбара на единичен изглед
	 */
	public static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		$rec = &$data->rec;
		
		if(store_ReserveStocks::haveRightFor('add', (object)array('originId' => $rec->containerId))){
			$data->toolbar->addBtn("Резервиране", array('store_ReserveStocks', 'add', 'originId' => $rec->containerId, 'ret_url' => TRUE), 'row=1,ef_icon=img/16/view.png,title=Резервиране на складови наличности');
		} elseif($sRid = store_ReserveStocks::fetchField("#originId = {$rec->containerId} AND #state = 'active'", 'id')){
			$arrow = html_entity_decode('&#9660;', ENT_COMPAT | ENT_HTML401, 'UTF-8');
			$data->toolbar->addBtn("Резервиране|* {$arrow}", array('store_ReserveStocks', 'single', $sRid, 'ret_url' => TRUE), 'title=Отваряне на документа за резервиране на складови наличности,ef_icon=img/16/view.png');
		}
	}
}