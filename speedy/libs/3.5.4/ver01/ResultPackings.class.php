<?php

/**
 * Instances of this class are used as a result of make picking info methods
 * @since 3.2.6
 */
class ResultPackings {
	
	/**
	 * Packing id.
	 * @var signed 64-bit integer
	 */
	protected $_packingId;
	
	/**
	 * Packings count.
	 * @var signed 32-bit integer
	 */
	protected $_count;
	
	/**
	 * Constructs new instance of this class
	 * @param $stdClassResultPackings
	 */
	function __construct($stdClassResultPackings) {
		$this->_packingId = isset($stdClassResultPackings->packingId) ? $stdClassResultPackings->packingId : null;
		$this->_count = isset($stdClassResultPackings->count) ? $stdClassResultPackings->count : null;
	}
	
	/**
	 * Get Packing id.
	 * @return 64-bit integer
	 */
	public function getPackingId() {
		return $this->_packingId;
	}
	
	
	/**
	 * Get Packings count.
	 * @return 32-bit integer
	 */
	public function getCount() {
		return $this->_count;
	}
	
}
?>