<?php



/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа findeals_CreditDocuments
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 * @see acc_TransactionSourceIntf
 *
 */
class findeals_transaction_CreditDocument extends acc_DocumentTransactionSource
{
    
    
    /**
     *
     * @var findeals_CreditDocuments
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
        expect($origin = $this->class->getOrigin($rec));
        
        if ($rec->isReverse == 'yes') {
            // Ако документа е обратен, правим контировката на прехвърлянето на взимане но с отрицателен знак
            $entry = findeals_transaction_DebitDocument::getReverseEntries($rec, $origin);
        } else {
            
            // Ако документа не е обратен, правим нормална контировка на прехвърляне на задължение
            $entry = $this->getEntry($rec, $origin);
        }
         
        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
                'reason' => $rec->name, // основанието за ордера
                'valior' => $rec->valior,   // датата на ордера
                'entries' => array($entry)
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
         
        $origin = findeals_CreditDocuments::getOrigin($rec);
        $originCodeId = currency_Currencies::getIdByCode($origin->fetchField('currencyId'));
        $dealCodeId = currency_Currencies::getIdByCode(findeals_Deals::fetchField($rec->dealId, 'currencyId'));
         
        if ($rec->currencyId == $baseCurrencyId) {
            $amount = $rec->amountDeal;
        } elseif ($originCodeId == $baseCurrencyId) {
            $amount = $rec->amount;
        } else {
            $amount = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $origin->fetchField('currencyId'));
        }
         
        $dealRec = findeals_Deals::fetch($rec->dealId);
         
        // Кредитираме разчетната сметка на избраната финансова сделка
        $debitArr = array($rec->debitAccount,
                array($rec->contragentClassId, $rec->contragentId),
                array($origin->className, $origin->that),
                array('currency_Currencies', currency_Currencies::getIdByCode($origin->fetchField('currencyId'))),
                'quantity' => $sign * $rec->amountDeal);
         
        // Дебитираме разчетната сметка на сделката, начало на нишка
        $creditArr = array($rec->creditAccount,
                array($dealRec->contragentClassId, $dealRec->contragentId),
                array($dealRec->dealManId, $rec->dealId),
                array('currency_Currencies', $dealCodeId),
                
                'quantity' => $sign * $rec->amount);
         
        $entry = array('amount' => $sign * $amount,
                'debit' => $debitArr,
                'credit' => $creditArr,);
        
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
