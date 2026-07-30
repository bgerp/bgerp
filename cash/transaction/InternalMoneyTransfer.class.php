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

        $rec->amount = $rec->amount ?? 0;
        $currencyId = $rec->currencyId ?? null;
        $debitCase = $rec->debitCase ?? null;
        $debitBank = $rec->debitBank ?? null;
        $paymentDebitId = $rec->paymentDebitId ?? null;
        $creditCase = $rec->creditCase ?? null;
        $operationSysId = $rec->operationSysId ?? null;
        $creditAccId = $rec->creditAccId ?? null;
        $debitAccId = $rec->debitAccId ?? null;

        $debitArr = $debitCase ? array('cash_Cases', $debitCase) : array('bank_OwnAccounts', $debitBank);
        $item2Arr = $paymentDebitId ? array('cond_Payments', $paymentDebitId) : array('currency_Currencies', $currencyId);

        $creditArr = array($creditAccId, array('cash_Cases', $creditCase), array('currency_Currencies', $currencyId), 'quantity' => $rec->amount);
        $currencyCode = currency_Currencies::getCodeById($currencyId);
        $reason = cash_InternalMoneyTransfer::getVerbal($rec, 'operationSysId');
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        $entries = array();
        if (in_array($operationSysId, array('nonecash2bank', 'nonecash2case', 'noncash2noncash'))) {
            $paymentId = $rec->paymentId ?? null;
            expect($paymentId);
            $creditArr = array($creditAccId, array('cash_Cases', $creditCase),
                                                  array('cond_Payments', $paymentId),
                                                  'quantity' => $rec->amount);

            if ($operationSysId == 'nonecash2case') {
                $creditArr['quantity'] = round(currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $currencyCode), 2);
            }

            $baseCurrencyEquivalent = round(cond_Payments::toBaseCurrency($paymentId, $rec->amount, $rec->valior), 2);
            $entries[] = array('amount' => $baseCurrencyEquivalent,
                'debit' => array($debitAccId, $debitArr, $item2Arr, 'quantity' => $rec->amount),
                'credit' => array('481', array('currency_Currencies', $currencyId), 'quantity' => $baseCurrencyEquivalent), 'reason' => $reason);

            $entries[] = array('debit' => array('481', array('currency_Currencies', $currencyId), 'quantity' => $baseCurrencyEquivalent),
                              'credit' => $creditArr, 'reason' => $reason);

        } else {
            // Кредитирането на сметката за разликите сумата е винаци тази на валутата към централния курс
            $debitAmount = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $currencyCode);
            $entries[] = array('amount' => $debitAmount,
                               'debit' => array($debitAccId, $debitArr, $item2Arr, 'quantity' => $rec->amount),
                               'credit' => array('481', array('currency_Currencies', $currencyId), 'quantity' => $rec->amount), 'reason' => $reason);

            $entries[] = array('debit' => array('481', array('currency_Currencies', $currencyId), 'quantity' => $rec->amount),
                               'credit' => $creditArr, 'reason' => $reason);
        }

        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
            'reason' => $rec->reason ?? null,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => $entries);
        
        return $result;
    }
}
