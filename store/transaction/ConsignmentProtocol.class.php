<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_ConsignmentProtocols
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class store_transaction_ConsignmentProtocol extends acc_DocumentTransactionSource
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
		$rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
		
		$result = (object)array(
				'reason' => "Протокол за отговорно пазене №{$rec->id}",
				'valior' => $rec->valior,
				'totalAmount' => NULL,
				'entries' => array()
		);
		
		if($rec->id){
			$result->entries = $this->getEntries($rec);
		}
		
		return $result;
	}
	
	
	/**
	 * Подготвя записите
	 * 
	 * За предадените артикули:
	 * 		
	 * 		Dt: 323. СМЗ на отговорно пазене				    (Контрагенти, Артикули)
	 *      Ct: 321. Суровини, материали, продукция, стоки	    (Складове, Артикули)
	 * 
	 * За върнатите артикули:
	 * 		
	 * 		Dt: 321. Суровини, материали, продукция, стоки		(Складове, Артикули)
	 *      Ct: 323. СМЗ на отговорно пазене					(Контрагенти, Артикули)
	 */
	private function getEntries($rec)
	{
		$entries = array();
		
		// Намираме всички предадени артикули
		$sendQuery = store_ConsignmentProtocolDetailsSend::getQuery();
		$sendQuery->where("#protocolId = {$rec->id}");
		while($sendRec = $sendQuery->fetch()){
			$quantity = $sendRec->quantityInPack * $sendRec->packQuantity;
			$entries[] = array(
					'debit' => array('323', 
										array($rec->contragentClassId, $rec->contragentId), 
										array('cat_Products', $sendRec->productId), 
									'quantity' => $quantity),
					'credit' => array('321', 
										array('store_Stores', $rec->storeId),
										array('cat_Products', $sendRec->productId),
									'quantity' => $quantity),
									);
		}
		
		// Намираме всички върнати артикули
		$receivedQuery = store_ConsignmentProtocolDetailsReceived::getQuery();
		$receivedQuery->where("#protocolId = {$rec->id}");
		while($recRec = $receivedQuery->fetch()){
			$quantity = $recRec->quantityInPack * $recRec->packQuantity;
			$entries[] = array(
					'debit' => array('321',
									array('store_Stores', $rec->storeId),
									array('cat_Products', $recRec->productId),
								'quantity' => $quantity),
					'credit' => array('323', 
									array($rec->contragentClassId, $rec->contragentId), 
									array('cat_Products', $recRec->productId), 
								'quantity' => $quantity),
					
			);
		}
		
		// Връщаме записите
		return $entries;
	}
}