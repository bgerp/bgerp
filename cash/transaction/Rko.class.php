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
     * Ако валутата е различна от основната за сч. период
     * 
     *    Dt: XXX. Разчетна сметка             (Доставчик, Сделки, Валута)
     *    Ct: 481. Разчети по курсови разлики  (Валута)
     *    
     *    Dt: 481. Разчети по курсови разлики  (Валута)
     *    Ct: 501. Каси   					   (Каса, Валута)
     *    
     * @param stdClass $rec
     * @return array
     */
    private function getEntry($rec, $origin, $reverse = FALSE)
    {
    	$amount = $rec->rate * $rec->amount;
    	
    	// Ако е обратна транзакцията, сумите и к-та са с минус
    	$sign = ($reverse) ? -1 : 1;
    	
    	// Дебита е винаги във валутата на пораждащия документ,
    	$debitCurrency = currency_Currencies::getIdByCode($origin->fetchField('currencyId'));
    	$debitQuantity = $amount / $origin->fetchField('currencyRate');
    	
    	// Дебитираме разчетната сметка
    	$dealArr = array($rec->debitAccount,
    			array($rec->contragentClassId, $rec->contragentId),
    			array($origin->className, $origin->that),
    			array('currency_Currencies', $debitCurrency),
    			'quantity' => $sign * $debitQuantity);
    	 
    	$caseCredit = array($rec->creditAccount,
    			array('cash_Cases', $rec->peroCase),
    			array('currency_Currencies', $rec->currencyId),
    			'quantity' => $sign * $rec->amount);
    	 
    	// Ако документа е в основната валута, няма к-ви разлики
    	if($rec->currencyId == acc_Periods::getBaseCurrencyId($rec->valior)){
    		$entry = array('amount' => $sign * $amount, 'debit' => $dealArr, 'credit' => $caseCredit,);
    		$entry = array($entry);
    	} else {
    		
    		// Ако не е минаваме през транзитна сметка '481'
    		$entry = array();
    		$entry[] = array('amount' => $sign * $amount,
    						'debit' => $dealArr,
    						'credit' => array('481', array('currency_Currencies', $rec->currencyId), 
    						'quantity' => $sign * $rec->amount));
    		
    		$entry[] = array('amount' => $sign * $amount, 'debit' => array('481', array('currency_Currencies', $rec->currencyId), 'quantity' => $sign * $rec->amount), 'credit' => $caseCredit);
    	}
    	
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