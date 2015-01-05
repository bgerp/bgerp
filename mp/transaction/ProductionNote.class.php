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
class mp_transaction_ProductionNote
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
	 */
	private static function getEntries($rec, &$total)
	{
		$entries = array();
		
		$dQuery = mp_ProductionNoteDetails::getQuery();
		$dQuery->where("#noteId = {$rec->id}");
		while($dRec = $dQuery->fetch()){
	
			// Референция към артикула
			$productRef = new core_ObjectReference($dRec->classId, $dRec->productId);
	
			//@TODO да се използва интерфейсен метод а не тази проверка
	
			// Взимаме к-то от последното активно задание за артикула, ако има
			$quantityJob = ($productRef->getInstance() instanceof techno2_SpecificationDoc) ? $productRef->getQuantityFromLastActiveJob() : NULL;
			
			$usesResources = FALSE;
	
			// Ако има к-во от задание
			if(isset($quantityJob)){

				// Проверяваме имали активна технологична карта, и извличаме ресурсите от нея
				if($mapArr = ($productRef->getInstance() instanceof techno2_SpecificationDoc) ? $productRef->getResourcesFromMap() : NULL){
					$usesResources = TRUE;
					
					foreach ($mapArr as $index => $resInfo){
						
						// Центъра на дейност е този от картата или по дефолт е избрания център от документа
						$activityCenterId = (isset($resInfo->activityCenterId)) ? $resInfo->activityCenterId : $rec->activityCenterId;
						
						$pInfo = $productRef->getProductInfo($dRec->productId);
						$creditAccId = (isset($pInfo->meta['materials'])) ? '302' : '321';
						
					   /*
						* За всеки ресурс началното количество се разделя на количеството от заданието и се събира
						* с пропорционалното количество. След това се умножава по количеството посочено в протокола за 
						* от производството и това количество се изписва от ресурсите.
						*/
						$resQuantity = $dRec->quantity * ($resInfo->baseQuantity / $quantityJob + $resInfo->propQuantity);
						$amount = $resQuantity * mp_Resources::fetchField($resInfo->resourceId, "selfValue");
						$total += $amount;
						
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
	
			if($usesResources === FALSE){
				//@TODO ако няма използвани ресурси за влагането, дебитираме сметката, която не е разбита по ресурси
			}
		}
		
		// Връщаме ентритата
		return $entries;
	}
	
	
	/**
	 * @param int $id
	 * @return stdClass
	 * @see acc_TransactionSourceIntf::getTransaction
	 */
	public function finalizeTransaction($id)
	{
		$rec = $this->class->fetchRec($id);
		$rec->state = 'active';
	
		if($id = $this->class->save($rec)) {
            $this->class->invoke('AfterActivation', array($rec));
        }
        
        return $id;
	}
}