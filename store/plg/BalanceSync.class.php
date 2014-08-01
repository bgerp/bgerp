<?php



/**
 * Клас 'store_plg_BalanceSync'
 * Плъгин който след изчисляването на горещия баланс го синхронизира с store_Products и pos_Stocks
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see 	  acc_Balances
 */
class store_plg_BalanceSync extends core_Plugin
{
	
	
	/**
	 * След изчисляване на баланса синхронизира складовите наличности
	 */
	public static function on_AfterRecalcBalances(acc_Balances $mvc, &$data)
	{
		// Извличане на данните за склада от баланса
		$all = self::prepareStoreData();
		
		// Ако има данни за синхронизиране
		if($all){
			
			// Синхронизиране на складовите продукти с тези от баланса (@see store_Products)
			store_Products::sync($all);
			
			// Синхронизиране на pos наличностите с тези от баланса (@see pos_Stocks)
			pos_Stocks::sync($all);
		}
	}
	
	
	/**
	 * Извлича информацията нужна за ъпдейт на склада
	 */
	private static function prepareStoreData()
	{
		$all = array();
		$balanceRec = acc_Balances::getLastBalance();
		 
		// Ако няма баланс няма какво да подготвяме
		if(empty($balanceRec)) return FALSE;
		
		// Извличане на сметките по които ще се ситематизират данните
		$conf = core_Packs::getConfig('store');
		$storeAccs = keylist::toArray($conf->STORE_ACC_ACCOUNTS);
		 
		// Филтриране да се показват само записите от зададените сметки
		$dQuery = acc_BalanceDetails::getQuery();
		foreach ($storeAccs as $sysId){
			$dQuery->orWhere("#accountId = {$sysId}");
		}
		 
		$dQuery->where("#balanceId = {$balanceRec->id}");
		 
		while($rec = $dQuery->fetch()){
			if($rec->ent1Id){
				 
				// Перо 'Склад'
				$storeItem = acc_Items::fetch($rec->ent1Id);
		   
				// Перо 'Артикул'
				$pItem = acc_Items::fetch($rec->ent2Id);
		   
				// Съмаризиране на информацията за артикул / склад
				$index = $storeItem->objectId . "|" . $pItem->classId . "|" . $pItem->objectId;
				if(empty($all[$index])){
	
					// Ако няма такъв продукт в масива, се записва
					$all[$index] = $rec->blQuantity;
				} else {
	
					// Ако го има добавяме количеството на записа
					$all[$index] += $rec->blQuantity;
				}
			}
		}
		 
		// Връщане на групираните крайни суми
		return $all;
	}
}
