<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа acc_ValueCorrections
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class acc_transaction_RateDifferences extends acc_DocumentTransactionSource
{
    /**
     * @param int $id
     *
     * @return stdClass
     *
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function getTransaction($id)
    {
        // Извличане на мастър-записа
        expect($rec = $this->class->fetchRec($id));

        $result = (object)array(
            'reason' => $rec->reason,
            'valior' => null,
            'totalAmount' => 0,
            'entries' => array()
        );

        $rec->valior = $this->class->getDefaultValior($rec);
        $result->valior = $rec->valior;

        $tData = static::getTransactionData($rec->rate, $rec->valior, $rec->threadId);
        if (countR($tData->entries)) {
            $result->entries = $tData->entries;
            $result->totalAmount = $tData->amount;
        }

        $sumTotal = array_sum($tData->data);
        $rec->lastRecalced = dt::now();
        if(isset($rec->id)){
            if($sumTotal != $rec->total){
                $rec->oldTotal = $rec->total;
                $rec->oldData = $rec->data;
            }
        } else {
            $rec->oldTotal = null;
            $rec->oldData = null;
        }
        $rec->data = $tData->data;
        $rec->total = $sumTotal;

        return $result;
    }


    /**
     * Връща транзакционните данни
     *
     * @param double $rate
     * @param date $valior
     * @param int $threadId
     * @return object
     */
    public static function getTransactionData($rate, $valior, $threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        $dealRec = $firstDoc->fetch();

        $res = (object)array('entries' => array(), 'amount' => 0, 'data' => array());

        // В кои нишки ще се търсят платежните документи
        $threads = array($dealRec->threadId => $dealRec->threadId);
        $closedDocs = keylist::toArray($dealRec->closedDocuments);
        if (countR($closedDocs)) {

            // Ако е обединяващ договор ще се гледа и в обединените такива
            $tQuery = $firstDoc->getInstance()->getQuery();
            $tQuery->in('id', $closedDocs);
            $tQuery->show('threadId');
            $threads += arr::extractValuesFromArray($tQuery->fetchAll(), 'threadId');
        }

        $paymentIds = array(sales_Sales::getClassId(), purchase_Purchases::getClassId(), acc_ValueCorrections::getClassId());
        $query = doc_Containers::getQuery();
        $query->where("#state = 'active'");
        $query->in('docClass', $paymentIds);
        $query->in('threadId', $threads);
        $query->show('id');
        $documents = $query->fetchAll();

        if($firstDoc->isInstanceOf('purchase_Purchases')){
            $res->entries = static::getPurchaseEntries($rate, $valior, $documents, $dealRec, $res->amount, $res->data);
        } elseif($firstDoc->isInstanceOf('sales_Sales')){
            $res->entries = static::getSaleEntries($rate, $valior, $documents, $dealRec, $res->amount, $res->data);
        } elseif($firstDoc->isInstanceOf('findeals_Deals')){
            $res->entries = static::getFinDealEntries($rate, $valior, $documents, $dealRec, $res->amount, $res->data);
        }

        return $res;
    }


    /**
     * Контировка за курсови разлики към продажба
     *
     * @param double $rate        - курс
     * @param date $valior        - вальор
     * @param array $documents    - масив с платежни документи
     * @param stdClass $dealRec   - запис на сделката
     * @param double $totalAmount - обща сума досега
     * @param  array $data        - масив с намерените документи и коригираните суми
     * @return array
     */
    private static function getFinDealEntries($rate, $valior, $documents, $dealRec, &$totalAmount, &$data)
    {
        $entries = array();

        foreach ($documents as $d) {
            $Doc = doc_Containers::getDocument($d->id);
            $docRec = $Doc->fetch();

            $sign = ($docRec->isReverse == 'yes') ? -1 : 1;

            if ($Doc->isInstanceOf('deals_PaymentDocument')) {
                    $currencyId = $docRec->dealCurrencyId;
                    if($Doc->isInstanceOf('bank_Document')){
                        $creditAccId = $docRec->creditAccId;
                        $debitAccId = $docRec->debitAccId;
                    } else {
                        $creditAccId = $docRec->creditAccount;
                        $debitAccId = $docRec->debitAccount;
                    }

                    $currencyItemId = acc_Items::fetchItem('currency_Currencies', $currencyId)->id;
                    $currencyCode = currency_Currencies::getCodeById($currencyId);

                    if($Doc->isInstanceOf('cash_Pko') || $Doc->isInstanceOf('bank_IncomeDocuments')){
                        $rateFromJournal = static::getJournalCurrencyPrice('debit', $debitAccId, $currencyItemId, $Doc);
                        $debitRecId = acc_Accounts::getRecBySystemId($creditAccId)->id;
                        $journalId = acc_Journal::fetchByDoc($Doc->getInstance(), $Doc->that)->id;
                        if(isset($journalId)){
                            $jQuery = acc_JournalDetails::getQuery();
                            $jQuery->where("#journalId = {$journalId} AND #creditAccId = {$debitRecId} AND #creditItem3 = {$currencyItemId}");
                            $rateFromJournal = $jQuery->fetch()->creditPrice;
                        }
                    } else {
                        $debitRecId = acc_Accounts::getRecBySystemId($debitAccId)->id;
                        $journalId = acc_Journal::fetchByDoc($Doc->getInstance(), $Doc->that)->id;
                        if(isset($journalId)){
                            $jQuery = acc_JournalDetails::getQuery();
                            $jQuery->where("#journalId = {$journalId} AND #debitAccId = {$debitRecId} AND #debitItem3 = {$currencyItemId}");
                            $rateFromJournal = $jQuery->fetch()->debitPrice;
                        }
                    }

                    $strategyRate = !empty($rateFromJournal) ? $rateFromJournal : currency_CurrencyRates::getRate($docRec->valior, $currencyCode, null);

                    $diffRate = $rate - $strategyRate;
                    $finalAmountNotRound = $diffRate * $sign * $docRec->amountDeal;
                    $finalAmountNotRoundCheck = preg_replace('/\.(\d{2}).*/', '.$1', abs($finalAmountNotRound));
                    if(empty($finalAmountNotRoundCheck) || $finalAmountNotRoundCheck < 0.005) continue;

                    $finalAmount = round($diffRate * $sign * $docRec->amountDeal, 2);
                    $totalAmount += $finalAmount;
                    $data[$docRec->containerId] = $finalAmount;

                    if($Doc->isInstanceOf('cash_Pko') || $Doc->isInstanceOf('bank_IncomeDocuments')){
                        $entries[] = array('amount' => $finalAmount,
                            'credit' => array($creditAccId,
                                array($dealRec->contragentClassId, $dealRec->contragentId),
                                array('findeals_Deals', $dealRec->id),
                                $currencyItemId,
                                'quantity' => $sign * round($docRec->amountDeal, 2)),
                            'debit' => array('481',
                                $currencyItemId,
                                'quantity' => $sign * round($docRec->amountDeal, 2)),
                            'reason' => "Валутни разлики");
                    } else {
                        $entries[] = array('amount' => $finalAmount,
                            'debit' => array($debitAccId,
                                array($dealRec->contragentClassId, $dealRec->contragentId),
                                array('findeals_Deals', $dealRec->id),
                                $currencyItemId,
                                'quantity' => $sign * round($docRec->amountDeal, 2)),
                            'credit' => array('481',
                                $currencyItemId,
                                'quantity' => $sign * round($docRec->amountDeal, 2)),
                            'reason' => "Валутни разлики");
                    }
            }
        }

        return $entries;
    }


    /**
     * Контировка за курсови разлики към продажба
     *
     * @param double $rate        - курс
     * @param date $valior        - вальор
     * @param array $documents    - масив с платежни документи
     * @param stdClass $dealRec   - запис на сделката
     * @param double $totalAmount - обща сума досега
     * @param  array $data        - масив с намерените документи и коригираните суми
     * @return array
     */
    private static function getSaleEntries($rate, $valior, $documents, $dealRec, &$totalAmount, &$data)
    {
        $entries = array();

        foreach ($documents as $d) {
            $Doc = doc_Containers::getDocument($d->id);
            $docRec = $Doc->fetch();
            $finalAmount = null;

            $reverseDebit = false;
            if($Doc->isInstanceOf('deals_PaymentDocument')){

                $sign = ($docRec->isReverse == 'yes') ? -1 : 1;
                $currencyId = currency_Currencies::getIdByCode($dealRec->currencyId);
                $currencyCode = currency_Currencies::getCodeById($docRec->currencyId);
                if($docRec->isReverse == 'yes' && in_array($docRec->operationSysId, array('case2customerRet', 'bank2customerRet', 'caseAdvance2customerRet', 'bankAdvance2customerRet'))){
                    continue;
                }

                if($Doc->isInstanceOf('bank_Document')){
                    $creditAccId = $docRec->creditAccId;
                    $debitAccId = $docRec->debitAccId;
                } else {
                    $creditAccId = $docRec->creditAccount;
                    $debitAccId = $docRec->debitAccount;
                }

                $currencyItemId = acc_Items::fetchItem('currency_Currencies', $docRec->currencyId)->id;
                $rateFromJournal = static::getJournalCurrencyPrice('debit', $debitAccId, $currencyItemId, $Doc);
                $strategyRate = !empty($rateFromJournal) ? $rateFromJournal : currency_CurrencyRates::getRate($docRec->valior, $currencyCode, null);

                if(round($docRec->amountDeal, 2) != round($docRec->amount, 2)){
                    $delta = $docRec->amount / $docRec->amountDeal;
                    $strategyRate = $strategyRate * $delta;
                }

                $diffRate = $rate - $strategyRate;
                $finalAmount = round($diffRate * $sign * $docRec->amountDeal, 2);
                $quantity = round($docRec->amountDeal, 2);

            } elseif($Doc->isInstanceOf('acc_ValueCorrections')){
                $sign = ($docRec->action == 'increase') ? -1 : 1;
                $diffRate = round($docRec->rate - $rate, 5);

                $amount = $docRec->amount;
                if (in_array($dealRec->chargeVat, array('yes', 'separate'))) {
                    $averageRate = $Doc->getInstance()->getAverageVatRate($docRec->productsData, $docRec);
                    $amount = $amount * (1 + $averageRate);
                }

                $finalAmount = round($diffRate * $sign * ($amount / $docRec->rate), 2);
                $quantity = round($amount / $docRec->rate, 2);
                $creditAccId = 411;
                $currencyId = currency_Currencies::getIdByCode($docRec->currencyId);
                $reverseDebit = true;
            } else {
                continue;
            }

            if(!$finalAmount) continue;

            $totalAmount += $finalAmount;
            $data[$docRec->containerId] = $finalAmount;

            if($reverseDebit){
                $entries[] = array('amount' => $finalAmount,
                    'debit' => array($creditAccId,
                        array($dealRec->contragentClassId, $dealRec->contragentId),
                        array('sales_Sales', $dealRec->id),
                        array('currency_Currencies', $currencyId),
                        'quantity' => $sign * $quantity),
                    'credit' => array('481',
                        array('currency_Currencies', $currencyId),
                        'quantity' => $sign * $quantity),
                    'reason' => "Валутни разлики");
            } else {
                $entries[] = array('amount' => $finalAmount,
                    'credit' => array($creditAccId,
                        array($dealRec->contragentClassId, $dealRec->contragentId),
                        array('sales_Sales', $dealRec->id),
                        array('currency_Currencies', $currencyId),
                        'quantity' => $sign * $quantity),
                    'debit' => array('481',
                        array('currency_Currencies', $currencyId),
                        'quantity' => $sign * $quantity),
                        'reason' => "Валутни разлики");
                }
        }

        return $entries;
    }


    /**
     * Извлича изчислената от баланса цена
     *
     * @param string $type
     * @param string $creditSysId
     * @param int $currencyItemId
     * @param core_ObjectReference $Doc
     * @return null
     */
    private static function getJournalCurrencyPrice($type, $creditSysId, $currencyItemId, $Doc)
    {
        $creditRec = acc_Accounts::getRecBySystemId($creditSysId)->id;
        $journalId = acc_Journal::fetchByDoc($Doc->getInstance(), $Doc->that)->id;
        if(isset($journalId)){
            $jQuery = acc_JournalDetails::getQuery();
            if($Doc->isInstanceOf('deals_Document')){
                $jQuery->where("#journalId = {$journalId} AND #{$type}AccId = {$creditRec} AND #{$type}Item3 = {$currencyItemId}");
            } else {
                $jQuery->where("#journalId = {$journalId} AND #{$type}AccId = {$creditRec} AND #{$type}Item2 = {$currencyItemId}");
            }

            return $jQuery->fetch()->{"{$type}Price"};
        }

        return null;
    }


    /**
     * Контировка за курсови разлики към покупка
     *
     * @param double $rate        - курс
     * @param date $valior        - вальор
     * @param array $documents    - масив с платежни документи
     * @param stdClass $dealRec   - запис на сделката
     * @param double $totalAmount - обща сума досега
     * @param  array $data        - масив с намерените документи и коригираните суми
     * @return array
     */
    private static function getPurchaseEntries($rate, $valior, $documents, $dealRec, &$totalAmount, &$data)
    {
        $entries = array();

        foreach ($documents as $d){
            $Doc = doc_Containers::getDocument($d->id);
            $docRec = $Doc->fetch();

            if($Doc->isInstanceOf('deals_PaymentDocument')){
                $sign = ($docRec->isReverse == 'yes') ? -1 : 1;

                if($Doc->isInstanceOf('bank_Document')){
                    $debitAccId = $docRec->debitAccId;
                    $creditAccId = $docRec->creditAccId;
                    $item1Id = acc_Items::fetchItem('bank_OwnAccounts', $docRec->ownAccount)->id;
                } else {
                    $debitAccId = $docRec->debitAccount;
                    $creditAccId = $docRec->creditAccount;
                    $item1Id = acc_Items::fetchItem('cash_Cases', $docRec->peroCase)->id;
                }

                $currencyItemId = acc_Items::fetchItem('currency_Currencies', $docRec->currencyId)->id;

                if($docRec->isReverse == 'yes' && in_array($docRec->operationSysId, array('supplier2caseRet', 'supplier2bankRet', 'supplierAdvance2caseRet', 'supplierAdvance2bankRet'))){
                    $strategyRate = self::getJournalCurrencyPrice('debit', $creditAccId, $currencyItemId, $Doc);
                } else {
                    $strategyRate = self::getJournalCurrencyPrice('credit', $creditAccId, $currencyItemId, $Doc);
                }

                if(empty($strategyRate)){
                    $strategyRate = acc_strategy_WAC::getAmount(1, $valior, $creditAccId, $item1Id, $currencyItemId, null);
                }

                if(empty($strategyRate)){
                    $strategyRate = currency_CurrencyRates::getRate($valior, currency_Currencies::getCodeById($docRec->currencyId), null);
                }

                if(round($docRec->amountDeal, 2) != round($docRec->amount, 2)){
                    $delta = $docRec->amount / $docRec->amountDeal;
                    $strategyRate = $strategyRate * $delta;
                }
                $diffRate = $rate - $strategyRate;
                $finalAmount = round($diffRate * $sign * $docRec->amountDeal, 2);
                $totalAmount += $finalAmount;
                $debitQuantity = $docRec->amountDeal;
                $creditQuantity = $docRec->amount;
                $currencyId = $docRec->dealCurrencyId;
            } elseif($Doc->isInstanceOf('purchase_Purchases')){

                $sign = 1;
                $contoActions = type_Set::toArray($docRec->contoActions);

                if(!isset($contoActions['pay'])) continue;

                $currencyId = currency_Currencies::getIdByCode($docRec->currencyId);
                $currencyItemId = acc_Items::fetchItem('currency_Currencies', $currencyId)->id;
                $caseItemId = acc_Items::fetchItem('cash_Cases', $docRec->caseId)->id;

                $strategyRate = self::getJournalCurrencyPrice('credit',501, $currencyItemId, $Doc);
                if(empty($strategyRate)){
                    $strategyRate = acc_strategy_WAC::getAmount(1, $valior, 501, $caseItemId, $currencyItemId, null);
                }
                if(empty($strategyRate)){
                    $strategyRate = currency_CurrencyRates::getRate($valior, $docRec->currencyId, null);
                }

                $debitAccId = '401';
                $diffRate = round($rate - $strategyRate, 5);
                $finalAmount = round($diffRate * $sign * ($docRec->amountDeal / $rate), 2);
                $totalAmount += $finalAmount;

                $debitQuantity = ($docRec->amountDeal / $rate);
            } elseif($Doc->isInstanceOf('acc_ValueCorrections')) {
                $sign = ($docRec->action == 'increase') ? -1 : 1;
                $currencyId = currency_Currencies::getIdByCode($docRec->currencyId);
                $diffRate = round($docRec->rate - $rate, 5);

                $amount = $docRec->amount;
                if (in_array($dealRec->chargeVat, array('yes', 'separate'))) {
                    $averageRate = $Doc->getInstance()->getAverageVatRate($docRec->productsData, $docRec);
                    $amount = $amount * (1 + $averageRate);
                }

                $finalAmount = round($diffRate * $sign * ($amount / $docRec->rate), 2);
                $debitQuantity = ($amount / $docRec->rate);
                $totalAmount += $finalAmount;

                $data[$docRec->containerId] = $finalAmount;
                $entries[] = array('amount' => $finalAmount,
                    'credit' => array('401',
                        array($dealRec->contragentClassId, $dealRec->contragentId),
                        array('purchase_Purchases', $dealRec->id),
                        array('currency_Currencies', $currencyId),
                        'quantity' => $sign * round($debitQuantity, 2)),
                    'debit' => array('481',
                        array('currency_Currencies', $currencyId),
                        'quantity' => $sign * round($debitQuantity, 2)),
                    'reason' => "Валутни разлики");
                continue;
            } else {
                continue;
            }

            if(empty($finalAmount)) continue;

            $data[$docRec->containerId] = $finalAmount;
            $entries[] = array('amount' => $finalAmount,
                'debit' => array($debitAccId,
                    array($dealRec->contragentClassId, $dealRec->contragentId),
                    array('purchase_Purchases', $dealRec->id),
                    array('currency_Currencies', $currencyId),
                    'quantity' => $sign * round($debitQuantity, 2)),
                'credit' => array('481',
                    array('currency_Currencies', $currencyId),
                    'quantity' => $sign * round($debitQuantity, 2)),
                'reason' => "Валутни разлики");
        }

        return $entries;
    }
}
