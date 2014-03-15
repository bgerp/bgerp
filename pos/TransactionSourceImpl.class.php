<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа pos_Receipts
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class pos_TransactionSourceImpl
{
    /**
     * @var pos_Receipts
     */
    public $class;
    
    
    /**
     * Връща транзакцията на бележката
     */
    public function getTransaction($id)
    {
        $rec = $this->class->fetchRec($id);
        $posRec = pos_Points::fetch($rec->pointId);
    	$entries = array();
        $totalVat = 0;
    	
    	$products = ($rec->id) ? pos_Receipts::getProducts($rec->id) : array();
    	
    	// Генериране на записите
        $entries = array_merge($entries, $this->getTakingPart($rec, $products, $totalVat, $posRec));
        
        // Начисляване на ддс ако има
        if($totalVat){
        	$entries = array_merge($entries, $this->getVatPart($rec, $totalVat, $posRec));
        }
        
        $transaction = (object)array(
            'reason'  => 'Бележка за продажба №' . $rec->id,
            'valior'  => $rec->createdOn,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     * 
     *    Dt: 501. Каси  (Каси, Валута)
     *    
     *    Ct: 701. Приходи от POS продажби                    (Стоки и Продукти)
     *    	  703. Приходи от продажби на услуги 		      (Клиент, Услуга)
     *    	  706. Приходи от продажба на суровини/материали  (Клиент, Суровини и материали)
     * 
     * Експедиране (ако артикула не е услуга @see getDeliveryPart)
     * 
     * @param stdClass $rec    - записа
     * @param array $products  - продуктите
     * @param doube $totalVat  - общото ддс
     * @param stdClass $posRec - точката на продажба
     */
    protected function getTakingPart($rec, $products, &$totalVat, $posRec)
    {
    	$entries = array();
    	
    	foreach ($products as $product) {
    		$product->totalQuantity = currency_Currencies::round($product->quantity * $product->quantityInPack);
    		$totalAmount   = currency_Currencies::round($product->totalQuantity * $product->price);
    		$totalVat     += $product->vatPrice;
	    	
    		$currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
    		$pInfo = cat_Products::getProductInfo($product->productId);
    		$storable = isset($pInfo->meta['canStore']);
    		$convertable = isset($pInfo->meta['canConvert']);
    		
    		// Нескладируемите продукти дебит 703. Складируемите и вложими 706 останалите 701
    		$creditAccId = ($storable) ? (($convertable) ? '706' : '701') : '703';
    		$credit = array(
	              $creditAccId, 
	                    array($rec->contragentClass, $rec->contragentObjectId), // Перо 1 - Клиент
	                    array('cat_Products', $product->productId), // Перо 2 - Артикул
	              'quantity' => $product->totalQuantity, // Количество продукт в основната му мярка
	        );
	        
    		$entries[] = array(
	        'amount' => $totalAmount, // Стойност на продукта за цялото количество, в основна валута
	        'debit' => array(
	            '501',  
	                array('cash_Cases', $posRec->caseId), // Перо 1 - Каса
	                array('currency_Currencies', $currencyId), // Перо 3 - Валута
	            'quantity' => $totalAmount), // "брой пари" във валутата на продажбата
	        
	        'credit' => $credit,
	    	);
	    	
	    	if($storable){
	    		$entries = array_merge($entries, $this->getDeliveryPart($rec, $product, $posRec, $convertable));
	    	}
    	}
    	
    	return $entries;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за продажба (ако има)
     * Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 701. Приходи от продажби на стоки и продукти     (Клиент, Стоки и Продукти)
     *    	  706. Приходи от продажба на суровини/материали   (Клиент, Суровини и материали)
     *    
     *    Ct: 321. Стоки и Продукти 	   (Склад, Стоки и Продукти)
     *    	  302. Суровини и материали    (Складове, Суровини и материали)
     *    
     * @param stdClass $rec        - записа
     * @param array $product       - артикула
     * @param stdClass $posRec     - точката на продажба
     * @param boolean $convertable - вложим ли е продукта
     * @return array 
     */
    protected function getDeliveryPart($rec, $product, $posRec, $convertable)
    {
        $entries = array();
        $pInfo = cat_Products::getProductInfo($product->productId);
        $creditAccId = ($convertable) ? '302' : '321';
        $debitAccId = ($convertable) ? '706' : '701';
        
        // После се отчита експедиране от склада
	    $entries[] = array(
			 'debit' => array(
			       $debitAccId,
			       		array($rec->contragentClass, $rec->contragentObjectId), // Перо 1 - Клиент
			            array('cat_Products', $product->productId), // Перо 1 - Продукт
		           'quantity' => $product->totalQuantity),
			        
			 'credit' => array(
			        $creditAccId,
			            array('store_Stores', $posRec->storeId), // Перо 1 - Склад
			            array('cat_Products', $product->productId), // Перо 1 - Продукт
		            'quantity' => $product->totalQuantity),
		);
		
		return $entries;
    }
    
    
    /**
     * Връща часта контираща ддс-то
     * 
     * 		Dt: 501.  Каси                       (Каси, Валути)
     * 
     * 		Ct: 4532. Начислен ДДС за продажбите
     * 
     * @param stdClass $rec    - записа
     * @param double $totalVat - начисленото ддс
     * @param stdClass $posRec - точката на продажба
     */
    protected function getVatPart($rec, $totalVat, $posRec)
    {
    	$entries = array();
    	$entries[] = array(
	         'amount' => currency_Currencies::round($totalVat), // равностойноста на сумата в основната валута
	            
	         'debit' => array(
	              '501',  
	            	 array('cash_Cases', $posRec->caseId), // Перо 1 - Каса
	            	 array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->createdOn)), 
	              'quantity' => currency_Currencies::round($totalVat), 
	            ),
	            
	        'credit' => array(
	              '4532',  
	              'quantity' => currency_Currencies::round($totalVat),
	            )
	    	);
	    	
	    return $entries;
    }
    
    
	/**
     * Финализиране на транзакцията
     */
    public function finalizeTransaction($id)
    {
        $rec = $this->class->fetchRec($id);
        $rec->state = 'active';
        
        return $this->class->save($rec);
    }
}