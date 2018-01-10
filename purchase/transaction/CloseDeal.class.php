<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_ClosedDeals
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class purchase_transaction_CloseDeal extends deals_ClosedDealTransaction
{
    /**
     * 
     * @var purchase_ClosedDeals
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
     * Кеш на извънредния разход
     */
    private $bl6912 = 0;
    
    
    /**
     * Кеш на извънредния приход
     */
    private $bl7912 = 0;
    
    
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
    	
    	$dealItem = acc_Items::fetch("#classId = {$firstDoc->getInstance()->getClassId()} AND #objectId = '$firstDoc->that' ");
    	
    	if(Mode::get('saveTransaction')){
    		acc_journal_RejectRedirect::expect(!acc_plg_Contable::havePendingDocuments($rec->threadId), tr("Към покупката има документ в състояние \'Заявка\'|*"));
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
    			$closeDealItem = array('purchase_Purchases', $rec->closeWith);
    			$closeEntries = $this->class->getTransferEntries($dealItem, $result->totalAmount, $closeDealItem, $rec);
    			$result->entries = array_merge($result->entries, $closeEntries);
    		}
    	} else {
    		$dealInfo = $this->class->getDealInfo($rec->threadId);
    		 
    		// Кеширане на перото на текущата година
    		$date = ($dealInfo->get('invoicedValior')) ? $dealInfo->get('invoicedValior') : $dealInfo->get('agreedValior');
    		$this->date = acc_Periods::forceYearItem($date);
    		
    		// Създаване на запис за прехвърляне на всеки аванс
    		$entry2 = $this->trasnferDownpayments($dealInfo, $docRec, $downpaymentAmounts, $firstDoc, $result);
    		 
    		// Ако тотала не е нула добавяме ентритата
    		if(count($entry2)){
    			$result->entries = array_merge($result->entries, $entry2);
    		}
    		
    		$entry3 = $this->transferVatNotCharged($dealInfo, $docRec, $result->totalAmount, $firstDoc);
    		 
    		// Ако тотала не е нула добавяме ентритата
    		if(count($entry3)){
    			$result->entries[] = $entry3;
    		}
    		 
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
    		
    		$item = acc_Items::fetchItem('purchase_Purchases', $firstDoc->that)->id;
    		$quantities = acc_Balances::getBlQuantities($jRecs, '401', NULL, NULL, array(NULL, $item, NULL));
    		
    		if(is_array($downpaymentAmounts)){
    			foreach ($downpaymentAmounts as $index => $obj){
    				if(!array_key_exists($index, $quantities)){
    					$quantities[$index] = new stdClass();
    				}
    				
    				$quantities[$index]->quantity += $obj->quantity;
    				$quantities[$index]->amount += $obj->amount;
    			}
    		}
    		
    		if(is_array($this->blQuantities)){
    			foreach ($this->blQuantities as $index => $obj){
    				if(!array_key_exists($index, $quantities)){
    					$quantities[$index] = new stdClass();
    				}
    		
    				$quantities[$index]->quantity -= $obj->quantity;
    				$quantities[$index]->amount -= $obj->amount;
    			}
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
    	
    	// Връщане на резултата
    	return $result;
    }
    
    
    /**
     * Ако в текущата сделка салдото по сметка 402 е различно от "0"
     *
     * 		Намаляваме задължението си към доставчика със сумата на платения му аванс, респективно - намаляваме
     * 		направените към Доставчика плащания с отрицателната сума на евентуално върнат ни аванс, без да сме
     * 		платили такъв (т.к. системата допуска създаването на revert операция без наличието на права такава преди това),
     * 		със сумата 1:1 (включително и ако е отрицателна) на дебитното салдо на с/ка 402
     *
     * 			Dt: 401 Задължения към доставчици
     * 			Ct: 402 Вземания от доставчици по аванси
     */
    private function trasnferDownpayments(bgerp_iface_DealAggregator $dealInfo, $docRec, &$downpaymentAmounts, $firstDoc, &$result)
    {
    	$entryArr = array();
	    if(!$downpaymentAmounts){
	    	$downpaymentAmounts = array();
	    }
    	
    	$docRec = $firstDoc->rec();
    
    	// Валутата на плащането е тази на сделката
    	$currencyId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
    	
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	$downpaymentArrs = acc_Balances::getBlQuantities($jRecs, '402');
    	
    	if(is_array($downpaymentArrs)){
    		foreach ($downpaymentArrs as $index => $obj){
    			$res = deals_Helper::convertJournalCurrencies(array($index => $obj), $docRec->currencyId, $result->valior);
    			$cItemId = acc_Items::fetchItem('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId))->id;
    			
    			$entry = array();
    			$entry['amount'] = $res->amount;
    			$entry['debit'] = array('401',
    					array($docRec->contragentClassId, $docRec->contragentId),
    					array($firstDoc->className, $firstDoc->that),
    					$cItemId,
    					'quantity' => $res->quantity);
    			
    			$entry['credit'] = array('402',
    					array($docRec->contragentClassId, $docRec->contragentId),
    					array($firstDoc->className, $firstDoc->that),
    					$index,
    					'quantity' => $obj->quantity);
    			$entry['reason'] = 'Приспадане на авансово плащане';
    			
    			$entryArr[] = $entry;
    			$result->totalAmount += $entry['amount'];
    			
    			if(!array_key_exists($cItemId, $downpaymentAmounts)){
    				$downpaymentAmounts = array($cItemId => (object)array('quantity' => 0, 'amount' => 0));
    			}
    			
    			$downpaymentAmounts[$cItemId]->quantity += $res->quantity;
    			$downpaymentAmounts[$cItemId]->amount += $res->amount;
    		}
    	}
	
    	return $entryArr;
    }


    /**
     * Прехвърля не неначисленото ДДС
     * Ако в текущата сделка салдото по сметка 4530 е различно от "0":
     *
     * Сметка 4530 има Кредитно (Ct) салдо;
     *
     * 		Увеличаваме задълженията си към Доставчика със сумата на надфактурираното ДДС, със сумата на кредитното салдо на с/ка 4530
     *
     * 			Dt: 4530 - ДДС за начисляване
     * 			Ct: 401 - Задължения към доставчици
     *
     * Сметка 4530 има Дебитно (Dt) салдо;
     *
     * 		Тъй като отделеното за начисляване и нефактурирано (неначислено) ДДС не може да бъде възстановено, както се е
     * 		очаквало при отделянето му за начисляване по с/ка 4530, го отнасяме като извънреден разход по сделката,
     * 		със сумата на дебитното салдо (отделеното, но неначислено ДДС) на с/ка 4530
     *
     * 			Dt: 6912 - Извънредни разходи по Покупки
     * 			Ct: 4530 - ДДС за начисляване
     */
    private function transferVatNotCharged($dealInfo, $docRec, &$total, $firstDoc)
    {
    	$entries = array();
    	 
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	$blAmount = acc_Balances::getBlAmounts($jRecs, '4530')->amount;
    	
    	$total += abs($blAmount);
    	 
    	if($blAmount == 0) return $entries;
    	 
    	// Сметка 4530 има Кредитно (Ct) салдо
    	if($blAmount < 0){
    		$quantity = round(abs($blAmount) / $docRec->currencyRate, 5);
    		$currencyItem = acc_Items::fetchItem('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency')));
    		
    		$entries = array('amount' => abs($blAmount),
    				'debit'  => array('4530', array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('401',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency'))),
    						'quantity' => $quantity),
    				'reason' => 'Доначисляване на ДДС');
    		
    		
    		$this->blQuantities[$currencyItem->id] = (object)array('quantity' => $quantity, 'amount' => abs($blAmount));
    	} elseif($blAmount > 0){
    
    		// Сметка 4530 има Дебитно (Dt) салдо
    		$entries1 = array('amount' => $blAmount,
    				'debit' => array('6912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit'  => array('4530',
    						array($firstDoc->className, $firstDoc->that),
    						'quantity' => $blAmount),
    				'reason' => 'Отделено, но невъзстановимо ДДС');
    		
    		$entries = $entries1;
    
    	}
    	 
    	// Връщаме ентритата
    	return $entries;
    }
    
    
    /**
     * Отчитане на извънредните приходи/разходи от сделката
     * Ако в текущата сделка салдото по сметка 401 е различно от "0"
     *
     * Сметка 401 има Кредитно (Ct) салдо
     *
     * 		Намаляваме задълженията си към Доставчика с неиздължената сума с обратна (revers) операция,
     *		със сумата на кредитното салдо на с/ка 401
     *
     * 			Dt: 401 - Задължения към доставчици
     * 			Ct: 7912 - Отписани задължения по Покупки
     *
     * Сметка 401 има Дебитно (Dt) салдо
     *
     * 		Намаляваме плащанията към Доставчика с надплатената сума с обратна (revers) операция, със сумата
     * 		на дебитното салдо на с/ка 401
     *
     * 			Dt: 6912 - Извънредни разходи по Покупки
     * 			Ct: 401 - Задължения към доставчици
     *
     */
    private function getCloseEntry($amount, $quantity, $index, &$totalAmount, $docRec, $firstDoc)
    {
    	$entry = array();
    	
    	if(round($amount, 2) == 0) return $entry;
    	$quantity = round($quantity, 9);
    	
    	// Сметка 401 има Дебитно (Dt) салдо
    	if($amount > 0){
    		$entry1 = array(
    				'amount' => $amount,
    				'credit' => array('401',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						$index,
    						'quantity' => $quantity),
    				'debit'  => array('6912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'reason' => 'Извънредни разходи - надплатени'
    		);
    		
    		$totalAmount +=  $amount;
    		
    		// Сметка 401 има Кредитно (Ct) салдо
    	} elseif($amount < 0){
    		$entry1 = array(
    				'amount' => -1 * $amount,
    				'credit'  => array('7912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'debit' => array('401',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						$index,
    						'quantity' => -1 * $quantity),
    				'reason' => 'Извънредни приходи - недоплатени');
    		
    		$totalAmount += -1 * $amount;
    	}
    	 
    	// Връщане на записа
    	return array($entry1);
    }
}