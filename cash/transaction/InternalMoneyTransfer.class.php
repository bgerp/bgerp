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

        if ($rec->operationSysId == 'nonecash2bank' || $rec->operationSysId == 'nonecash2case' || $rec->operationSysId == 'noncash2noncash') {
            $creditArr = array($rec->creditAccId,
                array('cash_Cases', $rec->creditCase),
                array('cond_Payments', $rec->paymentId),
                'quantity' => $rec->amount);
            
                if($rec->operationSysId == 'nonecash2case'){
                    $currencyCode = currency_Currencies::getCodeById($rec->currencyId);
                    $creditArr['quantity'] = currency_CurrencyRates::convertAmount($rec->amount, $rec->valior, $currencyCode);
                }
        }
        
        $reason = cash_InternalMoneyTransfer::getVerbal($rec, 'operationSysId');
        
        $entry = array('debit' => array($rec->debitAccId, $debitArr,
            $item2Arr,
            'quantity' => $rec->amount),
        'credit' => $creditArr, 'reason' => $reason);
        
        $entry = array($entry);
        
        $rec->valior = empty($rec->valior) ? dt::today() : $rec->valior;
        
        // Подготвяме информацията която ще записваме в Журнала
        $result = (object) array(
            'reason' => $rec->reason,   // основанието за ордера
            'valior' => $rec->valior,   // датата на ордера
            'entries' => $entry);
        
        return $result;
    }
}
