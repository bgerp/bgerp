<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа bank_SpendingDocuments
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 */
class bank_transaction_SpendingDocument extends acc_DocumentTransactionSource
{
    /**
     *
     * @var bank_SpendingDocuments
     */
    public $class;


    /**
     * Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     * Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        $origin = $this->class->getOrigin($rec);

        // Ако няма вальор - ще е ДНЕС, ще се подмени и централния курс към ДНЕС
        if(empty($rec->valior)){
            $rec->valior = dt::today();
            $currencyCode = currency_Currencies::getCodeById($rec->currencyId);
            $rec->rate = currency_CurrencyRates::getRate($rec->valior, $currencyCode, null);
        }

        if ($rec->isReverse == 'yes') {
            // Ако документа е обратен, правим контировката на ПБД-то но с отрицателен знак
            $entry = bank_transaction_IncomeDocument::getReverseEntries($rec, $origin);
        } else {

            // Ако документа не е обратен, правим нормална контировка на РБД
            $entry = $this->getEntry($rec, $origin);
        }

        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
            'reason' => (!empty($rec->reason)) ? $rec->reason : deals_Helper::getPaymentOperationText($rec->operationSysId),   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => $entry,
        );

        return $result;
    }


    /**
     * Връща записа на транзакцията
     */
    private function getEntry($rec, $origin, $reverse = false)
    {
        // Ако е обратна транзакцията, сумите и к-та са с минус
        $sign = ($reverse) ? -1 : 1;

        $dealRec = $origin->fetch();
        $dealCurrencyRate = $dealRec->currencyRate;
        $bgnCurrencyId = currency_Currencies::getIdByCode('BGN');
        $euroCurrencyId = currency_Currencies::getIdByCode('EUR');

        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
        if ($rec->currencyId == $baseCurrencyId) {
            $amount = $rec->amount;
        } elseif ($rec->dealCurrencyId == $baseCurrencyId) {
            $amount = $rec->amountDeal;
        } else {
            $amount = $rec->amount * $rec->rate;
        }

        $currencyId481 = ($rec->currencyId != $baseCurrencyId) ? $rec->currencyId : $rec->dealCurrencyId;
        $amount481 = ($rec->currencyId != $baseCurrencyId) ? $rec->amount : $rec->amountDeal;

        $dealCompareCurrencyCode = $dealRec->currencyId;
        if($dealRec->valior < acc_Setup::getEurozoneDate() && $dealRec->oldCurrencyId == 'BGN'){
            $dealCompareCurrencyCode = $dealRec->oldCurrencyId;
            $dealCurrencyRate = 1;
        }

        $amountE = $dealCurrencyRate * $rec->amountDeal;
        $dealCurrencyCode = currency_Currencies::getCodeById($rec->dealCurrencyId);
        if($dealCurrencyCode != $dealCompareCurrencyCode){
            $amountE = $amount;
        }

        $amountE = deals_Helper::getSmartBaseCurrency($amountE, $dealRec->valior, $rec->valior);

        if ($reverse === true && in_array($rec->operationSysId, array('supplier2bankRet', 'supplierAdvance2bankRet'))) {
            $transAccArr = array('481', array('currency_Currencies', $currencyId481), 'quantity' => $sign * round($amount481, 2));
            if($rec->currencyId == $baseCurrencyId && $rec->dealCurrencyId == $baseCurrencyId){
                $transAccArr = array('482', array($rec->contragentClassId, $rec->contragentId),
                    array($origin->className, $origin->that),
                    array('currency_Currencies', $rec->currencyId),
                    'quantity' => $sign * round($rec->amount, 2));
            }

            $entry[] = array('amount' => $sign * $amountE,
                'debit' => array($rec->debitAccId,
                    array($rec->contragentClassId, $rec->contragentId),
                    array($origin->className, $origin->that),
                    array('currency_Currencies', $rec->dealCurrencyId),
                    'quantity' => $sign * round($rec->amountDeal, 2)),
                'credit' => $transAccArr);

            $transAccArr['quantity'] = abs($transAccArr['quantity']);
            $entry[] = array('amount' => round($rec->amount * $rec->rate, 2),
                'debit' => array($rec->creditAccId,
                    array('bank_OwnAccounts', $rec->ownAccount),
                    array('currency_Currencies', $rec->currencyId),
                    'quantity' => round($rec->amount, 2)),
                'credit' => $transAccArr
            );
        } else {
            $hasEarlierPayment = (!empty($rec->earlyPaymentUntil) && ($rec->valior <= $rec->earlyPaymentUntil));

            if((($rec->currencyId == $rec->dealCurrencyId && in_array($rec->dealCurrencyId, array($bgnCurrencyId, $euroCurrencyId)))) || ($baseCurrencyId == $euroCurrencyId && $rec->currencyId == $euroCurrencyId && $rec->dealCurrencyId != $bgnCurrencyId)) {

                // Ако има отстъпка за предсрочно плащане попълва се
                $amountEntry =  $amount;
                $debitQuantity = $rec->amountDeal;
                $creditQuantity = $rec->amount;
                $amountDiscount = $debitDiscount = 0;
                if($hasEarlierPayment){
                    $amountDiscount = $amountEntry * $rec->earlyPaymentPercent;
                    $debitDiscount = $debitQuantity * $rec->earlyPaymentPercent;
                    $amountEntry = $amountEntry * (1 - $rec->earlyPaymentPercent);
                    $debitQuantity = $debitQuantity * (1 - $rec->earlyPaymentPercent);
                    $creditQuantity = $creditQuantity * (1 - $rec->earlyPaymentPercent);
                }

                $entry[] = array('amount' => $sign * round($amountEntry, 2),
                    'debit' => array($rec->debitAccId,
                        array($rec->contragentClassId, $rec->contragentId),
                        array($origin->className, $origin->that),
                        array('currency_Currencies', $rec->dealCurrencyId),
                        'quantity' => $sign * round($debitQuantity, 2)),
                    'credit' => array($rec->creditAccId,
                        array('bank_OwnAccounts', $rec->ownAccount),
                        array('currency_Currencies', $rec->currencyId),
                        'quantity' => $sign * round($creditQuantity, 2)));

                if($hasEarlierPayment){
                    $entry[] = array('amount' => $sign * round($amountDiscount, 2),
                        'debit' => array($rec->debitAccId,
                            array($rec->contragentClassId, $rec->contragentId),
                            array($origin->className, $origin->that),
                            array('currency_Currencies', $rec->dealCurrencyId),
                            'quantity' => $sign * round($debitDiscount, 2)),
                        'credit' => array(729));

                }
            } else {

                // Ако има отстъпка за предсрочно плащане попълва се
                $mainAmount481 = $amount;
                $creditQuantity481 = $rec->amount;
                $originalAmount481 = $amount481;

                $entry[] = array('amount' => $sign * round($amountE, 2),
                    'debit' => array($rec->debitAccId,
                        array($rec->contragentClassId, $rec->contragentId),
                        array($origin->className, $origin->that),
                        array('currency_Currencies', $rec->dealCurrencyId),
                        'quantity' => $sign * round($rec->amountDeal, 2)),
                    'credit' => array(481,
                        array('currency_Currencies', $currencyId481),
                        'quantity' => $sign * round($amount481, 2)));

                if($hasEarlierPayment){
                    $mainAmount481 = $mainAmount481 * (1 - $rec->earlyPaymentPercent);
                    $amount481 = $amount481 * (1 - $rec->earlyPaymentPercent);
                    $creditQuantity481 = $creditQuantity481 * (1 - $rec->earlyPaymentPercent);
                }

                $entry[] = array('amount' => $sign * round($mainAmount481, 2),
                    'debit' => array(481,
                        array('currency_Currencies', $currencyId481),
                        'quantity' => $sign * round($amount481, 2)),
                    'credit' => array($rec->creditAccId,
                        array('bank_OwnAccounts', $rec->ownAccount),
                        array('currency_Currencies', $rec->currencyId),
                        'quantity' => $sign * round($creditQuantity481, 2))
                );

                if($hasEarlierPayment){
                    $amountDiscount = $originalAmount481 * $rec->earlyPaymentPercent;
                    $amountDiscount729 = currency_CurrencyRates::convertAmount($amountDiscount, $rec->valior, currency_Currencies::getCodeById($rec->currencyId));
                    $entry[] = array('amount' => $sign * round($amountDiscount729, 2),
                        'debit' => array(481,
                            array('currency_Currencies', $currencyId481),
                            'quantity' => $sign * round($amountDiscount, 2)),
                        'credit' => array(729));

                }
            }
        }

        return $entry;
    }


    /**
     * Връща обратна контировка на стандартната
     */
    public static function getReverseEntries($rec, $origin)
    {
        $self = cls::get(get_called_class());

        return $self->getEntry($rec, $origin, true);
    }
}
