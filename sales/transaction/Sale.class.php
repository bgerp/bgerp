<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа sales_Sales
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
class sales_transaction_Sale extends acc_DocumentTransactionSource
{
    /**
     * 
     * @var sales_Sales
     */
    public $class;
    
    
    /**
     * Систем ид на сметката за авансово плащане
     */
    const DOWNPAYMENT_ACCOUNT_ID = '412';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * Генериране на счетоводните транзакции, породени от продажба.
     * 
     * Счетоводната транзакция за породена от документ-продажба може да се раздели на три
     * части:
     *
     * 1. Задължаване на с/ката на клиента
     *
     *    Dt: 411. Вземания от клиенти  (Клиент, Сделка, Валута)
     *    
     *    Ct: 701. Приходи от продажби на Стоки и Продукти       (Клиент, Сделка, Стоки и Продукти)
     *    	  703. Приходи от продажби на услуги                 (Клиент, Сделка, Услуга)
     *    	  706. Приходи от продажби на Суровини и Материали   (Клиент, Сделка, Суровини и Материали)
     * 
     * 
     * 2. Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 701. Приходи от продажби на Стоки и Продукти  (Клиент, Сделка, Стоки и Продукти)
     *    
     *    Ct: 321. Суровини, материали, продукция, стоки       (Склад, Артикули)
     *
     *
     *
     * 3. Получаване на плащане (в някой случаи)
     *
     *    Dt: 501. Каси                  (Каса, Валута)
     *        503. Разпл. с/ки           (Сметка, Валута)
     *        
     *    Ct: 411. Вземания от клиенти   (Клиент, Сделка, Валута)
     *    
     * Такава транзакция се записва в журнала само при условие, че продабата е от текущата каса
     * и от текущия склад. В противен случай счетоводна транзакция не се прави. Вместо това,
     * първите две части се осчетоводяват при експедирането на стоката, а третата - при получа-
     * ване на плащане.
     *
     * @param int|object $id първичен ключ или запис на продажба
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
            
            $rec = $this->fetchSaleData($rec); // Продажбата ще контира - нужни са и детайлите
			deals_Helper::fillRecs($this->class, $rec->details, $rec, array('alwaysHideVat' => TRUE));
            
            if ($actions['ship']) {
                // Продажбата играе роля и на експедиционно нареждане.
                // Контирането е същото като при ЕН
                
                // Записите от тип 1 (вземане от клиент)
                $entries = array_merge($entries, $this->getTakingPart($rec));
                
                $delPart = $this->getDeliveryPart($rec);
                
                if(is_array($delPart)){
                	
                	// Записите от тип 2 (експедиция)
                	$entries = array_merge($entries, $delPart);
                }
            }
            
            if ($actions['pay']) {
                // Продажбата играе роля и на платежен документ (ПКО)
                // Записите от тип 3 (получаване на плащане)
                $entries = array_merge($entries, $this->getPaymentPart($rec));
            }
        }            
        
        $transaction = (object)array(
            'reason'  => 'Продажба #' . $rec->id,
            'valior'  => $rec->valior,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
    /**
     * Помощен метод за извличане на данните на продажбата - мастър + детайли
     * 
     * Детайлите на продажбата (продуктите) са записани в полето-масив 'details' на резултата 
     * 
     * @param int|object $id първичен ключ или запис на продажба
     * @param object запис на продажба (@see sales_Sales)
     */
    protected function fetchSaleData($id)
    {
        $rec = $this->class->fetchRec($id);

        $rec->details  = array();
        
        if (!empty($rec->id)) {
            // Извличаме детайлите на продажбата
            $detailQuery = sales_SalesDetails::getQuery();
            $detailQuery->where("#saleId = '{$rec->id}'");
            
            while ($dRec = $detailQuery->fetch()) {
                $rec->details[] = $dRec;
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     * 
     *    Dt: 411. Вземания от клиенти                   (Клиент, Сделка, Валута)
     *    
     *    Ct: 701. Приходи от продажби към Контрагенти   (Клиент, Сделка, Стоки и Продукти)
     *    	  703. Приходи от продажби на услуги         (Клиент, Сделка, Услуга)
     *    
     * ДДС за начисляване
     * 
     *    Dt: 411. Вземания от клиенти                   (Клиент, Сделка, Валута)
     *    
     *    Ct: 4530 - ДДС за начисляване
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getTakingPart($rec)
    {
        $entries = array();
        
        // Продажбата съхранява валутата като ISO код; преобразуваме в ПК.
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
       
        foreach ($rec->details as $detailRec) {
        	$pInfo = cat_Products::getProductInfo($detailRec->productId);
        	
    		$storable = isset($pInfo->meta['canStore']);
    		
    		// Нескладируемите продукти дебит 703. Складируемите и вложими 706 останалите 701
    		$creditAccId = ($storable) ? '701' : '703';
        	
    		$amount = $detailRec->amount;
        	$amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
        	$amount = round($amount, 2);
        	
        	$entries[] = array(
                'amount' => $amount * $rec->currencyRate, // В основна валута
                
                'debit' => array(
                    '411', 
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                		array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
                        array('currency_Currencies', $currencyId),          // Перо 3 - Валута
                    'quantity' => $amount, // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    $creditAccId,
                    	array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                		array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
                        array('cat_Products', $detailRec->productId), // Перо 3 - Продукт
                    'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
                ),
            );
        }
        
     	if($this->class->_total->vat){
     		$vat = $this->class->_total->vat;
        	$vatAmount = $this->class->_total->vat * $rec->currencyRate;
        	$entries[] = array(
                'amount' => $vatAmount, // В основна валута
                
                'debit' => array(
                    '411',
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                		array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
                        array('currency_Currencies', $currencyId), // Перо 3 - Валута
                    'quantity' => $vat, // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    '4530', 
                		array('sales_Sales', $rec->id),
                ),
            );
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира платежната част от транзакцията за продажба (ако има)
     * 
     *    Dt: 501. Каси                  (Каса, Валута)
     *        
     *    Ct: 411. Вземания от клиенти   (Клиент, Сделки, Валута)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getPaymentPart($rec)
    {
        $entries = array();
        
        // Продажбата съхранява валутата като ISO код; преобразуваме в ПК.
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
        
        $entries[] = array(
                'amount' => $amountBase  * $rec->currencyRate, // В основна валута
                
                'debit' => array(
                    '501', // Сметка "501. Каси"
                        array('cash_Cases', $rec->caseId),         // Перо 1 - Каса
                        array('currency_Currencies', $currencyId), // Перо 2 - Валута
                    'quantity' => $quantityAmount, // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    '411', // Сметка "411. Вземания от клиенти"
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                		array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
                        array('currency_Currencies', $currencyId),          // Перо 3 - Валута
                    'quantity' => $quantityAmount, // "брой пари" във валутата на продажбата
                ),
            );
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за продажба (ако има)
     * 
     * Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 701. Приходи от продажби на Стоки и Продукти    (Клиент, Сделки, Стоки и Продукти)
     *    	  706 - Приходи от продажба на Суровини и материали (Клиент, Сделки, Суровини и материали)
     *    
     *    Ct: 321. Суровини, материали, продукция, стоки (Склад, Стоки и Продукти)
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
        
        foreach ($rec->details as $detailRec) {
        	$pInfo = cat_Products::getProductInfo($detailRec->productId);
    		
        	// Само складируемите продукти се изписват от склада
        	if(isset($pInfo->meta['canStore'])){
        		$creditAccId = '321';
        		$debitAccId = '701';
        		
        		$entries[] = array(
	                'debit' => array(
	                    $debitAccId,
	                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
	                		array('sales_Sales', $rec->id), 					// Перо 2 - Сделки
        					array('cat_Products', $detailRec->productId), // Перо 3 - Продукт
	                    'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
	                ),
	                
	                'credit' => array(
	                    $creditAccId,
	                        array('store_Stores', $rec->shipmentStoreId), // Перо 1 - Склад
	                        array('cat_Products', $detailRec->productId), // Перо 2 - Продукт
	                    'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
	                ),
	            );
        	}
        }
        
        return $entries;
    }
    
    
    /**
     * Връща всички експедирани продукти и техните количества по сделката
     */
    public static function getShippedProducts($jRecs, $accs = '703,706,701')
    {
    	$res = array();
        
        // Извличаме тези, отнасящи се за експедиране
        $dInfo = acc_Balances::getBlAmounts($jRecs, $accs, 'credit');
        
        if(!count($dInfo->recs)) return $res;
        
        foreach ($dInfo->recs as $p){
        	
	         // Обикаляме всяко перо
	         foreach (range(1, 3) as $i){
	         	if(isset($p->{"creditItem{$i}"})){
	         		$itemRec = acc_Items::fetch($p->{"creditItem{$i}"});
	         		 
	         		// Ако има интерфейса за артикули-пера, го добавяме
	         		if(cls::haveInterface('cat_ProductAccRegIntf', $itemRec->classId)){
	         			$obj = new stdClass();
	         			$obj->productId  = $itemRec->objectId;
	         			
	         			$index = $obj->productId;
	         			if(empty($res[$index])){
	         				$res[$index] = $obj;
	         			}
	         			
	         			$res[$index]->amount += $p->amount;
	         			$res[$index]->quantity  += $p->creditQuantity;
	         		}
	         	}
	        }
    	}
    	
    	foreach ($res as &$r){
    		if($r->quantity){
    			$r->price = $r->amount / $r->quantity;
    		}
    	}
    	
    	// Връщаме масив със всички експедирани продукти по тази сделка
    	return $res;
	}
	
	
	/**
	 * Връща записите от журнала за това перо
	 */
	public static function getEntries($id)
	{
		return acc_Journal::getEntries(array('sales_Sales', $id));
	}
	
	
	/**
	 * Чисти работния кеш
	 */
	public static function clearCache()
	{
		self::$cache = array();
	}
	
	
	/**
	 * Колко е направеното авансово плащане досега
	 */
	public static function getDownpayment($jRecs)
	{
		return acc_Balances::getBlAmounts($jRecs, static::DOWNPAYMENT_ACCOUNT_ID, 'credit')->amount;
	}
	
	
	/**
	 * Колко е платеното по сделка
	 */
	public static function getPaidAmount($jRecs, $rec)
	{
		// Взимаме количествата по валути 
		$quantities = acc_Balances::getBlQuantities($jRecs, '411,412', 'credit', '501,503');
		$res = deals_Helper::convertJournalCurrencies($quantities, $rec->currencyId, $rec->valior);
		 
		// К-то платено във валутата на сделката го обръщаме в основна валута за изравнявания
		$amount = $res->quantity;
		$amount *= $rec->currencyRate;
		
		return $amount;
	}
	
	
	/**
	 * Колко е платеното по сделка
	 */
	public static function getBlAmount($jRecs, $id)
	{
		$itemRec = acc_Items::fetchItem('sales_Sales', $id);
		$paid = acc_Balances::getBlAmounts($jRecs, '411', NULL, NULL, array(NULL, $itemRec->id, NULL))->amount;
		
		$paid += acc_Balances::getBlAmounts($jRecs, '412', NULL, NULL, array(NULL, $itemRec->id, NULL))->amount;
		
		return $paid;
	}
	
	
	/**
	 * Колко е доставено по сделката
	 */
	public static function getDeliveryAmount($jRecs, $id)
	{
		$itemId = acc_Items::fetchItem('sales_Sales', $id)->id;
		$delivered = acc_Balances::getBlAmounts($jRecs, '411', 'debit', NULL, array(NULL, $itemId, NULL))->amount;
		$delivered -= acc_Balances::getBlAmounts($jRecs, '411', 'debit', '7911')->amount;
		
		return $delivered;
	}
	
	
	/**
	 * Колко е ддс-то за начисляване
	 */
	public static function getAmountToInvoice($jRecs)
	{
		return -1 * acc_Balances::getBlAmounts($jRecs, '4530')->amount;
	}
}