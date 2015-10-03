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
     * Ако избраната валута е в основна валута
     *
     * Dt: 503. Разплащателни сметки             (Банкова сметка, Валута)
     * Ct: 503. Разплащателни сметки             (Банкова сметка, Валута)
     *
     * Ако е в друга валута различна от основната
     *
     * Dt: 503. Разплащателни сметки             (Банкова сметка, Валута)
     * Ct: 481. Разчети по курсови разлики         (Валута)
     *
     * Dt: 481. Разчети по курсови разлики         (Валута)
     * Ct: 503. Разплащателни сметки             (Банкова сметка, Валута)
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    
    	$cOwnAcc = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom, 'currencyId');
    	$dOwnAcc = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
    
    	$toBank = array('503',
    			array('bank_OwnAccounts', $rec->peroTo),
    			array('currency_Currencies', $dOwnAcc->currencyId),
    			'quantity' => $rec->debitQuantity);
    
    	$fromBank = array('503',
    			array('bank_OwnAccounts', $rec->peroFrom),
    			array('currency_Currencies', $cOwnAcc->currencyId),
    			'quantity' => $rec->creditQuantity);
    
    	if($cOwnAcc->currencyId == acc_Periods::getBaseCurrencyId($rec->valior)){
    		$entry = array('amount' => $rec->debitQuantity * $rec->debitPrice, 'debit' => $toBank, 'credit' => $fromBank);
    		$entry = array($entry);
    	} else {
    		$entry = array();
    		$entry[] = array('amount' => $rec->debitQuantity,
    				'debit' => $toBank,
    				'credit' => array('481', array('currency_Currencies', $cOwnAcc->currencyId), 'quantity' => $rec->creditQuantity));
    		$entry[] = array('amount' => $rec->debitQuantity,
    				'debit' => array('481', array('currency_Currencies', $cOwnAcc->currencyId), 'quantity' => $rec->creditQuantity),
    				'credit' => $fromBank);
    	}
    
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason' => $rec->reason,   // основанието за ордера
    			'valior' => $rec->valior,   // датата на ордера
    			'entries' => $entry
    	);
    
    	return $result;
    }
}