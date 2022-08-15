<?php
/**
 * Instances of this class are returned as a result of Speedy address search web service methods
 */
class ResultAddressSearch {

    /**
     * Text description of the address found
     * @var string
     */
    private $_text;

    /**
     * GIS coordinates - X
     * @var double Signed 64-bit
     */
    private $_coordX;

    /**
     * GIS coordinates - Y
     * @var double Signed 64-bit
     */
    private $_coordY;

    /**
     * Microregion ID
     * @var integer Signed 64-bit
     */
    private $_microregionId;

    /**
     * Distance to site's center in kilometers (straight line)
     * @var double Signed 64-bit
     */
    private $_distanceToSiteCenter;

    /**
     * Specifies if the address is actual now
     * @var boolean
     */
    private $_actual;

    /**
     * GIS coordinates type
     * @var integer Signed 32-bit
     */
    private $_coordType;

    /**
     * Internal/debug info
     * @var integer Signed 32-bit
     */
    private $_additionalAddressProcessing;

    /**
     * Constructs new instance of ResultAddressSearch from stdClass
     * @param stdClass $stdClassResultAddressSearch
     */
    function __construct($stdClassResultAddressSearch) {
        $this->_text                        = isset($stdClassResultAddressSearch->text)                        ? $stdClassResultAddressSearch->text                        : null;
        $this->_coordX                      = isset($stdClassResultAddressSearch->coordX)                      ? $stdClassResultAddressSearch->coordX                      : null;
        $this->_coordY                      = isset($stdClassResultAddressSearch->coordY)                      ? $stdClassResultAddressSearch->coordY                      : null;
        $this->_microregionId               = isset($stdClassResultAddressSearch->microregionId)               ? $stdClassResultAddressSearch->microregionId               : null;
        $this->_distanceToSiteCenter        = isset($stdClassResultAddressSearch->distanceToSiteCenter)        ? $stdClassResultAddressSearch->distanceToSiteCenter        : null;
        $this->_actual                      = isset($stdClassResultAddressSearch->actual)                      ? $stdClassResultAddressSearch->actual                      : null;
        $this->_coordType                   = isset($stdClassResultAddressSearch->coordType)                   ? $stdClassResultAddressSearch->coordType                   : null;
        $this->_additionalAddressProcessing = isset($stdClassResultAddressSearch->additionalAddressProcessing) ? $stdClassResultAddressSearch->additionalAddressProcessing : null;
    }

    /**
     * Get text description of the address found
     * @return string
     */
    public function getText() {
        return $this->_text;
    }

    /**
     * Get GIS coordinate - X
     * @return double Signed 64-bit
     */
    public function getCoordX() {
        return $this->_coordX;
    }

    /**
     * Get GIS coordinate - Y
     * @return double Signed 64-bit
     */
    public function getCoordY() {
        return $this->_coordY;
    }

    /**
     * Get microregion ID
     * @return integer Signed 64-bit
     */
    public function getMicroregionId() {
        return $this->_microregionId;
    }

    /**
     * Get sistance to site's center in kilometers (straight line)
     * @return double Signed 64-bit
     */
    public function getDistanceToSiteCenter() {
        return $this->_distanceToSiteCenter;
    }

    /**
     * Check if address is actual now
     * @return boolean
     */
    public function isActual() {
        return $this->_actual;
    }

    /**
     * Get GIS coordinates type
     * @return integer Signed 32-bit
     */
    public function getCoordType() {
        return $this->_coordType;
    }

    /**
     * Get internal/debug info
     * @return integer Signed 32-bit
     */
    public function getAdditionalAddressProcessing() {
        return $this->_additionalAddressProcessing;
    }
}
?>