<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа findeals_DebitDocuments
 *
 * @category  bgerp
 * @package   findeals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class findeals_transaction_DebitDocument extends acc_DocumentTransactionSource
{
    /**
     *
     * @var findeals_DebitDocuments
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
            // Ако документа е обратен, правим контировката на прехвърлянето на задължения но с отрицателен знак
            $entries = findeals_transaction_CreditDocument::getReverseEntries($rec, $origin);
        } else {
            
            // Ако документа не е обратен, правим нормална контировка на прехвърлянето на взимане
            $entries = $this->getEntries($rec, $origin);
        }
        
        // Подготвяме информацията, която ще записваме в Журнала
        $result = (object) array(
            'reason' => $rec->name, // основанието за ордера
            'valior' => $rec->valior, // датата на ордера
            'entries' => $entries
        );
        
        return $result;
    }
    
    
    /**
     * Връща записа на транзакцията
     */
    private function getEntries($rec, $origin, $reverse = false)
    {
        $entries = array();

        // Ако е обратна транзакцията, сумите и к-та са с минус
        $sign = ($reverse) ? -1 : 1;
        
        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
        
        $origin = findeals_DebitDocuments::getOrigin($rec);
        $originCodeId = currency_Currencies::getIdByCode($origin->fetchField('currencyId'));
        
        $doc = doc_Containers::getDocument($rec->dealId);
        $dealCodeId = currency_Currencies::getIdByCode($doc->fetchField('currencyId'));

        if ($rec->currencyId == $baseCurrencyId) {
            $amount = $rec->amountDeal;
        } elseif ($originCodeId == $baseCurrencyId) {
            $amount = $rec->amount;
        } else {
            $originRate = $origin->fetchField('currencyRate');
            $amount = $rec->amount * $originRate;
        }
        
        $dealRec = $doc->fetch();
        $originCurrencyId = currency_Currencies::getIdByCode($origin->fetchField('currencyId'));
        $findeal2findeal = $doc->isInstanceOf('findeals_Deals') && $origin->isinstanceOf('findeals_Deals');

        if($rec->currencyId == $originCurrencyId && $rec->currencyId == $baseCurrencyId) {
            // Дебитираме разчетната сметка на избраната финансова сделка
            $debitArr = array($rec->debitAccount,
                array($dealRec->contragentClassId, $dealRec->contragentId),
                array($doc->getClassId(), $doc->that),
                array('currency_Currencies', $dealCodeId),
                'quantity' => $sign * round($rec->amountDeal, 2));

            // Кредитираме разчетната сметка на сделката, начало на нишка
            $creditArr = array($rec->creditAccount,
                array($rec->contragentClassId, $rec->contragentId),
                array($origin->className, $origin->that),
                array('currency_Currencies', $originCurrencyId),
                'quantity' => $sign * round($rec->amount, 2));

            $entries[] = array('amount' => $sign * round($amount, 2),
                'debit' => $debitArr,
                'credit' => $creditArr,);
        } else {
            $amountCredit = $amount;

            $originRate = $doc->fetchField('currencyRate');
            $amountDebit = $rec->amountDeal * $originRate;
            $entries[] = array('amount' => $sign * round($amountDebit, 2),
                'debit' => array($rec->debitAccount,
                    array($dealRec->contragentClassId, $dealRec->contragentId),
                    array($doc->getClassId(), $doc->that),
                    array('currency_Currencies', $dealCodeId),
                    'quantity' => $sign * round($rec->amountDeal, 2)),
                'credit' => array(481, array('currency_Currencies', $dealCodeId), 'quantity' => $sign * round($rec->amountDeal, 2)));

            $amountCredit = $amountCredit;
            $debitQuantity = $rec->amountDeal;
            if($findeal2findeal){
                $amountCredit = $rec->amount * $origin->fetchField('currencyRate');
                $debitQuantity = $amountCredit;
            }

            $entries[] = array('amount' => $sign * round($amountCredit, 2),
                'debit' => array(481, array('currency_Currencies', $dealCodeId), 'quantity' => $sign * round($debitQuantity, 2)),
                'credit' => array($rec->creditAccount,
                    array($rec->contragentClassId, $rec->contragentId),
                    array($origin->className, $origin->that),
                    array('currency_Currencies', $originCurrencyId),
                    'quantity' => $sign * round($rec->amount, 2)));
        }

        return $entries;
    }
    
    
    /**
     * Връща обратна контировка на стандартната
     */
    public static function getReverseEntries($rec, $origin)
    {
        $self = cls::get(get_called_class());
        
        return $self->getEntries($rec, $origin, true);
    }
}
