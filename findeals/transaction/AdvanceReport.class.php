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
    	 
    	$entries = array();
    	$creditArr = array($rec->creditAccount, array($originRec->contragentClassId, $originRec->contragentId), array($origin->className, $origin->that), array('currency_Currencies', $rec->currencyId));
    	
    	$vatAmount = 0;
    	$dQuery = findeals_AdvanceReportDetails::getQuery();
    	$dQuery->where("#reportId = '{$rec->id}'");
    	while($dRec = $dQuery->fetch()){
    		
    		// Към кои разходни обекти ще се разпределят разходите
    		$splitRecs = acc_ExpenseAllocations::getRecsByExpenses($rec->containerId, $dRec->productId, $dRec->quantity, $dRec->expenseItemId, $dRec->id, $dRec->amount);
    		
    		foreach ($splitRecs as $dRec1){
    			$amount = round($dRec1->amount, 2);
    			
    			$vatAmount += $dRec1->amount * $dRec->vat;
    			$vatAmount = round($vatAmount, 2);
    			
    			$creditArr['quantity'] = $dRec1->amount / $rec->rate;
    			
    			$entries[] = array('amount' => $amount,
    							   'debit'  => array('60201', 
    							   					$dRec1->expenseItemId, 
    							   					array('cat_Products', $dRec1->productId),
    							   					'quantity' => $dRec1->quantity),
    							   'credit' => $creditArr,
    							   'reason' => $dRec1->reason,
    			);
    		}
    	}
    	
    	$entries[] = array(
    			'amount' => $vatAmount,
    			'credit' => array(
    					$rec->creditAccount,
    					array($originRec->contragentClassId, $originRec->contragentId),
    					array($origin->className, $origin->that),
    					array('currency_Currencies', $rec->currencyId),
    					'quantity' => $vatAmount / $rec->rate,
    			),
    	
    			'debit' => array('4530', array($origin->className, $origin->that),),
    	);
    	
    	$result = (object)array(
    			'reason'  => $this->class->getRecTitle($rec),
    			'valior'  => $rec->valior,
    			'entries' => $entries);
    	 
    	return $result;
    }
}