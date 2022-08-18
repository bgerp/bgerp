<?php

/**
 * Instances of this class are used as a result of make picking info methods
 * @since 2.9.0
 */
class ResultReturnVoucher {
	
	/**
	 * Service type id.
	 * @var signed 64-bit integer
	 */
	protected $_serviceTypeId;
	
	/**
	 * Payer type (0=sender, 1=receiver or 2=third party)
	 * @var signed 32-bit integer
	 */
	protected $_payerType;
	
	/**
	 * Constructs new instance of this class
	 * @param $stdClassResultAddressString
	 */
	function __construct($stdClassResultReturnVoucher) {
		$this->_serviceTypeId = isset($stdClassResultReturnVoucher->serviceTypeId) ? $stdClassResultReturnVoucher->serviceTypeId : null;
		$this->_payerType = isset($stdClassResultReturnVoucher->payerType) ? $stdClassResultReturnVoucher->payerType : null;
	}
	
	/**
	 * Get Service type id.
	 * @return 64-bit integer
	 */
	public function getServiceTypeId() {
		return $this->_serviceTypeId;
	}
	
	
	/**
	 * Get Payer type (0=sender, 1=receiver or 2=third party).
	 * @return 32-bit integer
	 */
	public function getPayerType() {
		return $this->_payerType;
	}
	
}
?>