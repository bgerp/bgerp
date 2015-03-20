<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа planning_ConsumptionNotes
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class acc_transaction_BalanceRepair extends acc_DocumentTransactionSource
{
	
	
	/**
	 * Запис на баланса
	 */
	private $balanceRec;
	
	
	/**
	 * Дата
	 */
	private $date;
	
	
	/**
	 * Сума
	 */
	private $amount488 = 0;
	
	
	/**
	 * @param int $id
	 * @return stdClass
	 * @see acc_TransactionSourceIntf::getTransaction
	 */
	public function getTransaction($id)
	{
		// Извличане на мастър-записа
		expect($rec = $this->class->fetchRec($id));
	
		$this->balanceRec = acc_Balances::fetch($rec->balanceId);
		$pRec = acc_Periods::fetch($this->balanceRec->periodId);
		$this->date = acc_Periods::forceYearItem($rec->valior);
		
		$result = (object)array(
				'reason' => "Счетоводна разлика №{$rec->id}",
				'valior' => $pRec->end,
				'totalAmount' => NULL,
				'entries' => array()
		);
	
		// Ако има ид
		if($rec->id){
			
			// За всяка сметка в детайла
			$dQuery = acc_BalanceRepairDetails::getQuery();
			$dQuery->where("#repairId = {$rec->id}");
			while($dRec = $dQuery->fetch()){
				
				// Взимаме и записите
				$entries = $this->getEntries($dRec, $result->totalAmount);
				if(count($entries)){
					
					// Обединяваме тези записи с общите
					$result->entries = array_merge($result->entries, $entries);
				}
			}
		}
		
		return $result;
	}
	
	
	/**
	 * Връща ентритата 
	 */
	private function getEntries($dRec, &$total)
	{
		$entries = array();
		$sysId = acc_Accounts::fetchField($dRec->accountId, 'systemId');
		$bQuery = acc_BalanceDetails::getQuery();
		acc_BalanceDetails::filterQuery($bQuery, $this->balanceRec->id, $sysId);
		$bQuery->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
		
		$Items = cls::get('acc_Items');
		$itemsArr = $Items->getCachedItems();
		
		// За всеки запис
		while($bRec = $bQuery->fetch()){
			$continue = TRUE;
				
			$blAmount = $blQuantity = NULL;
			
			// Ако крайното салдо и к-во са в допустимите граници
			foreach (array('Quantity', 'Amount') as $fld){
				if(!empty($dRec->{"bl{$fld}"})){
					$var = &${"bl{$fld}"};
					$var = round($bRec->{"debit{$fld}"} - $bRec->{"credit{$fld}"}, 8);
						
					if($var != 0 && $var >= -1 * $dRec->{"bl{$fld}"} && $var <= $dRec->{"bl{$fld}"}){
						$continue = FALSE;
					}
				}
			}
			
			// Ако не са продължаваме
			if($continue) continue;
			
			// Ако има поне едно перо
			if(!empty($bRec->ent1Id) || !empty($bRec->ent2Id) || !empty($bRec->ent3Id)){
				
				// Проверяваме всички пера
				$continue = TRUE;
				foreach (array('ent1Id', 'ent2Id', 'ent3Id') as $ent){
					if(!empty($bRec->$ent)){
						
						// Ако има поне едно затворено
						if($itemsArr['items'][$bRec->$ent]->state == 'closed'){
							$continue = FALSE;
							break;
						}
					}
				}
				
				// Ако всички пера са отворени продължаваме без да правим нищо
				if($continue) continue;
			}
			
			$ourSideArr = array($sysId, $bRec->ent1Id, $bRec->ent2Id, $bRec->ent3Id);
			
			$entry = array('amount' => abs($blAmount));
			$total += abs($blAmount);
			
			if(!is_null($blQuantity)){
				$ourSideArr['quantity'] = abs($blQuantity);
			}
				
			// Ако салдото е отрицателно отива като приход
			if($blAmount < 0){
				$entry['debit'] = $ourSideArr;
				$entry['credit'] = array('488');
				
				$this->amount488 -= $entry['amount'];
			} else {
				
				// Ако салдото е положително отива като разход
				$entry['debit'] = array('488');
				$entry['credit'] = $ourSideArr;
				
				$this->amount488 += $entry['amount'];
			}
			
			$entry['reason'] = 'Разлики от закръгляния';
			$entries[] = $entry;
		}
		
		// Връщаме ентритата
		return $entries;
	}
}
