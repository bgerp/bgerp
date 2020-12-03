<?php
/**
 * Instances of this class are returned as a result of create picking Speedy web service calls
 */
class ResultParcelInfo {

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
     * Constructs new instance of ResultParcelInfo from stdClass
     * @param stdClass $stdClassResultParcelInfo
     */
    function __construct($stdClassResultParcelInfo) {
        $this->_seqNo    = isset($stdClassResultParcelInfo->seqNo)    ? $stdClassResultParcelInfo->seqNo    : null;
        $this->_parcelId = isset($stdClassResultParcelInfo->parcelId) ? $stdClassResultParcelInfo->parcelId : null;
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
}
?>