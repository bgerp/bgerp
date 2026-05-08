<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа cash_InternalMoneyTransfer
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see acc_TransactionSourceIntf
 *
 */
class cash_transaction_InternalMoneyTransfer extends acc_DocumentTransactionSource
{
    /**
     *
     * @var cash_InternalMoneyTransfer
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *  Ако избраната валута е в основна валута
     *
     *  Dt: 501. Каси 					(Каса, Валута)
     *  Dt:	503. Разплащателни сметки	(Банкова сметка, Валута)
     *
     *  Ct: 501. Каси					(Каса, Валута)
     */
    public function getTransaction($id)
    {
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        
        $debitArr = ($rec->debitCase) ? array('cash_Cases', $rec->debitCase) : array('bank_OwnAccounts', $rec->debitBank);
        $item2Arr = ($rec->paymentDebitId) ? array('cond_Payments', $rec->paymentDebitId) : array('currency_Currencies', $rec->currencyId);

        $creditArr = array($rec->creditAccId, array('cash_Cases', $rec->creditCase), array('currency_Currencies', $rec->currencyId), 'quantity' => $rec->amount);
        $currencyCode = currency_Currencies::getCodeById($rec->currencyId);
        $rec->amount = $rec->amount ?? 0;
        $reason = cash_InternalMoneyTransfer::getVerbal($rec, 'operationSysId');
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;

        if (in_array($rec->operationSysId, array('nonecash2bank', 'nonecash2case', 'noncash2noncash'))) {
            $creditArr = array($rec->creditAccId, array('cash_Cases', $rec->creditCase),
                                                  array('cond_Payments', $rec->paymentId),
                                                  'quantity' => $rec->amount);

            if($rec->operationSysId == 'nonecash2case'){
                $creditArr['quantity'] = round(currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $currencyCode), 2);
            }
            $entry = array('debit' => array($rec->debitAccId, $debitArr, $item2Arr, 'quantity' => $rec->amount),
                           'credit' => $creditArr, 'reason' => $reason);
            $entries = array($entry);
        } else {
            // Кредитирането на сметката за разликите сумата е винаци тази на валутата към централния курс
            $debitAmount = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $currencyCode);
            $entries[] = array('amount' => $debitAmount,
                               'debit' => array($rec->debitAccId, $debitArr, $item2Arr, 'quantity' => $rec->amount),
                               'credit' => array('481', array('currency_Currencies', $rec->currencyId), 'quantity' => $rec->amount), 'reason' => $reason);

            $entries[] = array('debit' => array('481', array('currency_Currencies', $rec->currencyId), 'quantity' => $rec->amount),
                               'credit' => $creditArr, 'reason' => $reason);
        }

        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => $entries);
        
        return $result;
    }
}
