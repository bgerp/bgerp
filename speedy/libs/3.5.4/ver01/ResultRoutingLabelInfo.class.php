<?php

/**
 * Instances of this class are returned routing information for specified parcel number.
 */
class ResultRoutingLabelInfo {

    /**
     * Delivery hub id
     * @access private
     * @var integer
     */
    private $_hubId;

    /**
     * Delivery office id
     * @access private
     * @var integer
     */
    private $_officeId;

    /**
     * Delivery deadline day of month
     * @access private
     * @var integer
     */
    private $_deadlineDay;

    /**
     * The delivery deadline month
     * @access private
     * @var integer
     */
    private $_deadlineMonth;

    /**
     * Tour Id
     * @access private
     * @var integer
     */
    private $_tourId;

    /**
     * Barcode containing the parcel number and important routing information.
     * @access private
     * @var string
     */
    private $_fullBarcode;


    /**
     * Constructs new instance of stdClassResultRoutingLabelInfo from stdClass
     * @param stdClass $stdClassResultRoutingLabelInfo
     */
    function __construct($stdClassResultRoutingLabelInfo) {
        $this->_hubId = isset($stdClassResultRoutingLabelInfo -> hubId) ? $stdClassResultRoutingLabelInfo -> hubId : null;
        $this->_officeId = isset($stdClassResultRoutingLabelInfo -> officeId) ? $stdClassResultRoutingLabelInfo -> officeId : null;
        $this->_deadlineDay = isset($stdClassResultRoutingLabelInfo -> deadlineDay) ? $stdClassResultRoutingLabelInfo -> deadlineDay : null;
        $this->_deadlineMonth = isset($stdClassResultRoutingLabelInfo -> deadlineMonth) ? $stdClassResultRoutingLabelInfo -> deadlineMonth : null;
        $this->_tourId = isset($stdClassResultRoutingLabelInfo -> tourId) ? $stdClassResultRoutingLabelInfo -> tourId : null;
        $this->_fullBarcode = isset($stdClassResultRoutingLabelInfo -> fullBarcode) ? $stdClassResultRoutingLabelInfo -> fullBarcode : null;
    }

    /**
     * Get delivery hub id
     * @return integer
     */
    public function getHubId() {
        return $this->_hubId;
    }

    /**
     * Get delivery office id
     * @return integer
     */
    public function getOfficeId() {
        return $this->_officeId;
    }

    /**
     * Get delivery deadline day of month
     * @return integer
     */
    public function getDeadlineDay() {
        return $this->_deadlineDay;
    }

    /**
     * Get delivery deadline month
     * @return integer
     */
    public function getDeadlineMonth() {
        return $this->_deadlineMonth;
    }

    /**
     * Get tour Id
     * @return integer
     */
    public function getTourId() {
        return $this->_tourId;
    }

    /**
     * Get fullBarcode
     * @return string
     */
    public function getFullBarcode() {
        return $this->_fullBarcode;
    }

}
?>