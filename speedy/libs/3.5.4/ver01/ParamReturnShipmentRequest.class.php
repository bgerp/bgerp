<?php

/**
 * Instances of this class are passed as a parameter of Speedy web service calls to specify return shipment requests
 * @since 2.5.0
 */
class ParamReturnShipmentRequest {

	/**
	 * Insurance base amount
	 * MANDATORY: NO
	 * @var double Signed 64-bit
	 */
    private $_amountInsuranceBase;
    
    /**
     * Fragile flag
     * MANDATORY: NO
     * @var boolean
     */
    private $_fragile;
    
    /**
     * Number of parcels
     * MANDATORY: YES
     * @var integer Signed 32-bit
     */
    private $_parcelsCount;
    
    /**
     * Service type id
     * MANDATORY: YES
     * @var integer Signed 64-bit
     */
    private $_serviceTypeId;

    /**
     * Gets insurance base amount
     * @return double signed 64-bit Insurance base amount
     */
    public function getAmountInsuranceBase() {
        return $this->_amountInsuranceBase;
    }

    /**
     * Sets the insurance base amount
     * @param double signed 64-bit $amountInsuranceBase Insurance base amount
     */
    public function setAmountInsuranceBase($amountInsuranceBase) {
         $this->_amountInsuranceBase = $amountInsuranceBase;
    }

    /**
     * Gets fragile flag
     * @return boolean Fragile flag
     */
    public function isFragile() {
       return $this->_fragile;
    }

    /**
     * Sets fragile flag
     * @param boolean $fragile Fragile flag
     */
    public function setFragile($fragile) {
         $this->_fragile = $fragile;
    }

    /**
     * Get courier service type ID
     * @return integer Signed 64-bit
     */
    public function getServiceTypeId() {
        return $this->_serviceTypeId;
    }
    
    /**
     * Set courier service type ID
     * @param integer $serviceTypeId Signed 64-bit
     */
    public function setServiceTypeId($serviceTypeId) {
        $this->_serviceTypeId = $serviceTypeId;
    }

    /**
     * Get parcels count
     * @return integer Signed 32-bit
     */
    public function getParcelsCount() {
        return $this->_parcelsCount;
    }
    
    /**
     * Set parcels count
     * @param integer $parcelsCount Signed 32-bit
     */
    public function setParcelsCount($parcelsCount) {
        $this->_parcelsCount = $parcelsCount;
    }
    
    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->amountInsuranceBase = $this->_amountInsuranceBase;
        $stdClass->fragile             = $this->_fragile;
        $stdClass->parcelsCount        = $this->_parcelsCount;
        $stdClass->serviceTypeId       = $this->_serviceTypeId;
        return $stdClass;
    }
}
?>