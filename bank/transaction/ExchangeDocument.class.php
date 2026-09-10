<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа bank_ExchangeDocument
 *
 * @category  bgerp
 * @package   bank
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
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
     * Dt: 481. Разчети по курсови разлики       (Валути)
     * Ct: 503. Разплащателни сметки             (Банкови сметки, Валути)
     *
     * Dt: 503. Разплащателни сметки             (Банкови сметки, Валути)
     * Ct: 481. Разчети по курсови разлики       (Валути)
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);

        $toBank = array('503',
            array('bank_OwnAccounts', $rec->peroTo),
            array('currency_Currencies', $rec->debitCurrency),
            'quantity' => $rec->debitQuantity);
        
        $fromBank = array('503',
            array('bank_OwnAccounts', $rec->peroFrom),
            array('currency_Currencies', $rec->creditCurrency),
            'quantity' => $rec->creditQuantity);

        if($rec->debitCurrency == $baseCurrencyId){
            $amount = $rec->debitQuantity;
        } elseif($rec->creditCurrency == $baseCurrencyId){
            $amount = $rec->creditQuantity;
        } else {
            $dCode = currency_Currencies::getCodeById($rec->debitCurrency);
            $amount = currency_CurrencyRates::convertAmount($rec->debitQuantity, $rec->valior, $dCode);
        }

        $entries = array();
        // Първо е редът за източника на средствата, след него - за получателя
        $entries[] = array('debit' => array('481', array('currency_Currencies', $rec->creditCurrency), 'quantity' => $rec->creditQuantity),
            'credit' => $fromBank);
        $entries[] = array('amount' => $amount,
            'debit' => $toBank,
            'credit' => array('481', array('currency_Currencies', $rec->creditCurrency), 'quantity' => $rec->creditQuantity));

        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => $entries
        );
        
        return $result;
    }
}
