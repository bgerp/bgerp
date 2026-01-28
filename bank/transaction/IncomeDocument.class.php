<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа bank_IncomeDocuments
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
class bank_transaction_IncomeDocument extends acc_DocumentTransactionSource
{
    /**
     *
     * @var bank_IncomeDocuments
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
            // Ако документа е обратен, правим контировката на РБД-то но с отрицателен знак
            $entry = bank_transaction_SpendingDocument::getReverseEntries($rec, $origin);
        } else {
            
            // Ако документа не е обратен, правим нормална контировка на ПБД
            $entry = $this->getEntry($rec, $origin);
        }
        


        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
            'reason' => (!empty($rec->reason)) ? $rec->reason : deals_Helper::getPaymentOperationText($rec->operationSysId),   // основанието за ордера
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

        if ($reverse === true && in_array($rec->operationSysId, array('bank2customerRet', 'bankAdvance2customerRet'))) {
            $transAccArr = array('481', array('currency_Currencies', $currencyId481), 'quantity' => $sign * round($amount481, 2));
            if($rec->currencyId == $baseCurrencyId && $rec->dealCurrencyId == $baseCurrencyId){
                $transAccArr = array('482', array($rec->contragentClassId, $rec->contragentId),
                                          array($origin->className, $origin->that),
                                          array('currency_Currencies', $rec->currencyId),
                                        'quantity' => $sign * round($rec->amount, 2));
            }

            $entry1 = array('amount' => $sign * $amountE,
                'debit' => $transAccArr,
                'credit' => array($rec->creditAccId,
                    array($rec->contragentClassId, $rec->contragentId),
                    array($origin->className, $origin->that),
                    array('currency_Currencies', $rec->dealCurrencyId),
                    'quantity' => $sign * round($rec->amountDeal, 2)),);

            $entry[] = $entry1;
            $transAccArr['quantity'] = abs($transAccArr['quantity']);
            $entry2 = array('amount' => round($rec->amount * $rec->rate, 2),
                'debit' => $transAccArr,
                'credit' => array($rec->debitAccId,
                    array('bank_OwnAccounts', $rec->ownAccount),
                    array('currency_Currencies', $rec->currencyId),
                    'quantity' => round($rec->amount, 2)),);

            $entry[] = $entry2;

        } else {

            if((($rec->currencyId == $rec->dealCurrencyId && in_array($rec->dealCurrencyId, array($bgnCurrencyId, $euroCurrencyId)))) || ($baseCurrencyId == $euroCurrencyId && $rec->currencyId == $euroCurrencyId && $rec->dealCurrencyId != $bgnCurrencyId)) {
                $entry1 = array('amount' => $sign * round($amount, 2),
                    'debit' => array($rec->debitAccId,
                        array('bank_OwnAccounts', $rec->ownAccount),
                        array('currency_Currencies', $rec->currencyId),
                        'quantity' => $sign * round($rec->amount, 2)),

                    'credit' => array($rec->creditAccId,
                        array($rec->contragentClassId, $rec->contragentId),
                        array($origin->className, $origin->that),
                        array('currency_Currencies', $rec->dealCurrencyId),
                        'quantity' => $sign * round($rec->amountDeal, 2)),);

                $entry[] = $entry1;
            } else {
                $entry2 = array('amount' => $sign * round($amountE, 2),
                    'debit' => array('481', array('currency_Currencies', $currencyId481),
                        'quantity' => $sign * round($amount481, 2)),

                    'credit' => array($rec->creditAccId,
                        array($rec->contragentClassId, $rec->contragentId),
                        array($origin->className, $origin->that),
                        array('currency_Currencies', $rec->dealCurrencyId),
                        'quantity' => $sign * round($rec->amountDeal, 2)),);

                $entry[] = $entry2;

                $entry1 = array('amount' => $sign * round($rec->amount * $rec->rate, 2),
                    'debit' => array($rec->debitAccId,
                        array('bank_OwnAccounts', $rec->ownAccount),
                        array('currency_Currencies', $rec->currencyId),
                        'quantity' => $sign * round($rec->amount, 2)),

                    'credit' => array('481',
                        array('currency_Currencies', $currencyId481),
                        'quantity' => $sign * round($amount481, 2)),);
                $entry[] = $entry1;
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
