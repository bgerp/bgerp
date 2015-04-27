<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_Services
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class purchase_transaction_Service extends acc_DocumentTransactionSource
{
    /**
     * 
     * @var purchase_Services
     */
    public $class;
    
    
    /**
     * Транзакция за запис в журнала
     * @param int $id
     */
    public function getTransaction($id)
    {
    	$entries = array();
    	$rec = $this->class->fetchRec($id);
    	$origin = $this->class->getOrigin($rec);
    	
    	if (!empty($rec->id)) {
    		$dQuery = purchase_ServicesDetails::getQuery();
    		$dQuery->where("#shipmentId = {$rec->id}");
    		$rec->details = $dQuery->fetchAll();
    	}
    	
    	$entries = array();
    	 
    	// Всяко ЕН трябва да има поне един детайл
    	if (count($rec->details) > 0) {
    		 
    		if($rec->isReverse == 'yes'){
    			 
    			// Ако ЕН е обратна, тя прави контировка на СР но с отрицателни стойностти
    			$reverseSource = cls::getInterface('acc_TransactionSourceIntf', 'sales_Services');
    			$entries = $reverseSource->getReverseEntries($rec, $origin);
    		} else {
    			// Записите от тип 1 (вземане от клиент)
    			$entries = $this->getEntries($rec, $origin);
    		}
    	}
    
    	$transaction = (object)array(
    			'reason'  => 'Протокол за покупка на услуги #' . $rec->id,
    			'valior'  => $rec->valior,
    			'entries' => $entries,
    	);
    
    	return $transaction;
    }
    
    
    /**
     * Записите на транзакцията 
     */
    public function getEntries($rec, $origin, $reverse = FALSE)
    {
    	$entries = array();
    	$sign = ($reverse) ? -1 : 1;
    	
    	if(count($rec->details)){
    		deals_Helper::fillRecs($this->class, $rec->details, $rec, array('alwaysHideVat' => TRUE));
			$currencyId = currency_Currencies::getIdByCode($rec->currencyId);
			
    		foreach ($rec->details as $dRec) {
    			$pInfo = cls::get($dRec->classId)->getProductInfo($dRec->productId);
    			$transferTo6113 = FALSE;
    			
    			if(isset($pInfo->meta['fixedAsset'])){
    				
    				// Ако артикула е ДМА отчитаме го като разход за ДМА-та
    				$debitArr = array(613, array($dRec->classId, $dRec->productId),
    										'quantity' => $sign * $dRec->quantity);
    			} else {
    				
    				// Дали артикула има ресурс
    				$resourceRec = planning_ObjectResources::getResource($dRec->classId, $dRec->productId);
    				if($resourceRec){
    					// Ако има го отчитаме като разход за ресурси
						$debitArr = array('611', array('planning_Resources', $resourceRec->resourceId),
											'quantity' => $sign * $dRec->quantity / $resourceRec->conversionRate);
    				} else {
    					$transferTo6113 = TRUE;
    					// Ако няма ресурс го отчитаме като разход по центрове на дейности
						$debitArr = array('6112', array('hr_Departments', $rec->activityCenterId),
										 array($dRec->classId, $dRec->productId),
										'quantity' => $sign * $dRec->quantity);
    				}
    			}
    	
    			$amount = $dRec->amount;
    			$amount = ($dRec->discount) ?  $amount * (1 - $dRec->discount) : $amount;
    			$amount = round($amount, 2);
    			
    			$entries[] = array(
    					'amount' => $sign * $amount * $rec->currencyRate, // В основна валута
    	
    					'debit' => $debitArr,
    	
    					'credit' => array(
    							$rec->accountId, 
    							array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Доставчик
    							array($origin->className, $origin->that),			// Перо 2 - Сделка
    							array('currency_Currencies', $currencyId),          // Перо 3 - Валута
    							'quantity' => $sign * $amount, // "брой пари" във валутата на покупката
    					),
    			);
    			
    			if($transferTo6113){
    				$entries[] = array('debit' => array('6113'),
    						'credit' => array('6112', array('hr_Departments', $rec->activityCenterId),
    								array($dRec->classId, $dRec->productId),
    								'quantity' => $sign * $dRec->quantity));
    			}
    		}
    		
    		if($this->class->_total){
    			$vatAmount = $this->class->_total->vat * $rec->currencyRate;
    			$entries[] = array(
    					'amount' => $sign * $vatAmount, // В основна валута
    	
    					'credit' => array(
    							$rec->accountId,
    							array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
    							array($origin->className, $origin->that),			// Перо 2 - Сделка
    							array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->valior)), // Перо 3 - Валута
    							'quantity' => $sign * $vatAmount, // "брой пари" във валутата на продажбата
    					),
    	
    					'debit' => array(
    							'4530',
    							array($origin->className, $origin->that),
    					),
    			);
    		}
    	}
    	
    	return $entries;
    }
    
    
    /**
     * Връща обратна контировка на стандартната
     */
    public function getReverseEntries($rec, $origin)
    {
    	return $this->getEntries($rec, $origin, TRUE);
    }
}