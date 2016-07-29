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
				'reason'      => "Протокол за производство №{$rec->id}",
				'valior'      => $rec->valior,
				'totalAmount' => NULL,
				'entries'     => array()
		);
	
		// Ако има ид, добавяме записите
		//if(isset($rec->id)){
			$entries = $this->getEntries($rec, $result->totalAmount);
			if(count($entries)){
				$result->entries = $entries;
			}
		//}
		
		return $result;
	}
	
	
	/**
	 * Подготовка на записите
	 * 
	 * 1. Етап: влагаме материалите, които ще изпишем при производството
	 * 
	 * Всички складируеми материали в секцията за влагане ги влагаме в производството.
	 * Нескладируемите се предполага, че вече са вложени там при покупката им
	 * 
	 * Dt: 61101 - Незавършено производство                (Артикули)
     * 
     * Ct: 321   - Суровини, материали, продукция, стоки   (Складове, Артикули)
     *   или ако артикула е услуга Ct: 703 - Приходи от продажби на услуги  (Контрагенти, Сделки, Артикули)
	 * 
	 * 2. Етап: вкарваме в склада произведения продукт
	 * 
	 * Изписваме вложените материали и вкарваме в склада продукта. Той влиза с цялото си количество
	 * при изписването на първия материал/услуга, а останалите натрупват себестойността си към неговата
	 * Отпадъка само намаля себестойността на проудкта съя своята себестойност
	 * 
	 * Вкарване на материал
	 * 
     * Dt: 321   - Суровини, материали, продукция, стоки   (Складове, Артикули)
     * или ако артикула е услуга Dt: 703 - Приходи от продажби на услуги  (Контрагенти, Сделки, Артикули)
     * 
     * Ct: 61101 - Незавършено производство                (Артикули)
	 * 
	 * Вкарване на отпадък
	 * 
	 * Dt: 61101 - Незавършено производство                (Артикули)
	 * 
     * Ct: 321   - Суровини, материали, продукция, стоки   (Складове, Артикули)
     * или ако артикула е услуга Ct: 703 - Приходи от продажби на услуги  (Контрагенти, Сделки, Артикули)
     * 
     * 3. Етап: Ако има режийни разходи за разпределение
     * 
     * Dt: 321   - Суровини, материали, продукция, стоки   (Складове, Артикули)
     * или ако артикула е услуга Dt: 703 - Приходи от продажби на услуги  (Контрагенти, Сделки, Артикули)
     * 
     * Ct: 61102 - Други разходи (общо)                
	 */
	private function getEntries($rec, &$total)
	{
		$resourcesArr = $entries = array();
		$pInfo = cat_Products::getProductInfo($rec->productId);
		$canStore = isset($pInfo->meta['canStore']) ? TRUE : FALSE;
		
		if($canStore === TRUE){
			$array = array('321', array('store_Stores', $rec->storeId),
								  array('cat_Products', $rec->productId));
		} else {
			$array = array('60201', $rec->expenseItemId, array('cat_Products', $rec->productId));
		}
		
		$dRecs = array();
		if(isset($rec->id)){
			$dQuery = planning_DirectProductNoteDetails::getQuery();
			$dQuery->where("#noteId = {$rec->id}");
			$dQuery->orderBy('id,type', 'ASC');
			$dRecs = $dQuery->fetchAll();
		}
		
		if(is_array($dRecs)){
			
			if(!count($dRecs)){
				$rec->debitAmount = ($rec->debitAmount) ? $rec->debitAmount : 0;
				
				$amount = $rec->debitAmount;
				$costAmount = $rec->debitAmount;
				$array['quantity'] = $rec->quantity;
				
				$entry = array('amount' => $amount,
							   'debit'  => $array,
							   'credit' => array('61102'), 'reason' => 'Бездетайлно произвеждане');
					
				$entries[] = $entry;
			} else {
				foreach ($dRecs as $dRec){
				
					// Влагаме артикула, само ако е складируем, ако не е
					// се предполага ,че вече е вложен в незавършеното производство
					if($dRec->type == 'input'){
						$productInfo = cat_Products::getProductInfo($dRec->productId);
						if(isset($productInfo->meta['canStore'])){
							if(empty($dRec->storeId)) continue;
							
							$entry = array('debit' => array('61101', 
															array('cat_Products', $dRec->productId),
															'quantity' => $dRec->quantity),
										   'credit' => array('321', 
													         array('store_Stores', $dRec->storeId),
													         array('cat_Products', $dRec->productId),
											                'quantity' => $dRec->quantity),
									       'reason' => 'Влагане на материал в производството');
						} else {
							$item = acc_Items::forceSystemItem('Неразпределени разходи', 'unallocated', 'costObjects')->id;
							$entry = array('debit' => array('61101',
														array('cat_Products', $dRec->productId),
														'quantity' => $dRec->quantity),
										   'credit' => array('60201',
															$item,
															array('cat_Products', $dRec->productId),
															'quantity' => $dRec->quantity),
										   'reason' => 'Влагане на нескладируема услуга или консуматив в производството');
						}
							
						$entries[] = $entry;
					}
				}

				arr::orderA($dRecs, 'type');
				
				$costAmount = $index = 0;
				foreach ($dRecs as $dRec1){
				
					$sign = ($dRec1->type == 'input') ? 1 : -1;
					
					if($dRec1->type == 'input'){
						$productInfo = cat_Products::getProductInfo($dRec1->productId);
						
						// Ако артикула е складируем търсим средната му цена във всички складове, иначе търсим в незавършеното производство
						if(isset($productInfo->meta['canStore'])){
							$primeCost = cat_Products::getWacAmountInStore($dRec1->quantity, $dRec1->productId, $rec->valior);
						} else {
							$primeCost = planning_ObjectResources::getWacAmountInProduction($dRec1->quantity, $dRec1->productId, $rec->valior);
						}
						
						$sign = 1;
					} else {
						$primeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $dRec1->productId, NULL, $rec->valior);
						$sign = -1;
					}
					
					if(!$primeCost){
						$primeCost = 0;
					}
					
					$pAmount = $sign * $primeCost;
					$costAmount += $pAmount;
				
					$quantity = ($index == 0) ? $rec->quantity : 0;
				
					// Ако е материал го изписваме към произведения продукт
					if($dRec1->type == 'input'){
						$reason = ($index == 0) ? 'Засклаждане на произведен артикул' : ((!isset($productInfo->meta['canStore']) ? 'Вложен нескладируем артикул в производството на продукт' : 'Вложен материал в производството на артикул'));
				
						$array['quantity'] = $quantity;
						$entry['debit'] = $array;
				
						$entry['credit'] = array('61101', array('cat_Products', $dRec1->productId),
								'quantity' => $dRec1->quantity);
						$entry['reason'] = $reason;
					} else {
						$entry['debit'] = array('61101', array('cat_Products', $dRec1->productId),
								'quantity' => $dRec1->quantity);
						
						$array['quantity'] = $quantity;
						$entry['credit'] = $array;
						
						$entry['amount'] = $primeCost;
						$entry['reason'] = 'Приспадане себестойността на отпадък от произведен артикул';
						//$total -= $amount;
					}
				
					$entries[] = $entry;
					$index++;
				}
			}
			
			// Ако има режийни разходи, разпределяме ги
			if(isset($rec->expenses)){
				$costAmount = $costAmount * $rec->expenses;
				$costAmount = round($costAmount, 2);
				
				// Ако себестойността е неположителна, режийните са винаги 0
				if($costAmount <= 0){
					$costAmount = 0;
				}
				
				$array['quantity'] = 0;
				
				$costArray = array(
						'amount' => $costAmount,
						'debit'  => $array,
						'credit' => array('61102'),
						'reason' => 'Разпределени режийни разходи');
					
				$entries[] = $costArray;
			}
		}
		
		return $entries;
	}
}