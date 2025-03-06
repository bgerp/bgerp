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
        
        if ($rec->isReverse == 'yes') {
            // Ако документа е обратен, правим контировката на РБД-то но с отрицателен знак
            $entry = bank_transaction_SpendingDocument::getReverseEntries($rec, $origin);
        } else {
            
            // Ако документа не е обратен, правим нормална контировка на ПБД
            $entry = $this->getEntry($rec, $origin);
        }
        
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        
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

        $dealCurrencyRate = $origin->fetchField('currencyRate');
        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
        if ($rec->currencyId == $baseCurrencyId) {
            $amount = $rec->amount;
        } elseif ($rec->dealCurrencyId == $baseCurrencyId) {
            $amount = $rec->amountDeal;
        } else {
            if ($reverse === true && ($rec->operationSysId == 'bank2customerRet' || $rec->operationSysId == 'bankAdvance2customerRet')) {
                $amount = $rec->amount * $dealCurrencyRate;
            } else {
                $amount = $rec->amount * $rec->rate;
            }
        }

        if ($reverse === true && in_array($rec->operationSysId, array('bank2customerRet', 'bankAdvance2customerRet'))) {
            $entry1 = array('amount' => $sign * round($rec->amountDeal * $dealCurrencyRate, 2),
                'debit' => array(481,
                    array('currency_Currencies', $rec->currencyId),
                    'quantity' => $sign * round($rec->amount, 2)),

                'credit' => array($rec->creditAccId,
                    array($rec->contragentClassId, $rec->contragentId),
                    array($origin->className, $origin->that),
                    array('currency_Currencies', $rec->dealCurrencyId),
                    'quantity' => $sign * round($rec->amountDeal, 2)),);

            $entry[] = $entry1;

            $entry2 = array('amount' => round($amount, 2),
                'debit' => array(481, array('currency_Currencies', $rec->currencyId),
                    'quantity' => round($rec->amount, 2)),

                'credit' => array($rec->debitAccId,
                    array('bank_OwnAccounts', $rec->ownAccount),
                    array('currency_Currencies', $rec->currencyId),
                    'quantity' => round($rec->amount, 2)),);

            $entry[] = $entry2;

        } else {
            if($rec->currencyId != $baseCurrencyId || $rec->dealCurrencyId != $baseCurrencyId){
                $currencyId481 = ($rec->currencyId != $baseCurrencyId) ? $rec->currencyId : $rec->dealCurrencyId;
                $amount481 = ($rec->currencyId != $baseCurrencyId) ? $rec->amount : $$rec->amountDeal;

                $entry1 = array('amount' => $sign * round($amount, 2),
                    'debit' => array($rec->debitAccId,
                        array('bank_OwnAccounts', $rec->ownAccount),
                        array('currency_Currencies', $rec->currencyId),
                        'quantity' => $sign * round($rec->amount, 2)),

                    'credit' => array(481,
                        array('currency_Currencies', $currencyId481),
                        'quantity' => $sign * round($amount481, 2)),);
                $entry[] = $entry1;

                $entry2 = array('amount' => $sign * round($dealCurrencyRate * $rec->amountDeal, 2),
                    'debit' => array(481, array('currency_Currencies', $currencyId481),
                        'quantity' => $sign * round($amount481, 2)),

                    'credit' => array($rec->creditAccId,
                        array($rec->contragentClassId, $rec->contragentId),
                        array($origin->className, $origin->that),
                        array('currency_Currencies', $rec->dealCurrencyId),
                        'quantity' => $sign * round($rec->amountDeal, 2)),);

                $entry[] = $entry2;
            } else {
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
