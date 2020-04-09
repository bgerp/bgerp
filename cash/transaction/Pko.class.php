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
        
        if ($rec->isReverse == 'yes') {
            // Ако документа е обратен, правим контировката на РКО-то но с отрицателен знак
            $entry = cash_transaction_Rko::getReverseEntries($rec, $origin);
        } else {
            
            // Ако документа не е обратен, правим нормална контировка на ПКО
            $entry = $this->getEntry($rec, $origin);
        }
        
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        
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
        
        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
        if ($rec->currencyId == $baseCurrencyId) {
            $amount = $rec->amount;
        } elseif ($rec->dealCurrencyId == $baseCurrencyId) {
            $amount = $rec->amountDeal;
        } else {
            $amount = $rec->amount * $rec->rate;
        }
        
        $entry = array('amount' => $sign * $amount,
            'debit' => array($rec->debitAccount,
                array('cash_Cases', $rec->peroCase),
                array('currency_Currencies', $rec->currencyId),
                'quantity' => $sign * $rec->amount),
            
            'credit' => array($rec->creditAccount,
                array($rec->contragentClassId, $rec->contragentId),
                array($origin->className, $origin->that),
                array('currency_Currencies', $rec->dealCurrencyId),
                'quantity' => $sign * $rec->amountDeal),);
        
        $entry = array($entry);
        
        if ($reverse === false) {
            $dQuery = cash_NonCashPaymentDetails::getQuery();
            $dQuery->where("#documentId = '{$rec->id}'");
            
            while ($dRec = $dQuery->fetch()) {
                $baseAmount = $dRec->amount;
                $dRec->amount = cond_Payments::toBaseCurrency($dRec->paymentId, $baseAmount, $rec->valior);
                $dRec->amount /= $rec->rate;
                $amount = $dRec->amount * $rec->rate;
                
                $type = cond_Payments::getTitleById($dRec->paymentId);
                
                $entry[] = array('amount' => $sign * $amount,
                    'debit' => array('502',
                        array('cash_Cases', $rec->peroCase),
                        array('cond_Payments', $dRec->paymentId),
                        'quantity' => $sign * $baseAmount),
                    
                    'credit' => array($rec->debitAccount,
                        array('cash_Cases', $rec->peroCase),
                        array('currency_Currencies', $rec->currencyId),
                        'quantity' => $sign * $dRec->amount),
                    'reason' => "Плащане с '{$type}'",
                );
            }
        } elseif ($rec->operationSysId == 'case2customerRet' || $rec->operationSysId == 'caseAdvance2customerRet') {
            $entry2 = $entry[0];
            $entry2['amount'] = abs($entry2['amount']);
            $debitArr = $entry2['debit'];
            $creditArr = $entry2['credit'];
            $entry[0]['debit'] = $creditArr;
            $entry[0]['debit'][0] = '482';
            
            $entry2['credit'] = $debitArr;
            $entry2['credit']['quantity'] = abs($entry2['credit']['quantity']);
            $entry2['debit'] = $entry[0]['debit'];
            $entry2['debit']['quantity'] = abs($entry2['debit']['quantity']);
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
