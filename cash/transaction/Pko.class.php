<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа cash_Pko
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
class cash_transaction_Pko
{
    /**
     * 
     * @var cash_Pko
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
    		// Нотифицираме origin-документа, че някой от веригата му се е променил
    		if ($origin = $this->class->getOrigin($rec)) {
    			$ref = new core_ObjectReference($this->class, $rec);
    			$origin->getInstance()->invoke('DescendantChanged', array($origin, $ref));
    		}
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
    	 
    	$origin = $this->class->getOrigin($rec);
    	$dealInfo = $origin->getAggregateDealInfo();
    	$amount = round($rec->rate * $rec->amount, 2);
    
    	// Ако е обратна транзакцията, сумите и к-та са с минус
    	$sign = ($rec->isReverse == 'no') ? 1 : -1;
    	 
    	// Кредита е винаги във валутата на пораждащия документ,
    	$creditCurrency = currency_Currencies::getIdByCode($dealInfo->agreed->currency);
    	$creditQuantity = round($amount / $dealInfo->agreed->rate, 2);
    
    	$creditArr[] = $rec->creditAccount;
    	$debitArr[] = $rec->debitAccount;
    
    	$cashArr = array('1' => array('cash_Cases', $rec->peroCase),
    			'2' => array('currency_Currencies', $rec->currencyId),
    			'quantity' => $sign * $rec->amount);
    
    	$dealArr = array('1' => array($rec->contragentClassId, $rec->contragentId),
    			'2' => array($origin->className, $origin->that),
    			'quantity' => $sign * $creditQuantity);
    
    	$creditArr += ($rec->isReverse == 'no') ? $dealArr : $cashArr;
    	$debitArr += ($rec->isReverse == 'no') ? $cashArr : $dealArr;
    
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason' => $rec->reason, // основанието за ордера
    			'valior' => $rec->valior,   // датата на ордера
    			'entries' => array(
    					array('amount' => $sign * $amount, 'debit' => $debitArr, 'credit' => $creditArr,)
    			)
    	);
    
    	return $result;
    }
}