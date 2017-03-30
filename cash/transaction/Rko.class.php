<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа cash_Rko
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
class cash_transaction_Rko extends acc_DocumentTransactionSource
{
	
    /**
     * 
     * @var cash_Rko
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    
    	$origin = $this->class->getOrigin($rec);
    	
    	if($rec->isReverse == 'yes'){
    		
    		// Ако документа е обратен, правим контировката на ПКО-то но с отрицателен знак
    		$entry = cash_transaction_Pko::getReverseEntries($rec, $origin);
    	} else {
    		
    		// Ако документа не е обратен, правим нормална контировка на РКО
    		$entry = $this->getEntry($rec, $origin);
    	}
    	
    	$rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
    	
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason'  => $rec->reason,   // основанието за ордера
    			'valior'  => $rec->valior,   // датата на ордера
    			'entries' => $entry,
    	);
    
    	return $result;
    }
    
    
    /**
     * Ако валутата е основната за сч. период
     * 
     *    Dt: XXX. Разчетна сметка  (Доставчик, Сделки, Валута)
     *    Ct: 501. Каси             (Каса, Валута)
     *    
     * @param stdClass $rec
     * @return array
     */
    private function getEntry($rec, $origin, $reverse = FALSE)
    {
    	// Ако е обратна транзакцията, сумите и к-та са с минус
    	$sign = ($reverse) ? -1 : 1;
    	
    	$baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
    	if($rec->currencyId == $baseCurrencyId){
    		$amount = $rec->amount;
    	} elseif($rec->dealCurrencyId == $baseCurrencyId){
    		$amount = $rec->amountDeal;
    	} else {
    		$amount = $rec->amount * $rec->rate;
    	}
    	
    	$entry = array('amount' => $sign * $amount, 
    			
    				   'debit' => array($rec->debitAccount, 
    				   					array($rec->contragentClassId, $rec->contragentId), 
    				   				    array($origin->className, $origin->that), 
    				   					array('currency_Currencies', $rec->dealCurrencyId),
    				   					'quantity' => $sign * $rec->amountDeal), 
    			
    				   'credit' => array($rec->creditAccount, 
    									array('cash_Cases', $rec->peroCase), 
    									array('currency_Currencies', $rec->currencyId),
    									'quantity' => $sign * $rec->amount));
    	$entry = array($entry);
    	
    	return $entry;
    }
    
    
    /**
     * Връща обратна контировка на стандартната
     */
    public static function getReverseEntries($rec, $origin)
    {
    	$self = cls::get(get_called_class());
    	
    	return $self->getEntry($rec, $origin, TRUE);
    }
}