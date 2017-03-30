<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_Receipts
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
class store_transaction_Receipt extends acc_DocumentTransactionSource
{
    /**
     * 
     * @var purchase_Purchases
     */
    public $class;
    
    
    /**
     * Генериране на счетоводните транзакции, породени от складова разписка
     * Заприхождаване на артикул: Dt:321
     *	  
     *	  Dt: 321. Суровини, материали, продукция, стоки 	  (Склад, Артикули)
     *
     *    Ct: 401. Задължения към доставчици (Доставчик, Валути)
     *
     * @param int|object $id първичен ключ или запис на покупка
     * @return object NULL означава, че документа няма отношение към счетоводството, няма да генерира
     *                счетоводни транзакции
     * @throws core_exception_Expect когато възникне грешка при генерирането на транзакция               
     */
    public function getTransaction($id)
    {
        $entries = array();
        
        $rec = $this->fetchShipmentData($id);
        
        $origin = $this->class->getOrigin($rec);
        
        // Всяка СР трябва да има поне един детайл
        if (count($rec->details) > 0) {
        	
        	if($rec->isReverse == 'yes'){
        		
        		// Ако СР е обратна, тя прави контировка на ЕН но с отрицателни стойностти
        		$reverseSource = cls::getInterface('acc_TransactionSourceIntf', 'store_ShipmentOrders');
        		$entries = $reverseSource->getReverseEntries($rec, $origin);
        	} else {
        		
        		// Ако СР е права, тя си прави дефолт стойностите
        		$entries = $this->getDeliveryPart($rec, $origin);
        	} 
        }
        
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        
        $transaction = (object)array(
            'reason'  => 'Складова разписка №' . $rec->id,
            'valior'  => $rec->valior,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
    /**
     * Помощен метод за извличане на данните на СР - мастър + детайли
     * 
     * Детайлите на СР (продуктите) са записани в полето-масив 'details' на резултата 
     * 
     * @param int|object $id първичен ключ или запис на СР
     * @param object запис на СР (@see store_Receipts)
     */
    protected function fetchShipmentData($id)
    {
        $rec = $this->class->fetchRec($id);
        
        $rec->details = array();
        
        if (!empty($rec->id)) {
            // Извличаме детайлите на покупката
            $detailQuery = store_ReceiptDetails::getQuery();
            $detailQuery->where("#receiptId = '{$rec->id}'");
            $rec->details  = array();
            
            while ($dRec = $detailQuery->fetch()) {
                $rec->details[] = $dRec;
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за покупка
     * Вкарване на стоката в склада (в някои случаи)
     * 
     *	  Dt: 321. Суровини, материали, продукция, стоки   (Склад, Артикули)
     *
     *    Ct: 401. Задължения към доставчици (Доставчик, Сделки, Валути)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getDeliveryPart($rec, $origin, $reverse = FALSE)
    {
        $entries = array();
        $sign = ($reverse) ? -1 : 1;
        
        expect($rec->storeId, 'Генериране на експедиционна част при липсващ склад!');
        $currencyRate = $rec->currencyRate;
        currency_CurrencyRates::checkRateAndRedirect($currencyRate);
        $currencyCode = ($rec->currencyId) ? $rec->currencyId : $this->class->fetchField($rec->id, 'currencyId');
        $currencyId   = currency_Currencies::getIdByCode($currencyCode);
        deals_Helper::fillRecs($this->class, $rec->details, $rec, array('alwaysHideVat' => TRUE));
        
        foreach ($rec->details as $detailRec) {
        	$pInfo = cat_Products::getProductInfo($detailRec->productId);
        	$amount = $detailRec->amount;
        	$amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
        	$amount = round($amount, 2);
        	
        	$debitAccId = '321';
        		
        	$debit = array(
                  $debitAccId, 
                       array('store_Stores', $rec->storeId), // Перо 1 - Склад
                       array('cat_Products', $detailRec->productId),  // Перо 2 - Артикул
                  'quantity' => $sign * $detailRec->quantity, // Количество продукт в основната му мярка
            );
        	
        	$entries[] = array(
        		 'amount' => $sign * $amount * $rec->currencyRate,
        		 'debit'  => $debit,
	             'credit' => array(
	                   $rec->accountId, 
                       array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Доставчик
	             	   array($origin->className, $origin->that),		   // Перо 2 - Сделка
                       array('currency_Currencies', $currencyId),          // Перо 3 - Валута
                    'quantity' => $sign * $amount, // "брой пари" във валутата на покупката
	             ),
	        );
        }
        
    	if($this->class->_total->vat){
    		$vat = $this->class->_total->vat;
        	$vatAmount = $this->class->_total->vat * $currencyRate;
        	$entries[] = array(
                'amount' => $sign * $vatAmount, // В основна валута
                
                'credit' => array(
                    $rec->accountId,
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                		array($origin->className, $origin->that),			// Перо 2 - Сделка
                        array('currency_Currencies', $currencyId), // Перо 3 - Валута
                    'quantity' => $sign * $vat, // "брой пари" във валутата на продажбата
                ),
                
                'debit' => array(
                    '4530',
                		array($origin->className, $origin->that),
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
    	$entries = $this->getDeliveryPart($rec, $origin, TRUE);
    	 
    	return $entries;
    }
}