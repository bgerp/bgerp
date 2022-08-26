<?php

/**
 * Instances of this class are returned information for specified parcel number.
 */
class CreatePDFExResponse {

    /**
     * PDF content data
     * @access private
     * @var byteArray
     */
    private $_pdfBytes;
       
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
     * Export priority.
     * @access private
     * @var integer
     */
    private $_exportPriority;
   
    /**
     * Constructs new instance of stdClassCreatePDFExResponse from stdClass
     * @param stdClass $stdClassCreatePDFExResponse
     */
    function __construct($stdClassCreatePDFExResponse) {
        $this->_pdfBytes = isset($stdClassCreatePDFExResponse -> pdfBytes) ? $stdClassCreatePDFExResponse -> pdfBytes : null;
        $this->_hubId = isset($stdClassCreatePDFExResponse -> hubId) ? $stdClassCreatePDFExResponse -> hubId : null;
        $this->_officeId = isset($stdClassCreatePDFExResponse -> officeId) ? $stdClassCreatePDFExResponse -> officeId : null;
        $this->_deadlineDay = isset($stdClassCreatePDFExResponse -> deadlineDay) ? $stdClassCreatePDFExResponse -> deadlineDay : null;
        $this->_deadlineMonth = isset($stdClassCreatePDFExResponse -> deadlineMonth) ? $stdClassCreatePDFExResponse -> deadlineMonth : null;
        $this->_tourId = isset($stdClassCreatePDFExResponse -> tourId) ? $stdClassCreatePDFExResponse -> tourId : null;
        $this->_fullBarcode = isset($stdClassCreatePDFExResponse -> fullBarcode) ? $stdClassCreatePDFExResponse -> fullBarcode : null;
        $this->_exportPriority = isset($stdClassCreatePDFExResponse -> exportPriority) ? $stdClassCreatePDFExResponse -> exportPriority : null;
    }

    /**
     * Get PDF content data
     * @return byteArray
     */
    public function getPdfBytes() {
        return $this->_pdfBytes;
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

    /**
     * Get export priority
     * @return string
     */
    public function getExportPriority() {
        return $this->_exportPriority;
    }   
}
?>