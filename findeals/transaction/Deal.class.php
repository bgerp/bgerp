<?php



/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа findeals_Deals и findeals_AdvanceDeals
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
class findeals_transaction_Deal extends acc_DocumentTransactionSource
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
    	$title = str::mbUcfirst($this->class->singleTitle);
    	
    	setIfNot($valior, $rec->valior, dt::today());
    	setIfNot($rec->currencyRate, $rec->currencyRate, currency_CurrencyRates::getRate($valior, $rec->currencyId, NULL));
    	
    	$result = (object)array(
    			'reason'  => "{$title} №{$rec->id}",
    			'valior'  => $rec->valior, 
    			'entries' => array(),
    	);
    	
    	if(isset($rec->baseAccountId)){
    		acc_journal_Exception::expect($rec->valior, 'Няма вальор');
    		acc_journal_Exception::expect(isset($rec->baseAmount), 'Липсва начално салдо');
    		
    		$accountSysId = acc_Accounts::fetch($rec->accountId)->systemId;
    		$correspondingSysId = acc_Accounts::fetch($rec->baseAccountId)->systemId;
    		$currencyId = currency_Currencies::getIdByCode($rec->currencyId);
    			 
    		$thisDealArr = array($accountSysId, array($rec->contragentClassId, $rec->contragentId),
    				                            array($this->class->className, ($rec->id) ? $rec->id : 0),
    				                            array('currency_Currencies', $currencyId),
    				                            'quantity' => $rec->baseAmount,);
    		
    		$correspondingArr = array($correspondingSysId);
    		$baseAmount = $rec->baseAmount * $rec->currencyRate;
    		$baseAmount = currency_Currencies::round($baseAmount);
    			 
    		if($rec->baseAmountType == 'debit'){
    			$result->entries[] = array('amount' => $baseAmount, 'debit' => $thisDealArr, 'credit' => $correspondingArr, 'reason' => 'Начално сладо по финансова сделка');
    		} elseif($rec->baseAmountType == 'credit'){
    			$result->entries[] = array('amount' => $baseAmount, 'debit' => $correspondingArr, 'credit' => $thisDealArr, 'reason' => 'Начално сладо по финансова сделка');
    		}
    	}
    	
    	return $result;
    }
}