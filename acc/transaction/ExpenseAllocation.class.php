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
    	set_time_limit(600);
    	
    	$rec = $this->class->fetchRec($id);
    	
    	$result = (object)array(
    			'reason' => $this->class->getRecTitle($id),
    			'valior' => $rec->valior,
    			'totalAmount' => NULL,
    			'entries' => array()
    	);
    	
    	return $result;
    }
}