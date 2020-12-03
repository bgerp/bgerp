<?php
/**
 * Instances of this class are returned as a result of extended track picking web service calls
 * @since 1.1
 */
class ResultTrackPickingEx {

	/**
     * BOL number or parcel id
     * @var integer signed 64-bit (nullable)
     * @since 2.8.0
     */
	private $_barcode;

    /**
     * Date and time
     * @var date
     */
    private $_moment;

    /**
     * Operation code
     * @var integer signed 32-bit
     */
    private $_operationCode;

    /**
     * Text description of the operation
     * @var string
     */
    private $_operationDescription;

    /**
     * Additional note/comment
     * @var string
     */
    private $_operationComment;

    /**
     * Site type
     * @var string
     */
    private $_siteType;

    /**
     * Site name
     * @var string
     */
    private $_siteName;

    /**
     * The name of the person who received the shipment
     * @var string
     */
    private $_consignee;

    /**
     * Returning bill of lading
     * @var integer Signed 64-bit
     */
    private $_returnBillOfLading;

    /**
     * Redirecting bill of lading
     * @var integer Signed 64-bit
     */
    private $_redirectBillOfLading;

	/**
     * Image URL for proof of delivery
     * @var string
     * @since 2.8.0
     */
	private $_signatureImage;

	/**
     * Foreign parcel number associated with this parcel
     * @var string
     * @since 2.9.0
     */
	private $_foreignParcelNumber;

	/**
     * List of exception codes.
     * @var List of signed 32-bit integer (nullable)
     * @since 2.9.0
     */
	private $_exceptionCodes;

	/**
     * List of foreign parcel numbers list associated with this parcel.
     * @var List string
     * @since 3.2.2
     */
	private $_foreignParcelNumbersList;

	/**
     * URL with additional information.
     * @var List string
     * @since 3.4.2
     */
	private $_infoURL;

	/**
     * Additional information.
     * @var string
     * @since 3.4.8
     */
	private $_additionalInfo;

    /**
     * Constructs new instance of ResultTrackPickingEx
     * @param stdClass $stdClassResultTrackPickingEx
     */
    function __construct($stdClassResultTrackPickingEx) {
        $this->_barcode              = isset($stdClassResultTrackPickingEx->barcode)              ? $stdClassResultTrackPickingEx->barcode              : null;
        $this->_moment               = isset($stdClassResultTrackPickingEx->moment)               ? $stdClassResultTrackPickingEx->moment               : null;
        $this->_operationCode        = isset($stdClassResultTrackPickingEx->operationCode)        ? $stdClassResultTrackPickingEx->operationCode        : null;
        $this->_operationDescription = isset($stdClassResultTrackPickingEx->operationDescription) ? $stdClassResultTrackPickingEx->operationDescription : null;
        $this->_operationComment     = isset($stdClassResultTrackPickingEx->operationComment)     ? $stdClassResultTrackPickingEx->operationComment     : null;
        $this->_siteType             = isset($stdClassResultTrackPickingEx->siteType)             ? $stdClassResultTrackPickingEx->siteType             : null;
        $this->_siteName             = isset($stdClassResultTrackPickingEx->siteName)             ? $stdClassResultTrackPickingEx->siteName             : null;
        $this->_consignee            = isset($stdClassResultTrackPickingEx->consignee)            ? $stdClassResultTrackPickingEx->consignee            : null;
        $this->_returnBillOfLading   = isset($stdClassResultTrackPickingEx->returnBillOfLading)   ? $stdClassResultTrackPickingEx->returnBillOfLading   : null;
        $this->_redirectBillOfLading = isset($stdClassResultTrackPickingEx->redirectBillOfLading) ? $stdClassResultTrackPickingEx->redirectBillOfLading : null;
        $this->_signatureImage       = isset($stdClassResultTrackPickingEx->signatureImage)       ? $stdClassResultTrackPickingEx->signatureImage       : null;
        $this->_foreignParcelNumber  = isset($stdClassResultTrackPickingEx->foreignParcelNumber)  ? $stdClassResultTrackPickingEx->foreignParcelNumber  : null;
        $this->_exceptionCodes       = isset($stdClassResultTrackPickingEx->exceptionCodes)       ? $stdClassResultTrackPickingEx->exceptionCodes       : null;
        $this->_foreignParcelNumbersList = isset($stdClassResultTrackPickingEx->foreignParcelNumbersList) ? $stdClassResultTrackPickingEx->foreignParcelNumbersList : null;
        $this->_infoURL              = isset($stdClassResultTrackPickingEx->infoURL)              ? $stdClassResultTrackPickingEx->infoURL              : null;
        $this->_additionalInfo       = isset($stdClassResultTrackPickingEx->additionalInfo)       ? $stdClassResultTrackPickingEx->additionalInfo       : null;
    }

	/**
     * Get barcode (BOL number or parcel id)
     * @return integer signed 64-bit
     * @since 2.8.0
     */
    public function getBarcode() {
        return $this->_barcode;
    }
    
    /**
     * Get date and time of the request
     * @return date
     */
    public function getMoment() {
        return $this->_moment;
    }

    /**
     * Get operation code
     * @return integer signed 32-bit
     */
    public function getOperationCode() {
        return $this->_operationCode;
    }

    /**
     * Get text description of the operation
     * @return string
     */
    public function getOperationDescription() {
        return $this->_operationDescription;
    }

    /**
     * Get additional note/comment
     * @return string
     */
    public function getOperationComment() {
        return $this->_operationComment;
    }

    /**
     * Get site type
     * @return string
     */
    public function getSiteType() {
        return $this->_siteType;
    }

    /**
     * Get site name
     * @return string
     */
    public function getSiteName() {
        return $this->_siteName;
    }

    /**
     * Get name of the person who received the shipment
     * @return string
     */
    public function getConsignee() {
        return $this->_consignee;
    }

    /**
     * Get returning bill of lading
     * @return integer signed 64-bit
     */
    public function getReturnBillOfLading() {
        return $this->_returnBillOfLading;
    }

    /**
     * Get redirecting bill of lading
     * @return integer signed 64-bit
     */
    public function getRedirectBillOfLading() {
        return $this->_redirectBillOfLading;
    }
    
    /**
     * Get image URL for proof of delivery
     * @return integer signed 64-bit
     * @since 2.8.0
     */
    public function getSignatureImage() {
        return $this->_signatureImage;
    }

    /**
     * Get foreignParcelNumber
     * @return string
     * @since 2.9.0
     */
    public function getForeignParcelNumber() {
        return $this->_foreignParcelNumber;
    }

    /**
     * Get list of exception codes.
     * @return list of signed 32-bit integer (nullable)
     * @since 2.9.0
     */
    public function getExceptionCodes() {
        return $this->_exceptionCodes;
    }

    /**
     * Get list of foreign parcel numbers list associated with this parcel
     * @return list 
     * @since 3.2.2
     */
    public function getForeignParcelNumbersList() {
        return $this->_foreignParcelNumbersList;
    }

    /**
     * Get URL with additional information
     * @return string 
     * @since 3.4.2
     */
    public function getInfoURL() {
        return $this->_infoURL;
    }

    /**
     * Get additional information
     * @return string 
     * @since 3.4.8
     */
    public function getAdditionalInfo() {
        return $this->_additionalInfo;
    }

}
?>