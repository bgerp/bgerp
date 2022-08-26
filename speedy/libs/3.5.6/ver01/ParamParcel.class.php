<?php

require_once 'Size.class.php';

/**
 * Inctances of this class are used as a paremeter to add parcels to pickings
 */
class ParamParcel {

    /**
     * The BOL to which the parcel is to be added
     * MANDATORY: YES
     * @var integer signed 64-bit
     */
    private $_billOfLading;

    /**
     * Parcel ID (if empty, the server will generate one)
     * MANDATORY: NO
     * @var integer signed 64-bit
     */
    private $_parcelId;

    /**
     * Packing ID
     * MANDATORY: NO
     * @var integer Signed 64-bit
     */
    private $_packId;

    /**
     * Real weight (kg)
     * MANDATORY: YES
     * @var double Signed 64-bit
     */
    private $_weight;

    /**
     * Parcel size
     * MANDATORY: NO
     * @var Size
     */
    private $_size;
    
    /**
     * Foreign parcel number
     * MANDATORY: NO
     * @var string
     * @since 2.5.0
     */
    private $_foreignParcelNumber;

    /**
     * Set BOL number
     * @param integer $billOfLading Signed 64-bit
     */
    public function setBillOfLading($billOfLading) {
        $this->_billOfLading = $billOfLading;
    }

    /**
     * Get BOL number
     * @return integer Signed 64-bit
     */
    public function getBillOfLading() {
        return $this->_billOfLading;
    }

    /**
     * Set parcel ID (if empty, the server will generate one)
     * @param integer $parcelId Signed 64-bit
     */
    public function setParcelId($parcelId) {
        $this->_parcelId = $parcelId;
    }

    /**
     * Get parcel ID
     * @return integer Signed 64-bit
     */
    public function getParcelId() {
        return $this->_parcelId;
    }

    /**
     * Set packing ID
     * @param integer $packId Signed 64-bit
     */
    public function setPackId($packId) {
        $this->_packId = $packId;
    }

    /**
     * Get packing ID
     * @return integer Signed 64-bit
     */
    public function getPackId() {
        return $this->_packId;
    }

    /**
     * Set real weight
     * @param double $weight Signed 64-bit
     */
    public function setWeight($weight) {
        $this->_weight = $weight;
    }

    /**
     * Get real weight
     * @return double Signed 64-bit
     */
    public function getWeight() {
        return $this->_weight;
    }

    /**
     * Set parcel size
     * @param Size $size
     */
    public function setSize($size) {
        $this->_size = $size;
    }

    /**
     * Get parcel size
     * @return Size
     */
    public function getSize() {
        return $this->_size;
    }
    
    /**
     * Set foreign parcel number
     * @param string $foreignParcelNumber
     */
    public function setForeignParcelNumber($foreignParcelNumber) {
    	$this->_foreignParcelNumber = $foreignParcelNumber;
    }
    
    /**
     * Get foreign parcel number
     * @return string
     */
    public function getForeignParcelNumber() {
    	return $this->_foreignParcelNumber;
    }
    
    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->billOfLading = $this->_billOfLading;
        $stdClass->parcelId     = $this->_parcelId;
        $stdClass->packId       = $this->_packId;
        $stdClass->weight       = $this->_weight;
        if (isset($this->_size)) {
            $stdClass->size = $this->_size->toStdClass();
        }
        $stdClass->foreignParcelNumber = $this->_foreignParcelNumber;
        return $stdClass;
    }
}
?>