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
				'reason' => "Протокол от производство №{$rec->id}",
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
					
					$priceObj = cat_Boms::getPrice($dRec->productId, $dRec->bomId);
					$bomAmount = ($priceObj->base + $priceObj->prop) / $resourceInfo['quantity'];
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
								$reason = ($index == 0) ? 'Засклаждане на произведения артикул' : 'Вложени ресурси в произведения артикул';
								$entry = array(
										'debit' => array('321', array('store_Stores', $rec->storeId),
															  array($dRec->classId, $dRec->productId),
												'quantity' => $pQuantity),
										'credit' => array('61101', array('planning_Resources', $res->resourceId),
												'quantity' => $res->finalQuantity),
										'reason' => $reason,
								);
							} else {
								
								// Сумата на дебита е себестойността на отпадния ресурс
								$amount = $resQuantity * planning_Resources::fetchField($res->resourceId, "selfValue");
								$resQuantity = $dRec->quantity * ($res->baseQuantity / $quantityJob + ($res->propQuantity / $resourceInfo['quantity']));
								$resQuantity = core_Math::roundNumber($resQuantity);
								
								$entry = array(
										'amount' => $amount,
										'debit' => array('61101', array('planning_Resources', $res->resourceId),
														'quantity' => $resQuantity),
										'credit' => array('321', array('store_Stores', $rec->storeId),
																 array($dRec->classId, $dRec->productId),
															'quantity' => $pQuantity),
										'reason' => 'Приспадане себестойността на отпадъка от произведения артикул',
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
			
			if(!$entry){
				$errorArr[] = cls::get($dRec->classId)->getVerbal($dRec->productId, 'name');
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
	
	
	/**
	 * Връща директната контировка ако:
	 * за артикула има ресурс, който има дебитно салдо в 61101 и е вложим
	 * или ако няма рецепта.
	 * 
	 * 		Dt: 321. Суровини, материали, продукция, стоки      (Складове, Артикули)
	 * 		Ct: 61101. Разходи за Ресурси             (Ресурси)
	 * 
	 */
	private static function getDirectEntry($dRec, $rec)
	{
		$entry = array();
		
		// Референция към артикула
		$productRef = new core_ObjectReference($dRec->classId, $dRec->productId);
		$pInfo = $productRef->getProductInfo();
		
		// Ако към артикула имаме ресурс
		if($resourceId = planning_ObjectResources::getResource($dRec->classId, $dRec->productId)->resourceId){
			$blQuantity = FALSE;
		
			// И ресурса е перо
			$item = acc_Items::fetchItem('planning_Resources', $resourceId);
			if($item){
				
				// Намираме крайното салдо на ресурса по сметка 61101 за този център и този ресурс
				$bQuery = acc_BalanceDetails::getQuery();

				acc_BalanceDetails::filterQuery($bQuery, acc_Balances::getLastBalance()->id, '61101', NULL, $item->id);
				$bRec = $bQuery->fetch();
				
				// Ако имаме дебитно салдо
				if($bRec->blQuantity > 0){
					$blQuantity = $bRec->blQuantity;
				}
			}
		
			// и е вложим
			if(isset($pInfo->meta['canConvert'])){
				
				// и имаме дебитно салдо или няма рецепта директно го произвеждаме от ресурса
				if($blQuantity || empty($dRec->bomId)){
					$entry = array(
							'debit' => array('321', array('store_Stores', $rec->storeId),
									array($dRec->classId, $dRec->productId),
									'quantity' => $dRec->quantity),
							'credit' => array('61101', array('planning_Resources', $resourceId),
									'quantity' => $dRec->quantity),
					);
				}
			}
		}
		
		return $entry;
	}
}
