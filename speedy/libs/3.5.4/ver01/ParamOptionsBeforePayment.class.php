<?php

/**
 * Instances of this class are used as parameters to specify picking options before payment
 * @since 2.3.0
 */
class ParamOptionsBeforePayment {

   /**
	 * Open before payment option
	 * MANDATORY: NO
	 * @var boolean Flag
	 */
    private $_open;

	/**
	 * Test before payment option
	 * MANDATORY: NO
	 * @var boolean Flag
	 */
	private $_test;

	/**
	 * serviceId option
	 * MANDATORY: NO
	 * @var signed 64-bit integer
	 */
	private $_returnServiceTypeId;

	/**
	 * payerType option
	 * MANDATORY: NO
	 * @var signed 32-bit integer
	 */
	private $_returnPayerType;

    /**
     * Set open option before payment flag
     * @param boolean $open Open option before payment flag
     */
    public function setOpen($open) {
        $this->_open = $open;
    }

    /**
     * Get open option before payment flag
     * @return boolean Open option before payment flag
     */
    public function isOpen() {
        return $this->_open;
    }
    
     /**
     * Set test option before payment flag
     * @param boolean $test Test option before payment flag
     */
    public function setTest($test) {
        $this->_test = $test;
    }

    /**
     * Get test option before payment flag
     * @return boolean test option before payment flag
     */
    public function isTest() {
        return $this->_test;
    }


     /**
     * Set serviceTypeId option
     * @param signed 64-bit integer
     */
    public function setReturnServiceTypeId($returnServiceTypeId) {
        $this->_returnServiceTypeId = $returnServiceTypeId;
    }

    /**
     * Get test option before payment flag
     * @return signed 64-bit integer
     */
    public function getReturnServiceTypeId() {
        return $this->_returnServiceTypeId;
    }

     /**
     * Set payerType option
     * @param signed signed 32-bit integer
     */
    public function setReturnPayerType($returnPayerType) {
        $this->_returnPayerType = $returnPayerType;
    }

    /**
     * Get payerType option
     * @return signed signed 32-bit integer
     */
    public function getReturnPayerType() {
        return $this->_returnPayerType;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->open = $this->_open;
        $stdClass->test = $this->_test;
        $stdClass->returnServiceTypeId = $this->_returnServiceTypeId;
        $stdClass->returnPayerType = $this->_returnPayerType;
        return $stdClass;
    }
}
?>