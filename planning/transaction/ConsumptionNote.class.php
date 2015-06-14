<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа planning_ConsumptionNotes
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
class planning_transaction_ConsumptionNote extends acc_DocumentTransactionSource
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
		
		$dQuery = planning_ConsumptionNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		while($dRec = $dQuery->fetch()){
			$pInfo = cls::get($dRec->classId)->getProductInfo($dRec->productId);
			$debitArr = NULL;
			
			if($rec->useResourceAccounts == 'yes'){
				$resourceRec = planning_ObjectResources::getResource($dRec->classId, $dRec->productId);
				if($resourceRec){
					// Ако е указано да влагаме само в център на дейност и ресурси, иначе влагаме в център на дейност
					$debitArr = array('61101', array('planning_Resources', $resourceRec->resourceId),
							'quantity' => $dRec->quantity / $resourceRec->conversionRate);
				}
				$reason = 'Влагане на ресурс в производството';
			} 
			
			// Ако не е ресурс, дебитираме общата сметка за разходи '61102. Други разходи (общо)'
			if(empty($debitArr)){
				$debitArr = array('61102');
				$reason = 'Бездетайлно влагане на артикул в производството';
			}
			
			$entries[] = array('debit' => $debitArr,
							   'credit' => array(321,
									array('store_Stores', $rec->storeId),
									array($dRec->classId, $dRec->productId),
									'quantity' => $dRec->quantity),
							   'reason' => $reason);
		}
		
		// Връщаме ентритата
		return $entries;
	}
}
