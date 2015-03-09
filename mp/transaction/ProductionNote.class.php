<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа mp_ProductionNotes
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
class mp_transaction_ProductionNote extends acc_DocumentTransactionSource
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
	 * 		Ct: 611. Разходи по Центрове и Ресурси		(Център на дейност, Ресурс)
	 * 
	 * В противен случай
	 * 
	 * 		Dt: 321. Суровини, материали, продукция, стоки   (Складове, Артикули)
	 * 		Ct: 6112. Разходи по Центрове на дейност   (Център на дейност)
	 * 
	 */
	private function getEntries($rec, &$total)
	{
		$entries = array();
		
		$dQuery = mp_ProductionNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		
			
		while($dRec = $dQuery->fetch()){
	
			$entry = $this->getDirectEntry($dRec, $rec);
			
			if(!count($entry)){
				
				if(isset($dRec->bomId)){
					
					if(empty($dRec->jobId)) return FALSE;
					
					$quantityJob = mp_Jobs::fetchField($dRec->jobId, 'quantity');
					$mapArr = cat_Boms::getResourceInfo($dRec->bomId);
				
					if(count($mapArr)){
						foreach ($mapArr as $index => $res){
							if($res->type == 'input'){
								
								/*
								 * За всеки ресурс началното количество се разделя на количеството от заданието и се събира
								 * с пропорционалното количество. След това се умножава по количеството посочено в протокола за
								 * от производството и това количество се изписва от ресурсите.
								 */
								$resQuantity = $dRec->quantity * ($res->baseQuantity / $quantityJob + $res->propQuantity);
								$resQuantity = core_Math::roundNumber($resQuantity);
									
								$res->finalQuantity = $resQuantity;
							}
						}
						arr::order($mapArr, 'finalQuantity', 'DESC');
						
						foreach ($mapArr as $index => $res){
							$pQuantity = ($index == 0) ? $dRec->quantity : 0;
							
							if($res->type == 'input'){
								
								$entry = array(
										'debit' => array('321', array('store_Stores', $rec->storeId),
															  array($dRec->classId, $dRec->productId),
												'quantity' => $pQuantity),
										'credit' => array('611', array('hr_Departments', $rec->activityCenterId)
												, 				 array('mp_Resources', $res->resourceId),
												'quantity' => $res->finalQuantity),
								);
							} else {
								
								// Сумата на дебита е себестойността на отпадния ресурс
								$amount = $resQuantity * mp_Resources::fetchField($res->resourceId, "selfValue");
								$resQuantity = $dRec->quantity * ($res->baseQuantity / $quantityJob + $res->propQuantity);
								$resQuantity = core_Math::roundNumber($resQuantity);
								
								$entry = array(
										'amount' => $amount,
										'debit' => array('611', array('hr_Departments', $rec->activityCenterId),
																 array('mp_Resources', $res->resourceId),
														'quantity' => $resQuantity),
										'credit' => array('321', array('store_Stores', $rec->storeId),
																 array($dRec->classId, $dRec->productId),
															'quantity' => $pQuantity),
								);
								
								$total += $amount;
							}
							
							$entries[] = $entry;
						}
					}
				}
			} else {
				$entries[] = $entry;
			}
		}
		
		// Връщаме ентритата
		return $entries;
	}
	
	
	/**
	 * Връща директната контировка ако:
	 * за артикула има ресурс, който има дебитно салдо в 611 и е вложим
	 * или ако няма рецепта.
	 * 
	 * 		Dt: 321. Суровини, материали, продукция, стоки      (Складове, Артикули)
	 * 		Ct: 6111. Разходи по Центрове и Ресурси             (Центрове на дейност, Ресурси)
	 * 
	 */
	private function getDirectEntry($dRec, $rec)
	{
		$entry = array();
		
		// Референция към артикула
		$productRef = new core_ObjectReference($dRec->classId, $dRec->productId);
		$pInfo = $productRef->getProductInfo();
		
		// Ако към артикула имаме ресурс
		if($resourceId = mp_ObjectResources::getResource($dRec->classId, $dRec->productId)->resourceId){
			$blQuantity = FALSE;
		
			// И ресурса е перо
			$item = acc_Items::fetchItem('mp_Resources', $resourceId);
			if($item){
				
				// Намираме крайното салдо на ресурса по сметка 611 за този център и този ресурс
				$bQuery = acc_BalanceDetails::getQuery();
				$centerId = acc_Items::fetchItem('hr_Departments', $rec->activityCenterId)->id;
		
				acc_BalanceDetails::filterQuery($bQuery, acc_Balances::getLastBalance()->id, '611', NULL, $centerId, $item->id);
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
							'credit' => array('611', array('hr_Departments', $rec->activityCenterId)
									, 				 array('mp_Resources', $resourceId),
									'quantity' => $dRec->quantity),
					);
				}
			}
		}
		
		return $entry;
	}
}