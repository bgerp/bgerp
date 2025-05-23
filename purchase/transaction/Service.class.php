<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_Services
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
class purchase_transaction_Service extends acc_DocumentTransactionSource
{
    /**
     *
     * @var purchase_Services
     */
    public $class;
    
    
    /**
     * Транзакция за запис в журнала
     *
     * @param int $id
     */
    public function getTransaction($id)
    {
        $entries = array();
        $rec = $this->class->fetchRec($id);
        $origin = $this->class->getOrigin($rec);
        
        if (!empty($rec->id)) {
            $dQuery = purchase_ServicesDetails::getQuery();
            $dQuery->where("#shipmentId = {$rec->id}");
            $rec->details = $dQuery->fetchAll();
        }
        
        $entries = array();
        
        // Всяко ЕН трябва да има поне един детайл
        if (countR($rec->details) > 0) {
            if ($rec->isReverse == 'yes') {
                
                // Ако ЕН е обратна, тя прави контировка на СР но с отрицателни стойностти
                $reverseSource = cls::getInterface('acc_TransactionSourceIntf', 'sales_Services');
                $entries = $reverseSource->getReverseEntries($rec, $origin);
            } else {
                // Записите от тип 1 (вземане от клиент)
                $entries = $this->getEntries($rec, $origin);
            }
        }
        
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        
        $transaction = (object) array(
            'reason' => 'Протокол за покупка на услуги #' . $rec->id,
            'valior' => $rec->valior,
            'entries' => $entries,
        );
        
        if (acc_Journal::throwErrorsIfFoundWhenTryingToPost()) {
            $property = ($rec->isReverse == 'yes') ? 'canSell' : 'canBuy';
            
            $productArr = arr::extractValuesFromArray($rec->details, 'productId');
            if (countR($productArr)) {
                $msg = ($rec->isReverse == 'yes') ? 'продаваеми услуги' : 'купуваеми услуги';
                $msg = "трябва да са {$msg} и да не са генерични";
                
                if($redirectError = deals_Helper::getContoRedirectError($productArr, $property, 'canStore,generic', $msg)){
                    
                    acc_journal_RejectRedirect::expect(false, $redirectError);
                }
            }
        }
        
        return $transaction;
    }
    
    
    /**
     * Генериране на записите от тип за изпълнение на услуги (ако има)
     *
     *    Dt: 60201. Разходи за (нескладируеми) услуги и консумативи    (Разходни обекти, Артикули)
     *
     *    Ct: 401. Задължения към доставчици   (Доставчик, Сделки, Валута)
     */
    public function getEntries($rec, $origin, $reverse = false)
    {
        $entries = array();
        $sign = ($reverse) ? -1 : 1;
        $dClass = ($reverse) ? 'sales_ServicesDetails' : 'purchase_ServicesDetails';
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);

        if (countR($rec->details)) {
            $firstRec = $firstDoc->fetch();
            $checkVatCredit = $firstDoc->isInstanceOf('purchase_Purchases') && $firstRec->haveVatCreditProducts == 'no';
            $entriesLast = array();

            deals_Helper::fillRecs($this->class, $rec->details, $rec, array('alwaysHideVat' => true));
            $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
            foreach ($rec->details as $dRec) {
                
                // Към кои разходни обекти ще се разпределят разходите
                $splitRecs = acc_CostAllocations::getRecsByExpenses($dClass, $dRec->id, $dRec->productId, $dRec->quantity, $dRec->amount, $dRec->discount);

                $revertVatPercent = null;
                if($checkVatCredit) {
                    $vatExceptionId = cond_VatExceptions::getFromThreadId($rec->threadId);
                    $revertVatPercent = cat_Products::getVat($dRec->productId, $rec->valior, $vatExceptionId);
                }

                foreach ($splitRecs as $dRec1) {
                    $amount = $dRec1->amount;
                    $amountAllocated = $amount * $rec->currencyRate;
                    
                    $entries[] = array(
                        'amount' => $sign * $amountAllocated, // В основна валута
                        'debit' => array('60201',
                            $dRec1->expenseItemId,
                            array('cat_Products', $dRec1->productId),
                            'quantity' => $sign * $dRec1->quantity),
                        'credit' => array($rec->accountId,
                            array($rec->contragentClassId, $rec->contragentId),
                            array($origin->className, $origin->that),
                            array('currency_Currencies', $currencyId),
                            'quantity' => $sign * $amount,
                        ),
                        'reason' => $dRec1->reason,
                    );
                    
                    // Корекция на стойности при нужда
                    if (isset($dRec1->correctProducts) && countR($dRec1->correctProducts)) {
                        $correctionEntries = acc_transaction_ValueCorrection::getCorrectionEntries($dRec1->correctProducts, $dRec1->productId, $dRec1->expenseItemId, $dRec1->quantity, $dRec1->allocationBy, $reverse);
                        if (countR($correctionEntries)) {
                            $entries = array_merge($entries, $correctionEntries);
                        }
                    }

                    if(isset($revertVatPercent)) {
                        $entriesLast[] = array(
                            'amount' => $sign * round($amountAllocated * $revertVatPercent, 2), // В основна валута
                            'debit' => array('60201',
                                $dRec1->expenseItemId,
                                array('cat_Products', $dRec1->productId),
                                'quantity' => 0),
                            'credit' => array('4530',
                                array($origin->className, $origin->that),
                            ),
                            'reason' => 'Сторно ДДС за начисляване при покупка - сделка БЕЗ право на Данъчен кредит');
                    }
                }
            }

            // Отчитаме ддс-то
            if ($this->class->_total) {
                $vat = $this->class->_total->vat;
                $vatAmount = $this->class->_total->vat * $rec->currencyRate;
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
                    'reason' => 'ДДС за начисляване при фактуриране',
                );
            }

            if(countR($entriesLast)){
                $entries = array_merge($entries, $entriesLast);
            }
        }

        return $entries;
    }
    
    
    /**
     * Връща обратна контировка на стандартната
     */
    public function getReverseEntries($rec, $origin)
    {
        return $this->getEntries($rec, $origin, true);
    }
}
