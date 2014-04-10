<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_ShipmentOrders
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
class store_transactionIntf_ShipmentOrder
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
     *    Ct: 321  - Стоки и Продукти                     (Склад, Стоки и Продукти)
     *		  302  - Суровини и Материали                 (Склад, Суровини и материали)
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
        
        $rec = $this->fetchShipmentData($id);
            
        // Всяко ЕН трябва да има поне един детайл
        if (count($rec->details) > 0) {
            // Записите от тип 1 (вземане от клиент)
            $entries = $this->getTakingPart($rec);
                
            if($rec->storeId){
            	// Записите от тип 2 (експедиция)
            	$entries = array_merge($entries, $this->getDeliveryPart($rec));
            }
        }
        
        $transaction = (object)array(
            'reason'  => 'Експедиционно нареждане №' . $rec->id,
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
        
        $rec->state = 'active';
        
        if ($this->class->save($rec)) {
            $this->class->invoke('Activation', array($rec));
        }
        
        // Нотификация към пораждащия документ, че нещо във веригата му от породени документи
        // се е променило.
        if ($origin = $this->class->getOrigin($rec)) {
            $rec = new core_ObjectReference($this->class, $rec);
            $origin->getInstance()->invoke('DescendantChanged', array($origin, $rec));
        }
    }
    
    
    /**
     * Помощен метод за извличане на данните на ЕН - мастър + детайли
     * 
     * Детайлите на ЕН (продуктите) са записани в полето-масив 'details' на резултата 
     * 
     * @param int|object $id първичен ключ или запис на ЕН
     * @param object запис на ЕН (@see store_ShipmentOrders)
     */
    protected function fetchShipmentData($id)
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
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     * 
     *    Dt: 411  - Вземания от клиенти               (Клиент, Валута)
     *    
     *    Ct: 701  - Приходи от продажби на Стоки и Продукти  (Клиенти, Стоки и Продукти)
     *    	  706  - Приходи от продажба на Суровини и Материали (Клиенти, Суровини и материали)
     * 
     * ДДС за начисляване
     * 
     *    Dt: 411. Вземания от клиенти                   (Клиент, Валута)
     *    
     *    Ct: 4530 - ДДС за начисляване
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getTakingPart($rec)
    {
        $entries = array();
        
        // Изчисляваме курса на валутата на продажбата към базовата валута
        $currencyRate = $rec->currencyRate;
        $currencyCode = ($rec->currencyId) ? $rec->currencyId : $this->class->fetchField($rec->id, 'currencyId');
        $currencyId   = currency_Currencies::getIdByCode($currencyCode);
        price_Helper::fillRecs($rec->details, $rec);
        
        foreach ($rec->details as $detailRec) {
        	if($rec->chargeVat == 'yes'){
        		$ProductManager = cls::get($detailRec->classId);
            	$vat = $ProductManager->getVat($detailRec->productId, $rec->valior);
            	$amount = $detailRec->amount - ($detailRec->amount * $vat / (1 + $vat));
        	} else {
        		$amount = $detailRec->amount;
        	}
        	
        	$amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
        	$pInfo = cls::get($detailRec->classId)->getProductInfo($detailRec->productId, $detailRec->packagingId);
        	
        	// Вложимите кредит 706, другите 701
        	$creditAccId = (isset($pInfo->meta['canConvert'])) ? '706' : '701';
            
        	$entries[] = array(
                'amount' => currency_Currencies::round($amount * $currencyRate), // В основна валута
                
                'debit' => array(
                    '411',
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array('currency_Currencies', $currencyId),     		// Перо 2 - Валута
                    'quantity' => currency_Currencies::round($amount, $currencyCode), // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                     $creditAccId, 
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                    	array($detailRec->classId, $detailRec->productId), // Перо 2 - Артикул
                    'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
                ),
            );
        }
        
        if($rec->_total->vat){
        	$vatAmount = currency_Currencies::round($rec->_total->vat * $currencyRate);
        	$entries[] = array(
                'amount' => $vatAmount, // В основна валута
                
                'debit' => array(
                    '411',
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->valior)), // Перо 2 - Валута
                    'quantity' => $vatAmount, // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    '4530', 
                    'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
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
     *    Dt: 701 - Приходи от продажби на Стоки и Продукти 	(Клиент, Стоки и Продукти) 
     *    	  706 - Приходи от продажба на Суровини и материали (Клиент, Суровини и материали)
     *    
     *    Ct: 321  - Стоки и Продукти                 			(Склад, Стоки и Продукти)
     *    	  302  - Суровини и материали             			(Склад, Суровини и материали)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getDeliveryPart($rec)
    {
        $entries = array();
        
        expect($rec->storeId, 'Генериране на експедиционна част при липсващ склад!');
        foreach ($rec->details as $detailRec) {
        	$pInfo = cls::get($detailRec->classId)->getProductInfo($detailRec->productId, $detailRec->packagingId);
        	
        	// Вложимите кредит 706, другите 701
        	$debitAccId = (isset($pInfo->meta['canConvert'])) ? '706' : '701';
        	$creditAccId = (isset($pInfo->meta['canConvert'])) ? '302' : '321';
        	
        	$entries[] = array(
	             'debit' => array(
	                    $debitAccId, 
	                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
        					array($detailRec->classId, $detailRec->productId), // Перо 2 - Продукт
	                    'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
	                ),
	                
	                'credit' => array(
	                    $creditAccId, 
	                        array('store_Stores', $rec->storeId), // Перо 1 - Склад
	                        array($detailRec->classId, $detailRec->productId), // Перо 2 - Продукт
	                    'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
	                ),
	       );
        }
        
        return $entries;
    }
}