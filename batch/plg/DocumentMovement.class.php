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
	 * Можели да се активира документа за движение
	 * 
	 * @param core_Master $mvc
	 * @param int $id
	 * @return boolean
	 */
	private static function canActivateMovementDoc(core_Master $mvc, $id)
	{
		$rec = $mvc->fetchRec($id);
		$Detail = cls::get($mvc->mainDetail);
		$qQuery = $Detail->getQuery();
		$qQuery->where("#{$Detail->masterKey} = {$rec->id}");
			
		while($dRec = $qQuery->fetch()){
			if(batch_plg_DocumentMovementDetail::getBatchRecInvalidMessage($Detail, $dRec)){
				return FALSE;
			}
		}
			
		return TRUE;
	}
	
	
	/**
	 * Изпълнява се преди контиране на документа
	 */
	public static function on_BeforeConto(core_Mvc $mvc, &$res, $id)
	{
		if(!self::canActivateMovementDoc($mvc, $id)){
			redirect(array($mvc, 'single', $id), FALSE, '|Не може да се контира|*, |докато има несъответствия|*');
		}
	}
}