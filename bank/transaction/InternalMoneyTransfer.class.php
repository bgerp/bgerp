<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа bank_InternalMoneyTransfer
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
 *
 */
class bank_transaction_InternalMoneyTransfer extends acc_DocumentTransactionSource
{
    /**
     *
     * @var bank_InternalMoneyTransfer
     */
    public $class;
    
    
    /**
     * Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     * Създава транзакция която се записва в Журнала, при контирането
     *
     * Dt: 501. Каси                    (Каса, Валута)
     * Dt: 503. Разплащателни сметки    (Банкова сметка, Валута)
     *
     * Ct: 503. Разплащателни сметки    (Банкова сметка, Валута)
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;

        $cCode = currency_Currencies::getCodeById($rec->currencyId);
        $amount = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $cCode);
        $debitCase = $rec->debitCase ?? null;
        $debitArr = $debitCase ? array('cash_Cases', $debitCase) : array('bank_OwnAccounts', $rec->debitBank ?? null);
        $entries = array();
        // Първо е редът за източника на средствата, след него - за получателя
        $entries[] = array('debit' => array('481', array('currency_Currencies', $rec->currencyId), 'quantity' => $rec->amount),
                           'credit' => array($rec->creditAccId, array('bank_OwnAccounts', $rec->creditBank), array('currency_Currencies', $rec->currencyId),
                                            'quantity' => $rec->amount));

        $entries[] = array('amount' => $amount, 'debit' => array($rec->debitAccId, $debitArr, array('currency_Currencies', $rec->currencyId),
                                        'quantity' => $rec->amount),
                            'credit' => array('481', array('currency_Currencies', $rec->currencyId), 'quantity' => $rec->amount));

        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => $entries,
        );
        
        return $result;
    }
}
