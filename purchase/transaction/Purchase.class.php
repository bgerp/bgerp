<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_Purchases
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class purchase_transaction_Purchase extends acc_DocumentTransactionSource
{
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
     *
     * @return object NULL означава, че документа няма отношение към счетоводството, няма да генерира
     *                счетоводни транзакции
     *
     * @throws core_exception_Expect когато възникне грешка при генерирането на транзакция
     */
    public function getTransaction($id)
    {
        $entries = array();
        $rec = $this->class->fetchRec($id);
        $actions = type_Set::toArray($rec->contoActions);
        
        if ($actions['ship'] || $actions['pay']) {
            $rec = $this->fetchPurchaseData($rec); // покупката ще контира - нужни са и детайлите
            deals_Helper::fillRecs($this->class, $rec->details, $rec, array('alwaysHideVat' => true));
            
            if ($actions['ship']) {
                // Покупката играе роля и на складова разписка.
                // Контирането е същото като при СР
                
                // Записите от тип 1 (вземане от клиент)
                $entries = array_merge($entries, $this->getTakingPart($rec));
                
                $delPart = $this->getDeliveryPart($rec);
                
                if (is_array($delPart)) {
                    
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
        
        $transaction = (object) array(
            'reason' => 'Покупка #' . $rec->id,
            'valior' => $rec->valior,
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
        
        $rec->details = array();
        
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
     *    Dt: 60201. Разходи за (нескладируеми) услуги и консумативи    (Разходни обекти, Артикули)
     *
     *    Ct: 401. Задължения към доставчици   (Доставчик, Сделки, Валута)
     */
    protected function getTakingPart($rec)
    {
        $entries = array();
        
        // Покупката съхранява валутата като ISO код; преобразуваме в ПК.
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        
        foreach ($rec->details as $dRec) {
            $pInfo = cat_Products::getProductInfo($dRec->productId);
            if (isset($pInfo->meta['canStore'])) {
                continue;
            }
            
            // Към кои разходни обекти ще се разпределят разходите
            $splitRecs = acc_CostAllocations::getRecsByExpenses('purchase_PurchasesDetails', $dRec->id, $dRec->productId, $dRec->quantity, $dRec->amount, $dRec->discount);
            
            foreach ($splitRecs as $dRec1) {
                $amount = $dRec1->amount;
                $amountAllocated = $amount * $rec->currencyRate;
                
                $entries[] = array(
                    'amount' => $amountAllocated,
                    'debit' => array('60201',
                        $dRec1->expenseItemId,
                        array('cat_Products', $dRec1->productId),
                        'quantity' => $dRec1->quantity),
                    'credit' => array('401',
                        array($rec->contragentClassId, $rec->contragentId),
                        array('purchase_Purchases', $rec->id),
                        array('currency_Currencies', $currencyId),
                        'quantity' => $amount),
                    'reason' => $dRec1->reason,
                );
                
                // Корекция на стойности при нужда
                if (isset($dRec1->correctProducts) && count($dRec1->correctProducts)) {
                    $correctionEntries = acc_transaction_ValueCorrection::getCorrectionEntries($dRec1->correctProducts, $dRec1->productId, $dRec1->expenseItemId, $dRec1->quantity, $dRec1->allocationBy);
                    if (count($correctionEntries)) {
                        $entries = array_merge($entries, $correctionEntries);
                    }
                }
            }
        }
        
        // Отчитаме ддс-то
        if ($this->class->_total->vat) {
            $vat = $this->class->_total->vat;
            $vatAmount = $this->class->_total->vat * $rec->currencyRate;
            $entries[] = array(
                'amount' => $vatAmount, // В основна валута
                
                'credit' => array(
                    '401',
                    array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
                    array('purchase_Purchases', $rec->id),				// Перо 2 - Сделки
                    array('currency_Currencies', $currencyId), // Перо 3 - Валута
                    'quantity' => $vat, // "брой пари" във валутата на продажбата
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
     * Dt: 401. Задължения към доставчици   (Доставчик, Сделки, Валута)
     * Ct: 501. Каси                  	   (Каса, Валута)
     *
     * @param stdClass $rec
     *
     * @return array
     */
    protected function getPaymentPart($rec)
    {
        $entries = array();
        
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        $amountBase = $quantityAmount = 0;
        
        foreach ($rec->details as $detailRec) {
            $amount = ($detailRec->discount) ?  $detailRec->amount * (1 - $detailRec->discount) : $detailRec->amount;
            $amount = round($amount, 2);
            $amountBase += $amount;
        }
        
        if ($rec->chargeVat == 'separate' || $rec->chargeVat == 'yes') {
            $amountBase += $this->class->_total->vat;
        }
        
        $quantityAmount += $amountBase;
        
        $entries[] = array('amount' => $amountBase * $rec->currencyRate,
            'debit' => array('401',
                array($rec->contragentClassId, $rec->contragentId),
                array('purchase_Purchases', $rec->id),
                array('currency_Currencies', $currencyId),
                'quantity' => $quantityAmount,),
            'credit' => array('501',
                array('cash_Cases', $rec->caseId),
                array('currency_Currencies', $currencyId),
                'quantity' => $quantityAmount,),
            'reason' => 'Плащане към доставчик');
        
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
     *
     * @return array
     */
    protected function getDeliveryPart($rec)
    {
        $entries = array();
        
        if (empty($rec->shipmentStoreId)) {
            
            return;
        }
        
        $currencyCode = ($rec->currencyId) ? $rec->currencyId : $this->class->fetchField($rec->id, 'currencyId');
        $currencyId = currency_Currencies::getIdByCode($currencyCode);
        
        foreach ($rec->details as $detailRec) {
            $pInfo = cat_Products::getProductInfo($detailRec->productId);
            
            // Само складируемите продукти се изписват от склада
            if (isset($pInfo->meta['canStore'])) {
                $amount = $detailRec->amount;
                $amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
                $amount = round($amount, 2);
                
                $debitAccId = '321';
                
                $debit = array(
                    $debitAccId,
                    array('store_Stores', $rec->shipmentStoreId), // Перо 1 - Склад
                    array('cat_Products', $detailRec->productId),  // Перо 2 - Артикул
                    'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
                );
                
                $entries[] = array(
                    'amount' => $amount * $rec->currencyRate,
                    'debit' => $debit,
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
    public static function getEntries($id)
    {
        // Връщане на кешираните записи
        return acc_Journal::getEntries(array('purchase_Purchases', $id));
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
        return acc_Balances::getBlAmounts($jRecs, static::DOWNPAYMENT_ACCOUNT_ID, 'debit')->amount;
    }
    
    
    /**
     * Колко е платеното по сделка
     */
    public static function getPaidAmount($jRecs, $rec)
    {
        // Взимаме количествата по валути
        $quantities = acc_Balances::getBlQuantities($jRecs, '401,402', 'debit', '501,503,482');
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
        $itemId = acc_items::fetchItem('purchase_Purchases', $id)->id;
        $paid = acc_Balances::getBlAmounts($jRecs, '401', null, null, array(null, $itemId, null))->amount;
        $paid += acc_Balances::getBlAmounts($jRecs, '402', null, null, array(null, $itemId, null))->amount;
        
        return $paid;
    }
    
    
    /**
     * Колко е доставено по сделката
     */
    public static function getDeliveryAmount($jRecs, $id)
    {
        $itemId = acc_items::fetchItem('purchase_Purchases', $id)->id;
        
        $delivered = acc_Balances::getBlAmounts($jRecs, '401', 'credit', null, array(null, $itemId, null))->amount;
        $delivered -= acc_Balances::getBlAmounts($jRecs, '401', 'credit', '6912')->amount;
        
        return $delivered;
    }
    
    
    /**
     * Колко е ддс-то за начисляване
     */
    public static function getAmountToInvoice($jRecs)
    {
        return acc_Balances::getBlAmounts($jRecs, '4530')->amount;
    }
    
    
    /**
     * Връща всички експедирани продукти и техните количества по сделката
     */
    public static function getShippedProducts($jRecs, $id, $accs = '321,302,601,602,60010,60020,60201', $groupByStore = false, $onlySupplier = true)
    {
        $res = array();
        
        // Извличаме тези, отнасящи се за експедиране
        $itemId = acc_items::fetchItem('purchase_Purchases', $id)->id;
        $from = ($onlySupplier === true) ? '401' : null;
        $dInfo = acc_Balances::getBlAmounts($jRecs, $accs, 'debit', $from);
        
        if (!count($dInfo->recs)) {
            
            return $res;
        }
        
        foreach ($dInfo->recs as $p) {
            if ($p->creditItem2 != $itemId) {
                continue;
            }
            
            // Обикаляме всяко перо
            if (isset($p->debitItem2)) {
                $itemRec = acc_Items::fetch($p->debitItem2);
                
                // Ако има интерфейса за артикули-пера, го добавяме
                if (cls::haveInterface('cat_ProductAccRegIntf', $itemRec->classId)) {
                    $obj = new stdClass();
                    $obj->productId = $itemRec->objectId;
                    
                    $index = $obj->productId;
                    if (empty($res[$index])) {
                        $res[$index] = $obj;
                    }
                    
                    $res[$index]->amount += $p->amount;
                    $res[$index]->quantity += $p->debitQuantity;
                    
                    if ($groupByStore === true) {
                        $storePositionId = acc_Lists::getPosition(acc_Accounts::fetchField($p->debitAccId, 'systemId'), 'store_AccRegIntf');
                        
                        if ($p->{"debitItem{$storePositionId}"}) {
                            $storeItem = acc_Items::fetch($p->{"debitItem{$storePositionId}"});
                            
                            $res[$index]->inStores[$storeItem->objectId]['amount'] += $p->amount;
                            $res[$index]->inStores[$storeItem->objectId]['quantity'] += $p->debitQuantity;
                        }
                    }
                }
            }
        }
        
        foreach ($res as &$r) {
            if ($r->quantity) {
                $r->price = $r->amount / $r->quantity;
            }
        }
        
        // Връщаме масив със всички експедирани продукти по тази сделка
        return $res;
    }
}
