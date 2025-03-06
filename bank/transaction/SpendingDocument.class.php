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
        
        if ($rec->isReverse == 'yes') {
            // Ако документа е обратен, правим контировката на ПБД-то но с отрицателен знак
            $entry = bank_transaction_IncomeDocument::getReverseEntries($rec, $origin);
        } else {
            
            // Ако документа не е обратен, правим нормална контировка на РБД
            $entry = $this->getEntry($rec, $origin);
        }
        
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        
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

        $dealCurrencyRate = $origin->fetchField('currencyRate');
        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
        if ($rec->currencyId == $baseCurrencyId) {
            $amount = $rec->amount;
        } elseif ($rec->dealCurrencyId == $baseCurrencyId) {
            $amount = $rec->amountDeal;
        } else {
            $item1 = acc_Items::fetchItem('bank_OwnAccounts', $rec->ownAccount)->id;
            $item2 = acc_Items::fetchItem('currency_Currencies', $rec->currencyId)->id;
            $strategyRate = acc_strategy_WAC::getAmount(1, $rec->valior, $rec->creditAccId, $item1, $item2, null);
            $amount = $rec->amount * $strategyRate;
        }

        if ($reverse === true && in_array($rec->operationSysId, array('supplier2bankRet', 'supplierAdvance2bankRet'))) {
            $entry[] = array('amount' => $sign * round($dealCurrencyRate * $rec->amountDeal, 2),
                'debit' => array($rec->debitAccId,
                    array($rec->contragentClassId, $rec->contragentId),
                    array($origin->className, $origin->that),
                    array('currency_Currencies', $rec->dealCurrencyId),
                    'quantity' => $sign * round($rec->amountDeal, 2)),
                'credit' => array(481,
                    array('currency_Currencies', $rec->currencyId),
                    'quantity' => $sign * round($rec->amount, 2)));

            $entry[] = array('amount' => round($amount, 2),
                'debit' => array($rec->creditAccId,
                    array('bank_OwnAccounts', $rec->ownAccount),
                    array('currency_Currencies', $rec->currencyId),
                    'quantity' => $sign * round($rec->amount, 2)),
                'credit' => array(481,
                    array('currency_Currencies', $rec->currencyId),
                    'quantity' => round($rec->amount, 2))
            );
        } else {
            if($rec->currencyId != $baseCurrencyId || $rec->dealCurrencyId != $baseCurrencyId){
                $currencyId481 = ($rec->currencyId != $baseCurrencyId) ? $rec->currencyId : $rec->dealCurrencyId;
                $amount481 = ($rec->currencyId != $baseCurrencyId) ? $rec->amount : $$rec->amountDeal;

                $entry[] = array('amount' => $sign * round($dealCurrencyRate * $rec->amountDeal, 2),
                    'debit' => array($rec->debitAccId,
                        array($rec->contragentClassId, $rec->contragentId),
                        array($origin->className, $origin->that),
                        array('currency_Currencies', $rec->dealCurrencyId),
                        'quantity' => $sign * round($rec->amountDeal, 2)),
                    'credit' => array(481,
                        array('currency_Currencies', $currencyId481),
                        'quantity' => $sign * round($amount481, 2)));

                $entry[] = array('amount' => $sign * round($amount, 2),
                    'debit' => array(481,
                        array('currency_Currencies', $currencyId481),
                        'quantity' => $sign * round($amount481, 2)),
                    'credit' => array($rec->creditAccId,
                        array('bank_OwnAccounts', $rec->ownAccount),
                        array('currency_Currencies', $rec->currencyId),
                        'quantity' => $sign * round($rec->amount, 2))
                );
            } else {
                $entry[] = array('amount' => $sign * round($amount, 2),
                    'debit' => array($rec->debitAccId,
                        array($rec->contragentClassId, $rec->contragentId),
                        array($origin->className, $origin->that),
                        array('currency_Currencies', $rec->dealCurrencyId),
                        'quantity' => $sign * round($rec->amountDeal, 2)),
                    'credit' => array($rec->creditAccId,
                        array('bank_OwnAccounts', $rec->ownAccount),
                        array('currency_Currencies', $rec->currencyId),
                        'quantity' => $sign * round($rec->amount, 2)));
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
