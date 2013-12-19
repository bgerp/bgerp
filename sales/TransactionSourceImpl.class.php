<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа sales_Sales
 *
 * @category  bgerp
 * @package   sales
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class sales_TransactionSourceImpl
{
    /**
     * 
     * @var sales_Sales
     */
    public $class;
    
    
    /**
     * Генериране на счетоводните транзакции, породени от продажба.
     * 
     * Счетоводната транзакция за породена от документ-продажба може да се раздели на три
     * части:
     *
     * 1. Задължаване на с/ката на клиента
     *
     *    Dt: 411. Вземания от клиенти  (Клиент, Валута)
     *    
     *    Ct: 701. Приходи от продажби на Стоки и Продукти       (Стоки и Продукти)
     *    	  703. Приходи от продажби на услуги                 (Клиент, Услуга)
     *    	  706. Приходи от продажби на Суровини и Материали   (Клиент, Суровини и Материали)
     * 
     * 
     * 2. Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 701. Приходи от продажби на Стоки и Продукти  (Стоки и Продукти)
     *    
     *    Ct: 321. Стоки и Продукти       (Склад, Стоки и Продукти)
     *    	  302. Суровини и Материали   (Склад, Суровини и Материали)
     *
     *
     *
     * 3. Получаване на плащане (в някой случаи)
     *
     *    Dt: 501. Каси                  (Каса, Валута)
     *        503. Разпл. с/ки           (Сметка, Валута)
     *        
     *    Ct: 411. Вземания от клиенти   (Клиент, Валута)
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
        
        $hasDeliveryPart = $this->hasDeliveryPart($rec);
        $hasPaymentPart  = $this->hasPaymentPart($rec);
        
        if ($hasDeliveryPart || $hasPaymentPart) {
            
            $rec = $this->fetchSaleData($rec); // Продажбата ще контира - нужни са и детайлите

            if ($hasDeliveryPart) {
                // Продажбата играе роля и на експедиционно нареждане.
                // Контирането е същото като при ЕН
                
                // Записите от тип 1 (вземане от клиент)
                $entries = array_merge($entries, $this->getTakingPart($rec));
                
                // Записите от тип 2 (експедиция)
                $entries = array_merge($entries, $this->getDeliveryPart($rec));
                
            }
            
            if ($hasPaymentPart) {
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
     * Финализиране на транзакцията
     */
    public function finalizeTransaction($id)
    {
        $rec = $this->class->fetchRec($id);

        // Обновяване на кеша (платено)
        if ($this->hasPaymentPart($rec)) {
            $rec->amountPaid = $rec->amountDeal;
        }

        // Обновяване на кеша (доставено)
        if ($this->hasDeliveryPart($rec)) {
            $rec->amountDelivered = $rec->amountDeal;
            
            // Извличане на детайлите на продажбата
            $SalesDetails = cls::get('sales_SalesDetails');
        
            $detailQuery = $SalesDetails->getQuery();
            $detailQuery->where("#saleId = '{$rec->id}'");
            $detailQuery->show('id, quantity');
        
            while ($dRec = $detailQuery->fetch()) {
                $dRec->quantityDelivered = $dRec->quantity;
                $SalesDetails->save_($dRec, 'id, quantityDelivered');
            }
        }
        
        // Активиране и запис
        $rec->state = 'active';
        
        if ($this->class->save($rec)) {
            $this->class->invoke('Activation', array($rec));
        }
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
     * Ще има ли транзакцията записи от тип 2 (експедиция)?
     * 
     * @param stdClass $rec
     * @return boolean
     */
    protected function hasDeliveryPart($rec)
    {
        // има ли зададен склад?
        if (empty($rec->shipmentStoreId)) {
            // няма зададен склад
            return FALSE;
        }
        
        return $rec->isInstantShipment == 'yes';
    }
    
    
    /**
     * Ще има ли транзакцията записи от тип 3 (плащане)?
     *
     * @param stdClass $rec
     * @return boolean
     */
    protected function hasPaymentPart($rec)
    {
        // Плащане в брой?
        if ($rec->paymentMethodId && cond_PaymentMethods::fetchField($rec->paymentMethodId, 'name') != 'COD') {
            // Не е плащане в брой
            return FALSE;
        }
        
        return $rec->isInstantPayment == 'yes';
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     * 
     *    Dt: 411. Вземания от клиенти                   (Клиент, Валута)
     *    
     *    Ct: 701. Приходи от продажби към Контрагенти   (Клиент, Стоки и Продукти)
     *    	  703. Приходи от продажби на услуги         (Клиент, Услуга)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getTakingPart($rec)
    {
        $entries = array();
        
        // Продажбата съхранява валутата като ISO код; преобразуваме в ПК.
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        price_Helper::fillRecs($rec->details, $rec);
        
        foreach ($rec->details as $detailRec) {
        	$pInfo = cls::get($detailRec->classId)->getProductInfo($detailRec->productId);
        	
    		$storable = isset($pInfo->meta['canStore']);
    		$convertable = isset($pInfo->meta['canConvert']);
    		
    		// Нескладируемите продукти дебит 703. Складируемите и вложими 706 останалите 701
    		$creditAccId = ($storable) ? (($convertable) ? '706' : '701') : '703';
        	
        	$amount = ($detailRec->discount) ?  $detailRec->amount * (1 - $detailRec->discount) : $detailRec->amount;
            
        	$entries[] = array(
                'amount' => currency_Currencies::round($amount * $rec->currencyRate), // В основна валута
                
                'debit' => array(
                    '411', 
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array('currency_Currencies', $currencyId),          // Перо 2 - Валута
                    'quantity' => currency_Currencies::round($amount, $rec->currencyId), // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    $creditAccId,
                    	array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array($detailRec->classId, $detailRec->productId), // Перо 2 - Продукт
                    'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
                ),
            );
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира платежната част от транзакцията за продажба (ако има)
     * 
     *    Dt: 501. Каси                  (Каса, Валута)
     *        503. Разпл. с/ки           (Сметка, Валута)
     *        
     *    Ct: 411. Вземания от клиенти   (Клиент, Валута)
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
        price_Helper::fillRecs($rec->details, $rec);  
        
        foreach ($rec->details as $detailRec) {
        	$amount = ($detailRec->discount) ?  $detailRec->amount * (1 - $detailRec->discount) : $detailRec->amount;
        	
            $entries[] = array(
                'amount' => currency_Currencies::round($amount), // В основна валута
                
                'debit' => array(
                    '501', // Сметка "501. Каси"
                        array('cash_Cases', $rec->caseId),         // Перо 1 - Каса
                        array('currency_Currencies', $currencyId), // Перо 2 - Валута
                    'quantity' => currency_Currencies::round($amount, $rec->currencyId), // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    '411', // Сметка "411. Вземания от клиенти"
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array('currency_Currencies', $currencyId),          // Перо 2 - Валута
                    'quantity' => currency_Currencies::round($amount, $rec->currencyId), // "брой пари" във валутата на продажбата
                ),
            );
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за продажба (ако има)
     * 
     * Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 701. Приходи от продажби на Стоки и Продукти    (Клиент, Стоки и Продукти)
     *    
     *    Ct: 321. Стоки и Продукти                           (Склад, Стоки и Продукти)
     *        302. Суровини и Материали                       (Склад, Суровини и Материали)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getDeliveryPart($rec)
    {
        $entries = array();
        
        expect($rec->shipmentStoreId, 'Генериране на експедиционна част при липсващ склад!');
            
        foreach ($rec->details as $detailRec) {
        	$pInfo = cls::get($detailRec->classId)->getProductInfo($detailRec->productId);
        	$convertable = isset($pInfo->meta['canConvert']);
    		
        	// Само складируемите продукти се изписват от склада
        	if(isset($pInfo->meta['canStore'])){
        		$creditAccId = ($convertable) ? '302' : '321';
        		
        		$entries[] = array(
	                'debit' => array(
	                    '701',
	                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
        					array($detailRec->classId, $detailRec->productId), // Перо 2 - Продукт
	                    'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
	                ),
	                
	                'credit' => array(
	                    $creditAccId,
	                        array('store_Stores', $rec->shipmentStoreId), // Перо 1 - Склад
	                        array($detailRec->classId, $detailRec->productId), // Перо 2 - Продукт
	                    'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
	                ),
	            );
        	}
        }
        
        return $entries;
    }
}