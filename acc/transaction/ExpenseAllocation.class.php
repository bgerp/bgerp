<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа acc_ExpenseAllocations
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class acc_transaction_ExpenseAllocation extends acc_DocumentTransactionSource
{
	
	
    /**
     * 
     * @var acc_ExpenseAllocations
     */
    public $class;
    
    
    /**
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function getTransaction($id)
    {
    	$rec = $this->class->fetchRec($id);
    	
    	$result = (object)array(
    			'reason'      => $this->class->getRecTitle($id),
    			'valior'      => $rec->valior,
    			'totalAmount' => 0,
    			'entries'     => NULL
    	);
    	
    	if($rec->id){
    		
    		// Намираме записите
    		$entries = $this->getEntries($rec, $result->totalAmount);
    		if(count($entries)){
    			$result->entries = $entries;
    		}
    	}
    	
    	return $result;
    }
    
    
    /**
     * Връщане на записите на транзакцията
     * 
     * Намираме общата сума на разходите, които ще 
     * разпределяме и за всеки складируем артикул в документа
     * 
     * 		Dt: 321. Суровини, материали, продукция, стоки		 (Складове, Артикули)
     * 		Ct: 6113. Други разходи (общо)							
     */
    public function getEntries($rec, &$total)
    {
    	$entries = array();
    	
    	// Изчисляваме общата сума на разходите
    	$amount = 0;
    	$exQuery = acc_ExpenseAllocationExpenses::getQuery();
    	$exQuery->where("#masterId = {$rec->id}");
    	$exQuery->show("amount");
    	while($expensesRec = $exQuery->fetch()){
    		$amount += $expensesRec->amount;
    	}
    	
    	// Намираме всички складируеми артикули в документа
    	$dQuery = acc_ExpenseAllocationProducts::getQuery();
    	$dQuery->where("#masterId = {$rec->id}");
    	
    	// За всеки
    	while($dRec = $dQuery->fetch()){
    		
    		// Според коефициента, определяме какъв разход ще разпределим към артикула
    		$thisAmount = round($dRec->weight * $amount, 2);
    		$itemRec = acc_Items::fetch($dRec->itemId, 'objectId,classId');
    		
    		$entries[] = array('amount' => $thisAmount, 
    						   'debit' => array('321', array('store_Stores', $rec->storeId), 
    						   						   array($itemRec->classId, $itemRec->objectId),
    						   					'quantity' => 0), 
    						   'credit' => array('6112'));
    		
    		$total += $thisAmount;
    	}
    	
    	// Връщаме намерените записи
    	return $entries;
    }
}