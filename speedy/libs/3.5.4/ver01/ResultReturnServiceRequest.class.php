<?php

/**
 * Instances of this class are used as a result of make picking info methods
 * @since 3.2.6
 */
class ResultReturnServiceRequest {
	
	/**
	 * Service type id.
	 * @var signed 64-bit integer
	 */
	protected $_serviceTypeId;
	
	/**
	 * Number of parcels.
	 * @var signed 32-bit integer
	 */
	protected $_parcelsCount;
	
	/**
	 * Constructs new instance of this class
	 * @param $stdClassResultReturnServiceRequest
	 */
	function __construct($stdClassResultReturnServiceRequest) {
		$this->_serviceTypeId = isset($stdClassResultReturnServiceRequest->serviceTypeId) ? $stdClassResultReturnServiceRequest->serviceTypeId : null;
		$this->_parcelsCount = isset($stdClassResultReturnServiceRequest->parcelsCount) ? $stdClassResultReturnServiceRequest->parcelsCount : null;
	}
	
	/**
	 * Get Service type id.
	 * @return 64-bit integer
	 */
	public function getServiceTypeId() {
		return $this->_serviceTypeId;
	}
	
	
	/**
	 * Get Number of parcels.
	 * @return 32-bit integer
	 */
	public function getParcelsCount() {
		return $this->_parcelsCount;
	}
	
}
?>