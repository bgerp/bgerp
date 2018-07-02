<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа bank_ExchangeDocument
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 * @see acc_TransactionSourceIntf
 *
 */
class bank_transaction_ExchangeDocument extends acc_DocumentTransactionSource
{
    
    
    /**
     *
     * @var bank_ExchangeDocument
     */
    public $class;
    
    
    /**
     * Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     * Създава транзакция която се записва в Журнала, при контирането
     *
     * Ако избраната валута е в основна валута
     *
     * Dt: 503. Разплащателни сметки             (Банкови сметки, Валути)
     * Ct: 503. Разплащателни сметки             (Банкови сметки, Валути)
     *
     * Ако е в друга валута различна от основната
     *
     * Dt: 503. Разплащателни сметки             (Банкови сметки, Валути)
     * Ct: 481. Разчети по курсови разлики       (Валути)
     *
     * Dt: 481. Разчети по курсови разлики       (Валути)
     * Ct: 503. Разплащателни сметки             (Банкови сметки, Валути)
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
        
        $cOwnAcc = bank_OwnAccounts::getOwnAccountInfo($rec->peroFrom, 'currencyId');
        $dOwnAcc = bank_OwnAccounts::getOwnAccountInfo($rec->peroTo);
    
        $toBank = array('503',
                array('bank_OwnAccounts', $rec->peroTo),
                array('currency_Currencies', $dOwnAcc->currencyId),
                'quantity' => $rec->debitQuantity);
        
        $fromBank = array('503',
                array('bank_OwnAccounts', $rec->peroFrom),
                array('currency_Currencies', $cOwnAcc->currencyId),
                'quantity' => $rec->creditQuantity);

        if ($dOwnAcc->currencyId == $baseCurrencyId && $cOwnAcc->currencyId != $baseCurrencyId) {
            $entry = array();
            $entry[] = array('amount' => $rec->debitQuantity,
                             'debit' => $toBank,
                             'credit' => array('481', array('currency_Currencies', $cOwnAcc->currencyId), 'quantity' => $rec->creditQuantity));
            $entry[] = array('debit' => array('481', array('currency_Currencies', $cOwnAcc->currencyId), 'quantity' => $rec->creditQuantity),
                             'credit' => $fromBank);
        } else {
            $entry = array('debit' => $toBank, 'credit' => $fromBank);
            $entry = array($entry);
        }
    
        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
                'reason' => $rec->reason,   // основанието за ордера
                'valior' => $rec->valior,   // датата на ордера
                'entries' => $entry
        );
    
        return $result;
    }
}
