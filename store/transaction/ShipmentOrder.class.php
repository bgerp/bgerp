<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_ShipmentOrders
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class store_transaction_ShipmentOrder extends acc_DocumentTransactionSource
{
    /**
     * 
     * @var sales_Sales
     */
    public $class;
    
    
    /**
     * Генериране на счетоводните транзакции, породени от експедиционно нареждане.
     * 
     * Счетоводната транзакция, породена от експедиционно нареждане може да се раздели на две
     * части:
     *
     * 1. Задължаване на с/ката на клиента
     *
     *    Dt: 411    - Вземания от клиенти                        (Клиент, Валута)
     *    
     *    Ct: 701 - Приходи от продажби на Стоки и Продукти     (Клиент, Стоки и Продукти)
     *    	  708 - Приходи от продажби на Суровини и Материали (Клиент, Стоки и Продукти)
     * 
     * 2. Експедиране на стоката от склада
     *
     *    Dt: 701 - Приходи от продажби на Стоки и Продукти (Клиент, Стоки и Продукти)
     *    
     *    Ct: 321  - Суровини, материали, продукция, стоки  (Склад, Артикули)
     * 
     *
     * @param int|object $id първичен ключ или запис на продажба
     * @return object NULL означава, че документа няма отношение към счетоводството, няма да генерира
     *                счетоводни транзакции
     * @throws core_exception_Expect когато възникне грешка при генерирането на транзакция               
     */
    public function getTransaction($id)
    {
        $entries = array();
        
        $rec = $this->fetchShipmentData($id, $error);
            
        if(Mode::get('saveTransaction')){
        	if(count($error)){
        		acc_journal_RejectRedirect::expect(FALSE, "Всички редове трябва да имат положително количество|*!");
        	}
        }
        
        $origin = $this->class->getOrigin($rec);
        
        // Всяко ЕН трябва да има поне един детайл
        if (count($rec->details) > 0) {
            
        	if($rec->isReverse == 'yes'){
        		
        		// Ако ЕН е обратна, тя прави контировка на СР но с отрицателни стойностти
        		$reverseSource = cls::getInterface('acc_TransactionSourceIntf', 'store_Receipts');
        		$entries = $reverseSource->getReverseEntries($rec, $origin);
        	} else {
        		// Записите от тип 1 (вземане от клиент)
        		$entries = $this->getEntries($rec, $origin);
        	}
        }
       
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        
        $transaction = (object)array(
            'reason'  => 'Експедиционно нареждане №' . $rec->id,
            'valior'  => $rec->valior,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
    /**
     * Връща ентритата на транзакцията
     */
    private function getEntries($rec, $origin, $reverse = FALSE)
    {
    	// Записите от тип 1 (вземане от клиент)
    	$entries = $this->getTakingPart($rec, $origin, $reverse);
    	
    	// Записите от тип 2 (експедиция)
    	
    	$entries = array_merge($entries, $this->getDeliveryPart($rec, $origin, $reverse));
    	
    	return $entries;
    }
    
    
    /**
     * Помощен метод за извличане на данните на ЕН - мастър + детайли
     * 
     * Детайлите на ЕН (продуктите) са записани в полето-масив 'details' на резултата 
     * 
     * @param int|object $id първичен ключ или запис на ЕН
     * @param object запис на ЕН (@see store_ShipmentOrders)
     */
    protected function fetchShipmentData($id, &$error = array())
    {
        $rec = $this->class->fetchRec($id);
        
        $rec->details = array();
        
        if (!empty($rec->id)) {
            // Извличаме детайлите на продажбата
            $detailQuery = store_ShipmentOrderDetails::getQuery();
            $detailQuery->where("#shipmentId = '{$rec->id}'");
            $rec->details  = array();
            
            while ($dRec = $detailQuery->fetch()) {
                $rec->details[] = $dRec;
                if(empty($dRec->quantity)){
                	$error[] = cat_Products::getTitleById($dRec->productId);
                }
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     * 
     *    Dt: 411  - Вземания от клиенти               (Клиент, Сделка, Валута)
     *    
     *    Ct: 701  - Приходи от продажби на Стоки и Продукти  (Клиенти, Сделка, Стоки и Продукти)
     *    	  706  - Приходи от продажба на Суровини и Материали (Клиенти, Сделка, Суровини и материали)
     * 
     * ДДС за начисляване
     * 
     *    Dt: 411. Вземания от клиенти                   (Клиент, Сделки, Валута)
     *    
     *    Ct: 4530 - ДДС за начисляване
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getTakingPart($rec, $origin, $reverse = FALSE)
    {
        $entries = array();
        $sign = ($reverse) ? -1 : 1;
       
        // Изчисляваме курса на валутата на продажбата към базовата валута
        $currencyRate = $rec->currencyRate;
        $currencyCode = ($rec->currencyId) ? $rec->currencyId : $this->class->fetchField($rec->id, 'currencyId');
        $currencyId   = currency_Currencies::getIdByCode($currencyCode);
        deals_Helper::fillRecs($this->class, $rec->details, $rec, array('alwaysHideVat' => TRUE));
        
        foreach ($rec->details as $detailRec) {
        	$amount = $detailRec->amount;
        	$amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
        	$amount = round($amount, 2);
        	
        	$pInfo = cat_Products::getProductInfo($detailRec->productId, $detailRec->packagingId);
        	
        	// Вложимите кредит 706, другите 701
        	$creditAccId = '701';
            
        	$entries[] = array(
                'amount' => $sign * $amount * $currencyRate, // В основна валута
                
                'debit' => array(
                    $rec->accountId,
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                		array($origin->className, $origin->that),			// Перо 2 - Сделка
                        array('currency_Currencies', $currencyId),     		// Перо 3 - Валута
                    'quantity' => $sign * $amount, // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                     $creditAccId, 
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                		array($origin->className, $origin->that),			// Перо 2 - Сделка
                    	array('cat_Products', $detailRec->productId), // Перо 3 - Артикул
                    'quantity' => $sign * $detailRec->quantity, // Количество продукт в основната му мярка
                ),
            );
        }
        
        if($this->class->_total->vat){
        	$vat = $this->class->_total->vat;
        	$vatAmount = $this->class->_total->vat * $currencyRate;
        	$entries[] = array(
                'amount' => $sign * $vatAmount, // В основна валута
                
                'debit' => array(
                    $rec->accountId,
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                		array($origin->className, $origin->that),			// Перо 2 - Сделка
                        array('currency_Currencies', $currencyId), // Перо 3 - Валута
                    'quantity' => $sign * $vat, // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    '4530',
                		array($origin->className, $origin->that),
                ),
            );
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за продажба (ако има)
     * 
     * Експедиране на стоката от склада
     *
     *    Dt: 701 - Приходи от продажби на Стоки и Продукти 	(Клиент, Сделка, Стоки и Продукти) 
     *    	  706 - Приходи от продажба на Суровини и материали (Клиент, Сделка, Суровини и материали)
     *    
     *    Ct: 321  - Суровини, материали, продукция, стоки      (Склад, Стоки и Продукти)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getDeliveryPart($rec, $origin, $reverse = FALSE)
    {
        $entries = array();
        
        $sign = ($reverse) ? -1 : 1;
        $rec->storeId = ($rec->storeId) ? $rec->storeId : $this->class->fetchField($rec->id, 'storeId');
        acc_journal_Exception::expect($rec->storeId, 'Генериране на експедиционна част при липсващ склад!');
        
        foreach ($rec->details as $detailRec) {
        	$pInfo = cat_Products::getProductInfo($detailRec->productId, $detailRec->packagingId);
        	
        	// Вложимите кредит 706, другите 701
        	$debitAccId = (isset($pInfo->meta['materials'])) ? '706' : '701';
        	$creditAccId = '321';
        	
        	$entries[] = array(
	             'debit' => array(
	                    $debitAccId, 
	                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
	             			array($origin->className, $origin->that),			// Перо 2 - Сделка
        					array('cat_Products', $detailRec->productId), // Перо 3 - Продукт
	                    'quantity' => $sign * $detailRec->quantity, // Количество продукт в основна мярка
	                ),
	                
	                'credit' => array(
	                    $creditAccId, 
	                        array('store_Stores', $rec->storeId), // Перо 1 - Склад
	                        array('cat_Products', $detailRec->productId), // Перо 2 - Продукт
	                    'quantity' => $sign * $detailRec->quantity, // Количество продукт в основна мярка
	                ),
	       );
        }
        
        return $entries;
    }
    
    
    /**
     * Връща обратна контировка на стандартната
     */
    public function getReverseEntries($rec, $origin)
    {
    	return $this->getEntries($rec, $origin, TRUE);
    }
}