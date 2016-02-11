<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа cash_ExchangeDocument
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class cash_transaction_ExchangeDocument extends acc_DocumentTransactionSource
{
    
    
    /**
     * 
     * @var cash_ExchangeDocument
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *	Dt: 501. Каси 					(Каса, Валута)
     *  Ct: 501. Каси					(Каса, Валута)аса, Валута)
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    
    	$entry = array('debit' => array('501',
					    			array('cash_Cases', $rec->peroTo),
					    			array('currency_Currencies', $rec->debitCurrency),
					    			'quantity' => $rec->debitQuantity), 
    				   'credit' => array('501',
					    			array('cash_Cases', $rec->peroFrom),
					    			array('currency_Currencies', $rec->creditCurrency),
					    			'quantity' => $rec->creditQuantity));
    	$entry = array($entry);
    	
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason' => $rec->reason,   // основанието за ордера
    			'valior' => $rec->valior,   // датата на ордера
    			'entries' => $entry,
    	);
    
    	return $result;
    }
}