<?php

/**
 * @since 3.3.0
 */
class MoneyTransferPayment {
	
	/**
	 * date.
	 * @var signed Date
	 */
	protected $_date;
	
	/**
	 * Total payed out amount
	 * @var signed 32-bit real
	 */
	protected $_totalPayedOutAmount;

	
	/**
	 * Constructs new instance of this class
	 * @param $stdClassMoneyTransferPayment
	 */
	function __construct($stdClassMoneyTransferPayment) {
		$this->_date = isset($stdClassMoneyTransferPayment->date) ? $stdClassMoneyTransferPayment->date : null;
		$this->_totalPayedOutAmount = isset($stdClassMoneyTransferPayment->totalPayedOutAmount) ? $stdClassMoneyTransferPayment->totalPayedOutAmount : null;
	}
	
	/**
	 * Get Date of payment.
	 * @return Date
	 */
	public function getDate() {
		return $this->_date;
	}
	
	
	/**
	 * Get Total payed out amount
	 * @return signed 32-bit real
	 */
	public function getTotalPayedOutAmount() {
		return $this->_totalPayedOutAmount;
	}

	
}
?>