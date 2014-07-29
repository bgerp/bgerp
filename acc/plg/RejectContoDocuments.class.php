<?php



/**
 * Плъгин който преди оттегляне/възстановяване на контиращи документи, провеврява имали в тях приключени пера
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_RejectContoDocuments extends core_Plugin
{
	/**
	 * Кои са затворените пера в транзакцията на документа
	 */
	public static function on_AfterGetClosedItemsInTransaction($mvc, &$res, $id)
	{
		// Ако няма пера
		if(!is_array($res)){
			
			// Взима всички от текущата транзакция
			$transaction = $mvc->getValidatedTransaction($id);
			if($transaction){
				$res = $transaction->getClosedItems();
			}
		}
	}
	
	
	/**
	 * Дали документа може да бъде възстановен/оттеглен/контиран, ако в транзакцията му има
	 * поне едно затворено перо връща FALSE
	 */
	public static function on_AfterCanRejectOrRestore($mvc, &$res, $id)
	{
		$closedItems = $mvc->getClosedItemsInTransaction($id);
		 
		if(count($closedItems)){
			$msg = tr('Документа не може да бъде оттеглен/възстановен докато перата:');
			foreach ($closedItems as $itemId){
				$msg .= "'" . acc_Items::getVerbal($itemId, 'title') . "', ";
			}
			$msg = trim($msg, ', ');
			$msg .= " " . tr("са затворени");
	
			core_Statuses::newStatus($msg, 'error');
	
			$res = FALSE;
		} else {
	
			$res = TRUE;
		}
	}
	
	
	/**
	 * Преди оттегляне, ако има затворени пера в транзакцията, не може да се оттегля
	 */
	public static function on_BeforeConto($mvc, &$res, $id)
	{
		// Ако не може да се оттегля, връща FALSE за да се стопира оттеглянето
		return $mvc->canRejectOrRestore($id);
	}
	
	
	/**
	 * Преди оттегляне, ако има затворени пера в транзакцията, не може да се оттегля
	 */
	public static function on_BeforeReject($mvc, &$res, $id)
	{
		$rec = $mvc->fetchRec($id);
		
		if($rec->state != 'draft'){
			
			// Ако не може да се оттегля, връща FALSE за да се стопира оттеглянето
			return $mvc->canRejectOrRestore($id);
		}
	}
	
	
	/**
	 * Преди възстановяване, ако има затворени пера в транзакцията, не може да се възстановява
	 */
	public static function on_BeforeRestore($mvc, &$res, $id)
	{
		$rec = $mvc->fetchRec($id);
		
		// Ако не може да се възстановява, връща FALSE за да се стопира възстановяването
		if($rec->brState != 'draft'){
			
			// Ако не може да се оттегля, връща FALSE за да се стопира оттеглянето
			return $mvc->canRejectOrRestore($id);
		}
	}
}