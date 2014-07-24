<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа sales_Services
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class sales_transaction_Service
{
    /**
     * 
     * @var sales_Services
     */
    public $class;
    
    
    /**
     * Финализиране на транзакцията
     * @param int $id
     */
    public function finalizeTransaction($id)
    {
    	$rec = $this->class->fetchRec($id);
    	$rec->state = 'active';
    
    	if ($this->class->save($rec)) {
    		$this->class->invoke('AfterActivation', array($rec));
    	}
    }


    /**
     * Транзакция за запис в журнала
     * @param int $id
     */
    public function getTransaction($id)
    {
    	$entries = array();
    
    	$rec = $this->class->fetchRec($id);
    	$origin = $this->class->getOrigin($rec);
    
    	$currencyId = currency_Currencies::getIdByCode($rec->currencyId);
    
    	if($rec->id){
    		$dQuery = sales_ServicesDetails::getQuery();
    		$dQuery->where("#shipmentId = {$rec->id}");
    		$detailsRec = $dQuery->fetchAll();
    	}
    	
    	if(count($detailsRec)){
    		deals_Helper::fillRecs($this->class, $detailsRec, $rec);
    		 
    		foreach ($detailsRec as $dRec) {
    			if($rec->chargeVat == 'yes'){
    				$ProductManager = cls::get($dRec->classId);
    				$vat = $ProductManager->getVat($dRec->productId, $rec->valior);
    				$amount = $dRec->amount - ($dRec->amount * $vat / (1 + $vat));
    			} else {
    				$amount = $dRec->amount;
    			}
    
    			$amount = ($dRec->discount) ?  $amount * (1 - $dRec->discount) : $amount;
    
    			$entries[] = array(
    					'amount' => currency_Currencies::round($amount * $rec->currencyRate), // В основна валута
    					 
    					'debit' => array(
    							'411', // Сметка "411. Вземания от клиенти"
    							array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
    							array($origin->className, $origin->that),			// Перо 2 - Сделка
    							array('currency_Currencies', $currencyId),     		// Перо 3 - Валута
    							'quantity' => currency_Currencies::round($amount, $rec->currencyId), // "брой пари" във валутата на продажбата
    					),
    					 
    					'credit' => array(
    							'703', // Сметка "703". Приходи от продажби на услуги
    							array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
    							array($origin->className, $origin->that),			// Перо 2 - Сделка
    							array($dRec->classId, $dRec->productId), // Перо 3 - Артикул
    							'quantity' => $dRec->quantity, // Количество продукт в основната му мярка
    					),
    			);
    		}
    		 
    		if($this->class->vat){
    			$vatAmount = currency_Currencies::round($this->class->_total->vat * $rec->currencyRate);
    			$entries[] = array(
    					'amount' => $vatAmount, // В основна валута
    					 
    					'debit' => array(
    							'411',
    							array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
    							array($origin->className, $origin->that),			// Перо 2 - Сделка
    							array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->valior)), // Перо 3 - Валута
    							'quantity' => $vatAmount, // "брой пари" във валутата на продажбата
    					),
    					 
    					'credit' => array(
    							'4530',
    								array($origin->className, $origin->that),
    					),
    			);
    		}
    	}
    
    	$transaction = (object)array(
    			'reason'  => 'Протокол за доставка на услуги #' . $rec->id,
    			'valior'  => $rec->valior,
    			'entries' => $entries,
    	);
    
    	return $transaction;
    }
}