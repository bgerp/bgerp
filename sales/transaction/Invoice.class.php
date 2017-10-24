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
class sales_transaction_Invoice extends acc_DocumentTransactionSource
{
    
    
    /**
     * 
     * @var sales_Invoices
     */
    public $class;
    
    
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
    	
    	if(Mode::get('saveTransaction')){
    		$restore = ($rec->state == 'draft') ? FALSE : TRUE;
    		if(!$this->class->isAllowedToBePosted($rec, $error, TRUE)){
    			acc_journal_RejectRedirect::expect(FALSE, $error);
    		}
    	}
    	
    	$origin = $this->class->getOrigin($rec);
    	
    	// Ако е ДИ или КИ се посочва към коя фактура е то
    	if($rec->type != 'invoice') {
    		$origin = $this->class->getOrigin($rec);
    		
    		$type = ($rec->dealValue > 0) ? 'Дебитно известие' : 'Кредитно известие';
    		if(!$origin) return $result;
    		$result->reason = "{$type} към фактура №" . str_pad($origin->fetchField('number'), '10', '0', STR_PAD_LEFT);
    	
    		// Намираме оридиджана на фактурата върху която е ДИ или КИ
    		$origin = $origin->getOrigin();
    		
    		// Ако е Ди или Ки без промяна не може да се контира
    		if(Mode::get('saveTransaction')){
    			if(!$rec->dealValue){
    				acc_journal_RejectRedirect::expect(FALSE, "Дебитното/кредитното известие не може да бъде контирано, докато сумата е нула");
    			}
    		}
    	}
    
    	// Ако фактурата е от пос продажба не се контира ддс
    	if($cloneRec->type == 'invoice' && isset($cloneRec->docType) && isset($cloneRec->docId)) return $result;
    	 
    	$entries = array();
    
    	if(isset($cloneRec->vatAmount)){
    		$entries[] = array(
    				'amount' => $cloneRec->vatAmount * (($rec->type == 'credit_note') ? -1 : 1),  // равностойноста на сумата в основната валута
    
    				'debit' => array('4530', array($origin->className, $origin->that)),
    
    				'credit' => array('4532'),
    		);
    	}
    
    	$result->entries = $entries;
    	
    	return $result;
    }
}