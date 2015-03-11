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
		$errorArr = array();
		
		$dQuery = mp_ConsumptionNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		while($dRec = $dQuery->fetch()){
			$resourceRec = mp_ObjectResources::getResource($dRec->classId, $dRec->productId);
			
			if($resourceRec){

				$pInfo = cls::get($dRec->classId)->getProductInfo($dRec->productId);
					
				$creditAccId = '321';
					
				// Ако е указано да влагаме само в център на дейност и ресурси, иначе влагаме в център на дейност
				if($rec->useResourceAccounts == 'no'){
					$debitArr = array('6112', array('hr_Departments', $rec->activityCenterId),);
				} else {
					$debitArr = array('611', array('hr_Departments', $rec->activityCenterId),
							array('mp_Resources', $resourceRec->resourceId),
							'quantity' => $dRec->quantity);
				}
					
				$entries[] = array('debit' => $debitArr,
						'credit' => array($creditAccId,
								array('store_Stores', $rec->storeId),
								array($dRec->classId, $dRec->productId),
								'quantity' => $dRec->quantity));
			} else {
				$errorArr[] = cls::get($dRec->classId)->getVerbal($dRec->productId, 'name');
			}
		}
		
		// Ако някой от артикулите не може да бдъе произведем сетваме че ще правимр едирект със съобщението
		if(Mode::get('saveTransaction')){
			if(count($errorArr)){
				$errorArr = implode(', ', $errorArr);
				acc_journal_RejectRedirect::expect($entry, "Артикулите: |{$errorArr}|* не могат да бъдат вложени, защото не са асоциирани с ресурс");
			}
		}
		
		// Връщаме ентритата
		return $entries;
	}
}
