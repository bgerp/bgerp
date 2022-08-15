<?php
/**
 * Instances of this class are used as parameters in web service calls for picking creation
 */
class ParamReturnVoucher {

    /**
     * Service type id ID
     * MANDATORY: YES
     * @var integer Signed 64-bit
     */
    private $serviceTypeId;

    /**
     * Payer type (0=sender, 1=receiver or 2=third party)
     * MANDATORY: YES
     * @var integer Signed 32-bit
     */
    private $_payerType;

    /**
     * Validity period
     * MANDATORY: NO
     * @var signed 32-bit integer
     * @since 3.5.4
     */
    private $_validityPeriod;




    /**
     * Set serviceTypeId ID
     * @param integer $serviceTypeId Signed 64-bit
     */
    public function setServiceTypeId($serviceTypeId) {
        $this->_serviceTypeId = $serviceTypeId;
    }

    /**
     * Get serviceTypeId ID
     * @return integer Signed 64-bit
     */
    public function getServiceTypeId() {
        return $this->_serviceTypeId;
    }

    /**
     * Set payerType
     * @param integer $payerType Signed 32-bit
     */
    public function setPayerType($payerType) {
        $this->_payerType = $payerType;
    }

    /**
     * Get payerType
     * @return integer Signed 32-bit
     */
    public function getPayerType() {
        return $this->_payerType;
    }

    /**
     * Set validityPeriod
     * @param integer $validityPeriod signed 32-bit
     */
    public function setValidityPeriod($validityPeriod) {
        $this->_validityPeriod = $validityPeriod;
    }

    /**
     * Get validityPeriod
     * @return integer signed 32-bit
     */
    public function getValidityPeriod() {
        return $this->_validityPeriod;
    }
    
    
    

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->serviceTypeId = $this->_serviceTypeId;
        $stdClass->payerType = $this->_payerType;
        $stdClass->validityPeriod = $this->_validityPeriod;
        return $stdClass;
    }
}
?>