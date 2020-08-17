<?php

/**
 * Instances of this class are used as a result of make picking info methods
 * @since 3.2.0
 */
class ResultReturnShipmentRequest {
	
	/**
	 * Insurance base amount.
	 * @var signed 64-bit integer (nullable)
	 */
	protected $_amountInsuranceBase;
	
	/**
	 * Fragile flag
	 * @var boolean (nullable)
	 */
	protected $_fragile;

	/**
	 * Number of parcels
	 * @var signed 32-bit integer
	 */
	protected $_parcelsCount;

	/**
	 * Service type id
	 * @var signed 64-bit integer
	 */
	protected $_serviceTypeId;
	
	/**
	 * Constructs new instance of this class
	 * @param $stdClassResultReturnShipmentRequest
	 */
	function __construct($stdClassResultReturnShipmentRequest) {
		$this->_amountInsuranceBase = isset($stdClassResultReturnShipmentRequest->amountInsuranceBase) ? $stdClassResultReturnShipmentRequest->amountInsuranceBase : null;
		$this->_fragile = isset($stdClassResultReturnShipmentRequest->fragile) ? $stdClassResultReturnShipmentRequest->fragile : null;
		$this->_parcelsCount = isset($stdClassResultReturnShipmentRequest->parcelsCount) ? $stdClassResultReturnShipmentRequest->parcelsCount : null;
		$this->_serviceTypeId = isset($stdClassResultReturnShipmentRequest->serviceTypeId) ? $stdClassResultReturnShipmentRequest->serviceTypeId : null;
	}
	
	/**
	 * Get insurance base amount.
	 * @return signed 64-bit integer (nullable)
	 */
	public function getAmountInsuranceBase() {
		return $this->_amountInsuranceBase;
	}
	
	
	/**
	 * Get fragile flag
	 * @return boolean (nullable)
	 */
	public function getFragile() {
		return $this->_fragile;
	}

	/**
	 * Get number of parcels
	 * @return signed 32-bit integer
	 */
	public function getParcelsCount() {
		return $this->_parcelsCount;
	}

	/**
	 * Get service type id
	 * @return signed 64-bit integer
	 */
	public function getServiceTypeId() {
		return $this->_serviceTypeId;
	}
	
}
?>