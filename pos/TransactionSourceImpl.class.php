<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа pos_Receipts
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
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
            'reason'  => 'POS Продажба #' . $rec->id,
            'valior'  => $rec->createdOn,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
    /**
     * Генериране на записите от тип 1 (вземане от клиент)
     * 
     * Плащането
     *    Dt: 501. Каси               (Каси, Валута)
     *    Ct: 7012 или Ct: 703 - Приходи от POS продажби (Стоки и Продукти) / Приходи от продажби на услуги (Клиент, Услуга)
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
    		$product->totalQuantity = $product->quantity * $product->quantityInPack;
    		$totalAmount   = $product->totalQuantity * $product->price;
    		$totalVat     += $product->vatPrice;
	    	
    		$currencyId = acc_Periods::getBaseCurrencyId($rec->createdOn);
    		$pInfo = cat_Products::getProductInfo($product->productId);
    		$storable = isset($pInfo->meta['canStore']);
    		$convertable = isset($pInfo->meta['canConvert']);
    		
    		if($storable){
    			if($convertable){
    				$credit = array(
	                    '706', // Сметка "706". Приходи от продажби на суровини/материали
	                        array('cat_Products', $product->productId), // Перо 2 - Артикул
	                    'quantity' => $product->quantity, // Количество продукт в основната му мярка
	                );
    			} else {
    				$credit = array(
	                     '7012', // Сметка "7012. Приходи от POS продажби" или "703. приходи от продажба на услуги"
	              			 array('cat_Products', $product->productId), // Перо 1 - Продукт
	            		 'quantity' => $product->totalQuantity);
    			}
    			
    		} else {
    			$credit = array(
	                    '703', // Сметка "703". Приходи от продажби на услуги
	                        array($rec->contragentClass, $rec->contragentObjectId), // Перо 1 - Клиент
	                    	array('cat_Products', $product->productId), // Перо 2 - Артикул
	                    'quantity' => $product->quantity, // Количество продукт в основната му мярка
	                );
    		}
    		    
    		$entries[] = array(
	        'amount' => $totalAmount, // Стойност на продукта за цялото количество, в основна валута
	        'debit' => array(
	            '501',  // Сметка "501. Каси"
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
     * 
     * Експедиране на стоката от склада (в някой случаи)
     *
     *    Dt: 7012 или Dt: - Приходи от POS продажби          (Клиент, Стоки и Продукти)
     *    Ct: 321 или Ct: 302 - Стоки и Продукти (Склад, Стоки и Продукти) / Суровини и материали (Складове, Суровини и материали)
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
        $debitAccId = ($convertable) ? '706' : '7012';
        
        // После отчитаме експедиране от склада
	    $entries[] = array(
			 'debit' => array(
			       '7012', // Сметка "7012. Приходи от POS продажби" или Сметка "706. приходи от продажба на суровини/материали"
			            array('cat_Products', $product->productId), // Перо 1 - Продукт
		           'quantity' => $product->totalQuantity), // Количество продукт в основната му мярка
			        
			 'credit' => array(
			        $creditAccId, // Сметка "321. Стандартни продукти" или Сметка "302. Суровини и материали"
			            array('store_Stores', $posRec->storeId), // Перо 1 - Склад
			            array('cat_Products', $product->productId), // Перо 1 - Продукт
		            'quantity' => $product->totalQuantity), // Количество продукт в основната му мярка
		);
		
		return $entries;
    }
    
    
    /**
     * Връща часта контираща ддс-то
     * 		Dt: 501. Каси   (Каси, Валути)
     * 		Ct: 4532.Начислен ДДС за продажбите
     * 
     * @param stdClass $rec    - записа
     * @param double $totalVat - начисленото ддс
     * @param stdClass $posRec - точката на продажба
     */
    protected function getVatPart($rec, $totalVat, $posRec)
    {
    	$entries = array();
    	$entries[] = array(
	         'amount' => $totalVat, // равностойноста на сумата в основната валута
	            
	         'debit' => array(
	              '501',  // Сметка "501. Каси"
	            	 array('cash_Cases', $posRec->caseId), // Перо 1 - Каса
	            	 array('currency_Currencies', acc_Periods::getBaseCurrencyId($rec->createdOn)), 
	              'quantity' => $totalVat, 
	            ),
	            
	        'credit' => array(
	              '4532', // кредитна сметка
	              'quantity' => $totalVat,
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