<?php
/**
 * Instances of this class are used as parameters for searchSecondaryPickings method
 */
class ParamSearchSecondaryPickings {

    /**
     * BOL of the primary shipment
     * MANDATORY: YES
     * @var integer signed 64-bit
     */
    private $_billOfLading;

    /**
     * Filters the list for shipments of the specified type only. Not used if null.
	 * 1 = PICKING_TYPE_RETURN_SHIPMENT - return documents/receipt/service/shipment
	 * 2 = PICKING_TYPE_STORAGE_PAYMENT - warehouse charges
	 * 3 = PICKING_TYPE_REDIRECT - redirect shipment
	 * 4 = PICKING_TYPE_SEND_BACK - return to sender
	 * 5 = PICKING_TYPE_MONEY_TRANSFER - money transfer
	 * 6 = PICKING_TYPE_TRANSPORT_DAMAGED - damaged shipment transport 
     * MANDATORY: NO
     * @var integer Signed 32-bit
     */
    private $_secondaryPickingType;

    /**
     * Set BOL number
     * @param integer signed 64-bit $billOfLading
     */
    public function setBillOfLading($billOfLading) {
        $this->_billOfLading = $billOfLading;
    }

    /**
     * Get BOL number
     * @return integer signed 64-bit
     */
    public function getBillOfLading() {
        return $this->_billOfLading;
    }

    /**
     * Set search type
	 * 1 = PICKING_TYPE_RETURN_SHIPMENT - return documents/receipt/service/shipment
	 * 2 = PICKING_TYPE_STORAGE_PAYMENT - warehouse charges
	 * 3 = PICKING_TYPE_REDIRECT - redirect shipment
	 * 4 = PICKING_TYPE_SEND_BACK - return to sender
	 * 5 = PICKING_TYPE_MONEY_TRANSFER - money transfer
	 * 6 = PICKING_TYPE_TRANSPORT_DAMAGED - damaged shipment transport      
     * @param integer $secondaryPickingType Signed 32-bit
     */
    public function setSecondaryPickingType($secondaryPickingType) {
        $this->_secondaryPickingType = $secondaryPickingType;
    }

     /**
     * Get search type
	 * 1 = PICKING_TYPE_RETURN_SHIPMENT - return documents/receipt/service/shipment
	 * 2 = PICKING_TYPE_STORAGE_PAYMENT - warehouse charges
	 * 3 = PICKING_TYPE_REDIRECT - redirect shipment
	 * 4 = PICKING_TYPE_SEND_BACK - return to sender
	 * 5 = PICKING_TYPE_MONEY_TRANSFER - money transfer
	 * 6 = PICKING_TYPE_TRANSPORT_DAMAGED - damaged shipment transport      
     */
    public function getSecondaryPickingType() {
        return $this->_secondaryPickingType;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->billOfLading = $this->_billOfLading;
        $stdClass->secondaryPickingType = $this->_secondaryPickingType;
        return $stdClass;
    }
}
?>