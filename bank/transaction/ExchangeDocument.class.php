<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа bank_ExchangeDocument
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class bank_transaction_ExchangeDocument extends acc_DocumentTransactionSource
{
    
    
    /**
     * 
     * @var bank_ExchangeDocument
     */
    public $class;
    
    
    /**
     * Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     * Създава транзакция която се записва в Журнала, при контирането
     *
     * Dt: 503. Разплащателни сметки             (Банкова сметка, Валута)
     * Ct: 503. Разплащателни сметки             (Банкова сметка, Валута)
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    
    	$cOwnAcc = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom, 'currencyId');
    	$dOwnAcc = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
    
    	$entry = array('debit' => array('503',
					    			array('bank_OwnAccounts', $rec->peroTo),
					    			array('currency_Currencies', $dOwnAcc->currencyId),
					    			'quantity' => $rec->debitQuantity), 
    				   'credit' => array('503',
					    			array('bank_OwnAccounts', $rec->peroFrom),
					    			array('currency_Currencies', $cOwnAcc->currencyId),
					    			'quantity' => $rec->creditQuantity));
    	$entry = array($entry);
    
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason' => $rec->reason,   // основанието за ордера
    			'valior' => $rec->valior,   // датата на ордера
    			'entries' => $entry
    	);
    
    	return $result;
    }
}