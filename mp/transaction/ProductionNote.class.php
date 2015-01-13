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
	 * 		Dt: 321. Стоки и продукти                  (Складове, Артикули)
	 * 			Dt: 302. Суровини и материали          (Складове, Артикули)
	 * 
	 * 		Ct: 611. Разходи по Центрове и Ресурси		(Център на дейност, Ресурс)
	 * 
	 * В противен случай
	 * 
	 * 		Dt: 321. Стоки и продукти                  (Складове, Артикули)
	 * 			Dt: 302. Суровини и материали          (Складове, Артикули)
	 * 
	 * 		Ct: 6112. Разходи по Центрове на дейност   (Център на дейност)
	 * 
	 */
	private static function getEntries($rec, &$total)
	{
		$entries = array();
		
		$dQuery = mp_ProductionNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		while($dRec = $dQuery->fetch()){
	
			// Референция към артикула
			$productRef = new core_ObjectReference($dRec->classId, $dRec->productId);
	
			// Коя сметка от трета група да кредитираме, взависимост от свойствата на артикула
			$pInfo = $productRef->getProductInfo($dRec->productId);
			$creditAccId = (isset($pInfo->meta['materials'])) ? '302' : '321';
			
			//@TODO да се използва интерфейсен метод а не тази проверка
	
			// Взимаме к-то от последното активно задание за артикула, ако има
			$quantityJob = ($productRef->getInstance() instanceof techno2_SpecificationDoc) ? $productRef->getLastActiveJob()->quantity : NULL;
			
			$usesResources = FALSE;
	
			// Ако има к-во от задание
			if(isset($quantityJob)){

				// Проверяваме имали активна технологична карта, и извличаме ресурсите от нея
				if($bomRec = ($productRef->getInstance() instanceof techno2_SpecificationDoc) ? $productRef->getLastActiveBom() : NULL){
					$usesResources = TRUE;
					
					$mapArr = techno2_Boms::getResourceInfo($bomRec->id);
					// За всеки ресурс от картата
					foreach ($mapArr as $index => $resInfo){
						
						// Центъра на дейност е този от картата или по дефолт е избрания център от документа
						$activityCenterId = (isset($resInfo->activityCenterId)) ? $resInfo->activityCenterId : $rec->activityCenterId;
						
					   /*
						* За всеки ресурс началното количество се разделя на количеството от заданието и се събира
						* с пропорционалното количество. След това се умножава по количеството посочено в протокола за 
						* от производството и това количество се изписва от ресурсите.
						*/
						$resQuantity = $dRec->quantity * ($resInfo->baseQuantity / $quantityJob + $resInfo->propQuantity);
						$amount = $resQuantity * mp_Resources::fetchField($resInfo->resourceId, "selfValue");
						$total += $amount;
						
						//@TODO а себестойността, ако е въведена ?
						
						// Първото дебитиране на артикула става с цялото к-ва, а последващите с нулево
						$pQuantity = ($index == 0) ? $dRec->quantity : 0;
						$entry = array(
							'amount' => $amount,
							'debit' => array($creditAccId, array('store_Stores', $rec->storeId), 
													array($dRec->classId, $dRec->productId),
											 'quantity' => $pQuantity),
							'credit' => array('611', array('hr_Departments', $activityCenterId)
												   , array('mp_Resources', $resInfo->resourceId),
											  'quantity' => $resQuantity),
						);
						
						$entries[] = $entry;
					}
				}
			}
	
			// Ако няма технологична карта и/или количество от заданието
			if($usesResources === FALSE){
				
				// Тогава кредитираме сметка 6112
				$entries[] = array('amount' => $dRec->selfValue,
								   'debit' => array($creditAccId, 
								   				array('store_Stores', $rec->storeId), 
								   				array($dRec->classId, $dRec->productId),
												'quantity' => $dRec->quantity),
								   'credit' => array('6112', array('hr_Departments', $rec->activityCenterId))
							);
				
				$total += $dRec->selfValue;
			}
		}
		
		// Връщаме ентритата
		return $entries;
	}
}