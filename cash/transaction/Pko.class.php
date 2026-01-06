<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа cash_Pko
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class cash_transaction_Pko extends acc_DocumentTransactionSource
{
    /**
     *
     * @var cash_Pko
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        
        $origin = $this->class->getOrigin($rec);
        $rec->peroCase = (isset($rec->peroCase)) ? $rec->peroCase : $this->class->getDefaultCase($rec);

        // Ако няма вальор - ще е ДНЕС, ще се подмени и централния курс към ДНЕС
        if(empty($rec->valior)){
            $rec->valior = dt::today();
            $currencyCode = currency_Currencies::getCodeById($rec->currencyId);
            $rec->rate = currency_CurrencyRates::getRate($rec->valior, $currencyCode, null);
        }

        if ($rec->isReverse == 'yes') {
            // Ако документа е обратен, правим контировката на РКО-то но с отрицателен знак
            $entry = cash_transaction_Rko::getReverseEntries($rec, $origin);
        } else {
            
            // Ако документа не е обратен, правим нормална контировка на ПКО
            $entry = $this->getEntry($rec, $origin);
        }
        
        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
            'reason' => (!empty($rec->reason)) ? $rec->reason : deals_Helper::getPaymentOperationText($rec->operationSysId),
            'valior' => $rec->valior,   // датата на ордера
            'entries' => $entry
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
        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
        $bgnCurrencyId = currency_Currencies::getIdByCode('BGN');
        $euroCurrencyId = currency_Currencies::getIdByCode('EUR');
        if ($rec->currencyId == $baseCurrencyId) {
            $amount = $rec->amount;
        } elseif ($rec->dealCurrencyId == $baseCurrencyId) {
            $amount = $rec->amountDeal;
        } else {
            $amount = $rec->amount * $rec->rate;
        }

        $currencyId481 = ($rec->currencyId != $baseCurrencyId) ? $rec->currencyId : $rec->dealCurrencyId;
        $amount481 = ($rec->currencyId != $baseCurrencyId) ? $rec->amount : $rec->amountDeal;

        $amountE = $dealCurrencyRate * $rec->amountDeal;
        $dealCurrencyCode = currency_Currencies::getCodeById($rec->dealCurrencyId);
        if($dealCurrencyCode != $dealRec->currencyId){
            $amountE = $amount;
        }
        $amountE = deals_Helper::getSmartBaseCurrency($amountE, $dealRec->valior, $rec->valior);

        if ($reverse === true && in_array($rec->operationSysId, array('case2customerRet', 'caseAdvance2customerRet'))) {

            $transAccArr = array('481', array('currency_Currencies', $currencyId481), 'quantity' => $sign * round($amount481, 2));
            $amount = $dealCurrencyRate * $rec->amountDeal;
            if($rec->currencyId == $baseCurrencyId || ($rec->currencyId == $bgnCurrencyId && $baseCurrencyId == $euroCurrencyId)){
                $transAccArr = array('482', array($rec->contragentClassId, $rec->contragentId),
                    array($origin->className, $origin->that),
                    array('currency_Currencies', $rec->currencyId),
                    'quantity' => $sign * round($rec->amount, 2));

                $amount = round($rec->amount * $rec->rate, 2);
            }

            $entry1 = array('amount' => $sign * round($amount, 2),
                'debit' => $transAccArr,
                'credit' => array($rec->creditAccount,
                    array($rec->contragentClassId, $rec->contragentId),
                    array($origin->className, $origin->that),
                    array('currency_Currencies', $rec->dealCurrencyId),
                    'quantity' => $sign * round($rec->amountDeal, 2)),);

            $entry[] = $entry1;
            $transAccArr['quantity'] = abs($transAccArr['quantity']);
            $entry2 = array('amount' => round($rec->amount * $rec->rate, 2),
                'debit' => $transAccArr,
                'credit' => array($rec->debitAccount,
                    array('cash_Cases', $rec->peroCase),
                    array('currency_Currencies', $rec->currencyId),
                    'quantity' => round($rec->amount, 2)),);

            $entry[] = $entry2;

        } else {
            if((($rec->currencyId == $rec->dealCurrencyId && in_array($rec->dealCurrencyId, array($bgnCurrencyId, $euroCurrencyId)))) || ($baseCurrencyId == $euroCurrencyId && $rec->currencyId == $euroCurrencyId && $rec->dealCurrencyId != $bgnCurrencyId)) {

                $entry1 = array('amount' => $sign * round($amount, 2),
                    'debit' => array($rec->debitAccount,
                        array('cash_Cases', $rec->peroCase),
                        array('currency_Currencies', $rec->currencyId),
                        'quantity' => $sign * round($rec->amount, 2)),

                    'credit' => array($rec->creditAccount,
                        array($rec->contragentClassId, $rec->contragentId),
                        array($origin->className, $origin->that),
                        array('currency_Currencies', $rec->dealCurrencyId),
                        'quantity' => $sign * round($rec->amountDeal, 2)),);

                $entry[] = $entry1;
            } else {

                $entry2 = array('amount' => $sign * round($amountE, 2),
                    'debit' => array('481', array('currency_Currencies', $currencyId481),
                        'quantity' => $sign * round($amount481, 2)),

                    'credit' => array($rec->creditAccount,
                        array($rec->contragentClassId, $rec->contragentId),
                        array($origin->className, $origin->that),
                        array('currency_Currencies', $rec->dealCurrencyId),
                        'quantity' => $sign * round($rec->amountDeal, 2)),);

                $entry[] = $entry2;

                $entry1 = array('amount' => $sign * round($rec->amount * $rec->rate, 2),
                    'debit' => array($rec->debitAccount,
                        array('cash_Cases', $rec->peroCase),
                        array('currency_Currencies', $rec->currencyId),
                        'quantity' => $sign * round($rec->amount, 2)),

                    'credit' => array('481',
                        array('currency_Currencies', $currencyId481),
                        'quantity' => $sign * round($amount481, 2)),);
                $entry[] = $entry1;
            }

            if ($reverse === false) {
                $dQuery = cash_NonCashPaymentDetails::getQuery();
                $dQuery->where("#classId = {$this->class->getClassId()} AND #objectId = '{$rec->id}'");

                $cCode = currency_Currencies::getCodeById($rec->currencyId);
                while ($dRec = $dQuery->fetch()) {
                    $baseAmount = $dRec->amount;
                    $dRec->amount = currency_CurrencyRates::convertAmount($baseAmount, $rec->valior, $cCode);

                    $dRec->amount /= $rec->rate;
                    $amount = round($dRec->amount * $rec->rate, 2);

                    $type = cond_Payments::getTitleById($dRec->paymentId);

                    $entry[] = array('amount' => $sign * $amount,
                        'debit' => array('502',
                            array('cash_Cases', $rec->peroCase),
                            array('cond_Payments', $dRec->paymentId),
                            'quantity' => $sign * $amount),
                        'credit' => array($rec->debitAccount,
                            array('cash_Cases', $rec->peroCase),
                            array('currency_Currencies', $rec->currencyId),
                            'quantity' => $sign * round($dRec->amount, 2)),
                        'reason' => "Плащане с '{$type}'");
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
