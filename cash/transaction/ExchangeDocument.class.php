<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа cash_ExchangeDocument
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 * @see acc_TransactionSourceIntf
 *
 */
class cash_transaction_ExchangeDocument extends acc_DocumentTransactionSource
{
    
    
    /**
     *
     * @var cash_ExchangeDocument
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *	Ако избраната валута е в основна валута
     *
     *  Dt: 501. Каси 					(Каси, Валути)
     *    Ct: 501. Каси					(Каси, Валути)
     *
     *  Ако е в друга валута различна от основната
     *
     *  Dt: 501. Каси 					         (Каси, Валути)
     *  Ct: 481. Разчети по курсови разлики		 (Валути)
     *
     *  Dt: 481. Разчети по курсови разлики	     (Валути)
     *  Ct: 501. Каси 					         (Каси, Валути)
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
        
        $toCase = array('501',
                array('cash_Cases', $rec->peroTo),
                array('currency_Currencies', $rec->debitCurrency),
                'quantity' => $rec->debitQuantity);
                
        $fromCase = array('501',
                array('cash_Cases', $rec->peroFrom),
                array('currency_Currencies', $rec->creditCurrency),
                'quantity' => $rec->creditQuantity);
        if ($rec->debitCurrency == $baseCurrencyId && $rec->creditCurrency != $baseCurrencyId) {
            $dCode = currency_Currencies::getCodeById($rec->debitCurrency);
            $rate = currency_CurrencyRates::getRate($rec->valior, $dCode, null);
            $entry = array();
            $entry[] = array('amount' => $rec->debitQuantity,
                    'debit' => $toCase,
                    'credit' => array('481', array('currency_Currencies', $rec->creditCurrency), 'quantity' => $rec->creditQuantity));
            $entry[] = array(
                    'debit' => array('481', array('currency_Currencies', $rec->creditCurrency), 'quantity' => $rec->creditQuantity),
                    'credit' => $fromCase);
        } else {
            $entry = array('debit' => $toCase, 'credit' => $fromCase);
            $entry = array($entry);
        }
        
        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
                'reason' => $rec->reason,   // основанието за ордера
                'valior' => $rec->valior,   // датата на ордера
                'entries' => $entry,
        );
    
        return $result;
    }
}
