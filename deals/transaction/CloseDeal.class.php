<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа deals_ClosedDeals
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
class deals_transaction_CloseDeal
{
    /**
     * 
     * @var deals_ClosedDeals
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
    	expect($rec = $this->class->fetchRec($id));
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	$info = $this->class->getDealInfo($rec->threadId);
    	$docRec = $firstDoc->fetch();
    	$accRec = acc_Accounts::fetch($docRec->accountId);
    	 
    	$amount = $info->get('amount');
    	
    	// Създаване на обекта за транзакция
    	$result = (object)array(
    			'reason'      => $this->singleTitle . " #" . $firstDoc->getHandle(),
    			'valior'      => dt::now(),
    			'totalAmount' => 0,
    			'entries'     => array(),
    	);
    	
    	$date = ($info->get('invoicedValior')) ? $info->get('invoicedValior') : $info->get('agreedValior');
    	$this->date = acc_Periods::forceYearAndMonthItems($date);
    	
    	if($amount == 0) return $result;
    	
    	if($accRec->type == 'passive'){
    		$result->entries = array_merge($result->entries, $this->getPassiveEntries($amount, $info, $firstDoc, $docRec, $result->totalAmount));
    	} elseif($accRec->type == 'active'){
    		$result->entries[] = $this->getActiveEntries($amount, $info, $firstDoc, $docRec, $result->totalAmount);
    	} else {
    		//@TODO Какво се прави ако е смесена ?
    	}
    	
    	
    	
    	return $result;
    	// Извънреден разход
    	if($amount < 0){
    		$debitArr = array('6913',
    				array($docRec->contragentClassId, $docRec->contragentId),
    				array($firstDoc->className, $firstDoc->that));
    		$creditArr = array($docRec->accountId,
    				array($docRec->contragentClassId, $docRec->contragentId),
    				array('deals_Deals', $docRec->id),
    				array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    				'quantity' =>  abs($amount));
    	} else {
    		// Извънреден приход
    		$debitArr = array($docRec->accountId,
    				array($docRec->contragentClassId, $docRec->contragentId),
    				array('deals_Deals', $docRec->id),
    				array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    				'quantity' =>  abs($amount));
    		$creditArr = array('7913',
    				array($docRec->contragentClassId, $docRec->contragentId),
    				array($firstDoc->className, $firstDoc->that));
    	}
    	
    	
    	
    	$result->entries[] = array('amount' => abs($amount), 'debit' => $debitArr, 'credit' => $creditArr);
    	
    	return $result;
    }
    
    
    private function getPassiveEntries($amount, $info, $firstDoc, $docRec, &$total)
    {
    	$entry = array();
    	$account = acc_Accounts::fetchField($docRec->accountId, 'systemId');
    	
    	if($amount > 0){
    		$entry1 = array('amount' => -1 * $amount,
    						'debit' => array($account,
		    					array($docRec->contragentClassId, $docRec->contragentId),
		    					array('deals_Deals', $docRec->id),
		    					array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
		    					'quantity' =>  round(-1 * $amount / $info->get('rate'), 2)),
    						'credit' => array('6913',
		    					array($docRec->contragentClassId, $docRec->contragentId),
		    					array($firstDoc->className, $firstDoc->that)),);
    		
    		$entry2 = array('amount' => $amount,
    						'debit' => array('123', $this->date->year, $this->date->month),
    						'credit' => array('6913',
		    					array($docRec->contragentClassId, $docRec->contragentId),
		    					array($firstDoc->className, $firstDoc->that)),);
    	} else {
    		$entry1 = array('amount' => $amount,
    				'debit' => array('7913',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit' => array($account,
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array('deals_Deals', $docRec->id),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' =>  round($amount / $info->get('rate'), 2)));
    				
    		
    		$entry2 = array('amount' => abs($amount),
    				'debit' => array('7913',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('123', $this->date->year, $this->date->month));
    	}
    	
    	return array($entry1, $entry2);
    }
    
    
    private function getActiveEntries($amount, $info, $firstDoc, $docRec, &$total)
    {
    	 $entry = array();
    	 
    	 if($amount > 0){
    	 
    	 } else {
    	 
    	 }
    	 
    	 return $entry;
    }
    
    
    /**
     * Финализиране на транзакцията, изпълнява се ако всичко е ок
     *
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function finalizeTransaction($id)
    {
    	$rec = $this->class->fetchRec($id);
    	$rec->state = 'active';
    
    	if ($id = $this->class->save($rec)) {
    		$this->class->invoke('AfterActivation', array($rec));
    	}
    
    	return $id;
    }
}