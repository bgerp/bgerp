<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа acc_AllocatedExpenseses
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class acc_transaction_AllocatedExpense extends acc_DocumentTransactionSource
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
				'reason' => $rec->notes,
				'valior' => $rec->valior,
				'totalAmount' => 0,
				'entries' => array()
		);
	
		if(isset($rec->id)) {
			$entries = $this->getEntries($rec, $result->totalAmount);
			if(count($entries)){
				$result->entries = $entries;
			}
		}
		
		return $result;
	}
	
	
	private function getEntries($rec, $total)
	{
		$entries = array();
		
		// Кой е първия документ в треда ?
		$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
		$Instance = $firstDoc->getInstance();
		
		// Ако документа е към продажба
		if($Instance instanceof sales_Sales){
			
			// Ако е към покупка
		} elseif($Instance instanceof purchase_Purchases){
			
		}
		
		//bp($entries,$rec);
		return $entries;
	}
}