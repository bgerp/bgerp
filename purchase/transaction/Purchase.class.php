<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_Purchases
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class purchase_transaction_Purchase
{
    /**
     * 
     * @var puchase_Purchases
     */
    public $class;
    
    
    /**
     * Систем ид на сметката за авансово плащане
     */
    const DOWNPAYMENT_ACCOUNT_ID = '402';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * Работен кеш
     */
    private static $cache2 = array();
    
    
    /**
     * Генериране на счетоводните транзакции, породени от покупка.
     * 
     * Счетоводната транзакция за породена от документ-покупка може да се раздели на три
     * части:
     *
     * 1. Задължаване на с/ката на клиента за услуга
     *
     *    Dt: 602. Разходи за външни услуги    (Услуга)
     *    
     *    Ct: 401. Задължения към доставчици   (Доставчик, Сделки, Валута)
     * 
     * 2. Засклаждане на стоката в склада (в някой случаи)
     *
     *    Dt: 302. Суровини и материали 	  (Склад, Суровини и Материали) - за вложимите продукти
     *	  	  321. Стоки и Продукти 		  (Склад, Стоки и Продукти) - за всички останали складируеми продукти
     *
     *    Ct: 401. Задължения към доставчици (Доставчик, Сделки, Валути)
     *
     *
     *
     * 3. Получаване на плащане (в някой случаи)
     *
     *    Dt: 501. Каси                  (Каса, Валута)
     *        
     *    Ct: 401. Задължения към доставчици   (Доставчик, Сделки, Валута)
     *
     * @param int|object $id първичен ключ или запис на покупка
     * @return object NULL означава, че документа няма отношение към счетоводството, няма да генерира
     *                счетоводни транзакции
     * @throws core_exception_Expect когато възникне грешка при генерирането на транзакция               
     */
    public function getTransaction($id)
    {
        $entries = array();
        $rec     = $this->class->fetchRec($id);
        $actions = type_Set::toArray($rec->contoActions);
       
        if ($actions['ship'] || $actions['pay']) {
            
            $rec = $this->fetchPurchaseData($rec); // покупката ще контира - нужни са и детайлите
			deals_Helper::fillRecs($this->class, $rec->details, $rec); 
            
            if ($actions['ship']) {
                // Покупката играе роля и на складова разписка.
                // Контирането е същото като при СР
                
                // Записите от тип 1 (вземане от клиент)
                $entries = array_merge($entries, $this->getTakingPart($rec));
                
                $delPart = $this->getDeliveryPart($rec);
                
                if(is_array($delPart)){
                	
                	// Записите от тип 2 (засклаждане)
                	$entries = array_merge($entries, $delPart);
                }
            }
            
            if ($actions['pay']) {
                // покупката играе роля и на платежен документ (РКО)
                // Записите от тип 3 (получаване на плащане)
                $entries = array_merge($entries, $this->getPaymentPart($rec));
            }
        }            
        
        $transaction = (object)array(
            'reason'  => 'Покупка #' . $rec->id,
            'valior'  => $rec->valior,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
    /**
     * Финализиране на транзакцията
     */
    public function finalizeTransaction($id)
    {
        $rec = $this->fetchPurchaseData($id);
		$actions = type_Set::toArray($rec->contoActions);
        
        // Обновяване на кеша (платено)
        if ($actions['pay']) {
            $rec->amountPaid = $rec->amountDeal;
        }

        // Обновяване на кеша (доставено)
        if ($actions['ship']) {
            $rec->amountDelivered = $rec->amountDeal;
        
            foreach ($rec->details as $dRec) {
                $dRec->quantityDelivered = $dRec->quantity;
                purchase_PurchasesDetails::save($dRec, 'id, quantityDelivered');
            }
        }
        
        // Ако има активиран приключващ документ, покупката става затворена иначе е активирана
        $state = (purchase_ClosedDeals::fetch("#threadId = {$rec->threadId} AND #state = 'active'")) ? 'closed' : 'active';
        
        // Активиране и запис
        $rec->state = $state;
        
        if ($this->class->save($rec)) {
            $this->class->invoke('AfterActivation', array($rec));
        }
    }
    
    
    /**
     * Помощен метод за извличане на данните на покупката - мастър + детайли
     * 
     * Детайлите на покупката (продуктите) са записани в полето-масив 'details' на резултата 
     * 
     * @param int|object $id първичен ключ или запис на покупка
     * @param object запис на покупка (@see purchase_Purchases)
     */
    protected function fetchPurchaseData($id)
    {
        $rec = $this->class->fetchRec($id);

        $rec->details  = array();
        
        if (!empty($rec->id)) {
            // Извличаме детайлите на покупката
            $detailQuery = purchase_PurchasesDetails::getQuery();
            $detailQuery->where("#requestId = '{$rec->id}'");
            
            while ($dRec = $detailQuery->fetch()) {
                $rec->details[] = $dRec;
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Генериране на записите от тип за изпълнение на услуги (ако има)
     * 
     *    Dt: 602. Разходи за външни услуги    (Услуга)
     *    
     *    Ct: 401. Задължения към доставчици   (Доставчик, Сделки, Валута)
     *    	  
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getTakingPart($rec)
    {
        $entries = array();
        
        // Покупката съхранява валутата като ISO код; преобразуваме в ПК.
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        
        foreach ($rec->details as $detailRec) {
        	$pInfo = cls::get($detailRec->classId)->getProductInfo($detailRec->productId);
         	if($rec->chargeVat == 'yes'){
	        	$ProductManager = cls::get($detailRec->classId);
	            $vat = $ProductManager->getVat($detailRec->productId, $rec->valior);
	            $amount = $detailRec->amount - ($detailRec->amount * $vat / (1 + $vat));
	        } else {
	        	$amount = $detailRec->amount;
	        }
	        
        	$amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
        	
    		// Ако не е "Складируем" - значи е разход
			if(empty($pInfo->meta['canStore'])){

				// Ако е "Материали" дебит 601, иначе 602
	        	$costsAccNumber = (isset($pInfo->meta['materials'])) ? '601' : '602';

    			$entries[] = array(
	                'amount' => currency_Currencies::round($amount * $rec->currencyRate), // В основна валута
	                
	                'credit' => array(
	                    '401', 
	                        array($rec->contragentClassId, $rec->contragentId),
	                		array('purchase_Purchases', $rec->id),
	                        array('currency_Currencies', $currencyId),          
	                    'quantity' => currency_Currencies::round($amount, $rec->currencyId),
	                ),
	                
	                'debit' => array(
	                    $costsAccNumber, 
	                        array($detailRec->classId, $detailRec->productId),
	                    'quantity' => $detailRec->quantity,
	                ),
            	);
    		}
        }
        
    	if($this->class->_total->vat){
	        $vatAmount = currency_Currencies::round($this->class->_total->vat * $rec->currencyRate);
	        $entries[] = array(
	             'amount' => $vatAmount, // В основна валута
	                
	             'credit' => array(
	                   '401',
	                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
	             			array('purchase_Purchases', $rec->id),				// Перо 2 - Сделки
	                        array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->valior)), // Перо 3 - Валута
	                    'quantity' => $vatAmount, // "брой пари" във валутата на продажбата
	                ),
	                
	                'debit' => array(
	                    '4530', 
	                		array('purchase_Purchases', $rec->id),
	                ),
	         );
        }
	        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира платежната част от транзакцията за покупка (ако има)
     * 
     *    Dt: 501. Каси                  (Каса, Валута)
     *        
     *    Ct: 401. Задължения към доставчици   (Доставчик, Сделки, Валута)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getPaymentPart($rec)
    {
        $entries = array();
    	
    	// покупката съхранява валутата като ISO код; преобразуваме в ПК.
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        expect($rec->caseId, 'Генериране на платежна част при липсваща каса!');
        $amountBase = $quantityAmountBase = 0;
        
        foreach ($rec->details as $detailRec) {
        	$amount = ($detailRec->discount) ?  $detailRec->amount * (1 - $detailRec->discount) : $detailRec->amount;
        	$amountBase += $amount * $rec->currencyRate;
        }
        
        if($rec->chargeVat == 'separate'){
        	$amountBase += $this->class->_total->vat;
        }
        
        $quantityAmountBase += currency_Currencies::round($amountBase, $rec->currencyId);
        
        $entries[] = array(
                'amount' => currency_Currencies::round($amountBase), // В основна валута
                
                'credit' => array(
                    '501', // Сметка "501. Каси"
                        array('cash_Cases', $rec->caseId),         // Перо 1 - Каса
                        array('currency_Currencies', $currencyId), // Перо 2 - Валута
                    'quantity' => $quantityAmountBase, // "брой пари" във валутата на покупката
                ),
                
                'debit' => array(
                    '401', // Сметка "401. Задължения към доставчици"
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                		array('purchase_Purchases', $rec->id),				// Перо 2 - Сделки
                        array('currency_Currencies', $currencyId),          // Перо 3 - Валута
                    'quantity' => $quantityAmountBase, // "брой пари" във валутата на покупката
                ),
            );
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за покупка
     * Вкарване на стоката в склада (в някои случаи)
     * 
     *	  Dt: 302. Суровини и материали 	  (Склад, Суровини и Материали) - за вложимите продукти
     *	  	  321. Стоки и Продукти 		  (Склад, Стоки и Продукти) - за всички останали складируеми продукти
     *
     *    Ct: 401. Задължения към доставчици (Доставчик, Сделки, Валути)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getDeliveryPart($rec)
    {
        $entries = array();
            
        if(empty($rec->shipmentStoreId)){
        	return;
        }
        
        $currencyCode = ($rec->currencyId) ? $rec->currencyId : $this->class->fetchField($rec->id, 'currencyId');
        $currencyId   = currency_Currencies::getIdByCode($currencyCode);
        
        foreach ($rec->details as $detailRec) {
        	$pInfo = cls::get($detailRec->classId)->getProductInfo($detailRec->productId);
       		if($rec->chargeVat == 'yes'){
	        	$ProductManager = cls::get($detailRec->classId);
	            $vat = $ProductManager->getVat($detailRec->productId, $rec->valior);
	            $amount = $detailRec->amount - ($detailRec->amount * $vat / (1 + $vat));
	        } else {
	        	$amount = $detailRec->amount;
	        }
        	$amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
        	
        	// Само складируемите продукти се изписват от склада
        	if(isset($pInfo->meta['canStore'])){
        		
        		// Ако е материал дебит 302 иначе 321
	        	$debitAccId = (isset($pInfo->meta['materials'])) ? '302' : '321';
	        		
	        	$debit = array(
	                  $debitAccId, 
	                       array('store_Stores', $rec->shipmentStoreId), // Перо 1 - Склад
	                       array($detailRec->classId, $detailRec->productId),  // Перо 2 - Артикул
	                  'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
	            );
	        	
	        	$entries[] = array(
	        		 'amount' => currency_Currencies::round($amount * $rec->currencyRate),
	        		 'debit'  => $debit,
		             'credit' => array(
		                   '401', 
	                       array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Доставчик
		             	   array('purchase_Purchases', $rec->id),				// Перо 2 - Сделки
	                       array('currency_Currencies', $currencyId),          // Перо 3 - Валута
	                    'quantity' => currency_Currencies::round($amount, $currencyCode), // "брой пари" във валутата на покупката
		             ),
		        );
        	}
        }
        
        return $entries;
    }
    
    
    /**
     * Връща записите от журнала за това перо
     */
    private static function getEntries($id)
    {
    	// Кешираме записите за перото, ако не са извлечени
    	if(empty(static::$cache[$id])){
    		static::$cache[$id] = acc_Journal::getEntries(array('purchase_Purchases', $id));
    	}
    
    	// Връщане на кешираните записи
    	return static::$cache[$id];
    }
    
    
    /**
     * Чисти работния кеш
     */
    public static function clearCache()
    {
    	static::$cache = NULL;
    }
    
    
    /**
     * Колко е направеното авансово плащане досега
     */
    public static function getDownpayment($jRecs)
    {
    	$jRecs = static::getEntries($jRecs);
    	
    	return acc_Balances::getBlAmounts($jRecs, static::DOWNPAYMENT_ACCOUNT_ID, 'debit')->amount;
    }
    
    
    /**
     * Колко е платеното по сделка
     */
    public static function getPaidAmount($jRecs)
    {
    	$jRecs = static::getEntries($jRecs);
    	
    	$paid = acc_Balances::getBlAmounts($jRecs, '501,503', 'credit')->amount;
    	
    	return $paid;
    }
    
    
    /**
     * Колко е платеното по сделка
     */
    public static function getBlAmount($id)
    {
    	$jRecs = static::getEntries($id);
    
    	$paid = acc_Balances::getBlAmounts($jRecs, '401')->amount;
    
    	return $paid;
    }
    
    
    /**
     * Колко е доставено по сделката
     */
    public static function getDeliveryAmount($jRecs)
    {
    	$jRecs = static::getEntries($jRecs);
    
    	$delivered = acc_Balances::getBlAmounts($jRecs, '401', 'credit')->amount;
    	$delivered -= acc_Balances::getBlAmounts($jRecs, '401', 'credit', '6912')->amount;
    
    	return $delivered;
    }
    
    
    /**
     * Колко е ддс-то за начисляване
     */
    public static function getAmountToInvoice($jRecs)
    {
    	$jRecs = static::getEntries($jRecs);
    
    	return acc_Balances::getBlAmounts($jRecs, '4530')->amount;
    }
    
    
    /**
     * Връща всички експедирани продукти и техните количества по сделката
     */
    public static function getShippedProducts($id)
    {
    	$res = array();
    	$query = purchase_PurchasesDetails::getQuery();
    	$query->where("#requestId = '{$id}'");
    	$query->show('id, productId, classId, quantityDelivered');
    
    	// Намираме всички транзакции с перо сделката
    	$jRecs = self::getEntries($id);
    
    	// Извличаме тези, отнасящи се за експедиране
    	$dInfo = acc_Balances::getBlAmounts($jRecs, '321,302,601,602', 'debit');
    	
    	if(!count($dInfo->recs)) return $res;
    
    	foreach ($dInfo->recs as $p){
    		 
    		// Обикаляме всяко перо
    		foreach (range(1, 3) as $i){
    			if(isset($p->{"debitItem{$i}"})){
    				$itemRec = acc_Items::fetch($p->{"debitItem{$i}"});
    				 
    				// Ако има интерфейса за артикули-пера, го добавяме
    				if(cls::haveInterface('cat_ProductAccRegIntf', $itemRec->classId)){
    					$obj = new stdClass();
    					$obj->classId    = $itemRec->classId;
    					$obj->productId  = $itemRec->objectId;
    					 
    					$index = $obj->classId . "|" . $obj->productId;
    					if(empty($res[$index])){
    						$res[$index] = $obj;
    					}
    					 
    					$res[$index]->amount += $p->amount;
    					$res[$index]->quantity  += $p->debitQuantity;
    				}
    			}
    		}
    	}
    	
    	// Връщаме масив със всички експедирани продукти по тази сделка
    	return $res;
    }
}