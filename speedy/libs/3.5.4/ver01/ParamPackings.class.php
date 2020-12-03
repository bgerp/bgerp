<?php

/**
 * Instances of this class are used as parameters to specify packing type and quantity
 * @since 2.3.0
 */
class ParamPackings {

    /**
     * The number of packings
     * MANDATORY: YES
     * @var signed 32-bit integer
     */
    protected $_count;
    
    /**
     * Packing id
     * MANDATORY: YES
     * @var signed 64-bit integer
     */
    protected $_packingId;

    /**
     * Set the number of packings
     * @param signed 32-bit integer $count The number of packings
     */
    public function setCount($count) {
        $this->_count = $count;
    }

    /**
     * Get the number of packings
     * @return signed 32-bit integer The number of packings
     */
    public function getCount() {
        return $this->_count;
    }

    /**
     * Set the packing id
     * @param signed 32-bit integer $packingId The packing id
     */
    public function setPackingId($packingId) {
    	$this->_packingId = $packingId;
    }
    
    /**
     * Get the packing id
     * @return signed 32-bit integer The packing id
     */
    public function getPackingId() {
    	return $this->_packingId;
    }
    
    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->count     = $this->_count;
        $stdClass->packingId = $this->_packingId;
        return $stdClass;
    }
}
?>