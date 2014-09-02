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
     *  
     *  Разчетната сметка РС има Дебитно (Dt) салдо
     *  	
     *  	Намаляваме вземанията си от Контрагента с извънреден разход за съответната сума,
     *      със сумата на дебитното салдо на РС
     *  
     *  		Dt: 6913 - Отписани вземания по Финансови сделки
     *  		Ct: Разчетната сметка
     *  
     *  Разчетната сметка РС има Кредитно (Ct) салдо
     *  
     *  	Намаляваме задължението си към Контрагента за сметка на извънреден приход със сумата на неплатеното задължение,
     *  	със сумата на кредитното салдо на РС
     *  
     *  		Dt: Разчетната сметка
     *  		Ct: 7913 - Отписани задължения по Финансови сделки
     *  
     *  	
     *  
     */
    public function getTransaction($id)
    {
    	expect($rec = $this->class->fetchRec($id));
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	$info = $this->class->getDealInfo($rec->threadId);
    	$docRec = $firstDoc->fetch();
    	$accRec = acc_Accounts::fetch($docRec->accountId);
    	 
    	$amount = $info->get('blAmount');
    	
    	// Създаване на обекта за транзакция
    	$result = (object)array(
    			'reason'      => $rec->notes,
    			'valior'      => dt::now(),
    			'totalAmount' => abs($amount),
    			'entries'     => array(),
    	);
    	
    	if($amount == 0) return $result;
    	
    	if($rec->closeWith){
    		$closeDealItem = acc_Items::fetchItem('purchase_Purchases', $rec->closeWith);
    		$closeEntries = $this->class->getTransferEntries($firstDoc->instance->getClassId(), $result->totalAmount, $closeDealItem, $rec);
    		$result->entries = array_merge($result->entries, $closeEntries);
    	} else {
    		
    		$dealArr = array(acc_Accounts::fetchField($docRec->accountId, 'systemId'),
    				array($docRec->contragentClassId, $docRec->contragentId),
    				array('deals_Deals', $docRec->id),
    				array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    				'quantity' =>  abs($amount));
    		 
    		if($amount > 0){
    		
    			// Ако РС има дебитно салдо
    			$result->entries[] = array('amount' => $amount,
    					'debit' => array('6913',
    							array($docRec->contragentClassId, $docRec->contragentId),
    							array($firstDoc->className, $firstDoc->that)),
    					'credit' => $dealArr);
    		
    		} else {
    		
    			// Ако РС има кредитно салдо
    			$result->entries[] = array('amount' => abs($amount),
    					'debit' => $dealArr,
    					'credit' => array('7913',
    							array($docRec->contragentClassId, $docRec->contragentId),
    							array($firstDoc->className, $firstDoc->that))
    			);
    		}
    	}
    	
    	return $result;
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