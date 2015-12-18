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
	 * Извиква се преди изпълняването на екшън
	 *
	 * @param core_Mvc $mvc
	 * @param mixed $res
	 * @param string $action
	 */
	public static function on_BeforeAction($mvc, &$res, $action)
	{
		if($action == 'test'){
			//batch_Movements::saveMovement($mvc, 2149);
			//batch_Movements::removeMovement($mvc, 2149);
			return FALSE;
			
			//bp('LOVE');
		}
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
	{
		if($rec->state == 'active'){
			batch_Movements::saveMovement($mvc, $rec->id);
		} elseif($rec->state == 'rejected'){
			batch_Movements::removeMovement($mvc, $rec->id);
		}
	}
}