<?php
/**
 * Instances of thics class are used az parameters for picking search web services class
 */
class ParamSearchByRefNum {

    /**
     * Search in Ref1 or Ref2 fields
     * @var integer Signed 32-bit
     */
    const PARAM_SEARCH_REF1_OR_REF2 = 0;

    /**
     * Search in Ref1 field only
     * @var integer Signed 32-bit
     */
    const PARAM_SEARCH_REF1_ONLY = 1;

    /**
     * Search in Ref2 field only
     * @var integer Signed 32-bit
     */
    const PARAM_SEARCH_REF2_ONLY = 2;

    /**
     * The reference code to be searched (exact match, case sensitive)
     * MANDATORY: YES
     * @var string
     */
    private $_referenceNumber;

    /**
     * Specifies where to search: 0 means [Ref1 or Ref2], 1 means [Ref1], 2 means [Ref2]
     * MANDATORY: YES
     * @var integer Signed 32-bit
     */
    private $_searchInField;

    /**
     * Pick-up date - from
     * MANDATORY: NO
     * @var date
     */
    private $_dateFrom;

    /**
     * Pick-up date - to
     * MANDATORY: NO
     * @var date
     */
    private $_dateTo;

    /**
     * includeReturnBols
     * MANDATORY: NO
     * @var boolean
     */
    private $_includeReturnBols;

    /**
     * Set reference code to be searched (exact match, case sensitive)
     * @param string $referenceNumber
     */
    public function setReferenceNumber($referenceNumber) {
        $this->_referenceNumber = $referenceNumber;
    }

    /**
     * Get reference code to be searched (exact match, case sensitive)
     * @return string
     */
    public function getReferenceNumber() {
        return $this->_referenceNumber;
    }

    /**
     * Set search code: 0 means [Ref1 or Ref2], 1 means [Ref1], 2 means [Ref2]
     * @param integer $searchInField Signed 32-bit
     */
    public function setSearchInField($searchInField) {
        $this->_searchInField = $searchInField;
    }

    /**
     * Get search code: 0 means [Ref1 or Ref2], 1 means [Ref1], 2 means [Ref2]
     * @return integer Signed 32-bit
     */
    public function getSearchInField() {
        return $this->_searchInField;
    }

    /**
     * Set pick-up date - from
     * @param date $dateFrom
     */
    public function setDateFrom($dateFrom) {
        $this->_dateFrom = $dateFrom;
    }

    /**
     * Get pick-up date - from
     * @return date
     */
    public function getDateFrom() {
        return $this->_dateFrom;
    }

    /**
     * Set pick-up date - to
     * @param date $dateTo
     */
    public function setDateTo($dateTo) {
        $this->_dateTo = $dateTo;
    }

    /**
     * Get pick-up date - to
     * @return date
     */
    public function getDateTo() {
        return $this->_dateTo;
    }

    /**
     * Set includeReturnBols
     * @param boolean $includeReturnBols
     */
    public function setIncludeReturnBols($includeReturnBols) {
        $this->_includeReturnBols = $includeReturnBols;
    }

    /**
     * Get includeReturnBols
     * @return boolean
     */
    public function getIncludeReturnBols() {
        return $this->_includeReturnBols;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass -> referenceNumber = $this -> _referenceNumber;
        $stdClass -> searchInField = $this -> _searchInField;
        $stdClass -> dateFrom = $this -> _dateFrom;
        $stdClass -> dateTo = $this -> _dateTo;
        $stdClass -> includeReturnBols = $this -> _includeReturnBols;
        return $stdClass;
    }
}
?>