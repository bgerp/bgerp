<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа mp_ConsumptionNotes
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class mp_transaction_ConsumptionNote extends acc_DocumentTransactionSource
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
				'reason' => "Протокол за влагане №{$rec->id}",
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
		
		$dQuery = mp_ConsumptionNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		while($dRec = $dQuery->fetch()){
			$resourceRec = mp_ObjectResources::getResource($dRec->classId, $dRec->productId);
			
			if(!$resourceRec) return $entries;
			
			$pInfo = cls::get($dRec->classId)->getProductInfo($dRec->productId);
			 
			// Ако е материал кредит 302, другите 321
			$creditAccId = (isset($pInfo->meta['materials'])) ? '302' : '321';
			
			$entries[] = array('debit' => array('611', 
											array('hr_Departments', $rec->activityCenterId), 
											array('mp_Resources', $resourceRec->resourceId),
											'quantity' => $dRec->quantity),
							   'credit' => array($creditAccId,
												array('store_Stores', $rec->storeId),
							   					array($dRec->classId, $dRec->productId),
							   				'quantity' => $dRec->quantity));
		}
		
		// Връщаме ентритата
		return $entries;
	}
}