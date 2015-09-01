<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа planning_ReturnNotes
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class planning_transaction_ReturnNote extends acc_DocumentTransactionSource
{
	
	
	/**
	 * @param int $id
	 * @return stdClass
	 * @see acc_TransactionSourceIntf::getTransaction
	 */
	public function getTransaction($id)
	{
		// Извличане на мастър-записа
		expect($rec = $this->class->fetchRec($id));
	
		$result = (object)array(
				'reason' => "Протокол за връщане от производство №{$rec->id}",
				'valior' => $rec->valior,
				'totalAmount' => NULL,
				'entries' => array()
		);
		
		if(isset($rec->id)){
			$entries = $this->getEntries($rec, $result->totalAmount);
			
			if(count($entries)){
				$result->entries = array_merge($result->entries, $entries);
			}
		}
		
		return $result;
	}
	
	
	/**
	 * Връща записите на транзакцията
	 */
	private static function getEntries($rec, &$total)
	{
		$entries = array();
		$errorArr = array();
		
		$dQuery = planning_ReturnNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		while($dRec = $dQuery->fetch()){
			$pInfo = cls::get($dRec->classId)->getProductInfo($dRec->productId);
			$creditArr = NULL;
			
			if($rec->useResourceAccounts == 'yes'){
				
				$creditArr = array('61101', array($dRec->classId, $dRec->productId),
								  'quantity' => $dRec->quantity);
				
				$reason = 'Връщане на материал от производството';
			} 
			
			// Ако не е ресурс, кредитираме общата сметка за разходи '61102. Други разходи (общо)'
			$averageCost = NULL;
			if(empty($creditArr)){
				$creditArr = array('61102');
				$reason = 'Връщане от производство без детайли';
				
				// Сумата с която ще върнем артикула в склада е неговата средно претеглена
				$averageCost = cat_Products::getWeightedAverageValue($dRec->productId, $rec->storeId);
			}
			
			$entry = array('debit' => array(321,
								array('store_Stores', $rec->storeId),
								array($dRec->classId, $dRec->productId),
								'quantity' => $dRec->quantity),
							  'credit' => $creditArr,
						   'reason' => $reason);
			
			if(!is_null($averageCost)){
				$entry['amount'] = $averageCost;
			}
			
			$entries[] = $entry;
		}
		
		// Връщаме ентритата
		return $entries;
	}
}
