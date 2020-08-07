<?php

/**
 * Instances of this class are returned as a result of ResultPickingExtendedInfo
 */
class ResultParcelInfoEx {

    /**
     * Parcel's serial number (1, 2, 3, ...)
     * @var integer Signed 32-bit
     */
    private $_seqNo;

    /**
     * Parcel ID. First parcel's ID is always the same as the BOL number.
     * @var integer Signed 64-bit
     */
    private $_parcelId;

    /**
     * Measured weight
     * @var signed 64 bit real (nullable)
     */
    private $_weightMeasured;

    /**
     * Declared weight
     * @var signed 64 bit real (nullable)
     */
    private $_weightDeclared;

    /**
     * Measured size
     * @var signed Size
     */
    private $_sizeMeasured;

    /**
     * Declared size
     * @var signed Size
     */
    private $_sizeDeclared;

    /**
     * Foreign parcel number associated with this parcel
     * @var signed string (nullable)
     */
    private $_foreignParcelNumber;

    /**
     * Packing ID (number)
     * @var signed 64-bit integer (nullable)
     */
    private $_packId;

	/**
     * List of foreign parcel numbers list associated with this parcel.
     * @var List string
     * @since 3.2.2
     */
	private $_foreignParcelNumbersList;

    /**
     * Constructs new instance of stdClassResultParcelInfoEx from stdClass
     * @param stdClass $stdClassResultParcelInfoEx
     */
    function __construct($stdClassResultParcelInfoEx) {
        $this->_seqNo = isset($stdClassResultParcelInfoEx->seqNo) ? $stdClassResultParcelInfoEx->seqNo : null;
        $this->_parcelId = isset($stdClassResultParcelInfoEx->parcelId) ? $stdClassResultParcelInfoEx->parcelId : null;
        $this->_weightMeasured = isset($stdClassResultParcelInfoEx->weightMeasured) ? $stdClassResultParcelInfoEx->weightMeasured : null;
        $this->_weightDeclared = isset($stdClassResultParcelInfoEx->weightDeclared) ? $stdClassResultParcelInfoEx->weightDeclared : null;
        $this->_sizeMeasured = isset($stdClassResultParcelInfoEx->sizeMeasured) ? new Size($stdClassResultParcelInfoEx->sizeMeasured) : null;
        $this->_sizeDeclared = isset($stdClassResultParcelInfoEx->sizeDeclared) ? new Size($stdClassResultParcelInfoEx->sizeDeclared) : null;
        $this->_foreignParcelNumber = isset($stdClassResultParcelInfoEx->foreignParcelNumber) ? $stdClassResultParcelInfoEx->foreignParcelNumber : null;
        $this->_packId = isset($stdClassResultParcelInfoEx->packId) ? $stdClassResultParcelInfoEx->packId : null;
        $this->_foreignParcelNumbersList = isset($stdClassResultParcelInfoEx->foreignParcelNumbersList) ? $stdClassResultParcelInfoEx->foreignParcelNumbersList : null;
    }

    /**
     * Get parcel's serial number (1, 2, 3, ...)
     * @return integer Signed 32-bit
     */
    public function getSeqNo() {
        return $this->_seqNo;
    }

    /**
     * Get parcel ID. First parcel's ID is always the same as the BOL number.
     * @return integer Signed 64-bit
     */
    public function getParcelId() {
        return $this->_parcelId;
    }

    /**
     * Get Measured weight
     * @return 64 bit real (nullable)
     */
    public function getWeightMeasured() {
        return $this->_weightMeasured;
    }

    /**
     * Get Declared weight
     * @return 64 bit real (nullable)
     */
    public function getWeightDeclared() {
        return $this->_weightDeclared;
    }

    /**
     * Get Measured size
     * @return size
     */
    public function getSizeMeasured() {
        return $this->_sizeMeasured;
    }

    /**
     * Get Declared size
     * @return size
     */
    public function getSizeDeclared() {
        return $this->_sizeDeclared;
    }

    /**
     * Foreign parcel number associated with this parcel
     * return string (nullable)
     */
    public function getForeignParcelNumber() {
        return $this->_foreignParcelNumber;
    }

    /**
     * Packing ID (number)
     * return signed 64-bit integer (nullable)
     */
    public function getPackId() {
        return $this->_packId;
    }

    /**
     * Get list of foreign parcel numbers list associated with this parcel
     * @return list 
     * @since 3.2.2
     */
    public function getForeignParcelNumbersList() {
        return $this->_foreignParcelNumbersList;
    }

}
?>