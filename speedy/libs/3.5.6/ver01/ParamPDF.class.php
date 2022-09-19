<?php

require_once 'ParamBarcodeInfo.class.php';

/**
 * Instances of this class are used as a parameter for createPDF documents web service methods
 */
class ParamPDF {

    /**
     * BOL type
     * @var integer Signed 32-bit
     */
    const PARAM_PDF_TYPE_BOL = 10;

    /**
     * Labels type
     * @var integer Signed 32-bit
     */
    const PARAM_PDF_TYPE_LBL = 20;

    /**
     * labels with additional barcode type
     * @var integer Signed 32-bit
     */
    const PARAM_PDF_TYPE_LBL_WITH_ADDTNL_BARCODE = 25;

    /**
     * Additional barcode format CODE128
     * @var string
     */
    const PARAM_PDF_ADDIRIONAL_BARCODE_FMT_CODE128 = 'CODE128';

    /**
     * Additional barcode format EAN13
     * @var string
     */
    const PARAM_PDF_ADDIRIONAL_BARCODE_FMT_EAN13 = 'EAN13';

    /**
     * Additional barcode format EAN8
     * @var string
     */
    const PARAM_PDF_ADDIRIONAL_BARCODE_FMT_EAN8 = 'EAN8';

    /**
     * Additional barcode format UPC-A
     * @var string
     */
    const PARAM_PDF_ADDIRIONAL_BARCODE_FMT_UPC_A = 'UPC-A';

    /**
     * Additional barcode format UPC-E
     * @var string
     */
    const PARAM_PDF_ADDIRIONAL_BARCODE_FMT_UPC_E = 'UPC-E';

    /**
     * The document type (10 - BOL; 20 - labels; 25 - labels with additional barcode)
     * MANDATORY: YES
     * @var integer signed 32-bit
     */
    private $_type;

    /**
     * List of IDs.
     * For type 10 only the BOL number is needed.
     * For types 20 and 25 one or more parcel IDs are expected (parcels must be of a single BOL).
     * MANDATORY: YES
     * @var array List of signed 64-bit integers
     */
    private $_ids;

    /**
     * Specifies if embedded JavaScript code for direct printing to be generated (works for Adobe Acrobat Reader only).
     * MANDATORY: YES
     * @var boolean
     */
    private $_includeAutoPrintJS;

    /**
     * The printer name. If empty, the default printer is to be used. Only applicable if includeAutoPrintJS = true.
     * MANDATORY: NO
     * @var string
     */
    private $_printerName;

    /**
     * Only allowed for type 25. A list of additional (second) barcodes to be printed on the bottom of each label in the PDF document.
     * Note that the additional barcodes take some extra space so the label height for type 25 is greater than the label height for type 20.
     * Each element in the list corresponds to the element of 'ids' with the same index (position).
     * MANDATORY: NO
     * @var array List of ParamBarcodeInfo
     */
    private $_additionalBarcodes;

    /**
     * Only allowed for type 25.
     * Specifies the barcode format to be used for additionalBarcodes.
     * Accepts the following values: 'CODE128', 'EAN13', 'EAN8', 'UPC-A', 'UPC-E'
     * MANDATORY: NO
     * @var string
     */
    private $_additionalBarcodesFormat;

    /**
     * Specifies whether to print an additional copy for sender.
     * MANDATORY: NO
     * @var boolean (nullable)
     */
    private $_additionalCopyForSender;

    /**
     * Set document type (10 - BOL; 20 - labels; 25 - labels with additional barcode)
     * @param integer $type signed 32-bit
     */
    public function setType($type) {
        $this->_type = $type;
    }

    /**
     * Get document type (10 - BOL; 20 - labels; 25 - labels with additional barcode)
     * @return integer Signed 32-bit
     */
    public function getType() {
        return $this->_type;
    }

    /**
     * Set list of IDs. For type 10 only the BOL number is needed.
     * For types 20 and 25 one or more parcel IDs are expected (parcels must be of a single BOL).
     * @param array $ids List of signed 64-bit integers
     */
    public function setIds($ids) {
        $this->_ids = $ids;
    }

    /**
     * Get list of IDs.
     * @return array List of signed 64-bit integers
     */
    public function getIds() {
        return $this->_ids;
    }

    /**
     * Set flag to include embedded JavaScript code for direct printing for Adobe Acrobat Reader
     * @param boolean $includeAutoPrintJS
     */
    public function setIncludeAutoPrintJS($includeAutoPrintJS) {
        $this->_includeAutoPrintJS = $includeAutoPrintJS;
    }

    /**
     * Check flag to include embedded JavaScript code for direct printing for Adobe Acrobat Reader.
     * @return boolean
     */
    public function isIncludeAutoPrintJS() {
        return $this->_includeAutoPrintJS;
    }

    /**
     * Set printer name. If empty, the default printer is to be used. Only applicable if includeAutoPrintJS = true.
     * @param string $printerName
     */
    public function setPrinterName($printerName) {
        $this->_printerName = $printerName;
    }

    /**
     * Get printer name.
     * @return string
     */
    public function getPrinterName() {
        return $this->_printerName;
    }

    /**
     * Set list of additional (second) barcodes to be printed on the bottom of each label in the PDF document. Only allowed for type 25.
     * Note that the additional barcodes take some extra space so the label height for type 25 is greater than the label height for type 20.
     * Each element in the list corresponds to the element of 'ids' with the same index (position).
     * @param array $additionalBarcodes List of ParamBarcodeInfo
     */
    public function setAdditionalBarcodes($additionalBarcodes) {
        $this->_additionalBarcodes = $additionalBarcodes;
    }

    /**
     * Get list of additional (second) barcodes to be printed on the bottom of each label in the PDF document for type 25.
     * @return array List of ParamBarcodeInfo
     */
    public function getAdditionalBarcodes() {
        return $this->_additionalBarcodes;
    }

    /**
     * Set the barcode format to be used for additionalBarcodes. Only allowed for type 25.
     * Accepts the following values: 'CODE128', 'EAN13', 'EAN8', 'UPC-A', 'UPC-E'
     * @param string $additionalBarcodesFormat
     */
    public function setAdditionalBarcodesFormat($additionalBarcodesFormat) {
        $this->_additionalBarcodesFormat = $additionalBarcodesFormat;
    }

    /**
     * Get the barcode format to be used for additionalBarcodes.
     * Possible values: 'CODE128', 'EAN13', 'EAN8', 'UPC-A', 'UPC-E'
     * @return string
     */
    public function getAdditionalBarcodesFormat() {
        return $this->_additionalBarcodesFormat;
    }

    /**
     * Set whether to print an additional copy for sender.
     * Accepts the following values: TRUE / FALSE
     * @param boolean (nullable)
     */
    public function setAdditionalCopyForSender($additionalCopyForSender) {
        $this->_additionalCopyForSender = $additionalCopyForSender;
    }

    /**
     * Get whether to print an additional copy for sender.
     * @return boolean
     */
    public function getAdditionalCopyForSender() {
        return $this->_additionalCopyForSender;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass -> type = $this->_type;
        $arrIds = array();
        if (isset($this->_ids)) {
            if (is_array($this->_ids)) {
                for( $i = 0; $i < count($this->_ids); $i++ ) {
                    $arrIds[$i] = $this->_ids[$i];
                }
            } else {
                $arrIds[0] = $this->_ids;
            }
        }
        $stdClass->ids = $arrIds;
        $stdClass->includeAutoPrintJS = $this->_includeAutoPrintJS;
        $stdClass->printerName = $this->_printerName;
        $arrAdditionalBarcodes = array();
        if (isset($this->_additionalBarcodes)) {
            if (is_array($this->_additionalBarcodes)) {
                for($i = 0; $i < count($this->_additionalBarcodes); $i++) {
                    $arrAdditionalBarcodes[$i] = $this->_additionalBarcodes[$i]->toStdClass();
                }
            } else {
                $arrAdditionalBarcodes[0] = $this->_additionalBarcodes->toStdClass();
            }
        }
        $stdClass->additionalBarcodes = $arrAdditionalBarcodes;
        $stdClass->additionalBarcodesFormat = $this->_additionalBarcodesFormat;
        $stdClass->additionalCopyForSender = $this->_additionalCopyForSender;
        return $stdClass;
    }
}
?>