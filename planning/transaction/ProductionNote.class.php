<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа planning_ProductionNotes
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
class planning_transaction_ProductionNote extends acc_DocumentTransactionSource
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
				'reason'      => "Протокол от производство №{$rec->id}",
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
	 * Връща записите на транзакцията
	 * 
	 * Ако артикула има активно задание за производство и активна технологична карта.
	 * 		
	 * 		За всеки ресурс от картата:
	 * 
	 * 		Dt: 321. Суровини, материали, продукция, стоки     (Складове, Артикули)
	 * 		Ct: 61101. Разходи за Ресурси		(Ресурси)
	 * 
	 * В противен случай
	 * 
	 * 		Dt: 321. Суровини, материали, продукция, стоки   (Складове, Артикули)
	 * 		Ct: 61102. Други разходи (общо)
	 * 
	 */
	private function getEntries($rec, &$total)
	{
		$entries = array();
		
		$dQuery = planning_ProductionNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		$dQuery->orderBy("id", 'ASC');
		
		$errorArr = array();
		
		while($dRec = $dQuery->fetch()){
			unset($entry);
			
			if(isset($dRec->bomId)){
					
			if(empty($dRec->jobId)) return FALSE;
					$quantityJob = planning_Jobs::fetchField($dRec->jobId, 'quantity');
					$resourceInfo = cat_Boms::getResourceInfo($dRec->bomId);
					
					// Единични суми от рецептата
					$priceObj = cat_Boms::getPrice($dRec->productId, $dRec->bomId);
					
					// Проверяваме цената за к-то от заданието
					$bomAmount = ($priceObj->base + $quantityJob * $priceObj->prop) / $quantityJob;
					$bomAmount *= $dRec->quantity;
					
					$mapArr = $resourceInfo['resources'];
					if(count($mapArr)){
						foreach ($mapArr as $index => $res){
							if($res->type == 'input'){
								
								/*
								 * За всеки ресурс началното количество се разделя на количеството от заданието и се събира
								 * с пропорционалното количество. След това се умножава по количеството посочено в протокола за
								 * от производството и това количество се изписва от ресурсите.
								 */
								$resQuantity = $dRec->quantity * ($res->baseQuantity / $quantityJob + ($res->propQuantity / $resourceInfo['quantity']));
								$resQuantity = core_Math::roundNumber($resQuantity);
								
								$res->finalQuantity = $resQuantity;
							}
						}
						
						arr::order($mapArr, 'finalQuantity', 'DESC');
						
						foreach ($mapArr as $index => $res){
							$pQuantity = ($index == 0) ? $dRec->quantity : 0;
							
							if($res->type == 'input'){
								$reason = ($index == 0) ? 'Засклаждане на произведен артикул' : 'Вложени материали в производството на артикул';
								$entry = array(
										'debit' => array('321', array('store_Stores', $rec->storeId),
															  array($dRec->classId, $dRec->productId),
												'quantity' => $pQuantity),
										'credit' => array('61101', array('cat_Products', $res->productId),
												'quantity' => $res->finalQuantity),
										'reason' => $reason,
								);
							} else {
								$selfValue = planning_ObjectResources::getSelfValue($res->productId);
								
								// Сумата на дебита е себестойността на отпадния ресурс
								$amount = $resQuantity * $selfValue;
								$resQuantity = $dRec->quantity * ($res->baseQuantity / $quantityJob + ($res->propQuantity / $resourceInfo['quantity']));
								$resQuantity = core_Math::roundNumber($resQuantity);
								
								$entry = array(
										'amount' => $amount,
										'debit' => array('61101', array('cat_Products', $res->productId),
														'quantity' => $resQuantity),
										'credit' => array('321', array('store_Stores', $rec->storeId),
																 array($dRec->classId, $dRec->productId),
															'quantity' => $pQuantity),
										'reason' => 'Приспадане себестойността на отпадък от произведен артикул',
								);
								
								$total += $amount;
							}
							
							$entries[] = $entry;
						}
					}
					
					// Ако има режийни разходи за разпределение
					if(isset($resourceInfo['expenses'])){
						$costAmount = $resourceInfo['expenses'] * $bomAmount;
						$costAmount = round($costAmount, 2);
						
						if($costAmount){
							$costArray = array(
									'amount' => $costAmount,
									'debit' => array('321', array('store_Stores', $rec->storeId),
											array($dRec->classId, $dRec->productId),
											'quantity' => 0),
									'credit' => array('61102'),
									'reason' => 'Разпределени режийни разходи',
							);
							
							$total += $costAmount;
							$entries[] = $costArray;
						}
					}
				}
			
			if(!$entry){
				$errorArr[] = cls::get($dRec->classId)->getTitleById($dRec->productId);
			}
		}
		
		// Ако някой от артикулите не може да бдъе произведем сетваме че ще правимр едирект със съобщението
		if(Mode::get('saveTransaction')){
			if(count($errorArr)){
				$errorArr = implode(', ', $errorArr);
				acc_journal_RejectRedirect::expect(FALSE, "Артикулите: |{$errorArr}|* не могат да бъдат произведени");
			}
		}
		
		// Връщаме ентритата
		return $entries;
	}
}
