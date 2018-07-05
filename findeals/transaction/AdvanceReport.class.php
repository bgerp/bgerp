<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа findeals_AdvanceReports
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
class findeals_transaction_AdvanceReport extends acc_DocumentTransactionSource
{
    
    
    /**
     *
     * @var findeals_AdvanceReports
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
        $entries = array();
        
        // Извличаме записа
        expect($rec = $this->class->fetchRec($id));
        expect($origin = $this->class->getOrigin($rec));
        $originRec = $origin->fetch();
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        
        $entries = array();
        $creditArr = array($rec->creditAccount,
                            array($originRec->contragentClassId, $originRec->contragentId),
                            array($origin->className, $origin->that),
                            array('currency_Currencies', $currencyId));
        
        $dQuery = findeals_AdvanceReportDetails::getQuery();
        $dQuery->where("#reportId = '{$rec->id}'");
        $details = $dQuery->fetchAll();
        
        deals_Helper::fillRecs($this->class, $details, $rec, array('alwaysHideVat' => true));
        
        foreach ($details as $dRec) {
             
            // Към кои разходни обекти ще се разпределят разходите
            $splitRecs = acc_CostAllocations::getRecsByExpenses('findeals_AdvanceReportDetails', $dRec->id, $dRec->productId, $dRec->quantity, $dRec->amount, $dRec->discount);
            
            foreach ($splitRecs as $dRec1) {
                $amount = $dRec1->amount;
                $creditArr['quantity'] = $amount;
                $amountAllocated = $amount * $rec->currencyRate;
                
                $entries[] = array(
                        'amount' => $amountAllocated, // В основна валута
                        'debit' => array('60201',
                                            $dRec1->expenseItemId,
                                            array('cat_Products', $dRec1->productId),
                                            'quantity' => $dRec1->quantity),
                        'credit' => $creditArr,
                        'reason' => $dRec1->reason,
                );
                
                // Корекция на стойности при нужда
                if (isset($dRec1->correctProducts) && count($dRec1->correctProducts)) {
                    $correctionEntries = acc_transaction_ValueCorrection::getCorrectionEntries($dRec1->correctProducts, $dRec1->productId, $dRec1->expenseItemId, $dRec1->quantity, $dRec1->allocationBy);
                    if (count($correctionEntries)) {
                        $entries = array_merge($entries, $correctionEntries);
                    }
                }
            }
        }
        
        // Отчитаме ддс-то
        if ($this->class->_total) {
            $vat = $this->class->_total->vat;
            $vatAmount = $this->class->_total->vat * $rec->currencyRate;
            $creditArr['quantity'] = $vat;
            
            $entries[] = array(
                    'amount' => $vatAmount,
                    'credit' => $creditArr,
                    'debit' => array('4530', array($origin->className, $origin->that),),
                    'reason' => 'ДДС за начисляване при фактуриране',
            );
        }
        
        $result = (object) array(
                'reason' => $this->class->getRecTitle($rec),
                'valior' => $rec->valior,
                'entries' => $entries);
         
        
        return $result;
    }
}
