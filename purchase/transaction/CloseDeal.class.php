<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_ClosedDeals
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
class purchase_transaction_CloseDeal
{
    /**
     * 
     * @var purchase_ClosedDeals
     */
    public $class;
    
    
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
    		if(count($entry3) == 2){
    			$result->entries = array_merge($result->entries, $entry3);
    		} else {
    			$result->entries[] = $entry3;
    		}
    	}
    	 
    	// Ако има сума различна от нула значи има приход/разход
    	$amount = $this->class->getClosedDealAmount($firstDoc);
    	$amount += $this->diffAmount;
    	$entry = $this->getCloseEntry($amount, $result->totalAmount, $docRec, $firstDoc);
    	 
    	if(count($entry)){
    		$result->entries = array_merge($result->entries, $entry);
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
    private function trasnferDownpayments(bgerp_iface_DealAggregator $dealInfo, $docRec, &$total, $firstDoc)
    {
    	$entryArr = array();
    
    	$docRec = $firstDoc->rec();
    
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    
    	// Колко е направеното авансовото плащане
    	$downpaymentAmount = acc_Balances::getBlAmounts($jRecs, '402')->amount;
    	if($downpaymentAmount == 0) return $entryArr;
    	 
    	// Валутата на плащането е тази на сделката
    	$currencyId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
    	$amount = currency_Currencies::round($downpaymentAmount / $dealInfo->get('rate'), 2);
    	 
    	$entry = array();
    	$entry['amount'] = currency_Currencies::round($downpaymentAmount);
    	$entry['debit'] = array('401',
    			array($docRec->contragentClassId, $docRec->contragentId),
    			array($firstDoc->className, $firstDoc->that),
    			array('currency_Currencies', $currencyId),
    			'quantity' => $amount);
    	 
    	$entry['credit'] = array('402',
    			array($docRec->contragentClassId, $docRec->contragentId),
    			array($firstDoc->className, $firstDoc->that),
    			array('currency_Currencies', $currencyId),
    			'quantity' => $amount);
    	 
    	$total += $entry['amount'];
    	 
    	return $entry;
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
     *
     * 		и го приключваме като намаление на финансовия резултат за годината със същата сума
     *
     * 			Dt: 123 - Печалби и загуби от текущата година
     * 			Ct: 6912 - Извънредни разходи по Покупки
     *
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
    		$entries = array('amount' => abs($blAmount),
    				'debit'  => array('4530', array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('401',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency'))),
    						'quantity' => abs($blAmount)));
    	} elseif($blAmount > 0){
    
    		// Сметка 4530 има Дебитно (Dt) салдо
    		$entries1 = array('amount' => $blAmount,
    				'debit' => array('6912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit'  => array('4530',
    						array($firstDoc->className, $firstDoc->that),
    						'quantity' => $blAmount));
    
    		$entries2 = array('amount' => $blAmount,
    				'debit' => array('123', $this->date->year, $this->date->month),
    				'credit' => array('6912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    		);
    
    		$total += $blAmount;
    		$entries = array($entries1, $entries2);
    
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
     *		със сумата на кредитното салдо на с/ка 401 но с отрицателен знак
     *
     * 			Dt: 7912 - Отписани задължения по Покупки
     * 			Ct: 401 - Задължения към доставчици
     *
     * 		Отнасяме отписаните задължения (извънредния приход) по сделката като печалба по сметка 123 - Печалби и загуби от текущата година
     * 		със сумата на кредитното салдо на с/ка 401
     *
     * 			Dt: 7912 - Отписани задължения по Покупки
     * 			Ct: 123 - Печалби и загуби от текущата година
     *
     * Сметка 401 има Дебитно (Dt) салдо
     *
     * 		Намаляваме плащанията към Доставчика с надплатената сума с обратна (revers) операция, със сумата
     * 		на дебитното салдо на с/ка 401 но с отрицателен знак
     *
     * 			Dt: 401 - Задължения към доставчици
     * 			Ct: 6912 - Извънредни разходи по Покупки
     *
     * 		Отнасяме извънредните разходи по сделката като загуба по сметка 123 - Печалби и загуби от текущата година, със сумата на дебитното салдо на с/ка 401
     *
     * 			Dt: 123 - Печалби и загуби от текущата година
     * 			Ct: 6912 - Извънредни разходи по Покупки
     *
     */
    private function getCloseEntry($amount, $totalAmount, $docRec, $firstDoc)
    {
    	$entry = array();
    
    	if($amount == 0) return $entry;
    	 
    	// Сметка 401 има Дебитно (Dt) салдо
    	if($amount > 0){
    		$entry1 = array(
    				'amount' => -1 * $amount,
    				'debit' => array('401',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => currency_Currencies::round($amount / $docRec->currencyRate)),
    				'credit'  => array('6912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    		);
    
    		$entry2 = array(
    				'amount' => $amount,
    				'debit' => array('123', $this->date->year, $this->date->month),
    				'credit'  => array('6912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    		);
    
    		// Сметка 401 има Кредитно (Ct) салдо
    	} elseif($amount < 0){
    		$entry1 = array(
    				'amount' => $amount,
    				'debit'  => array('7912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('401',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => currency_Currencies::round($amount / $docRec->currencyRate)));
    
    		$entry2 = array(
    				'amount' => abs($amount),
    				'debit'  => array('7912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('123', $this->date->year, $this->date->month));
    	}
    	 
    	// Връщане на записа
    	return array($entry1, $entry2);
    }
}