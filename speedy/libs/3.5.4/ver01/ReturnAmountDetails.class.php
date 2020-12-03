<?php

/**
 * @since 3.5.2
 */
class ReturnAmountDetails {
	
	/**
	 * Amount in the return picking
	 * @var signed 32-bit real
	 */
	protected $_amount;
	
	/**
	 * The payer type for this amount in the return picking (0=sender, 1=reciever or 2=third party)
	 * @var signed 32-bit integer
	 */
	protected $_returnPayerType;

	
	/**
	 * Constructs new instance of this class
	 * @param $stdClassReturnAmountDetails
	 */
	function __construct($stdClassReturnAmountDetails) {
		$this->_amount = isset($stdClassReturnAmountDetails -> amount) ? $stdClassReturnAmountDetails -> amount : null;
		$this->_returnPayerType = isset($stdClassReturnAmountDetails -> returnPayerType) ? $stdClassReturnAmountDetails -> returnPayerType : null;
	}
	
	/**
	 * Get amount.
	 * @return signed 32-bit real
	 */
   public function getAmount() {
		return $this->_amount;
	}
	
	
	/**
	 * Get Total payed out amount
	 * @return signed 32-bit real
	 */
	public function getReturnPayerType() {
		return $this->_returnPayerType;
	}

}
?>