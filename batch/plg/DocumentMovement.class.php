<?php



/**
 * Клас 'batch_plg_DocumentActions' - За генериране на партидни движения от документите
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo да се разработи
 */
class batch_plg_DocumentMovement extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->storeFieldName, 'storeId');
		setIfNot($mvc->batchMovementDocument, 'out');
	}
	
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFileds = NULL)
	{
		if($rec->state == 'active'){
			if($mvc->hasPlugin('acc_plg_Contable')){
				if(isset($saveFileds)) return;
			}
			
			$containerId = (isset($rec->containerId)) ? $rec->containerId : $mvc->fetchField($rec->id, 'containerId');
			batch_Movements::saveMovement($containerId);
		} elseif($rec->state == 'rejected'){
			$containerId = (isset($rec->containerId)) ? $rec->containerId : $mvc->fetchField($rec->id, 'containerId');
			batch_Movements::removeMovement($containerId);
		}
	}
}