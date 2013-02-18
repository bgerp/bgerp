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
     * 1. Задължаване на с/ката на клиента - безусловно, винаги
     *
     *    Dt: 411  - Вземания от клиенти               (Клиент, Докум. за продажба, Валута)
     *    Ct: 7011 - Приходи от продажби по Документи  (Докум. за продажба, Стоки и Продукти)
     * 
     * 2. Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 7011 - Приходи от продажби по Документи (Докум. за продажба, Стоки и Продукти)
     *    Ct: 321  - Стоки и Продукти                 (Склад, Стоки и Продукти)
     *
     *    Цените, по които се изписват продуктите от с/ка 321 са според зададената стратегия 
     *
     * 3. Получаване на плащане (в някой случаи)
     *
     *    Dt: 501 - Каси                  (Каса, Валута) или
     *        503 - Разпл. с/ки           (Сметка, Валута)
     *    Ct: 411 - Вземания от клиенти   (Клиент, Докум. за продажба, Валута)
     *
     * @param int|object $id първичен ключ или запис на продажба
     * @return object NULL означава, че документа няма отношение към счетоводството, няма да генерира
     *                счетоводни транзакции
     * @throws core_exception_Expect когато възникне грешка при генерирането на транзакция               
     */
    public function getTransaction($id)
    {
        $rec = $this->fetchSaleData($id);
        
        // Всяка продажба трябва да има поне един детайл
        expect(count($rec->details) > 0);
        
        // Записите от тип 1 (вземане от клиент)
        $entries = $this->getTakingPart($rec);
        
        // Записите от тип 2 (експедиция, ако са изпълнени условията)
        if ($this->hasDeliveryPart($rec)) {
            $entries = array_merge($entries, $this->getDeliveryPart($rec));
        }
        
        // Записите от тип 3 (получаване на плащане, ако са изпълнени условията)
        if ($this->hasPaymentPart($rec)) {
            $entries = array_merge($entries, $this->getPaymentPart($rec));
        }
        
        $transaction = NULL;
        
        if (!empty($entries)) {
            $transaction = (object)array(
                'reason'  => 'Продажба #' . $rec->id,
                'valior'  => $rec->date,
                'entries' => $entries, 
            );
        }
        
        return $transaction;
    }
    
    
    public function finalizeTransaction($id)
    {
        $rec = $this->class->fetch($id);
        
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
        if (is_object($id)) {
            $rec = $id;
        } else {
            $rec = $this->class->fetch($id);
        }
        
        expect($rec->id);
        
        // Преобразуване на трибуквен ISO код на валута към първичен ключ на валута
        $rec->currencyCode = $rec->currencyId;
        $rec->currencyId   = currency_Currencies::getIdByCode($rec->currencyId);
        
        
        // Извличаме детайлите на продажбата
        /* @var $detailQuery core_Query */
        $detailQuery = sales_SalesDetails::getQuery();
        $detailQuery->where("#saleId = '{$rec->id}'");
        $rec->details  = array();
        
        while ($dRec = $detailQuery->fetch()) {
            $rec->details[] = $dRec;
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
        
        // Експедиране от текущ склад?
        if ($rec->shipmentStoreId != store_Stores::getCurrent('id', FALSE)) {
            // Експедиране от склад, който не е зададен като текущ
            return FALSE;
        }
        
        return TRUE;
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
        if (bank_PaymentMethods::fetchField($rec->paymentMethodId, 'name') != 'COD') {
            // Не е плащане в брой
            return FALSE;
        }
        
        // Плащане в текущата каса?
        if ($rec->caseId != cash_Cases::getCurrent('id', FALSE)) {
            // Избраната каса не е е текущата
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     * 
     *    Dt: 411  - Вземания от клиенти               (Клиент, Докум. за продажба, Валута)
     *    Ct: 7011 - Приходи от продажби по Документи  (Докум. за продажба, Стоки и Продукти)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getTakingPart($rec)
    {
        $entries = array();
        
        // Изчисляваме курса на валутата на продажбата към базовата валута
        $currencyRate = $this->getCurrencyRate($rec);

        foreach ($rec->details as $detailRec) {
            $entries[] = array(
                'amount' => $detailRec->amount * $currencyRate, // В основна валута
                
                'debit' => array(
                    '411', // Сметка "411. Вземания от клиенти"
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array('sales_Sales', $rec->id),                     // Перо 2 - Документ-продажба
                        array('currency_Currencies', $rec->currencyId),     // Перо 3 - Валута
                    'quantity' => $detailRec->amount, // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    '7011', // Сметка "7011. Приходи от продажби по Документи"
                        array('sales_Sales', $rec->id),               // Перо 1 - Документ-продажба
                        array('cat_Products', $detailRec->productId), // Перо 2 - Продукт
                    'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
                ),
            );
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира платежната част от транзакцията за продажба (ако има)
     * 
     *    Dt: 501 - Каси                  (Каса, Валута) или
     *        503 - Разпл. с/ки           (Сметка, Валута)
     *    Ct: 411 - Вземания от клиенти   (Клиент, Докум. за продажба, Валута)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getPaymentPart($rec)
    {
        $entries = array();
        
        // Изчисляваме курса на валутата на продажбата към базовата валута
        $currencyRate = $this->getCurrencyRate($rec);
        
        expect($rec->caseId);
            
        foreach ($rec->details as $detailRec) {
            $entries[] = array(
                'amount' => $detailRec->amount * $currencyRate, // В основна валута
                
                'debit' => array(
                    '501', // Сметка "501. Каси"
                        array('cash_Cases', $rec->caseId),              // Перо 1 - Каса
                        array('currency_Currencies', $rec->currencyId), // Перо 2 - Валута
                    'quantity' => $detailRec->amount, // "брой пари" във валутата на продажбата
                ),
                
                'credit' => array(
                    '411', // Сметка "411. Вземания от клиенти"
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                        array('sales_Sales', $rec->id),                     // Перо 2 - Документ-продажба
                        array('currency_Currencies', $rec->currencyId),     // Перо 3 - Валута
                    'quantity' => $detailRec->amount, // "брой пари" във валутата на продажбата
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
     *    Dt: 7011 - Приходи от продажби по Документи (Докум. за продажба, Стоки и Продукти)
     *    Ct: 321  - Стоки и Продукти                 (Склад, Стоки и Продукти)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getDeliveryPart($rec)
    {
        $entries = array();
        
        expect($rec->shipmentStoreId);
            
        foreach ($rec->details as $detailRec) {
            $entries[] = array(
                'debit' => array(
                    '7011', // Сметка "7011. Приходи от продажби по Документи"
                        array('sales_Sales', $rec->id),               // Перо 1 - Документ-продажба
                        array('cat_Products', $detailRec->productId), // Перо 2 - Продукт
                    'quantity' => $detailRec->quantity, // Количество продукт в основна мярка
                ),
                
                'credit' => array(
                    '321', // Сметка "321. Стоки и Продукти"
                        array('store_Stores', $rec->shipmentStoreId), // Перо 1 - Склад
                        array('cat_Products', $detailRec->productId), // Перо 2 - Продукт
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
        return currency_CurrencyRates::getRate($rec->date, $rec->currencyCode, NULL);
    }
}
