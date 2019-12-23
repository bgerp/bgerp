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
        
        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
        if ($rec->currencyId == $baseCurrencyId) {
            $amount = $rec->amount;
        } elseif ($rec->dealCurrencyId == $baseCurrencyId) {
            $amount = $rec->amountDeal;
        } else {
            $amount = $rec->amount * $rec->rate;
        }
        
        $entry[] = array('amount' => $sign * $amount,
            'debit' => array($rec->debitAccId,
                array($rec->contragentClassId, $rec->contragentId),
                array($origin->className, $origin->that),
                array('currency_Currencies', $rec->dealCurrencyId),
                'quantity' => $sign * $rec->amountDeal),
            'credit' => array($rec->creditAccId,
                array('bank_OwnAccounts', $rec->ownAccount),
                array('currency_Currencies', $rec->currencyId),
                'quantity' => $sign * $rec->amount));
        
        if ($reverse === true && ($rec->operationSysId == 'supplier2bankRet' || $rec->operationSysId == 'supplierAdvance2bankRet')) {
            $entry2 = $entry[0];
            $entry2['amount'] = abs($entry2['amount']);
            $debitArr = $entry2['credit'];
            $creditArr = $entry2['debit'];
            $entry[0]['credit'] = $creditArr;
            $entry[0]['credit'][0] = '482';
            
            $entry2['debit'] = $debitArr;
            $entry2['debit']['quantity'] = abs($entry2['debit']['quantity']);
            $entry2['credit'] = $entry[0]['credit'];
            $entry2['credit']['quantity'] = abs($entry2['credit']['quantity']);
            $entry[] = $entry2;
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
