<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_Services
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class purchase_transaction_Service
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
    
    	$currencyId = currency_Currencies::getIdByCode($rec->currencyId);
    
    	if (!empty($rec->id)) {
    		$dQuery = purchase_ServicesDetails::getQuery();
    		$dQuery->where("#shipmentId = {$rec->id}");
    		$detailsRecs = $dQuery->fetchAll();
    	}
    	
    	if(count($detailsRecs)){
    		deals_Helper::fillRecs($this->class, $detailsRecs, $rec);
    		 
    		foreach ($detailsRecs as $dRec) {
				$pInfo = cls::get($dRec->classId)->getProductInfo($dRec->productId);
    			if($rec->chargeVat == 'yes'){
    				$ProductManager = cls::get($dRec->classId);
    				$vat = $ProductManager->getVat($dRec->productId, $rec->valior);
    				$amount = $dRec->amount - ($dRec->amount * $vat / (1 + $vat));
    			} else {
    				$amount = $dRec->amount;
    			}

				// Ако е "Материали" дебит 601, иначе 602
	        	$costsAccNumber = (isset($pInfo->meta['materials'])) ? '601' : '602';
    
    			$amount = ($dRec->discount) ?  $amount * (1 - $dRec->discount) : $amount;
    
    			$entries[] = array(
    					'amount' => currency_Currencies::round($amount * $rec->currencyRate), // В основна валута
    					 
    					'debit' => array(
    							$costsAccNumber, // Сметка "602. Разходи за външни услуги" или "601. Разходи за материали"
    							array($dRec->classId, $dRec->productId), // Перо 1 - Артикул
    							'quantity' => $dRec->quantity, // Количество продукт в основната му мярка
    					),
    					 
    					'credit' => array(
    							'401', // Сметка "401. Задължения към доставчици (Доставчик, Валути)"
    							array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Доставчик
    							array($origin->className, $origin->that),			// Перо 2 - Сделка
    							array('currency_Currencies', $currencyId),          // Перо 3 - Валута
    							'quantity' => currency_Currencies::round($amount, $rec->currencyId), // "брой пари" във валутата на покупката
    					),
    			);
    		}
    		 
    		if($this->class->_total->vat){
    			$vatAmount = currency_Currencies::round($this->class->_total->vat * $rec->currencyRate);
    			$entries[] = array(
    					'amount' => $vatAmount, // В основна валута
    
    					'credit' => array(
    							'401',
    							array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Клиент
    							array($origin->className, $origin->that),			// Перо 2 - Сделка
    							array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->valior)), // Перо 3 - Валута
    							'quantity' => $vatAmount, // "брой пари" във валутата на продажбата
    					),
    
    					'debit' => array(
    							'4530',
    							array($origin->className, $origin->that),
    					),
    			);
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
}