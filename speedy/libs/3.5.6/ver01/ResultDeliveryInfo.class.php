<?php

/**
 * Instances of this class are used as a result of make picking info methods
 * @since 2.9.0
 */
class ResultDeliveryInfo {
	
	/**
	 * Delivery date.
	 * @var signed Date
	 */
	protected $_deliveryDate;
	
	/**
	 * The name of the person who received the shipment
	 * @var signed string
	 */
	protected $_consignee;

	/**
	 * Delivery Note
	 * @var signed string
	 */
	protected $_deliveryNote;
	
	/**
	 * Constructs new instance of this class
	 * @param $stdClassResultDeliveryInfo
	 */
	function __construct($stdClassResultDeliveryInfo) {
		$this->_deliveryDate = isset($stdClassResultDeliveryInfo->deliveryDate) ? $stdClassResultDeliveryInfo->deliveryDate : null;
		$this->_consignee = isset($stdClassResultDeliveryInfo->consignee) ? $stdClassResultDeliveryInfo->consignee : null;
		$this->_deliveryNote = isset($stdClassResultDeliveryInfo->deliveryNote) ? $stdClassResultDeliveryInfo->deliveryNote : null;
	}
	
	/**
	 * Get Delivery date.
	 * @return Date
	 */
	public function getDeliveryDate() {
		return $this->_deliveryDate;
	}
	
	
	/**
	 * Get The name of the person who received the shipment
	 * @return string
	 */
	public function getConsignee() {
		return $this->_consignee;
	}

	/**
	 * Get Delivery Note
	 * @return string
	 */
	public function getDeliveryNote() {
		return $this->_deliveryNote;
	}
	
}
?>