<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа sales_Invoices
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class sales_transaction_Invoice
{
    /**
     * 
     * @var sales_Invoices
     */
    public $class;
    
    
    /**
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function finalizeTransaction($id)
    {
    	$rec = $this->class->fetchRec($id);
    	$rec->state = 'active';
    
    	$this->class->save($rec);
    }
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *  Dt: 411  - ДДС за начисляване
     *  Ct: 4532 - Начислен ДДС за продажбите
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    	$cloneRec = clone $rec;
    
    	$result = (object)array(
    			'reason'  => "Фактура №{$rec->id}", // основанието за ордера
    			'valior'  => $rec->date,   // датата на ордера
    			'entries' => array(),
    	);
    	
    	$origin = $this->class->getOrigin($rec);
    	
    	// Ако е ДИ или КИ се посочва към коя фактура е то
    	if($rec->type != 'invoice') {
    		$origin = $this->class->getOrigin($rec);
    		
    		$type = $this->class->getVerbal($rec, 'type');
    		if(!$origin) return $result;
    		$result->reason = "{$type} към Фактура №" . str_pad($origin->fetchField('number'), '10', '0', STR_PAD_LEFT);
    	
    		// Намираме оридиджана на фактурата върху която е ДИ или КИ
    		$origin = $origin->getOrigin();
    	}
    
    	// Ако фактурата е от пос продажба не се контира ддс
    	if($cloneRec->type == 'invoice' && isset($cloneRec->docType) && isset($cloneRec->docId)) return $result;
    	 
    	$entries = array();
    
    	if(isset($cloneRec->vatAmount)){
    		$entries[] = array(
    				'amount' => $cloneRec->vatAmount * (($rec->type == 'credit_note') ? -1 : 1),  // равностойноста на сумата в основната валута
    
    				'debit' => array('4530', array($origin->className, $origin->that)),
    
    				'credit' => array('4532', array($origin->className, $origin->that)),
    		);
    	}
    
    	$result->entries = $entries;
    	 
    	return $result;
    }
}