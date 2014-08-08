<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа deals_DebitDocuments
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class deals_transaction_DebitDocument
{
    /**
     * 
     * @var deals_DebitDocuments
     */
    public $class;
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function finalizeTransaction($id)
    {
    	$rec = $this->class->fetchRec($id);
    	$rec->state = 'active';
    
    	if ($this->class->save($rec)) {
    		$this->class->invoke('AfterActivation', array($rec));
    	}
    }
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    	expect($origin = $this->class->getOrigin($rec));
    	
    	if($rec->isReverse == 'yes'){
    		// Ако документа е обратен, правим контировката на прехвърлянето на задължения но с отрицателен знак
    		$entry = deals_transaction_CreditDocument::getReverseEntries($rec, $origin);
    	} else {
    	
    		// Ако документа не е обратен, правим нормална контировка на прехвърлянето на взимане
    		$entry = $this->getEntry($rec, $origin);
    	}
    	 
    	// Подготвяме информацията, която ще записваме в Журнала
    	$result = (object)array(
    			'reason' => $rec->name, // основанието за ордера
    			'valior' => $rec->valior, // датата на ордера
    			'entries' => array($entry)
    	);
    	 
    	return $result;
    }
    
    
    /**
     * Връща записа на транзакцията
     */
    private function getEntry($rec, $origin, $reverse = FALSE)
    {
    	$dealInfo = $origin->getAggregateDealInfo();
    	$amount = $rec->rate * $rec->amount;
    	
    	// Ако е обратна транзакцията, сумите и к-та са с минус
    	$sign = ($reverse) ? -1 : 1;
    	
    	$dealRec = deals_Deals::fetch($rec->dealId);
    	
    	// Дебитираме разчетната сметка на избраната финансова сделка
    	$debitArr = array($rec->debitAccount,
    			array($dealRec->contragentClassId, $dealRec->contragentId),
    			array($dealRec->dealManId, $rec->dealId),
    			array('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency'))),
    			'quantity' => $sign * $amount / $dealRec->currencyRate);
    	
    	// Кредитираме разчетната сметка на сделката, начало на нишка
    	$creditArr = array($rec->creditAccount,
    						array($rec->contragentClassId, $rec->contragentId),
				    		array($origin->className, $origin->that),
				    		array('currency_Currencies', currency_Currencies::getIdByCode($dealRec->currencyId)),
    						'quantity' => $sign * $amount / $dealInfo->get('rate'));
    	
    	$entry = array('amount' => $sign * $amount, 'debit' => $debitArr, 'credit' => $creditArr,);
    	 
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