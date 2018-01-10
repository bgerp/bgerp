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
class sales_transaction_CloseDeal extends deals_ClosedDealTransaction
{
    /**
     * 
     * @var sales_ClosedDeals
     */
    public $class;
    
    
    /**
     * Работен кеш за запомняне на направения, оборот докато не е влязал в счетоводството
     */
    private $blQuantities = array();
    
    
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
    	
    	if(Mode::get('saveTransaction')){
    		acc_journal_RejectRedirect::expect(!acc_plg_Contable::havePendingDocuments($rec->threadId), tr("Към продажбата има документ в състояние|* '|Заявка|*'"));
    	}
    	
    	// Създаване на обекта за транзакция
    	$result = (object)array(
    			'reason'      => $rec->notes,
    			'valior'      => ($rec->valior) ? $rec->valior : $this->class->getValiorDate($rec),
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
    		$dealInfo = $this->class->getDealInfo($rec->threadId);
    		
    		// Създаване на запис за прехвърляне на всеки аванс
    		$entry2 = $this->transferDownpayments($dealInfo, $downpaymentAmounts, $firstDoc, $result);
    		
    		// Ако тотала не е нула добавяме ентритата
    		if(count($entry2)){
    			$result->entries = array_merge($result->entries, $entry2);
    		}
    		
    		$entry3 = $this->transferVatNotCharged($dealInfo, $docRec, $vatNotCharge, $firstDoc);
    		$result->totalAmount += $vatNotCharge;
    		
    		// Ако тотала не е нула добавяме ентритата
    		if(count($entry3)){
    			$result->entries[] = $entry3;
    		}
    		
    		$conf = core_Packs::getConfig('acc');
    		
    		$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    		
    		// За всеки случай махат се от записите, тези които са на приключването на покупка
    		if(isset($rec->id)){
    			if($thisRec = acc_Journal::fetchByDoc($this->class, $rec->id)){
    				$nQuery  = acc_JournalDetails::getQuery();
    				$nQuery->where("#journalId = {$thisRec->id}");
    				$thisIds = arr::extractValuesFromArray($nQuery->fetchAll(), 'id');
    				$jRecs = array_diff_key($jRecs, $thisIds);
    			}
    		}
    		
    		$quantities = acc_Balances::getBlQuantities($jRecs, '411');
    		
    		if(is_array($downpaymentAmounts)){
    			foreach ($downpaymentAmounts as $index => $obj){
    				if(!array_key_exists($index, $quantities)){
    					$quantities[$index] = new stdClass();
    				}
    		
    				$quantities[$index]->quantity -= $obj->quantity;
    				$quantities[$index]->amount -= $obj->amount;
    			}
    		}
    		
    		if(is_array($this->blQuantities)){
    			foreach ($this->blQuantities as $index => $obj){
    				if(!array_key_exists($index, $quantities)){
    					$quantities[$index] = new stdClass();
    				}
    		
    				$quantities[$index]->quantity += $obj->quantity;
    				$quantities[$index]->amount += $obj->amount;
    			}
    		}
    		
    		if(is_array($quantities)){
    			foreach ($quantities as $index => $obj1){
    				$entry = $this->getCloseEntry($obj1->amount, $obj1->quantity, $index, $result->totalAmount, $docRec, $firstDoc);
    				if(count($entry)){
    					$result->entries = array_merge($result->entries, $entry);
    				}
    			}
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
    private function getCloseEntry($amount, $quantity, $index, &$totalAmount, $docRec, $firstDoc)
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
    						$index,
    						'quantity' => abs($quantity)),
    				'reason' => 'Извънредни приходи - надплатени',
    		);
    
    		// Добавяме към общия оборот удвоената сума
    		$totalAmount += -1 * $amount;
    
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
    						'quantity' => $quantity),
    				'reason' => 'Извънредни разходи - недоплатени',
    		);	
    		
    		// Добавяме към общия оборот удвоената сума
    		$totalAmount += $amount;
    	}
    	
    	// Връщане на записа
    	return array($entry1);
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
    		$quantity = round($blAmount / $docRec->currencyRate, 5);
    		$currencyItem = acc_Items::fetchItem('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency')));
    		
    		$entries = array('amount' => $blAmount,
    				'credit'  => array('4530', array($firstDoc->className, $firstDoc->that)),
    				'debit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						$currencyItem->id,
    						'quantity' => $quantity),
    				'reason' => 'Доначисляване на ДДС');
    		
    		$this->blQuantities[$currencyItem->id] = (object)array('quantity' => $quantity, 'amount' => $blAmount);
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
    private function transferDownpayments(bgerp_iface_DealAggregator $dealInfo, &$downpaymentAmounts, $firstDoc, &$result)
    {
    	$entryArr = array();
    	if(!$downpaymentAmounts){
    		$downpaymentAmounts = array();
    	}
    	
    	$docRec = $firstDoc->rec();
    	 
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	$downpaymentArrs = acc_Balances::getBlQuantities($jRecs, '412');
    	
    	if(is_array($downpaymentArrs)){
    		foreach ($downpaymentArrs as $index => $obj){
    			$res = deals_Helper::convertJournalCurrencies(array($index => $obj), $docRec->currencyId, $result->valior);
    			$cItemId = acc_Items::fetchItem('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId))->id;
    			 
    			$entry = array();
    			$entry['amount'] = abs($res->amount);
				
				$entry['debit'] = array('412',
    					array($docRec->contragentClassId, $docRec->contragentId),
    					array($firstDoc->className, $firstDoc->that),
    					$index,
    					'quantity' => abs($obj->quantity));
    			
    			$entry['credit'] = array('411',
    					array($docRec->contragentClassId, $docRec->contragentId),
    					array($firstDoc->className, $firstDoc->that),
    					$cItemId,
    					'quantity' => abs($res->quantity));
    			$entry['reason'] = 'Приспадане на авансово плащане';
    			 
    			$entryArr[] = $entry;
    			$result->totalAmount += $entry['amount'];
    			 
    			if(!array_key_exists($cItemId, $downpaymentAmounts)){
    				$downpaymentAmounts = array($cItemId => (object)array('quantity' => 0, 'amount' => 0));
    			}
    			 
    			$downpaymentAmounts[$cItemId]->quantity -= $res->quantity;
    			$downpaymentAmounts[$cItemId]->amount -= $res->amount;
    		}
    	}
    	
    	return $entryArr;
    }
}