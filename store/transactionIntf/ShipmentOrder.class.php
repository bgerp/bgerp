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
     *    Dt: 411    - Вземания от клиенти               (Клиент, Валута)
     *    Ct: 7011 - Приходи от продажби към Клиенти   (Стоки и Продукти)
     * 
     * 2. Експедиране на стоката от склада
     *
     *    Dt: 7011 - Приходи от продажби към Клиенти (Стоки и Продукти)
     *    Ct: 321  - Стоки и Продукти                 (Склад, Стоки и Продукти)
     *
     *    Цените, по които се изписват продуктите от с/ка 321 са според зададената стратегия 
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
            'reason'  => 'ЕН #' . $rec->id,
            'valior'  => $rec->valior,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
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
     *    Ct: 7011 - Приходи от продажби към Контрагенти  (Клиенти, Стоки и Продукти)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getTakingPart($rec)
    {
        $entries = array();
        
        // Изчисляваме курса на валутата на продажбата към базовата валута
        $currencyRate = $this->getCurrencyRate($rec);
        $currencyCode = ($rec->currencyId) ? $rec->currencyId : $this->class->fetchField($rec->id, 'currencyId');
        $currencyId   = currency_Currencies::getIdByCode($currencyCode);
        
        foreach ($rec->details as $detailRec) {
        	
            $entries[] = array(
                'amount' => currency_Currencies::round($detailRec->amount * $currencyRate), // В основна валута
                
                'debit' => array(
                    '411', // Сметка "411. Вземания от клиенти"
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array('currency_Currencies', $currencyId),     		// Перо 2 - Валута
                    'quantity' => currency_Currencies::round($detailRec->amount, $currencyCode), // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                     '7011', // Сметка "7011. Приходи от продажби по Документи"
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                    	array($detailRec->classId, $detailRec->productId), // Перо 2 - Артикул
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
     *    Dt: 7011 - Приходи от продажби към Контрагенти (Клиент, Стоки и Продукти)
     *    Ct: 321  - Стоки и Продукти                 (Склад, Стоки и Продукти)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getDeliveryPart($rec)
    {
        $entries = array();
        
        expect($rec->storeId, 'Генериране на експедиционна част при липсващ склад!');
            
        foreach ($rec->details as $detailRec) {
        	$entries[] = array(
	             'debit' => array(
	                    '7011', // Сметка "7011. Приходи от продажби към Контрагенти"
	                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
        					array($detailRec->classId, $detailRec->productId), // Перо 2 - Продукт
	                    'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
	                ),
	                
	                'credit' => array(
	                    '321', // Сметка "321. Стоки и Продукти"
	                        array('store_Stores', $rec->storeId), // Перо 1 - Склад
	                        array($detailRec->classId, $detailRec->productId), // Перо 2 - Продукт
	                    'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
	                ),
	       );
        }
        
        return $entries;
    }
    
    
    /**
     * Курс на валутата на продажбата към базовата валута за периода, в който попада продажбата
     * 
     * @param stdClass $rec запис за продажба
     * @return float
     */
    protected function getCurrencyRate($rec)
    {
        return currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, NULL);
    }
}
