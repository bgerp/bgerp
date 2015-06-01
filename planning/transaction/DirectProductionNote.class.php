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
	 * 
	 */
	private function getEntries($rec, &$total)
	{
		$resourcesArr = $entries = array();
		
		$dQuery = planning_DirectProductNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		while($dRec = $dQuery->fetch()){
			$sign = ($dRec->type == 'input') ? 1 : -1;
			$rQuantity = $sign * $dRec->quantity;
			
			if($dRec->productId){
				$conversionRate = planning_ObjectResources::fetchField("#resourceId = {$dRec->resourceId} AND #objectId = {$dRec->productId}", 'conversionRate');
				$rQuantity = $sign * ($dRec->quantity / $conversionRate);
				
				$entry = array('debit' => array('61101', array('planning_Resources', $dRec->resourceId), 
												'quantity' => $sign * $rQuantity),
							   'credit' => array('321', array('store_Stores', $rec->storeId), 
														array($dRec->classId, $dRec->productId), 
												'quantity' => $sign * $dRec->quantity), 
							   );
				
				$entries[] = $entry;
			}
			
			$resourcesArr[$dRec->resourceId] = $sign * $rQuantity;
		}
		
		$index = 0;
		if(count($resourcesArr)){
			
			foreach ($resourcesArr as $resourceId => $resQuantity){
				$quantity = ($index == 0) ? $rec->quantity : 0;
				$entry = array('debit' => array('321', array('store_Stores', $rec->storeId),
												array(cat_Products::getClassId(), $rec->productId),
												'quantity' => $quantity),
							   'credit' => array('61101', array('planning_Resources', $resourceId),
												 'quantity' => $resQuantity));
				
				$entries[] = $entry;
				$index++;
			}
		}
		
		return $entries;
	}
}