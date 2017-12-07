<?php



/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_Invoices
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
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
    	setIfNot($rec->journalDate, $this->class->getDefaultAccDate($rec->date));
    	
    	$result = (object)array(
    			'reason'  => "Входяща фактура №{$rec->number}", // основанието за ордера
    			'valior'  => $rec->journalDate,   // датата на ордера
    			'entries' => array(),
    	);
    
    	if(Mode::get('saveTransaction')){
    		if(empty($rec->number)){
    			if($rec->type == 'dc_note'){
    				$name = ($rec->dealValue <= 0) ? 'Кредитното известие' : 'Дебитното известие';
    			} else {
    				$name = 'Фактурата';
    			}
    			
    			acc_journal_RejectRedirect::expect(FALSE, "{$name} няма номер");
    		}
    	}
    	
    	$origin = $this->class->getOrigin($rec);
    	
    	// Ако е ДИ или КИ се посочва към коя фактура е то
    	if($rec->type != 'invoice') {
    		$type = ($rec->dealValue > 0) ? 'Дебитно известие' : 'Кредитно известие';
    		$result->reason = "{$type} към фактура №" . str_pad($origin->fetchField('number'), '10', '0', STR_PAD_LEFT);
    		
    		// Намираме оридиджана на фактурата върху която е ДИ или КИ
    		$origin = $origin->getOrigin();
    		
    		// Ако е Ди или Ки без промяна не може да се контира
    		if(Mode::get('saveTransaction')){
    			if(!$rec->dealValue){
    				acc_journal_RejectRedirect::expect(FALSE, "Дебитното/кредитното известие не може да бъде контирано, докато сумата е нула");
    			}
    		}
    	} else {
    		if(Mode::get('saveTransaction')){
    			$noZeroQuantity = purchase_InvoiceDetails::fetch("#invoiceId = {$rec->id} AND (#quantity IS NOT NULL && #quantity != '' && #quantity != 0)");
    			if(empty($noZeroQuantity) && empty($rec->dpAmount)){
    				acc_journal_RejectRedirect::expect(FALSE, "Трябва да има поне един ред с ненулево количество|*!");
    			}
    		}
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