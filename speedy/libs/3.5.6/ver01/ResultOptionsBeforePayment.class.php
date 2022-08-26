<?php

/**
 * Instances of this class are used as a result of make picking info methods
 * @since 2.9.0
 */
class ResultOptionsBeforePayment {
	
	/**
	 * Indicates if the client is allowed to open the package before payment.
	 * @var boolean (nullable)
	 */
	protected $_open;
	
	/**
	 * Indicates if the client is allowed to test the package before payment.
	 * @var boolean (nullable)
	 */
	protected $_test;

	/**
	 * Indicates Service type id.
	 * @var signed 64-bit integer
	 */
	protected $_returnServiceTypeId;

	/**
	 * Indicates Payer type of the new bill of lading (0=sender, 1=receiver or 2=third party).
	 * @var signed 32-bit integer
	 */
	protected $_returnPayerType;
	
	/**
	 * Constructs new instance of this class
	 * @param unknown $stdClassResultAddressString
	 */
	function __construct($stdClassResultOptionsBeforePayment) {
		$this->_open = isset($stdClassResultOptionsBeforePayment->open) ? $stdClassResultOptionsBeforePayment->open : null;
		$this->_test = isset($stdClassResultOptionsBeforePayment->test) ? $stdClassResultOptionsBeforePayment->test : null;
		$this->_returnServiceTypeId = isset($stdClassResultOptionsBeforePayment->returnServiceTypeId) ? $stdClassResultOptionsBeforePayment->returnServiceTypeId : null;
		$this->_returnPayerType = isset($stdClassResultOptionsBeforePayment->returnPayerType) ? $stdClassResultOptionsBeforePayment->returnPayerType : null;
	}
	
	/**
	 * Gets Indicates if the client is allowed to open the package before payment.
	 * @return boolean (nullable)
	 */
	public function getOpen() {
		return $this->_open;
	}
	
	
	/**
	 * Gets Indicates if the client is allowed to test the package before payment.
	 * @return boolean (nullable)
	 */
	public function getTest() {
		return $this->_test;
	}

	/**
	 * Gets Return service type id.
	 * @return signed 64-bit integer
	 */
	public function getReturnServiceTypeId() {
		return $this->_returnServiceTypeId;
	}

	/**
	 * Gets Return Payer type of the new bill of lading (0=sender, 1=receiver or 2=third party).
	 * @return signed 32-bit integer
	 */
	public function getReturnPayerType() {
		return $this->_returnPayerType;
	}
	
}
?>