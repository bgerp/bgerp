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
    			$transfer = FALSE;
    			
    			if(isset($pInfo->meta['fixedAsset'])){
    				$reason = 'Приети ДА';
    				$debitArr = array('613', array($dRec->classId, $dRec->productId),
    									'quantity' => $dRec->quantity,);
    			} else {
    				$transfer = TRUE;
    				$centerId = ($rec->activityCenterId) ? $rec->activityCenterId : hr_Departments::fetchField("#systemId = 'emptyCenter'", 'id');
    				
    				$debitArr = array(
    							'60020', // Сметка "60020. Разходи за (нескладируеми) услуги и консумативи"
    							array('hr_Departments', $centerId),
    							array($dRec->classId, $dRec->productId), // Перо 1 - Артикул
    							'quantity' => $sign * $dRec->quantity, // Количество продукт в основната му мярка
    					);
    				$reason = 'Приети услуги и нескладируеми консумативи';
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
    					'reason' => $reason,
    			);
    			
    			// Ако сме дебитирали 60020 сметка, прехвърляме го в 61101 или 61102
    			if($transfer === TRUE){
    				$pInfo = cat_Products::getProductInfo($dRec->productId);
    				if(isset($pInfo->meta['canConvert'])){
    					$newArr = array('61101', array($dRec->classId , $dRec->productId),
    							'quantity' => $dRec->quantity);
    				} else {
    					$newArr = array('61102');
    				}
    				
    				$entries[] = array(
    						'amount' => $sign * $amount * $rec->currencyRate,
    						'debit' => $newArr,
    						'credit' => $debitArr,
    						'reason' => 'Вложени в производството нескладируеми услуги и консумативи',
    						);
    			}
    		}
    		
    		// Отчитаме ддс-то
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
    					'reason' => 'ДДС за начисляване при фактуриране',
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