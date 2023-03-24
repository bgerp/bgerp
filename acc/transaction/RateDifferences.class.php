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

        if($Deal->isInstanceOf('purchase_Purchases')){
            $entries = $this->getPurchaseEntries($rec, $documents, $dealRec, $result->totalAmount, $rec->data);
            if(countR($entries)){
                $result->entries = $entries;
            }
        }

        $rec->total = array_sum($rec->data);
        $this->class->save_($rec, 'data,total');

        return $result;
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
                $strategyRate = acc_strategy_WAC::getAmount(1, $rec->valior, $creditAccId, $item1Id, $currencyItemId, null, 1);
               // bp($strategyRate, 1, $rec->valior, $creditAccId, $item1Id, $currencyItemId, null);


                if(round($docRec->amountDeal, 2) != round($docRec->amount, 2)){
                    $strategyRate = acc_strategy_WAC::getAmount(1, $rec->valior, $creditAccId, $item1Id, $currencyItemId, null);
                    $delta = $docRec->amount / $docRec->amountDeal;
                    $strategyRate = $strategyRate * $delta;
                }



              //  $diffAmount = ($docRec->amountDeal * $rate) - ($docRec->amountDeal * $strategyRate);


                $diffRate = round($rate - $strategyRate, 7);
                //bp($docRec->amountDeal, $rate, $strategyRate);

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
                'reason' => "Валутни разлики " . $Doc->getHandle());
        }

        return $entries;

    }
}