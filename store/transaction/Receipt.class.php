<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_Receipts
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
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
     *
     * @return object NULL означава, че документа няма отношение към счетоводството, няма да генерира
     *                счетоводни транзакции
     *
     * @throws core_exception_Expect когато възникне грешка при генерирането на транзакция
     */
    public function getTransaction($id)
    {
        $entries = array();
        
        $error = null;
        $rec = $this->fetchShipmentData($id, $error);
        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
            if ($error === true) {
                acc_journal_RejectRedirect::expect(false, 'Трябва да има поне един ред с ненулево количество|*!');
            }
            
            // Проверка на артикулите
            $property = ($rec->isReverse == 'yes') ? 'canSell' : 'canBuy';
            
            $productArr = arr::extractValuesFromArray($rec->details, 'productId');
            if (countR($productArr)) {
                $msg = ($rec->isReverse == 'yes') ? 'продаваеми' : 'купуваеми';
                $msg = "трябва да са {$msg} и да не са генерични";
                
                if($redirectError = deals_Helper::getContoRedirectError($productArr, $property, 'generic', $msg)){
                    
                    acc_journal_RejectRedirect::expect(false, $redirectError);
                }
            }
        }
        
        $origin = $this->class->getOrigin($rec);
        $packRecs = store_DocumentPackagingDetail::getRecs($this->class, $rec->id);
        
        // Всяка СР трябва да има поне един детайл
        if (countR($rec->details) > 0 || countR($packRecs) > 0) {
            if ($rec->isReverse == 'yes') {
                
                // Ако СР е обратна, тя прави контировка на ЕН но с отрицателни стойностти
                $reverseSource = cls::getInterface('acc_TransactionSourceIntf', 'store_ShipmentOrders');
                $entries = $reverseSource->getReverseEntries($rec, $origin);
            } else {
                
                // Ако СР е права, тя си прави дефолт стойностите
                $entries = $this->getDeliveryPart($rec, $origin);
            }
        }
        
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        
        $transaction = (object) array(
            'reason' => 'Складова разписка №' . $rec->id,
            'valior' => $rec->valior,
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
    protected function fetchShipmentData($id, &$error = false)
    {
        $rec = $this->class->fetchRec($id);
        
        $rec->details = array();
        
        if (!empty($rec->id)) {
            // Извличаме детайлите на покупката
            $detailQuery = store_ReceiptDetails::getQuery();
            $detailQuery->where("#receiptId = '{$rec->id}'");
            $rec->details = array();
            
            $error = true;
            while ($dRec = $detailQuery->fetch()) {
                $rec->details[] = $dRec;
                if (!empty($dRec->quantity)) {
                    $error = false;
                }
            }
            
            if (!countR($rec->details)) {
                $error = false;
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
     *
     * @return array
     */
    protected function getDeliveryPart($rec, $origin, $reverse = false)
    {
        $entries = array();
        $sign = ($reverse) ? -1 : 1;

        $hasDifferentReverseEntries = $reverse && !cls::get('store_ShipmentOrders')->isDocForReturnFromDocument($rec);

        expect($rec->storeId, 'Генериране на експедиционна част при липсващ склад!');
        $currencyRate = $rec->currencyRate;
        currency_CurrencyRates::checkRateAndRedirect($currencyRate);
        $currencyCode = ($rec->currencyId) ? $rec->currencyId : $this->class->fetchField($rec->id, 'currencyId');
        $currencyId = currency_Currencies::getIdByCode($currencyCode);
        deals_Helper::fillRecs($this->class, $rec->details, $rec, array('alwaysHideVat' => true));
        $dClass = ($reverse) ? 'store_ShipmentOrderDetails' : 'store_ReceiptDetails';
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        $firstRec = $firstDoc->fetch();

        // Ако документа е с включено/отделно ддс и към покупка - ще се прави контировка за артикултие с данъчен кредит
        $checkVatCredit = $firstDoc->isInstanceOf('purchase_Purchases') && $firstRec->haveVatCreditProducts == 'no';
        $entriesLast = array();

        foreach ($rec->details as $detailRec) {
            if (empty($detailRec->quantity) && Mode::get('saveTransaction')) {
                continue;
            }

            $canStore = cat_Products::fetchField($detailRec->productId, 'canStore');
            $amount = $detailRec->amount;
            $amount = ($detailRec->discount) ?  $amount * (1 - $detailRec->discount) : $amount;
            $amount = round($amount, 2);
            $vatExceptionId = cond_VatExceptions::getFromThreadId($rec->threadId);
            $revertVatPercent = ($checkVatCredit) ? cat_Products::getVat($detailRec->productId, $rec->valior, $vatExceptionId) : null;
            $reason = $reverse ? ($hasDifferentReverseEntries ? 'Експедиране (връщане без ограничения) на Артикули към Доставчик' : "Връщане на Артикули към Доставчик - в месеца и от склада на доставката им") : null;

            if($canStore != 'yes'){
                // Към кои разходни обекти ще се разпределят разходите
                unset($detailRec->discount);
                $splitRecs = acc_CostAllocations::getRecsByExpenses($dClass, $detailRec->id, $detailRec->productId, $detailRec->quantity, $amount, $detailRec->discount);

                foreach ($splitRecs as $dRec1) {
                    $amount = $dRec1->amount;
                    $amountAllocated = $amount * $rec->currencyRate;

                    $debitArr = array('60201', $dRec1->expenseItemId, array('cat_Products', $dRec1->productId), 'quantity' => $sign * $dRec1->quantity);
                    if($hasDifferentReverseEntries){
                        $reverseCredit = $debitArr;
                        $reverseCredit['quantity'] = abs($reverseCredit['quantity']);
                        $debitArr = array('6912', array($rec->contragentClassId, $rec->contragentId), array($origin->className, $origin->that));
                    }

                    $entries[] = array(
                        'amount' => $sign * $amountAllocated, // В основна валута
                        'debit' => $debitArr,
                        'credit' => array($rec->accountId,
                            array($rec->contragentClassId, $rec->contragentId),
                            array($origin->className, $origin->that),
                            array('currency_Currencies', $currencyId),
                            'quantity' => $sign * $amount,
                        ),
                        'reason' => $dRec1->reason,
                    );

                    if($hasDifferentReverseEntries){
                        $entries[] = array(
                            'debit' => $debitArr,
                            'credit' => $reverseCredit,
                            'reason' => $reason,
                        );
                    }
                    
                    // Корекция на стойности при нужда
                    if (isset($dRec1->correctProducts) && countR($dRec1->correctProducts)) {
                        $correctionEntries = acc_transaction_ValueCorrection::getCorrectionEntries($dRec1->correctProducts, $dRec1->productId, $dRec1->expenseItemId, $dRec1->quantity, $dRec1->allocationBy, $reverse);
                        if (countR($correctionEntries)) {
                            $entries = array_merge($entries, $correctionEntries);
                        }
                    }

                    if($revertVatPercent){
                        $entriesLast[] = array(
                            'amount' => $sign * round($amountAllocated * $revertVatPercent, 2),
                            'debit' => array('60201',
                                            $dRec1->expenseItemId,
                                            array('cat_Products', $dRec1->productId),
                                            'quantity' => 0),
                            'credit' => array('4530', array($origin->className, $origin->that)),
                            'reason' => 'Сторно ДДС за начисляване при покупка - сделка БЕЗ право на Данъчен кредит');
                    }
                }
            } else {
                $debitAccId = '321';
                $debit = array(
                    $debitAccId,
                    array('store_Stores', $rec->storeId), // Перо 1 - Склад
                    array('cat_Products', $detailRec->productId),  // Перо 2 - Артикул
                    'quantity' => $sign * $detailRec->quantity, // Количество продукт в основната му мярка
                );

                $cQuantity = $sign * $amount;
                $amount = $sign * $amount * $rec->currencyRate;
                $amountPure = $amount;

                if($hasDifferentReverseEntries){
                    $reverseCredit = $debit;
                    $reverseCredit['quantity'] = abs($reverseCredit['quantity']);
                    $debit = array('6912', array($rec->contragentClassId, $rec->contragentId), array($origin->className, $origin->that));
                }

                $entries[] = array(
                    'amount' => $amount,
                    'debit' => $debit,
                    'credit' => array(
                        $rec->accountId,
                        array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Доставчик
                        array($origin->className, $origin->that),		   // Перо 2 - Сделка
                        array('currency_Currencies', $currencyId),          // Перо 3 - Валута
                        'quantity' => $cQuantity, // "брой пари" във валутата на покупката
                    ),
                    'reason' => $reason,
                );

                if($hasDifferentReverseEntries){
                    $entries[] = array(
                        'debit' => $debit,
                        'credit' => $reverseCredit,
                        'reason' => $reason,
                    );
                }

                if($revertVatPercent && !$reverse){
                    $entriesLast[] = array(
                        'amount' => round($amountPure * $revertVatPercent, 2),
                        'debit' => array($debitAccId,
                                   array('store_Stores', $rec->storeId),
                                   array('cat_Products', $detailRec->productId),
                                            'quantity' => 0),
                        'credit' => array('4530', array($origin->className, $origin->that)),
                        'reason' => 'Сторно ДДС за начисляване при покупка - сделка БЕЗ право на Данъчен кредит');
                }
            }
        }
        
        if ($this->class->_total->vat) {
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
        
        $class = ($reverse) ? cls::get('store_ShipmentOrders') : $this->class;
        $entries2 = store_DocumentPackagingDetail::getEntries($class, $rec, $reverse);
        if (countR($entries2)) {
            $entries = array_merge($entries, $entries2);
        }

        if(countR($entriesLast)){
            $entries = array_merge($entries, $entriesLast);
        }

        return $entries;
    }
    
    
    /**
     * Връща обратна контировка на стандартната
     */
    public function getReverseEntries($rec, $origin)
    {
        $entries = $this->getDeliveryPart($rec, $origin, true);
        
        return $entries;
    }
}
