<?php

/**
 * @since 3.1.0
 */
class CODPayment {
	
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
	 * @param $stdClassCODPayment
	 */
	function __construct($stdClassCODPayment) {
		$this->_date = isset($stdClassCODPayment->date) ? $stdClassCODPayment->date : null;
		$this->_totalPayedOutAmount = isset($stdClassCODPayment->totalPayedOutAmount) ? $stdClassCODPayment->totalPayedOutAmount : null;
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