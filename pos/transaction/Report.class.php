<?php


/**
 * Помощен клас-имплементация на интерфейса pos_transaction_Report за класа pos_Reports
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class pos_transaction_Report extends acc_DocumentTransactionSource
{
    /**
     * @var pos_Reports
     */
    public $class;
    
    
    /**
     * Обща сума
     */
    public $totalAmount = 0;


    /**
     * Обща сума
     */
    public $instantProducts = array();


    /**
     * Кеш на свойствата
     */
    protected $cachedMetas = array();


    /**
     * Кеш
     */
    protected static $savedBatches = false;


    /**
     * Връща транзакцията на бележката
     */
    public function getTransaction($id)
    {
        set_time_limit(300);

        $rec = $this->class->fetchRec($id);
        $posRec = pos_Points::fetch($rec->pointId);
        $paymentsArr = $productsArr = $totalVat = $entries = $batchesByStores = array();
        core_Debug::startTimer('GET_TRANSACTION');

        if(!Mode::is('recontoTransaction')){
            $this->class->extractData($rec);
            if(core_Packs::isInstalled('batch')){
                if(!static::$savedBatches){
                    batch_plg_PosReports::saveBatchesToDraft($rec);
                    static::$savedBatches = true;
                }
            }
        }

        if (countR($rec->details['receiptDetails'])) {
            foreach ($rec->details['receiptDetails'] as $dRec) {
                if ($dRec->action == 'sale') {
                    $productsArr[] = $dRec;
                    if(core_Packs::isInstalled('batch')){
                        $batchesByStores[$dRec->storeId][$dRec->value][$dRec->batch] += $dRec->quantity;
                    }
                } elseif ($dRec->action == 'payment') {
                    $paymentsArr[] = $dRec;
                }
            }

            $productIds = arr::extractValuesFromArray($productsArr, 'value');
            if(countR($productIds)){
                $mQuery = cat_Products::getQuery();
                $mQuery->in('id', $productIds);
                $mQuery->show('canManifacture,canStore,canConvert');
                $this->cachedMetas = $mQuery->fetchAll();
            }
        }
        
        if (isset($rec->id)) {
            core_Debug::startTimer('PRODUCTION_ENTRIES');
            pos_Reports::logDebug('START PRODUCTION_ENTRIES');
            $entriesProduction = $this->getProductionEntries($rec, $productsArr);
            pos_Reports::logDebug('END PRODUCTION_ENTRIES');
            core_Debug::stopTimer('PRODUCTION_ENTRIES');
            pos_Reports::logDebug("GET PRODUCTION_ENTRIES: " . round(core_Debug::$timers["PRODUCTION_ENTRIES"]->workingTime, 6));
            if (countR($entriesProduction)) {
                $entries = array_merge($entries, $entriesProduction);
            }
            
            // Генериране на записите
            core_Debug::startTimer('TAKING_PART');
            pos_Reports::logDebug('START TAKING_PART');
            $entries = array_merge($entries, $this->getTakingPart($rec, $productsArr, $totalVat, $posRec));
            pos_Reports::logDebug('END TAKING_PART');
            core_Debug::stopTimer('TAKING_PART');
            pos_Reports::logDebug("GET TAKING_PART: " . round(core_Debug::$timers["TAKING_PART"]->workingTime, 6));

            core_Debug::startTimer('PAYMENT_PART');
            pos_Reports::logDebug('START PAYMENT_PART');
            $entries = array_merge($entries, $this->getPaymentPart($rec, $paymentsArr, $posRec));
            pos_Reports::logDebug('END PAYMENT_PART');
            core_Debug::stopTimer('PAYMENT_PART');
            pos_Reports::logDebug("GET PAYMENT_PART: " . round(core_Debug::$timers["PAYMENT_PART"]->workingTime, 6));


            // Начисляване на ддс ако има и е разрешено
            if (countR($totalVat) && $rec->chargeVat != 'no') {
                $entries = array_merge($entries, $this->getVatPart($rec, $totalVat, $posRec));
            }
        }
        
        $rec->valior = !empty($rec->valior) ? $rec->valior : dt::today();
        $transaction = (object) array(
            'reason' => 'Отчет за POS продажба №' . $rec->id,
            'valior' => $rec->valior,
            'totalAmount' => $this->totalAmount,
            'entries' => $entries,
        );
        
        if (empty($rec->id)) {
            unset($rec->details);
        }
        
        // Проверка на артикулите преди контиране
        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {

            core_Debug::startTimer('META_CHECK');
            pos_Reports::logDebug('START META_CHECK');

            $productsArr = arr::extractValuesFromArray($productsArr, 'value');
            $productCheck = deals_Helper::checkProductForErrors($productsArr, 'canSell');

            // Извличане от контировката на артикулите за изписване
            $productsByStore = array();
            foreach ($entries as $d){
                if($d['credit'][0] == '321') {
                    if(!array_key_exists($d['credit'][2][1], $this->instantProducts)){
                        $productsByStore[$d['credit'][1][1]][] = (object)array('value' => $d['credit'][2][1], 'quantity' => $d['credit']['quantity']);
                    }
                }
            }

            // Проверка на артикулите
            if(countR($productCheck['notActive'])){
                acc_journal_RejectRedirect::expect(false, "Артикулите|*: " . implode(', ', $productCheck['notActive']) . " |не са активни|*!");
            } elseif($productCheck['metasError']){
                acc_journal_RejectRedirect::expect(false, "Артикулите|*: " . implode(', ', $productCheck['metasError']) . " |трябва да са продаваеми|*!");
            }

            if(!store_Setup::canDoShippingWhenStockIsNegative()){
                $contoWarnings =  array();
                foreach ($productsByStore as $storeId => $productArr){
                    if ($warning = deals_Helper::getWarningForNegativeQuantitiesInStore($productArr, $storeId, $rec->state, 'value', 'quantity')) {
                        $contoWarnings[] = $warning;
                    }
                }

                if(countR($contoWarnings)) {
                    $warning = implode('. ', $contoWarnings);
                    acc_journal_RejectRedirect::expect(false, $warning);
                }

                // Проверка за неналичните партиди
                if(!haveRole('contoNegativeBatches')){
                    $productsWithNotExistingBatchesArr = array();

                    foreach ($batchesByStores as $storeId => $productBatches) {
                        foreach ($productBatches as $productId => $batches) {
                            if($Def = batch_Defs::getBatchDef($productId)){
                                $checkIfBatchExists = $Def->getField('onlyExistingBatches');
                                if($checkIfBatchExists == 'yes'){
                                    $existingBatches = batch_Items::getBatchQuantitiesInStore($productId, $storeId);
                                    foreach ($batches as $b => $q) {
                                        $inStore = $existingBatches[$b] ?? 0;
                                        if(round($q, 5) > round($inStore, 5)){
                                            if(!array_key_exists($productId, $productsWithNotExistingBatchesArr)){
                                                $productsWithNotExistingBatchesArr[$productId] = "<b>" . cat_Products::getTitleById($productId, false) . "</b>";
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if(countR($productsWithNotExistingBatchesArr)){
                        $productMsg = implode(', ', $productsWithNotExistingBatchesArr);
                        acc_journal_RejectRedirect::expect(false, "Артикули с неналични партиди|*: {$productMsg}");
                    }
                }
            }

            pos_Reports::logDebug('END META_CHECK');
            core_Debug::stopTimer('META_CHECK');
            pos_Reports::logDebug("GET META_CHECK: " . round(core_Debug::$timers["META_CHECK"]->workingTime, 6));
        }
        core_Debug::stopTimer('GET_TRANSACTION');
        pos_Reports::logDebug("GET TRANSACTION: " . round(core_Debug::$timers["GET_TRANSACTION"]->workingTime, 6));

        return $transaction;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     *
     *    Dt: 411  - Вземания от клиенти               (Клиент, Сделки, Валута)
     *
     *    Ct: 701  - Приходи от продажби на Стоки и Продукти  (Клиенти, Сделки, Стоки и Продукти)
     *    	  703  - Приходи от продажба на услуги 			  (Клиенти, Сделки, Услуги)
     *
     * @param stdClass $rec      - записа
     * @param array    $products - продуктите
     * @param array    $totalVat - общото ддс
     * @param stdClass $posRec   - точката на продажба
     */
    protected function getTakingPart($rec, $products, &$totalVat, $posRec)
    {
        $entries = array();
      
        foreach ($products as $product) {
            $product->totalQuantity = round($product->quantity * $product->quantityInPack, 2);
            if($rec->chargeVat == 'no'){
                $product->amount *= (1 + $product->param);
            }
            
            $totalAmount = currency_Currencies::round($product->amount);
            if ($product->param) {
                $totalVat[$product->contragentClassId .'|'. $product->contragentId] += $product->param * $product->amount;
            }

            $currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
            $pRec = $this->cachedMetas[$product->value];
            $creditAccId = ($pRec->canStore == 'yes') ? '701' : '703';
            $credit = array(
                $creditAccId,
                array($product->contragentClassId, $product->contragentId),
                array('pos_Reports', $rec->id),
                array('cat_Products', $product->value),
                'quantity' => $product->totalQuantity,
            );
            
            $entries[] = array(
                'amount' => $totalAmount,
                'debit' => array(
                    '411',
                    array($product->contragentClassId, $product->contragentId),
                    array('pos_Reports', $rec->id),
                    array('currency_Currencies', $currencyId),
                    'quantity' => $totalAmount),
                
                'credit' => $credit,
            );
            
            $this->totalAmount += $totalAmount;
            
            if ($pRec->canStore == 'yes') {
                $entries = array_merge($entries, $this->getDeliveryPart($rec, $product, $posRec));
            }
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за продажба (ако има)
     * Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 701. Приходи от продажби на стоки и продукти     (Клиент, Сделки, Стоки и Продукти)
     *
     *    Ct: 321. Суровини, материали, продукция, стоки 	   (Склад, Стоки и Продукти)
     *
     * @param stdClass $rec         - записа
     * @param array    $product     - артикула
     * @param stdClass $posRec      - точката на продажба
     *
     * @return array
     */
    protected function getDeliveryPart($rec, $product, $posRec)
    {
        $entries = array();
        $creditAccId = '321';
        $debitAccId = '701';
        
        // После се отчита експедиране от склада
        $entries[] = array(
            'debit' => array(
                $debitAccId,
                array($product->contragentClassId, $product->contragentId),
                array('pos_Reports', $rec->id),
                array('cat_Products', $product->value),
                'quantity' => $product->totalQuantity),
            
            'credit' => array(
                $creditAccId,
                array('store_Stores', $product->storeId),
                array('cat_Products', $product->value),
                'quantity' => $product->totalQuantity),
        );
        
        return $entries;
    }
    
    
    /**
     * Връща часта контираща ддс-то
     *
     * 		Dt: 411.  Взимания от клиенти           (Клиенти, Сделки, Валути)
     *
     * 		Ct: 4532. Начислен ДДС за продажбите
     *
     * @param stdClass $rec      - записа
     * @param array    $totalVat - начисленото ддс
     * @param stdClass $posRec   - точката на продажба
     */
    protected function getVatPart($rec, $totalVat, $posRec)
    {
        $entries = array();
        foreach ($totalVat as $index => $value) {
            $contragentArr = explode('|', $index);
            
            $entries[] = array(
                'amount' => currency_Currencies::round($value),
                
                'debit' => array(
                    '411',
                    $contragentArr,
                    array('pos_Reports', $rec->id),
                    array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->createdOn)),
                    'quantity' => currency_Currencies::round($value),
                ),
                
                'credit' => array('4532')
            );
            
            $this->totalAmount += currency_Currencies::round($value);
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира платежната част от транзакцията за продажба (ако има)
     *
     *    Dt: 501. Каси                  (Каси, Валута)
     *    Ct: 411. Вземания от клиенти   (Контрагенти, Сделки, Валута)
     *
     * Ако има безналични методи на пращане (плащания не във брой)
     *
     *    Dt: 502. Каси - безналични плащания   (Каси, Безналични методи за плащане)
     *    Dt: 501. Каси                         (Каси, Валута)
     *
     * @param stdClass $rec
     *
     * @return array
     */
    protected function getPaymentPart($rec, $paymentsArr, $posRec)
    {
        $entries = array();
        
        // Продажбата съхранява валутата като ISO код; преобразуваме в ПК.
        $currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
        $nonCashPayments = array();
        
        foreach ($paymentsArr as $payment) {
            
            if ($payment->value != -1) {
                $payment->originalAmount = $payment->amount;
                $payment->amount = cond_Payments::toBaseCurrency($payment->value, $payment->amount, $payment->date);
            }
            
            $entries[] = array(
                'amount' => currency_Currencies::round($payment->amount),
                
                'debit' => array(
                    '501', // Сметка "501. Каси"
                    array('cash_Cases', $posRec->caseId),
                    array('currency_Currencies', $currencyId),
                    'quantity' => currency_Currencies::round($payment->amount),
                ),
                
                'credit' => array(
                    '411', // Сметка "411. Вземания от клиенти"
                    array($payment->contragentClassId, $payment->contragentId),
                    array('pos_Reports', $rec->id),
                    array('currency_Currencies', $currencyId),
                    'quantity' => currency_Currencies::round($payment->amount),
                ),
            );
            
            $this->totalAmount += currency_Currencies::round($payment->amount);
            
            if ($payment->value != -1) {
                $nonCashPayments[] = $payment;
            }
        }
        
        if (countR($nonCashPayments)) {
            foreach ($nonCashPayments as $payment1) {
                $entries[] = array(
                    'amount' => currency_Currencies::round($payment1->amount),
                    
                    'debit' => array(
                        '502', // Сметка "502. Каси - безналични плащания"
                        array('cash_Cases', $posRec->caseId),
                        array('cond_Payments', $payment1->value),
                        'quantity' => currency_Currencies::round($payment1->originalAmount),
                    ),
                    
                    'credit' => array(
                        '501', // Сметка "501. Каси"
                        array('cash_Cases', $posRec->caseId),
                        array('currency_Currencies', $currencyId),
                        'quantity' => currency_Currencies::round($payment1->amount),
                    ),
                
                );
                
                $this->totalAmount += currency_Currencies::round($payment1->amount);
            }
        }
        
        return $entries;
    }


    /**
     * Записи за произвеждане
     *
     * @param stdClass $rec
     * @param array $productsArr
     * @return array
     */
    private function getProductionEntries($rec, $productsArr)
    {
        $entries = $byStores = array();
        foreach ($productsArr as $dRec){
            $byStores[$dRec->storeId][] = $dRec;
        }

        // Кои материали ще се произвеждат преди да се вложат
        foreach ($byStores as $storeId => $dRecs){
            $clone = clone $rec;
            $clone->storeId = $storeId;
            $clone->details = $dRecs;
            $entriesProduction = sales_transaction_Sale::getProductionEntries($clone, 'pos_Reports', 'storeId', $this->instantProducts, 'value');

            if (countR($entriesProduction)) {
                foreach ($entriesProduction as $pRec){
                    $this->totalAmount += $pRec['amount'];
                }
                $entries = array_merge($entries, $entriesProduction);
            }
        }
        
        return $entries;
    }
}
