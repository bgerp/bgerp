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
		$dQuery->orderBy('id,type', 'ASC');
		$dRecs = $dQuery->fetchAll();
		
		if(is_array($dRecs)){
			foreach ($dRecs as $dRec){
				
				// Влагаме артикула, само ако е складируем, ако не е  
				// се предполага ,че вече е вложен в незавършеното производство
				if($dRec->type == 'input'){
					$productInfo = cat_Products::getProductInfo($dRec->productId);
					if(!isset($productInfo->meta['canStore'])) continue;
					$hasInput = TRUE;
					
					$entry = array('debit' => array('61101', array('cat_Products', $dRec->productId),
													'quantity' => $dRec->quantity),
								   'credit' => array('321', array('store_Stores', $rec->inputStoreId),
															array('cat_Products', $dRec->productId),
															'quantity' => $dRec->quantity),
								   'reason' => 'Влагане на материал в производството');
					
					$entries[] = $entry;
				}
			}
			
			$costAmount = $index = 0;
			foreach ($dRecs as $dRec1){
				$sign = ($dRec1->type == 'input') ? 1 : -1;
				$productInfo = cat_Products::getProductInfo($dRec1->productId);
				
				// Ако артикула е складируем търсим средната му цена във всички складове, иначе търсим в незавършеното производство
				if(isset($productInfo->meta['canStore'])){
					$primeCost = cat_Products::getWacAmountInStore($dRec1->quantity, $dRec1->productId, $rec->valior);
				} else {
					$primeCost = planning_ObjectResources::getWacAmountInProduction($dRec1->quantity, $dRec1->productId, $rec->valior);
				}
				
				$pAmount = $sign * $dRec1->quantity * $primeCost;
				$costAmount += $pAmount;
				
				$quantity = ($index == 0) ? $rec->quantity : 0;
				
				// Ако е материал го изписваме към произведения продукт
				if($dRec1->type == 'input'){
					$reason = ($index == 0) ? 'Засклаждане на произведен продукт' : ((!isset($productInfo->meta['canStore']) ? 'Вложен нескладируем артикул в производството на продукт' : 'Вложен материал в производството на продукт'));
				
					$entry['debit'] = array('321', array('store_Stores', $rec->storeId),
							array('cat_Products', $rec->productId),
							'quantity' => $quantity);
						
					$entry['credit'] = array('61101', array('cat_Products', $dRec1->productId),
							'quantity' => $dRec1->quantity);
					$entry['reason'] = $reason;
				} else {
					$amount = $selfValue;
					$entry['debit'] = array('61101', array('cat_Products', $dRec1->productId),
							'quantity' => $dRec1->quantity);
						
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
			
			// Ако има режийни разходи, разпределяме ги
			if(isset($rec->expenses)){
				$costAmount = $costAmount * $rec->expenses;
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
		}
		
		if(Mode::get('saveTransaction')){
			if($hasInput === FALSE){
				acc_journal_RejectRedirect::expect(FALSE, "Не може да се контира документа, без да има вложени ресурси");
			}
		}
		
		return $entries;
	}
}