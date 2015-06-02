<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа planning_DirectProductionNotes
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class planning_transaction_DirectProductionNote extends acc_DocumentTransactionSource
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
				'reason' => "Протокол за бързо производство №{$rec->id}",
				'valior' => $rec->valior,
				'totalAmount' => NULL,
				'entries' => array()
		);
	
		// Ако има ид, добавяме записите
		if(isset($rec->id)){
			$entries = $this->getEntries($rec, $result->totalAmount);
			if(count($entries)){
				$result->entries = $entries;
			}
		}
		
		return $result;
	}
	
	
	/**
	 * Подготовка на записите на артикула
	 */
	private function getEntries($rec, &$total)
	{
		$resourcesArr = $entries = array();
		$hasInput = FALSE;
		
		$dQuery = planning_DirectProductNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		$dQuery->orderBy('id', 'ASC');
		
		while($dRec = $dQuery->fetch()){
			$resourcesArr[$dRec->resourceId] = $dRec;
			$resourcesArr[$dRec->resourceId]->resourceQuantity = $dRec->quantity;
			$rQuantity = $dRec->quantity;
			
			if($dRec->productId && $dRec->type == 'input'){
				$hasInput = TRUE;
				$rQuantity = ($dRec->quantity / $dRec->conversionRate);
				$resourcesArr[$dRec->resourceId]->resourceQuantity = $rQuantity;
				
				$entry = array('debit' => array('61101', array('planning_Resources', $dRec->resourceId), 
												'quantity' => $rQuantity),
							   'credit' => array('321', array('store_Stores', $rec->storeId), 
														array($dRec->classId, $dRec->productId), 
												'quantity' => $dRec->quantity), 
							   );
				
				$entries[] = $entry;
			}
		}
		//bp($resourcesArr, $entries);
		$index = 0;
		if(count($resourcesArr)){
			arr::orderA($resourcesArr, 'type');
			
			foreach ($resourcesArr as $resourceId => $obj){
				$entry = array();
				
				$quantity = ($index == 0) ? $rec->quantity : 0;
				
				if($obj->type == 'input'){
					$entry['debit'] = array('321', array('store_Stores', $rec->storeId),
										 array(cat_Products::getClassId(), $rec->productId),
										'quantity' => $quantity);
					
					$entry['credit'] = array('61101', array('planning_Resources', $resourceId),
											   'quantity' => $obj->resourceQuantity);
				} else {
					$amount = planning_Resources::fetchField($resourceId, "selfValue");
					$entry['debit'] = array('321', array('store_Stores', $rec->storeId),
												   array(cat_Products::getClassId(), $obj->productId),
												  'quantity' => $obj->quantity);
					
					$entry['credit'] =  array('321', array('store_Stores', $rec->storeId),
										 array(cat_Products::getClassId(), $rec->productId),
										'quantity' => $quantity);
					$entry['amount'] = $amount;
					$total += $amount;
				}
				
				$entries[] = $entry;
				$index++;
			}
		}
		
		if(Mode::get('saveTransaction')){
			if($hasInput === FALSE){
				acc_journal_RejectRedirect::expect(FALSE, "Не може да се контира документа, без да има вложени ресурси");
			}
		}
		
		return $entries;
	}
}