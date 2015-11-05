<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_Invoices
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
class purchase_transaction_Invoice extends acc_DocumentTransactionSource
{
    /**
     * 
     * @var purchase_Invoices
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *  Dt: 4531 - Начислен ДДС за покупките
     *  Ct: 401  - Задължения към доставчици
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    	$cloneRec = clone $rec;
    
    	$result = (object)array(
    			'reason'  => "Входяща фактура №{$rec->number}", // основанието за ордера
    			'valior'  => $rec->date,   // датата на ордера
    			'entries' => array(),
    	);
    
    	$origin = $this->class->getOrigin($rec);
    	
    	// Ако е ДИ или КИ се посочва към коя фактура е то
    	if($rec->type != 'invoice') {
    		$type = $this->class->getVerbal($rec, 'type');
    		$result->reason = "{$type} към Фактура №" . str_pad($origin->fetchField('number'), '10', '0', STR_PAD_LEFT);
    		
    		// Намираме оридиджана на фактурата върху която е ДИ или КИ
    		$origin = $origin->getOrigin();
    	} 
    	 
    	if($origin->isInstanceOf('findeals_AdvanceReports')){
    		$origin = $origin->getOrigin();
    	}
    	
    	$entries = array();
    
    	if(isset($cloneRec->vatAmount)){
    		$entries[] = array(
    				'amount' => $cloneRec->vatAmount * (($rec->type == 'credit_note') ? -1 : 1),  // равностойноста на сумата в основната валута
    
    				'debit' => array('4531'),
    
    				'credit' => array('4530', array($origin->className, $origin->that)),
    		);
    	}
    
    	$result->entries = $entries;
    	 
    	return $result;
    }
}