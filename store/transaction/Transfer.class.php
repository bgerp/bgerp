<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_Transfers
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class store_transaction_Transfer extends acc_DocumentTransactionSource
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
		$rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
		
		$result = (object)array(
				'reason'      => "Междускладов трансфер №{$rec->id}",
				'valior'      => $rec->valior,
				'totalAmount' => NULL,
				'entries'     => array()
		);
	
		$error = TRUE;
		$dQuery = store_TransfersDetails::getQuery();
		$dQuery->where("#transferId = '{$rec->id}'");
		while($dRec = $dQuery->fetch()){
			if(empty($dRec->quantity)) {
				if(Mode::get('saveTransaction')){
					continue;
				}
			} else {
				$error = FALSE;
			}
			
			// Ако артикула е вложим сметка 321
			$accId = '321';
			$result->entries[] = array(
					'credit'  => array($accId,
							array('store_Stores', $rec->fromStore), // Перо 1 - Склад
							array('cat_Products', $dRec->newProductId),  // Перо 2 - Артикул
							'quantity' => $dRec->quantity, // Количество продукт в основната му мярка,
					),
	
					'debit' => array($accId,
							array('store_Stores', $rec->toStore), // Перо 1 - Склад
							array('cat_Products', $dRec->newProductId),  // Перо 2 - Артикул
							'quantity' => $dRec->quantity, // Количество продукт в основната му мярка
					),
			);
		}
	
		if(Mode::get('saveTransaction')){
			if($error === TRUE){
				acc_journal_RejectRedirect::expect(FALSE, "Всички редове трябва да имат положително количество|*!");
			}
		}
		
		return $result;
	}
}