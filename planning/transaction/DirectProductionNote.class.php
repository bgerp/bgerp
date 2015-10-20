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
				'reason'      => "Протокол за бързо производство №{$rec->id}",
				'valior'      => $rec->valior,
				'totalAmount' => NULL,
				'entries'     => array()
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
			$index = "{$dRec->productId}|{$dRec->type}";
			$resourcesArr[$index] = $dRec;
			$resourcesArr[$index]->resourceQuantity = $dRec->quantity;
			$resourcesArr[$index]->productInfo = cat_Products::getProductInfo($dRec->productId);
			$rQuantity = $dRec->quantity;
			
			if($dRec->productId && $dRec->type == 'input'){
				if(!isset($resourcesArr[$index]->productInfo->meta['canStore'])) continue;
				
				$hasInput = TRUE;
				$resourcesArr[$index]->resourceQuantity = $dRec->quantity;
				
				$entry = array('debit' => array('61101', array('cat_Products', $dRec->productId), 
												'quantity' => $dRec->quantity),
							   'credit' => array('321', array('store_Stores', $rec->inputStoreId), 
														array('cat_Products', $dRec->productId), 
												'quantity' => $dRec->quantity),
								'reason' => 'Влагане на материал в производството');
				
				$entries[] = $entry;
			}
		}
		
		$index = 0;
		$costAmount = 0;
		$expenses = 0;
		
		if(count($resourcesArr)){
			arr::orderA($resourcesArr, 'type');
			
			foreach ($resourcesArr as $resourceId => $obj){
				$entry = array();
				
				$selfValue = planning_ObjectResources::getSelfValue($obj->productId);
				$sign = ($obj->type == 'input') ? 1 : -1;
				$pAmount = $sign * $obj->resourceQuantity * $selfValue;
				
				$costAmount += $pAmount;
				$expenses += $pAmount * $obj->expensePercent;
				
				$quantity = ($index == 0) ? $rec->quantity : 0;
				
				if($obj->type == 'input'){
					
					$reason = ($index == 0) ? 'Засклаждане на произведен продукт' : ((!isset($obj->productInfo->meta['canStore']) ? 'Вложен нескладируем артикул в производството на продукт' : 'Вложен материал в производството на артикул'));
				
					$entry['debit'] = array('321', array('store_Stores', $rec->storeId),
										 array('cat_Products', $rec->productId),
										'quantity' => $quantity);
					
					$entry['credit'] = array('61101', array('cat_Products', $obj->productId),
											            'quantity' => $obj->resourceQuantity);
					$entry['reason'] = $reason;
				} else {
					$amount = $selfValue;
					$entry['debit'] = array('61101', array('cat_Products', $obj->productId),
												  'quantity' => $obj->resourceQuantity);
					
					$entry['credit'] =  array('321', array('store_Stores', $rec->storeId),
										 array('cat_Products', $rec->productId),
										'quantity' => $quantity);
					$entry['amount'] = $amount;
					$entry['reason'] = 'Приспадане себестойността на отпадък от произведен продукт';
					$total += $amount;
				}
				
				$entries[] = $entry;
				$index++;
			}
		}
		
		// Ако има режийни разходи, разпределяме ги
		if($expenses){
			$costAmount = $expenses;
			$costAmount = round($costAmount, 2);

			if($costAmount){
				$costArray = array(
						'amount' => $costAmount,
						'debit' => array('321', array('store_Stores', $rec->storeId),
											    array('cat_Products', $rec->productId),
										'quantity' => 0),
						'credit' => array('61102'),
						'reason' => 'Разпределени режийни разходи',
				);
					
				$total += $costAmount;
				$entries[] = $costArray;
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