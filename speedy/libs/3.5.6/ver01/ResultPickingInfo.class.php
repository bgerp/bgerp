<?php
/**
 * Instances of this class are returned as a result of searchSecondaryPickings
 */
class ResultPickingInfo {

    /**
     * BOL of the secondary shipment.
     * @var integer Signed 64-bit
     */
    private $_billOfLading;

    /**
     * Search type (nullable)
     * 1 = PICKING_TYPE_RETURN_SHIPMENT 	- return documents/receipt/service/shipment
	 * 2 = PICKING_TYPE_STORAGE_PAYMENT 	- warehouse charges
	 * 3 = PICKING_TYPE_REDIRECT 	- redirect shipment
	 * 4 = PICKING_TYPE_SEND_BACK 	- return to sender
	 * 5 = PICKING_TYPE_MONEY_TRANSFER 	- money transfer
	 * 6 = PICKING_TYPE_TRANSPORT_DAMAGED 	- damaged shipment transport
     * @var integer Signed 32-bit
     */
    private $_secondaryPickingType;
    
    /**
     * The date for shipment pick-up (the "time" component is ignored). Default value is "today".
     * @var date Taking date
     */
    private $_takingDate;
    
    /**
     * Courier service type ID.
     * @var integer Signed 64-bit
     */
    private $_serviceTypeId;
    
    /**
     * Shows whether the secondary shipment has any barcode history operations.
     * @var boolean Hs scan flag
     */
    private $_hasScans;
    

    /**
     * Constructs new instance of ResultPickingInfo from stdClass
     * @param stdClass $stdResultPickingInfo
     */
    function __construct($stdResultPickingInfo) {
        $this->_billOfLading         = isset($stdResultPickingInfo->billOfLading)         ? $stdResultPickingInfo->billOfLading         : null;
        $this->_secondaryPickingType = isset($stdResultPickingInfo->secondaryPickingType) ? $stdResultPickingInfo->secondaryPickingType : null;
        $this->_takingDate           = isset($stdResultPickingInfo->takingDate)           ? $stdResultPickingInfo->takingDate           : null;
        $this->_serviceTypeId        = isset($stdResultPickingInfo->serviceTypeId)        ? $stdResultPickingInfo->serviceTypeId        : null;
        $this->_hasScans             = isset($stdResultPickingInfo->hasScans)             ? $stdResultPickingInfo->hasScans             : null;
    }

    /**
     * Get BOL of the secondary shipment.
     * @return integer Signed 64-bit
     */
    public function getBillOfLading() {
        return $this->_billOfLading;
    }

   /**
     * Get search type
     * 1 = PICKING_TYPE_RETURN_SHIPMENT 	- return documents/receipt/service/shipment
	 * 2 = PICKING_TYPE_STORAGE_PAYMENT 	- warehouse charges
	 * 3 = PICKING_TYPE_REDIRECT 	- redirect shipment
	 * 4 = PICKING_TYPE_SEND_BACK 	- return to sender
	 * 5 = PICKING_TYPE_MONEY_TRANSFER 	- money transfer
	 * 6 = PICKING_TYPE_TRANSPORT_DAMAGED 	- damaged shipment transport
     * @return integer Signed 32-bit (nullable)
     */
    public function getSecondaryPickingType() {
        return $this->_secondaryPickingType;
    }
    
    /**
     * Get taking date
     * @return date Taking date
     */
    public function getTakingDate() {
        return $this->_takingDate;
    }
    
    /**
     * Get courier service type ID.
     * @return integer Signed 64-bit
     */
    public function getServiceTypeId() {
        return $this->_serviceTypeId;
    }
    
    /**
     * Checks whether the secondary shipment has any barcode history operations.
     * @return boolean Has scan flag
     */
    public function isHasScans() {
        return $this->_hasScans;
    }
}
?>