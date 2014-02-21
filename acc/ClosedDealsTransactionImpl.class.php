<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за наслендиците на acc_ClosedDeals
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class acc_ClosedDealsTransactionImpl
{
    
	
	/**
	 * 
	 * @var sales_ClosedDeals или purchase_ClosedDeals
	 */
	public $class;
    
    
    /**
     * Връща транзакцията за документа
     */
    public function getTransaction($id)
    {
    	// Извличаме мастър-записа
        expect($rec = $this->class->fetchRec($id));
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        $docRec = cls::get($rec->docClassId)->fetch($rec->docId);
		$amount = $this->class->getClosedDealAmount($firstDoc);
        
		// Създаване на обекта за транзакция
        $result = (object)array(
            'reason'      => $this->class->singleTitle . " #" . $firstDoc->getHandle(),
            'valior'      => dt::now(),
            'totalAmount' => currency_Currencies::round(abs($amount)),
            'entries'     => array()
        );
       
        $dealInfo = $this->class->getDealInfo($rec->threadId);
        
        // Ако има сума различна от нула значи има приход/разход
        if($amount != 0){
        	
        	// Взимаме записа за начисляване на извънредния приход/разход
        	$entry = $this->getCloseEntry($amount, $result->totalAmount, $docRec, $dealInfo->dealType);
        }
        
        // Ако има направено авансово плащане
        if($downpayment = $dealInfo->paid->downpayment){
        	
        	// Създаване на запис за прехвърляне на всеки аванс
        	$entry2 = $this->trasnferDownpayments($dealInfo, $docRec, $total);
        	$result->totalAmount += $total;
        }
        
        // Ако тотала не е нула добавяме ентритата
    	if($result->totalAmount != 0){
    		
    		if(count($entry)){
    			$result->entries[] = $entry;
    		}
    		
    		if(count($entry2)){
    			$result->entries = array_merge($result->entries, $entry2);
    		}
    	}
        
    	// Връщане на резултата
        return $result;
    }
    
    
    /**
     * Връща записа за начисляване на извънредния приход/разход
     * 
     * Приключване на продажба:
     * ------------------------------------------------------
     * Надплатеното: Dt:  411. Вземания от клиенти (Клиенти, Валути)
     * 				 Ct: 7911. Надплатени по продажби
     * 
     * Недоплатеното: Dt: 6911. Отписани вземания по продажби
     * 				  Ct:  411. Вземания от клиенти (Клиенти, Валути)
     * 
     * 
     * Приключване на покупка:
     * -------------------------------------------------------
     * Надплатеното:  Dt: 6912. Надплатени по покупки
     * 				  Ct:  401. Задължения към доставчици (Доставчици, Валути)
     * 
     * Недоплатеното: Dt:  401. Задължения към доставчици (Доставчици, Валути)
     * 				  Ct: 7912. Отписани задължения по покупки
     */
    protected function getCloseEntry($amount, $totalAmount, $docRec, $dealType)
    {
    	$entry = array();
    	$accounts = $this->class->contoAccounts;
    	
    	// Записа за извънреден разход
    	$spending = array(
    		'amount' => $totalAmount,
    		'debit'  => array($accounts['spending']['debit'], 'quantity' => $totalAmount),
    		'credit' => array($accounts['spending']['credit'],
    									array($docRec->contragentClassId, $docRec->contragentId), 
                        				array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
                       				'quantity' => $totalAmount),
    	);
    	
    	// Записа за извънреден приход
    	$income = array(
    		'amount' => $totalAmount,
    		'debit'  => array($accounts['income']['debit'],
    									array($docRec->contragentClassId, $docRec->contragentId), 
                        				array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
                       				'quantity' => $totalAmount),
            'credit' => array($accounts['income']['credit'], 'quantity' => $totalAmount),
    	);	
    	
    	// Ако се приключва покупка
    	if($dealType == bgerp_iface_DealResponse::TYPE_PURCHASE){
    		
    		// Недоплатеното е приход, а надплатеното разход
    		$entry = ($amount < 0) ? $income : $spending;
    	} else {
    		
    		// Ако се приключва продажба
    		// Недоплатеното е разход, а надплатеното приход
    		$entry = ($amount < 0) ? $spending : $income;
    	}
    	
    	// Връщане на записа
    	return $entry;
    }
    
    
    /**
     * Ако има направени авансови плащания към сделката се приключва и аванса
     * Направените аванси са сумирани по валута, така за всяко авансово плащане в различна валута
     * има запис за неговото приключване
     * 
     * Приключване на аванс на продажба:
     * ------------------------------------------------------
     * Dt:  412. Задължения към клиенти (по аванси)
     * Ct:  411. Вземания от клиенти (Клиенти, Валути)
     * 
     * 
     * Приключване на аванс на покупка:
     * -------------------------------------------------------
     * Dt: 401. Задължения към доставчици (Доставчици, Валути)
     * Ct: 402. Вземания от доставчици по аванси
     */
    protected function trasnferDownpayments(bgerp_iface_DealResponse $dealInfo, $docRec, &$total)
    {
    	$entryArr = array();
    	$total = 0;
    	
    	// Направените авансови плащания досега сумирани по валута
    	$downpayments = $dealInfo->paid->downpayments;
    	$accounts = $this->class->contoAccounts;
    	
    	// За всяко авансово плащане се създава запис
    	foreach ($downpayments as $currencyId => $rec){
    		$entry = array();
    		$entry['amount'] = currency_Currencies::round($rec['amountBase']);
    		$entry['debit'] = array($accounts['downpayments']['debit'],
    									array($docRec->contragentClassId, $docRec->contragentId), 
                     					array('currency_Currencies', $currencyId),
                     				'quantity' => $rec['amount']);
                     					
            $entry['credit'] = array($accounts['downpayments']['credit'],
    									array($docRec->contragentClassId, $docRec->contragentId), 
                     					array('currency_Currencies', $currencyId),
                     				'quantity' => $rec['amount']);
            
            // Сумиране на общото
            $total += $entry['amount'];
            $entryArr[] = $entry;
    	}
    	
    	// Връщане на готовия масив със записи
    	return $entryArr;
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
        
        return  $this->class->save($rec);
    }
}