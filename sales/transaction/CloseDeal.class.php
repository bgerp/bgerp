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
    private static $incomeAmount;
    
    
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
    	
    	$dealInfo = $this->class->getDealInfo($rec->threadId);
    	
    	// Кеширане на перото на текущата година
    	$date = ($dealInfo->get('invoicedValior')) ? $dealInfo->get('invoicedValior') : $dealInfo->get('agreedValior');
    	$this->date = acc_Periods::forceYearAndMonthItems($date);
    	
    	// Създаване на запис за прехвърляне на всеки аванс
    	$entry2 = $this->trasnferDownpayments($dealInfo, $docRec, $result->totalAmount, $firstDoc);
    	
    	// Ако тотала не е нула добавяме ентритата
    	if(count($entry2)){
    		$result->entries[] = $entry2;
    	}
    	 
    	$entry3 = $this->transferVatNotCharged($dealInfo, $docRec, $result->totalAmount, $firstDoc);
    	
    	// Ако тотала не е нула добавяме ентритата
    	if(count($entry3)){
    		$result->entries[] = $entry3;
    	}
    	
    	$entry4 = $this->transferIncome($dealInfo, $docRec, $result->totalAmount, $firstDoc);
    	if(count($entry4)){
    		$result->entries = array_merge($result->entries, $entry4);
    	}
    	 
    	// Ако има сума различна от нула значи има приход/разход
    	$amount = $this->class->getClosedDealAmount($firstDoc);
    	$amount += $this->diffAmount;
    	$entry = $this->getCloseEntry($amount, $result->totalAmount, $docRec, $firstDoc);
    	 
    	if(count($entry)){
    		$result->entries = array_merge($result->entries, $entry);
    	}
    	
    	$entry5 = $this->transferIncomeToYear($dealInfo, $docRec, $result->totalAmount, $firstDoc);
    	if(count($entry5)){
    		$result->entries[] = $entry5;
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
    private function getCloseEntry($amount, &$totalAmount, $docRec, $firstDoc)
    {
    	$entry = array();
    	 
    	if($amount == 0) return $entry;
    	if($amount > 0){
    
    		// Ако платеното е по-вече от доставеното (кредитно салдо)
    		$entry1 = array(
    				'amount' => currency_Currencies::round($amount),
    				'credit'  => array('7911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'debit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => currency_Currencies::round($amount / $docRec->currencyRate)),
    		);
    
    		$entry2 = array('amount' => currency_Currencies::round($amount),
    				'debit'  => array('7911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit'  => array('700', array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    		);
    
    		// Добавяме към общия оборот удвоената сума
    		$totalAmount += 2 * currency_Currencies::round($amount);
    		static::$incomeAmount -= $amount;
    
    	} elseif($amount < 0){
    
    		// Ако платеното е по-малко от доставеното (дебитно салдо)
    		$entry1 = array(
    				'amount' => -1 * currency_Currencies::round($amount),
    				'debit'  => array('6911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('411',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => currency_Currencies::round(-1 * $amount / $docRec->currencyRate)),
    		);
    
    		$entry2 = array('amount' => -1 * currency_Currencies::round($amount),
    				'debit'  => array('700', array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit'  => array('6911',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),);
    		
    		// Добавяме към общия оборот удвоената сума
    		$totalAmount += -2 * currency_Currencies::round($amount);
    		static::$incomeAmount += -1 * currency_Currencies::round($amount);
    
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
    protected function transferIncomeToYear($dealInfo, $docRec, &$total, $firstDoc)
    {
    	$arr1 = array('700', array($docRec->contragentClassId, $docRec->contragentId), array($firstDoc->className, $firstDoc->that));
    	$arr2 = array('123', $this->date->year, $this->date->month);
    	$total += abs(static::$incomeAmount);
    	 
    	// Дебитно салдо
    	if(static::$incomeAmount > 0){
    		$debitArr = $arr2;
    		$creditArr = $arr1;
    	} else {
    
    		// Кредитно салдо
    		$debitArr = $arr1;
    		$creditArr = $arr2;
    	}
    	 
    	$entry = array('amount' => abs(static::$incomeAmount), 'debit' => $debitArr, 'credit' => $creditArr);
    	 
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
    protected function transferIncome($dealInfo, $docRec, &$total, $firstDoc)
    {
    	$entries = array();
    	$balanceArr = $this->shortBalance->getShortBalance('701,706,703');
    	
    	$blAmountGoods = $this->shortBalance->getAmount('701,706,703');
    	$total += abs($blAmountGoods);
    	 
    	if(!count($balanceArr)) return $entries;
    	 
    	foreach ($balanceArr as $rec){
    		$arr1 = array('700', array($docRec->contragentClassId, $docRec->contragentId),
    				array($firstDoc->className, $firstDoc->that));
    		$arr2 = array($rec['accountSysId'], $rec['ent1Id'], $rec['ent2Id'], $rec['ent3Id'], 'quantity' => $rec['blQuantity']);
    
    		static::$incomeAmount += $blAmountGoods;
    
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
    private function transferVatNotCharged($dealInfo, $docRec, &$total, $firstDoc)
    {
    	$entries = array();
    	 
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	
    	$blAmount = acc_Balances::getBlAmounts($jRecs, '4530')->amount;
    	
    	$total += abs($blAmount);
    	
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
    
    		$this->diffAmount  -= $blAmount;
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
    private function trasnferDownpayments(bgerp_iface_DealAggregator $dealInfo, $docRec, &$total, $firstDoc)
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
    
    	$total += $entry['amount'];
    
    	return $entry;
    }
}