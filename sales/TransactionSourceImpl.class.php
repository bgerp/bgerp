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
     *    Dt: 411 - Вземания от клиенти          (Клиент, Докум. за продажба, Валута)
     *    Ct: 702 - Приходи от продажби на стоки (Стандартен продукт, Докум. за продажба)
     * 
     * 2. Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 702 - Приходи от продажби на стоки (Клиент, Докум. за продажба)
     *    Ct: 322 - Стандартни продукти          (Склад, Стандартен продукт)
     *
     *    Цените, по които се изписват продуктите от с/ка 322 са според зададената стратегия 
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
            $entries += $this->getDeliveryPart($rec);
        }
        
        // Записите от тип 3 (получаване на плащане, ако са изпълнени условията)
        if ($this->hasPaymentPart($rec)) {
            $entries += $this->getPaymentPart($rec);
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
        $rec->currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        
        
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
        if ($rec->shipmentStoreId != store_Stores::getCurrent(FALSE)) {
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
        if ($rec->caseId != cash_Cases::getCurrent(FALSE)) {
            // Избраната каса не е е текущата
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     * 
     *    Dt: 411 - Вземания от клиенти          (Клиент, Докум. за продажба, Валута)
     *    Ct: 702 - Приходи от продажби на стоки (Стандартен продукт, Докум. за продажба)
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
            $creditQuantity = $detailRec->quantity; // @TODO: Количество в основната мярка на продукта
            $creditPrice    = $detailRec->price * $currencyRate; // В основна валута
            
            $debitQuantity  = $detailRec->price * $detailRec->quantity; // "брой пари" във 
                                                                        // валутата на продажбата 
            $debitPrice     = $currencyRate;

            $entries[] = array(
                // Дебит
                'debitAcc' => '411', // Сметка "411. Вземания от клиенти"
                'debitItem1' => (object)array( // Перо 1 - Клиент
                    'cls' => $rec->contragentClassId,
                    'id'  => $rec->contragentId,
                ),
                'debitItem2' => (object)array( // Перо 2 - Документ-продажба
                    'cls' => 'sales_Sales',
                    'id'  => $rec->id,
                ),
                'debitItem3' => (object)array( // Перо 3 - Валута
                    'cls' => 'currency_Currencies',
                    'id'  => $rec->currencyId,
                ),
                'debitAmount'  => $amount,       // Сума в осн. валута
                'debitPrice'   => $currencyRate, // Курс на валутата към основната валута
                
                // Кредит
                'creditAcc' => '702', // Сметка "702. Приходи от продажби на стоки"
                'creditItem1' => (object)array( // Перо 1 - Продукт
                    'cls' => 'cat_Products',
                    'id'  => $detailRec->productId,
                ),
                'creditItem2' => (object)array( // Перо 2 - Документ-продажба
                    'cls' => 'sales_Sales',
                    'id'  => $rec->id,
                ),
                'creditQuantity' => $quantity,  // Количество продукти
                'creditPrice'    => $price,     // Единична цена на продукт в осн. валута 
            );
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира платежната част от транзакцията за продажба (ако има)
     * 
     * @param stdClass $rec
     * @return array
     */
    protected function getPaymentPart($rec)
    {
        $entries = array();
        
        foreach ($rec->details as $product) {
            $entry['debitAcc'] = '411'; // Вземания от клиенти
            $entry['debitItem1'] = (object)array(
                'cls' => $rec->contragentClassId,
                'id'  => $rec->contragentId,
            ); // Аналитичност "клиенти"
            
            // Определяне на цената на $product
            
            
//             $entry['creditPrice'] = 
//             $entry['debitAmount'] =  
            
            
        }
        
        return array();
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за продажба (ако има)
     * 
     * @param stdClass $rec
     * @return array
     */
    protected function getDeliveryPart($rec)
    {
        return array();
    }
    
    
    /**
     * Курс на валутата на продажбата към базовата валута за периода, в който попада продажбата
     * 
     * @param stdClass $rec запис за продажба
     * @return float
     * 
     * @TODO Да се реализира, използвайки @see currency_CurrencyRates 
     */
    protected function getCurrencyRate($rec)
    {
        return 1; // @TODO!!!
    }
}
