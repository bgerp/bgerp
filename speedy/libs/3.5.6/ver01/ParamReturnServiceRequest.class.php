<?php

/**
 * Instances of this class are passed as a parameter of Speedy web service calls to specify return service requests
 * @since 2.5.0
 */
class ParamReturnServiceRequest {

    /**
     * Number of parcels
     * MANDATORY: Only for updateBillOfLading. Null otherwise
     * @var integer signed 32-bit integer
     */
    private $_parcelsCount;
    
    /**
     * Service type id
     * According to return service configuration and limited to the number of parcels of original bill of lading. Minimum 1
     * MANDATORY
     * @var integer Signed 64-bit
     */
    private $_serviceTypeId;
    
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
        $stdClass->parcelsCount  = $this->_parcelsCount;
        $stdClass->serviceTypeId = $this->_serviceTypeId;
        return $stdClass;
    }
}
?>