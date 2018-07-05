<?php


/**
 * Помощен клас-имплементация на интерфейса pos_transaction_Report за класа pos_Reports
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
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
     * Връща транзакцията на бележката
     */
    public function getTransaction($id)
    {
        set_time_limit(300);
        
        $rec = $this->class->fetchRec($id);
        $posRec = pos_Points::fetch($rec->pointId);
        $entries = array();
        $totalVat = array();
        $totalAmount = 0;
        
        $paymentsArr = $productsArr = array();
        $this->class->extractData($rec);
        
        if (count($rec->details['receiptDetails'])) {
            foreach ($rec->details['receiptDetails'] as $dRec) {
                if ($dRec->action == 'sale') {
                    $productsArr[] = $dRec;
                } elseif ($dRec->action == 'payment') {
                    $paymentsArr[] = $dRec;
                }
            }
        }
        
        if (isset($rec->id)) {
            // Генериране на записите
            $entries = array_merge($entries, $this->getTakingPart($rec, $productsArr, $totalVat, $posRec));
            
            $entries = array_merge($entries, $this->getPaymentPart($rec, $paymentsArr, $posRec));
            
            // Начисляване на ддс ако има
            if (count($totalVat)) {
                $entries = array_merge($entries, $this->getVatPart($rec, $totalVat, $posRec));
            }
        }
        
        $transaction = (object) array(
            'reason' => 'Отчет за POS продажба №' . $rec->id,
            'valior' => $rec->createdOn,
            'totalAmount' => $this->totalAmount,
            'entries' => $entries,
        );
        
        if (empty($rec->id)) {
            unset($rec->details);
        }
        
        return $transaction;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     *
     *    Dt: 411  - Вземания от клиенти               (Клиент, Сделки, Валута)
     *
     *    Ct: 701  - Приходи от продажби на Стоки и Продукти  (Клиенти, Сделки, Стоки и Продукти)
     *    	  703  - Приходи от продажба на услуги 			  (Клиенти, Сделки, Услуги)
     *        706  - Приходи от продажба на Суровини и Материали (Клиенти, Суровини и материали)
     *
     * @param stdClass $rec      - записа
     * @param array    $products - продуктите
     * @param double   $totalVat - общото ддс
     * @param stdClass $posRec   - точката на продажба
     */
    protected function getTakingPart($rec, $products, &$totalVat, $posRec)
    {
        $entries = array();
        
        foreach ($products as $product) {
            $product->totalQuantity = round($product->quantity * $product->quantityInPack, 2);
            $totalAmount = currency_Currencies::round($product->amount);
            if ($product->param) {
                $totalVat[$product->contragentClassId .'|'. $product->contragentId] += $product->param * $product->amount;
            }
            
            $currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
            $pInfo = cat_Products::getProductInfo($product->value);
            $storable = isset($pInfo->meta['canStore']);
            $convertable = isset($pInfo->meta['canConvert']);
            
            // Нескладируемите продукти дебит 703. Складируемите и вложими 706 останалите 701
            $creditAccId = ($storable) ? (($convertable) ? '706' : '701') : '703';
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
            
            if ($storable) {
                $entries = array_merge($entries, $this->getDeliveryPart($rec, $product, $posRec, $convertable));
            }
        }
        
        return $entries;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за продажба (ако има)
     * Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 701. Приходи от продажби на стоки и продукти     (Клиент, Сделки, Стоки и Продукти)
     *    	  706. Приходи от продажба на суровини/материали   (Клиент, Сделки, Суровини и материали)
     *
     *    Ct: 321. Суровини, материали, продукция, стоки 	   (Склад, Стоки и Продукти)
     *
     * @param  stdClass $rec         - записа
     * @param  array    $product     - артикула
     * @param  stdClass $posRec      - точката на продажба
     * @param  boolean  $convertable - вложим ли е продукта
     * @return array
     */
    protected function getDeliveryPart($rec, $product, $posRec, $convertable)
    {
        $entries = array();
        $creditAccId = '321';
        $debitAccId = ($convertable) ? '706' : '701';
        
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
                        array('store_Stores', $posRec->storeId),
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
     * @param  stdClass $rec
     * @return array
     */
    protected function getPaymentPart($rec, $paymentsArr, $posRec)
    {
        $entries = array();
        
        // Продажбата съхранява валутата като ISO код; преобразуваме в ПК.
        $currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
        $nonCashPayments = array();
        
        foreach ($paymentsArr as $payment) {
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
            
        if (count($nonCashPayments)) {
            foreach ($nonCashPayments as $payment1) {
                $entries[] = array(
                        'amount' => currency_Currencies::round($payment1->amount),
                
                        'debit' => array(
                                '502', // Сметка "502. Каси - безналични плащания"
                                array('cash_Cases', $posRec->caseId),
                                array('cond_Payments', $payment1->value),
                                'quantity' => currency_Currencies::round($payment1->amount),
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
}
