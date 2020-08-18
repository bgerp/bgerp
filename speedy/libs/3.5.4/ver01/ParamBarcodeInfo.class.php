<?php
/**
 * Instance of this class are used as parameters for providing barcode inpuit fot pdf generation web service calls
 */
class ParamBarcodeInfo {

    /**
     * Barcode value. For barcode formats other than 'CODE128' it must contain digits only.
     * MANDATORY: YES
     * @var string
     */
    private $_barcodeValue;

    /**
     * Barcode label. It is printed just below the barcode image.
     * For barcode formats other than 'CODE128' barcodeLabel must be equal to barcodeValue.
     * MANDATORY: NO
     * @var string
     */
    private $_barcodeLabel;

    /**
     * Set barcode value. For barcode formats other than 'CODE128' it must contain digits only.
     * @param string $barcodeValue
     */
    public function setBarcodeValue($barcodeValue) {
        $this->_barcodeValue = $barcodeValue;
    }

    /**
     * Get barcode value. For barcode formats other than 'CODE128' it must contain digits only.
     * @return string
     */
    public function getBarcodeValue() {
        return $this->_barcodeValue;
    }

    /**
     * Set barcode label. It is printed just below the barcode image.
     * For barcode formats other than 'CODE128' barcodeLabel must be equal to barcodeValue.
     * @param string $barcodeLabel
     */
    public function setBarcodeLabel($barcodeLabel) {
        $this->_barcodeLabel = $barcodeLabel;
    }

    /**
     * Get barcode label. It is printed just below the barcode image.
     * For barcode formats other than 'CODE128' it must contain digits only.
     * @return string
     */
    public function getBarcodeLabel() {
        return $this->_barcodeLabel;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->barcodeValue = $this->_barcodeValue;
        $stdClass->barcodeLabel = $this->_barcodeLabel;
        return $stdClass;
    }
}
?>