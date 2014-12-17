<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа mp_ConsumptionNotes
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class mp_transaction_ConsumptionNote
{
	
	
	/**
	 * @param int $id
	 * @return stdClass
	 * @see acc_TransactionSourceIntf::getTransaction
	 */
	public function getTransaction($id)
	{
		// Извличане на мастър-записа
		expect($rec = $this->class->fetchRec($id));
	
		$result = (object)array(
				'reason' => "Протокол за влагане №{$rec->id}",
				'valior' => $rec->valior,
				'totalAmount' => NULL,
				'entries' => array()
		);
	
		//@TODO да се реализира контировката
		
		return $result;
	}
	
	
	/**
	 * @param int $id
	 * @return stdClass
	 * @see acc_TransactionSourceIntf::getTransaction
	 */
	public function finalizeTransaction($id)
	{
		$rec = $this->class->fetchRec($id);
		$rec->state = 'active';
	
		if($id = $this->class->save($rec)) {
            $this->class->invoke('AfterActivation', array($rec));
        }
        
        return $id;
	}
}