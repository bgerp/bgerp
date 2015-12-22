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
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $saveFileds = NULL)
	{
		//static $i = 0;
		
		if($rec->state == 'active'){
			if(isset($saveFileds)) return;
			//if($i == 2) bp();
			//core_Statuses::newStatus(str::getRand(), 'warning');
			batch_Movements::saveMovement($mvc, $rec->id);
		} elseif($rec->state == 'rejected'){
			batch_Movements::removeMovement($mvc, $rec->id);
		}
	}
	
	
	/**
	 * Изпълнява се преди контиране на документа
	 */
	public static function on_BeforeConto11(core_Mvc $mvc, &$res, $id)
	{
		expect($MovementImpl = cls::getInterface('batch_MovementSourceIntf', $mvc));
		expect($docRec = $mvc->fetchRec($id));
		
		$entries = $MovementImpl->getMovements($docRec);
		bp($entries);
		
		$mvc1 = cls::get('purchase_PurchasesDetails');
		$query = $mvc1->getQuery();
		$query->where("#{$mvc1->masterKey} = {$rec->{$mvc1->masterKey}}");
		bp($query->fetchAll());
		
		
		return FALSE;
	}
}