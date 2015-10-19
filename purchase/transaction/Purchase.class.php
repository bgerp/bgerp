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
class purchase_transaction_Purchase extends acc_DocumentTransactionSource
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
     *    Dt: 321. Суровини, материали, продукция, стоки (Склад, Артикули)
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
			deals_Helper::fillRecs($this->class, $rec->details, $rec, array('alwaysHideVat' => TRUE)); 
            
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
     *    Dt: 60020. Разходи за (нескладируеми) услуги и консумативи    (Центрове на дейност, Артикули)
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
        	$pInfo = cat_Products::getProductInfo($detailRec->productId);
        	$transfer = FALSE;
        	
        	$amount = $detailRec->amount;
        	$amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
        	$amount = round($amount, 2);
        	
        	// Ако не е "Складируем" - значи е разход
			if(empty($pInfo->meta['canStore'])){

				if(isset($pInfo->meta['fixedAsset'])){
					$reason = 'Приети ДА';
					$debitArr = array('613', array('cat_Products', $detailRec->productId),
											'quantity' => $detailRec->quantity,);
				} else {
					$transfer = TRUE;
					$centerId = ($rec->activityCenterId) ? $rec->activityCenterId : hr_Departments::fetchField("#systemId = 'emptyCenter'", 'id');
					
					$reason = 'Приети услуги и нескладируеми консумативи';
					$debitArr = array(
							'60020', // Сметка "60020. Разходи за (нескладируеми) услуги и консумативи"
							array('hr_Departments', $centerId),
							array('cat_Products', $detailRec->productId), // Перо 1 - Артикул
							'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
					);
				}

    			$entries[] = array(
	                'amount' => $amount * $rec->currencyRate, // В основна валута
	                
	                'credit' => array(
	                    '401', 
	                        array($rec->contragentClassId, $rec->contragentId),
	                		array('purchase_Purchases', $rec->id),
	                        array('currency_Currencies', $currencyId),          
	                    'quantity' => $amount,
	                ),
	                
	                'debit' => $debitArr,
    				'reason' => $reason,
            	);
    			
    			// Ако сме дебитирали 60020, превхвърляме сумата в 61101 или 61102
    			if($transfer === TRUE){
    				
    				$pInfo = cat_Products::getProductInfo($detailRec->productId);
    				if(isset($pInfo->meta['canConvert'])){
    					$newArr = array('61101', array('cat_Products', $detailRec->productId),
    							'quantity' => $detailRec->quantity);
    				} else {
    					$newArr = array('61102');
    				}
    			
    				$entries[] = array(
    						'amount' => $amount * $rec->currencyRate, // В основна валута
    						'debit' => $newArr,
    						'credit' => $debitArr, 
    						'reason' => 'Вложени в производството нескладируеми услуги и консумативи',
    				);
    			}
    		}
        }
        
        // Отчитаме ддс-то
    	if($this->class->_total->vat){
	        $vatAmount = $this->class->_total->vat * $rec->currencyRate;
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
	        		'reason' => 'ДДС за начисляване при фактуриране',
	         );
        }
	        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира платежната част от транзакцията за покупка (ако има)
     * 
     * Ако валутата е основната за сч. период
     * 
     *    Dt: 401. Задължения към доставчици   (Доставчик, Сделки, Валута)
     *    Ct: 501. Каси                  	   (Каса, Валута)
     *    
     * Ако валутата е различна от основната за сч. период
     * 
     *    Dt: 401. Задължения към доставчици   (Доставчик, Сделки, Валута)
     *    Ct: 481. Разчети по курсови разлики  (Валута)
     *    
     *    Dt: 481. Разчети по курсови разлики  (Валута)
     *    Ct: 501. Каси   					   (Каса, Валута)
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
        $amountBase = $quantityAmount = 0;
        
        foreach ($rec->details as $detailRec) {
        	$amount = ($detailRec->discount) ?  $detailRec->amount * (1 - $detailRec->discount) : $detailRec->amount;
        	$amount = round($amount, 2);
        	$amountBase += $amount;
        }
        
        if($rec->chargeVat == 'separate' || $rec->chargeVat == 'yes'){
        	$amountBase += $this->class->_total->vat;
        }
        
        $quantityAmount += $amountBase;
        
        $caseArr = array('501',
                        array('cash_Cases', $rec->caseId),        
                        array('currency_Currencies', $currencyId),
                    'quantity' => $quantityAmount,);
        
        $dealArr = array('401',
        				array($rec->contragentClassId, $rec->contragentId),
                		array('purchase_Purchases', $rec->id),
                        array('currency_Currencies', $currencyId),
                    'quantity' => $quantityAmount,);
        
        if($rec->currencyId == acc_Periods::getBaseCurrencyCode($rec->valior)){
        	$entries[] = array('amount' => $amountBase, 'debit' => $dealArr, 'credit' => $caseArr, 'reason' => 'Плащане към доставчик');
        } else {
        	$entries = array();
        	$entries[] = array('amount' => $amountBase * $rec->currencyRate,
        			'debit' => $dealArr,
        			'credit' => array('481', array('currency_Currencies', $currencyId),
        					'quantity' => $quantityAmount), 'reason' => 'Плащане във валута различна от основната');
        	$entries[] = array('amount' => $amountBase * $rec->currencyRate, 'debit' => array('481', array('currency_Currencies', $currencyId), 'quantity' => $quantityAmount), 'credit' => $caseArr, 'reason' => 'Плащане във валута различна от основната');
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за покупка
     * Вкарване на стоката в склада (в някои случаи)
     * 
     *	  Dt: 321. Суровини, материали, продукция, стоки 	  (Склад, Суровини и Материали)
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
        	$pInfo = cat_Products::getProductInfo($detailRec->productId);
        	$amount = round($detailRec->amount, 2);
        	$amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
        	
        	// Само складируемите продукти се изписват от склада
        	if(isset($pInfo->meta['canStore'])){
        		
	        	$debitAccId = '321';
	        		
	        	$debit = array(
	                  $debitAccId, 
	                       array('store_Stores', $rec->shipmentStoreId), // Перо 1 - Склад
	                       array('cat_Products', $detailRec->productId),  // Перо 2 - Артикул
	                  'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
	            );
	        	
	        	$entries[] = array(
	        		 'amount' => $amount * $rec->currencyRate,
	        		 'debit'  => $debit,
		             'credit' => array(
		                   '401', 
	                       array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Доставчик
		             	   array('purchase_Purchases', $rec->id),				// Перо 2 - Сделки
	                       array('currency_Currencies', $currencyId),          // Перо 3 - Валута
	                    'quantity' => $amount, // "брой пари" във валутата на покупката
		             ),
	        		'reason' => 'Заскладени материални запаси',
		        );
        	}
        }
        
        return $entries;
    }
    
    
    /**
     * Връща записите от журнала за това перо
     */
    protected static function getEntries($id)
    {
    	// Кешираме записите за перото, ако не са извлечени
    	if(empty(self::$cache[$id])){
    		self::$cache[$id] = acc_Journal::getEntries(array('purchase_Purchases', $id));
    	}
    
    	// Връщане на кешираните записи
    	return self::$cache[$id];
    }
    
    
    /**
     * Чисти работния кеш
     */
    public static function clearCache()
    {
    	self::$cache = NULL;
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
    	
    	$paid = acc_Balances::getBlAmounts($jRecs, '501,503,481', 'credit')->amount;
    	
    	return $paid;
    }
    
    
    /**
     * Колко е платеното по сделка
     */
    public static function getBlAmount($id)
    {
    	$jRecs = static::getEntries($id);
    	
    	$paid = acc_Balances::getBlAmounts($jRecs, '401')->amount;
    	$paid += acc_Balances::getBlAmounts($jRecs, '402')->amount;
    	
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
    public static function getShippedProducts($id, $accs = '321,302,601,602,60010,60020', $groupByStore = FALSE)
    {
    	$res = array();
    
    	// Намираме всички транзакции с перо сделката
    	$jRecs = self::getEntries($id);
    
    	// Извличаме тези, отнасящи се за експедиране
    	$dInfo = acc_Balances::getBlAmounts($jRecs, $accs, 'debit');
    	
    	if(!count($dInfo->recs)) return $res;
    
    	foreach ($dInfo->recs as $p){
    	
    		// Обикаляме всяко перо
    		foreach (range(1, 3) as $i){
    			if(isset($p->{"debitItem{$i}"})){
    				$itemRec = acc_Items::fetch($p->{"debitItem{$i}"});
    				 
    				// Ако има интерфейса за артикули-пера, го добавяме
    				if(cls::haveInterface('cat_ProductAccRegIntf', $itemRec->classId)){
    					$obj = new stdClass();
    					$obj->productId  = $itemRec->objectId;
    					 
    					$index = $obj->productId;
    					if(empty($res[$index])){
    						$res[$index] = $obj;
    					}
    					 
    					$res[$index]->amount += $p->amount;
    					$res[$index]->quantity  += $p->debitQuantity;
    					
    					if($groupByStore === TRUE){
    						$storePositionId = acc_Lists::getPosition(acc_Accounts::fetchField($p->debitAccId, 'systemId'), 'store_AccRegIntf');
    						$storeItem = acc_Items::fetch($p->{"debitItem{$storePositionId}"});
    						$res[$index]->inStores[$storeItem->objectId] += $p->debitQuantity;
    					}
    				}
    			}
    		}
    	}
    	
    	foreach ($res as &$r){
    		$r->price = $r->amount / $r->quantity;
    	}
    	
    	// Връщаме масив със всички експедирани продукти по тази сделка
    	return $res;
    }
}