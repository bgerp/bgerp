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
        $rec->data = array();
        $result->valior = $rec->valior;

        $paymentIds = array(sales_Sales::getClassId(), purchase_Purchases::getClassId(), cash_Pko::getClassId(), cash_Rko::getClassId(), bank_IncomeDocuments::getClassId(), bank_SpendingDocuments::getClassId());
        $query = doc_Containers::getQuery();
        $query->where("#state = 'active' AND #threadId = {$rec->threadId}");
        $query->in('docClass', $paymentIds);
        $documents = $query->fetchAll();

        $Deal = doc_Containers::getDocument($rec->dealOriginId);
        $dealRec = $Deal->fetch();
        $entries = array();
        if($Deal->isInstanceOf('purchase_Purchases')){
            $entries = $this->getPurchaseEntries($rec, $documents, $dealRec, $result->totalAmount, $rec->data);
        } elseif($Deal->isInstanceOf('sales_Sales')){
            $entries = $this->getSaleEntries($rec, $documents, $dealRec, $result->totalAmount, $rec->data);
        }
        if(countR($entries)){
            $result->entries = $entries;
        }

        $rec->lastRecalced = dt::now();
        $rec->total = array_sum($rec->data);
        $this->class->save_($rec, 'data,total,lastRecalced');

        return $result;
    }

    private function getSaleEntries($rec, $documents, $dealRec, &$totalAmount, &$data)
    {
        $entries = array();

        foreach ($documents as $d) {
            $Doc = doc_Containers::getDocument($d->id);
            $docRec = $Doc->fetch();
            $rate = $rec->rate;

            if($Doc->isInstanceOf('deals_PaymentDocument')){

                $sign = ($docRec->isReverse == 'yes') ? -1 : 1;
                $currencyId = currency_Currencies::getIdByCode($dealRec->currencyId);
                $currencyCode = currency_Currencies::getCodeById($docRec->currencyId);
                $strategyRate = currency_CurrencyRates::getRate($docRec->valior, $currencyCode, null);
                if($docRec->isReverse == 'yes' && in_array($docRec->operationSysId, array('case2customerRet', 'bank2customerRet', 'caseAdvance2customerRet', 'bankAdvance2customerRet'))){
                    continue;
                }

                if($Doc->isInstanceOf('bank_Document')){
                    $creditAccId = $docRec->creditAccId;
                } else {
                    $creditAccId = $docRec->creditAccount;
                }

                if(round($docRec->amountDeal, 2) != round($docRec->amount, 2)){
                    $delta = $docRec->amount / $docRec->amountDeal;
                    $strategyRate = $strategyRate * $delta;
                }

                $diffRate = round($rate - $strategyRate, 5);
                $finalAmount = round($diffRate * $sign * $docRec->amountDeal, 2);
                if($finalAmount){
                    $totalAmount += $finalAmount;

                    $data[$docRec->containerId] = $finalAmount;

                    $entries[] = array('amount' => $finalAmount,
                        'credit' => array($creditAccId,
                            array($dealRec->contragentClassId, $dealRec->contragentId),
                            array('sales_Sales', $dealRec->id),
                            array('currency_Currencies', $currencyId),
                            'quantity' => $sign * round($docRec->amountDeal, 2)),
                        'debit' => array('481',
                            array('currency_Currencies', $currencyId),
                            'quantity' => $sign * round($docRec->amountDeal, 2)),
                        'reason' => "Валутни разлики");
                }
            }
        }

        return $entries;
    }


    private function getPurchaseEntries($rec, $documents, $dealRec, &$totalAmount, &$data)
    {
        $entries = array();

        foreach ($documents as $d){
            $Doc = doc_Containers::getDocument($d);
            $docRec = $Doc->fetch();
            $rate = $rec->rate;

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
                $strategyRate = acc_strategy_WAC::getAmount(1, $rec->valior, $creditAccId, $item1Id, $currencyItemId, null);
                if(empty($strategyRate)){
                    $strategyRate = currency_CurrencyRates::getRate($rec->valior, currency_Currencies::getCodeById($docRec->currencyId), null);
                }

                if(round($docRec->amountDeal, 2) != round($docRec->amount, 2)){
                    $delta = $docRec->amount / $docRec->amountDeal;
                    $strategyRate = $strategyRate * $delta;
                }

                $diffRate = round($rate - $strategyRate, 5);

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

                $strategyRate = acc_strategy_WAC::getAmount(1, $rec->valior, 501, $caseItemId, $currencyItemId, null);

                $debitAccId = '401';
                $diffRate = round($rate - $strategyRate, 5);
                $finalAmount = round($diffRate * $sign * ($docRec->amountDeal / $rate), 2);
                $totalAmount += $finalAmount;

                $debitQuantity = ($docRec->amountDeal / $rate);
                $creditQuantity = $debitQuantity;
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