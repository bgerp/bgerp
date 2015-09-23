<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа bank_SpendingDocuments
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
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
     * В какво състояние да е документа след финализирането на транзакцията
     *
     * @var string
     */
    protected $finalizedState = 'closed';
    
    
    /**
     * Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     * Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        
        $origin = $this->class->getOrigin($rec);
        
        if($rec->isReverse == 'yes'){
            // Ако документа е обратен, правим контировката на ПБД-то но с отрицателен знак
            $entry = bank_transaction_IncomeDocument::getReverseEntries($rec, $origin);
        } else {
            
            // Ако документа не е обратен, правим нормална контировка на РБД
            $entry = $this->getEntry($rec, $origin);
        }
        
        // Подготвяме информацията която ще записваме в Журнала
        $result = (object)array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => $entry,
        );
        
        return $result;
    }
    
    
    /**
     * Връща записа на транзакцията
     */
    private function getEntry($rec, $origin, $reverse = FALSE)
    {
        $amount = round($rec->rate * $rec->amount, 2);
        
        // Ако е обратна транзакцията, сумите и к-та са с минус
        $sign = ($reverse) ? -1 : 1;
        
        // Дебита е винаги във валутата на пораждащия документ,
        $debitCurrency = currency_Currencies::getIdByCode($origin->fetchField('currencyId'));
        $debitQuantity = round($amount / $origin->fetchField('currencyRate'), 2);
        
        // Дебитираме Разчетна сметка
        $dealArr = array($rec->debitAccId,
            array($rec->contragentClassId, $rec->contragentId),
            array($origin->className, $origin->that),
            array('currency_Currencies', $debitCurrency),
            'quantity' => $sign * $debitQuantity);
        
        // Кредитираме банкова сметка
        $bankArr = array($rec->creditAccId,
            array('bank_OwnAccounts', $rec->ownAccount),
            array('currency_Currencies', $rec->currencyId),
            'quantity' => $sign * $rec->amount);
        
        // Ако документа е в основна валита, кредитираме директно касата
        if($rec->currencyId == acc_Periods::getBaseCurrencyId($rec->valior)){
            $entry = array('amount' => $sign * $amount, 'debit' => $dealArr, 'credit' => $bankArr, );
            $entry = array($entry);
        } else {
            
            // Ако не е минаваме през транзитна сметка '481'
            $entry = array();
            $entry[] = array('amount' => $sign * $amount,
                'debit' => $dealArr,
                'credit' => array('481', array('currency_Currencies', $rec->currencyId),
                    'quantity' => $sign * $rec->amount));
            
            $entry[] = array('amount' => $sign * $amount, 'debit' => array('481', array('currency_Currencies', $rec->currencyId), 'quantity' => $sign * $rec->amount), 'credit' => $bankArr);
        }
        
        return $entry;
    }
    
    
    /**
     * Връща обратна контировка на стандартната
     */
    public static function getReverseEntries($rec, $origin)
    {
        $self = cls::get(get_called_class());
        
        return $self->getEntry($rec, $origin, TRUE);
    }
}