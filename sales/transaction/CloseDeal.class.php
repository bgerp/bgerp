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
class sales_transaction_CloseDeal
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
    
    
    /**
     * Подготвя записите за приключване на дадена сделка с друга сделка
     * 
     * 1. Занулява салдата на първата сделка, прави обратни транзакции на всички записи от журнала свързани с тази сделка
     * 2. Прави същите операции но подменя перото на първата сделка с това на второто, така всички салда са
     * прихвърлени по втората сделка, а първата е приключена
     */
    private function getTransferEntries($dealItem, &$total, $closeDealItem, $rec)
    {
    	$newEntries = array();
    	$docs = array();
    	
    	// Намираме записите в които участва перото
    	$entries = acc_Journal::getEntries($dealItem);
    	
    	// Намираме документите, които имат транзакции към перото
    	if(count($entries)){
    		foreach ($entries as $ent){
    			if($ent->docType != $rec->classId && $ent->docId != $rec->id){
    				$docs[$ent->docType . "|" . $ent->docId] = (object)array('docType' => $ent->docType, 'docId' => $ent->docId);
    			}
    		}
    	}
    	$dealItem->docClassName = cls::get($dealItem->classId)->className;
    	
    	if(count($docs)){
    		
    		// За всеки транзакционен клас
    		foreach ($docs as $doc){
    			
    			// Взимаме му редовете на транзакцията
    			$transactionSource = cls::getInterface('acc_TransactionSourceIntf', $doc->docType);
    			$entries = $transactionSource->getTransaction($doc->docId)->entries;
    			
    			$copyEntries = $entries;
    			
    			// За всеки ред, генерираме запис с обратни стойностти (сумите и к-та са с обратен знак)
    			// Така зануляване салдата по следката
    			if(count($entries)){
    				foreach ($copyEntries as &$entry){
    					
    					// Ако има сума добавяме я към общата сума на транзакцията
    					if(isset($entry['amount'])){
    						$entry['amount'] *= -1;
    						$total += $entry['amount'];
    					}
    					if(isset($entry['debit']['quantity'])){
    						$entry['debit']['quantity'] *= -1;
    					}
    					if(isset($entry['credit']['quantity'])){
    						$entry['credit']['quantity'] *= -1;
    					}
    					
    					$newEntries[] = $entry;
    				}
    				
    				// Втори път обхождаме записите
    				foreach ($entries as &$entry2){
    					if(isset($entry2['amount'])){
    						$total += $entry2['amount'];
    					}
    					
    					// Генерираме запис, който прави същите действия но с перо новата сделка
    					foreach (array('debit', 'credit') as $type){
    						foreach ($entry2[$type] as $index => &$item){
    							
    							// Намираме кое перо отговаря на перото на текущата сделка и го заменяме с това на новата сделка
    							if($index != 0){
    								if(is_array($item) && $item[0] == $dealItem->docClassName && $item[1] == $dealItem->objectId){
    									$item = $closeDealItem->id;
    								}
    							}
    						}
    					}
    					
    					$newEntries[] = $entry2;
    				}
    			}
    		}
    	}
    	
    	// Връщаме генерираните записи
    	return $newEntries;
    }
    
    
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
    	
    	$dealItem = acc_Items::fetch("#classId = {$firstDoc->instance->getClassId()} AND #objectId = '$firstDoc->that' ");
    	$this->shortBalance = new acc_ActiveShortBalance($dealItem->id);
    	
    	// Създаване на обекта за транзакция
    	$result = (object)array(
    			'reason'      => $this->class->singleTitle . " #" . $firstDoc->getHandle(),
    			'valior'      => dt::now(),
    			'totalAmount' => 0,
    			'entries'     => array()
    	);
    	
    	if($rec->closeWith){
    		$closeDealItem = acc_Items::fetchItem('sales_Sales', $rec->closeWith);
    		$closeEntries = $this->getTransferEntries($dealItem, $result->totalAmount, $closeDealItem, $rec);
    		$result->entries = array_merge($result->entries, $closeEntries);
    	} else {
    		$dealInfo = $this->class->getDealInfo($rec->threadId);
    		 
    		$this->blAmount = $this->shortBalance->getAmount('411');
    		
    		// Кеширане на перото на текущата година
    		$date = ($dealInfo->get('invoicedValior')) ? $dealInfo->get('invoicedValior') : $dealInfo->get('agreedValior');
    		$this->date = acc_Periods::forceYearAndMonthItems($date);
    		 
    		// Създаване на запис за прехвърляне на всеки аванс
    		$entry2 = $this->trasnferDownpayments($dealInfo, $docRec, $downPaymentAmount, $firstDoc);
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
    		 
    		$entry4 = $this->transferIncome($dealInfo, $docRec, $result->totalAmount, $firstDoc, $incomeFromProducts);
    		if(count($entry4)){
    			$result->entries = array_merge($result->entries, $entry4);
    		}
    		
    		// Ако има сума различна от нула значи има приход/разход
    		$entry = $this->getCloseEntry($this->blAmount, $result->totalAmount, $docRec, $firstDoc, $incomeFromClosure);
    		
    		if(count($entry)){
    			$result->entries = array_merge($result->entries, $entry);
    		}
    		 
    		//bp($incomeFromClosure, $incomeFromProducts);
    		$totalIncome = $incomeFromClosure + $incomeFromProducts;
    		$entry5 = $this->transferIncomeToYear($docRec, $result->totalAmount, $firstDoc, $totalIncome);
    		if(count($entry5)){
    			$result->entries[] = $entry5;
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
     * 		Отнасяме отписаните вземания (извънредния разход) по сделката като разход по дебита на обобщаващата сметка 700,
     * 		със сумата на дебитното салдо на с/ка 411
     *
     * 			Dt: 700 - Приходи от продажби (по сделки)
     * 			Ct: 6911 - Отписани вземания по Продажби
     *
     * Сметка 411 има Кредитно (Ct) салдо
     *
     * 		Намаляваме прихода от Клиента с надвнесената сума с обратна (revers) операция,
     * 		със сумата на кредитното салдо на с/ка 411
    
     * 			Dt: 411 - Вземания от клиенти
     * 			Ct: 7911 - Извънредни приходи по Продажби
     *
     * 		Отнасяме извънредния приход по сделката като приход по кредита на обобщаващата сметка 700,
     * 		със сумата на кредитното салдо на с/ка 411
     *
     * 			Dt: 7911 - Извънредни приходи по Продажби
     * 			Ct: 700 - Приходи от продажби (по сделки)
     */
    private function getCloseEntry($amount, &$totalAmount, $docRec, $firstDoc, &$incomeFromClosure)
    {
    	$entry = array();
    	 
    	if($amount == 0) return $entry;
    	
    	if($amount < 0){
    		
    		// Ако платеното е по-вече от доставеното (кредитно салдо)
    		$entry1 = array(
    				'amount' => abs(currency_Currencies::round($amount)),
    				'credit'  => array('7911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'debit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => currency_Currencies::round(abs($amount) / $docRec->currencyRate)),
    		);
    
    		$entry2 = array('amount' => abs(currency_Currencies::round($amount)),
    				'debit'  => array('7911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit'  => array('700', array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    		);
    
    		// Добавяме към общия оборот удвоената сума
    		$totalAmount += -2 * currency_Currencies::round($amount);
    		$incomeFromClosure -= -1 * $amount;
    
    	} elseif($amount > 0){
    
    		// Ако платеното е по-малко от доставеното (дебитно салдо)
    		$entry1 = array(
    				'amount' => currency_Currencies::round($amount),
    				'debit'  => array('6911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => currency_Currencies::round($amount / $docRec->currencyRate)),
    		);
    
    		$entry2 = array('amount' => currency_Currencies::round($amount),
    				'debit'  => array('700', array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit'  => array('6911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),);
    		
    		// Добавяме към общия оборот удвоената сума
    		$totalAmount += 2 * currency_Currencies::round($amount);
    		$incomeFromClosure += currency_Currencies::round($amount);
    	}
    	
    	// Връщане на записа
    	return array($entry1, $entry2);
    }
    
    
    /**
     * Отчитане на финансовия резултат от сделката по сметка 123 - Печалби и загуби от текущата година
     *
     * Сметка 700 има Дебитно (Dt) салдо
     *
     * 		Отнасяме резултата от сделката като загуба по сметка 123, със сумата на дебитното салдо на с/ка 700 по сделката
     *
     *			Dt: 123 - Печалби и загуби от текущата година
     *			Ct: 700 - Приходи от продажби (по сделки)  (вече на ниво "Сделка")
     *
     * Сметка 700 има Кредитно (Ct) или нулево "0" салдо
     *
     * 		Отнасяме резултата от сделката като печалба по сметка 123, със сумата на кредитното салдо на с/ка 700 по сделката
     *
     * 			Dt: 700 - Приходи от продажби (по сделки)  (вече на ниво "Сделка")
     * 			Ct: 123 - Печалби и загуби от текущата година
     */
    protected function transferIncomeToYear($docRec, &$total, $firstDoc, $incomeFromClosure)
    {
    	$arr1 = array('700', array($docRec->contragentClassId, $docRec->contragentId), array($firstDoc->className, $firstDoc->that));
    	$arr2 = array('123', $this->date->year, $this->date->month);
    	$total += abs($incomeFromClosure);
    	
    	// Дебитно салдо
    	if($incomeFromClosure > 0){
    		$debitArr = $arr2;
    		$creditArr = $arr1;
    	} else {
    
    		// Кредитно салдо
    		$debitArr = $arr1;
    		$creditArr = $arr2;
    	}
    	 
    	$entry = array('amount' => abs($incomeFromClosure), 'debit' => $debitArr, 'credit' => $creditArr);
    	
    	return $entry;
    }
    
    
    /**
     * Отчитане на финансовия резултат от сделката по сметка 123 - Печалби и загуби от текущата година
     * Обобщаване на резултата от "Продажба"-та на ниво Сделка в с/ка 700 - Приходи от продажби (по сделки)
     *
     * Записа за конкретния артикул по сметка 701 (сметка от гр. 70) има Дебитно (Dt) салдо - т.е.
     *
     * 		Отнасяме резултата за артикула като разход по сметка 700 - Приходи от продажби (по сделки)
     *
     * 			Dt: 700 - Приходи от продажби (по сделки)
     * 				Ct: 701 - Приходи от продажби на Стоки и Продукти
     * 				Ct: 706 - Приходи от продажба на суровини/материали
     * 				Ct: 703 - Приходи от продажби на Услуги
     *
     * Записа за конкретния артикул по сметка 701 (сметка от гр. 70) има Кредитно (Ct) или нулево "0" салдо
     *
     * 		Отнасяме резултата за артикула като приход по сметка 700 - Приходи от продажби (по сделки)
     *
     * 				Dt: 701 - Приходи от продажби на Стоки и Продукти
     * 				Dt: 706 - Приходи от продажба на суровини/материали
     * 				Dt: 703 - Приходи от продажби на Услуги
     * 			Ct: 700 - Приходи от продажби (по сделки)
     */
    protected function transferIncome($dealInfo, $docRec, &$total, $firstDoc, &$incomeFromProducts)
    {
    	$entries = array();
    	$balanceArr = $this->shortBalance->getShortBalance('701,706,703');
    	
    	$blAmountGoods = $this->shortBalance->getAmount('701,706,703');
    	$incomeFromProducts += $blAmountGoods;
    	
    	$total += abs($blAmountGoods);
    	 
    	if(!count($balanceArr)) return $entries;
    	
    	foreach ($balanceArr as $rec){
    		$arr1 = array('700', array($docRec->contragentClassId, $docRec->contragentId),
    				array($firstDoc->className, $firstDoc->that));
    		$arr2 = array($rec['accountSysId'], $rec['ent1Id'], $rec['ent2Id'], $rec['ent3Id'], 'quantity' => $rec['blQuantity']);
    
    		if($blAmountGoods > 0){
    			$debitArr = $arr1;
    			$creditArr = $arr2;
    		} else {
    			$debitArr = $arr2;
    			$creditArr = $arr1;
    		}
    
    		$entries[] = array('amount' => abs($rec['blAmount']), 'debit' => $debitArr, 'credit' => $creditArr);
    	}
    	
    	return $entries;
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
    				'debit' => array('4530', array($firstDoc->className, $firstDoc->that)));
    	} elseif($blAmount > 0){
    		$entries = array('amount' => $blAmount,
    				'credit'  => array('4530', array($firstDoc->className, $firstDoc->that)),
    				'debit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency'))),
    						'quantity' => $blAmount));
    
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
    private function trasnferDownpayments(bgerp_iface_DealAggregator $dealInfo, $docRec, &$downPaymentAmount, $firstDoc)
    {
    	$entryArr = array();
    	 
    	$docRec = $firstDoc->rec();
    	 
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	 
    	// Колко е направеното авансовото плащане
    	$downpaymentAmount = -1 * acc_Balances::getBlAmounts($jRecs, '412')->amount;
    	if($downpaymentAmount == 0) return $entryArr;
    	 
    	// Валутата на плащането е тази на сделката
    	$currencyId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
    	$amount = currency_Currencies::round($downpaymentAmount / $dealInfo->get('rate'), 2);
    
    	$entry = array();
    	$entry['amount'] = currency_Currencies::round($downpaymentAmount);
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
    
    	$downPaymentAmount += $entry['amount'];
    	$this->blAmount -= $entry['amount'];
    	
    	return $entry;
    }
}