<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа sales_ClosedDeals
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class sales_transaction_CloseDeal extends acc_DocumentTransactionSource
{
    /**
     * 
     * @var sales_ClosedDeals
     */
    public $class;
    
    
    /**
     * Работен кеш за запомняне на направения, оборот докато не е влязал в счетоводството
     */
    private  $blAmount = 0;
    
    
    /**
     * Извлечен краткия баланс
     */
    private $shortBalance;
    
    
    /**
     * Дата
     */
    private $date;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
    	// Извличаме мастър-записа
    	expect($rec = $this->class->fetchRec($id));
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	$docRec = cls::get($rec->docClassId)->fetch($rec->docId);
    	
    	$dealItem = acc_Items::fetchItem('sales_Sales', $firstDoc->that);
    	
    	// Създаване на обекта за транзакция
    	$result = (object)array(
    			'reason'      => $rec->notes,
    			'valior'      => $this->class->getValiorDate($rec),
    			'totalAmount' => 0,
    			'entries'     => array()
    	);
    	
    	if($rec->closeWith){
    		if($dealItem){
    			$closeDeal = array('sales_Sales', $rec->closeWith);
    			
    			$closeEntries = $this->class->getTransferEntries($dealItem, $result->totalAmount, $closeDeal, $rec);
    			
    			$result->entries = array_merge($result->entries, $closeEntries);
    		}
    	} else {
    		$this->shortBalance = new acc_ActiveShortBalance(array('itemsAll' => $dealItem->id));
    		
    		$dealInfo = $this->class->getDealInfo($rec->threadId);
    	
    		$this->blAmount = $this->shortBalance->getAmount('411');
    		
    		// Създаване на запис за прехвърляне на всеки аванс
    		$entry2 = $this->transferDownpayments($dealInfo, $downPaymentAmount, $firstDoc);
    		$result->totalAmount += $downPaymentAmount;
    		
    		// Ако тотала не е нула добавяме ентритата
    		if(count($entry2)){
    			$result->entries[] = $entry2;
    		}
    		
    		$entry3 = $this->transferVatNotCharged($dealInfo, $docRec, $vatNotCharge, $firstDoc);
    		$result->totalAmount += $vatNotCharge;
    		
    		// Ако тотала не е нула добавяме ентритата
    		if(count($entry3)){
    			$result->entries[] = $entry3;
    		}
    		
    		$conf = core_Packs::getConfig('acc');
    		
    		// Ако има сума различна от нула значи има приход/разход
    		$entry = $this->getCloseEntry($this->blAmount, $result->totalAmount, $docRec, $firstDoc, $incomeFromClosure);
    		
    		if(count($entry)){
    			$result->entries[] = $entry;
    		}
    	}
    	
    	// Връщане на резултата
    	return $result;
    }
    
    
    /**
     * Ако в текущата сделка салдото по сметка 411 е различно от "0"
     *
     * Сметка 411 има Дебитно (Dt) салдо
     *
     * 		Намаляваме вземанията си от Клиента с неиздължената сума с обратна (revers) операция,
     * 		със сумата на дебитното салдо на с/ка 411
     *
     * 			Dt: 6911 - Отписани вземания по Продажби
     * 			Ct: 411 - Вземания от клиенти
     *
     * Сметка 411 има Кредитно (Ct) салдо
     *
     * 		Намаляваме прихода от Клиента с надвнесената сума с обратна (revers) операция,
     * 		със сумата на кредитното салдо на с/ка 411
    
     * 			Dt: 411 - Вземания от клиенти
     * 			Ct: 7911 - Извънредни приходи по Продажби
     */
    private function getCloseEntry($amount, &$totalAmount, $docRec, $firstDoc, &$incomeFromClosure)
    {
    	$entry = array();
    	
    	if(round($amount, 2) == 0) return $entry;
    	
    	if($amount < 0){
    		
    		// Ако платеното е по-вече от доставеното (кредитно салдо)
    		$entry1 = array(
    				'amount' => abs($amount),
    				'credit'  => array('7911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'debit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => abs($amount) / $docRec->currencyRate),
    				'reason' => 'Извънредни приходи - надплатени',
    		);
    
    		// Добавяме към общия оборот удвоената сума
    		$totalAmount += -1 * $amount;
    		$incomeFromClosure -= -1 * $amount;
    
    	} elseif($amount > 0){
    
    		// Ако платеното е по-малко от доставеното (дебитно салдо)
    		$entry1 = array(
    				'amount' => $amount,
    				'debit'  => array('6911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => $amount / $docRec->currencyRate),
    				'reason' => 'Извънредни разходи - недоплатени',
    		);	
    		
    		// Добавяме към общия оборот удвоената сума
    		$totalAmount += $amount;
    		$incomeFromClosure += $amount;
    	}
    	
    	// Връщане на записа
    	return $entry1;
    }
    
    
    /**
     * Прехвърля не неначисленото ДДС
     * За Продажба:
     * 		Dt: 4530. ДДС за начисляване
     *
     * 		Ct: 701. Приходи от продажби на Стоки и Продукти     (Клиенти, Сделки, Стоки и Продукти)
     * 			703. Приходи от продажби на услуги			     (Клиенти, Сделки, Услуги)
     * 			706. Приходи от продажба на суровини/материали   (Клиенти, Сделки, Суровини и Материали)
     *
     */
    private function transferVatNotCharged($dealInfo, $docRec, &$vatNotCharge, $firstDoc)
    {
    	$entries = array();
    	 
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	
    	$blAmount = acc_Balances::getBlAmounts($jRecs, '4530')->amount;
    	
    	$vatNotCharge += abs($blAmount);
    	
    	if($blAmount == 0) return $entries;
    	 
    	if($blAmount < 0){
    		$entries = array('amount' => abs($blAmount),
    				'credit'  => array('4535'),
    				'debit' => array('4530', array($firstDoc->className, $firstDoc->that)),
    				'reason' => 'ДДС по касови бележки');
    	} elseif($blAmount > 0){
    		$entries = array('amount' => $blAmount,
    				'credit'  => array('4530', array($firstDoc->className, $firstDoc->that)),
    				'debit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency'))),
    						'quantity' => $blAmount),
    				'reason' => 'Доначисляване на ДДС');
    
    		$this->blAmount  += $blAmount;
    	}
    	 
    	return $entries;
    }


    /**
     * Ако в текущата сделка салдото по сметка 412 е различно от "0"
     *
     * 		Намаляваме вземането си от клиента със сумата на получения от него аванс, респективно - намаляваме получените
     * 		от Клиента плащания с отрицателната сума на евентуално върнат му аванс, без да е платил такъв , със сумата 1:1 (включително
     * 		и ако е отрицателна) на кредитното салдо на с/ка 412
     *
     * 			Dt: 412 - Задължения към клиенти (по аванси)
     * 			Ct: 411 - Вземания от клиенти
     */
    private function transferDownpayments(bgerp_iface_DealAggregator $dealInfo, &$downPaymentAmount, $firstDoc)
    {
    	$entryArr = array();
    	 
    	$docRec = $firstDoc->rec();
    	 
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	 
    	// Колко е направеното авансовото плащане
    	$downpaymentAmount = -1 * acc_Balances::getBlAmounts($jRecs, '412')->amount;
    	if($downpaymentAmount == 0) return $entryArr;
    	 
    	// Валутата на плащането е тази на сделката
    	$currencyId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
    	$amount = $downpaymentAmount / $dealInfo->get('rate');
    
    	$entry = array();
    	$entry['amount'] = $downpaymentAmount;
    	$entry['debit'] = array('412',
    			array($docRec->contragentClassId, $docRec->contragentId),
    			array($firstDoc->className, $firstDoc->that),
    			array('currency_Currencies', $currencyId),
    			'quantity' => $amount);
    
    	$entry['credit'] = array('411',
    			array($docRec->contragentClassId, $docRec->contragentId),
    			array($firstDoc->className, $firstDoc->that),
    			array('currency_Currencies', $currencyId),
    			'quantity' => $amount);
    	$entry['reason'] = 'Приспадане на авансово плащане';
    	
    	$downPaymentAmount += $entry['amount'];
    	$this->blAmount -= $entry['amount'];
    	
    	return $entry;
    }
}