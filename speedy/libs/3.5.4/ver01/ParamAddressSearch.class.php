<?php
/**
 * Instances of this class are used as a parameter for Speedy web service address search methods
 */
class ParamAddressSearch {

    /**
     * Site ID
     * MANDATORY: YES
     * @var integer Signed 64-bit
     */
    private $_siteId;

    /**
     * Quarter ID
     * MANDATORY: NO
     * @var integer Signed 64-bit
     */
    private $_quarterId;

    /**
     * Street ID
     * MANDATORY: NO
     * @var integer Signed 64-bit
     */
    private $_streetId;

    /**
     * Common object ID
     * MANDATORY: NO
     * @var integer Signed 64-bit
     */
    private $_commonObjectId;

    /**
     * Block No/name
     * MANDATORY: NO
     * @var string
     */
    private $_blockNo;

    /**
     * Street No
     * MANDATORY: NO
     * @var string
     */
    private $_streetNo;

    /**
     * Entrance
     * MANDATORY: NO
     * @var string
     */
    private $_entranceNo;
    
    /**
     * Return city center if no address option
     * MANDATORY: NO
     * @var boolean
     * @since 2.6.0
     */
    private $_returnCityCenterIfNoAddress;

    /**
     * Set site ID
     * @param integer $siteId Signed 64-bit
     */
    public function setSiteId($siteId) {
        $this->_siteId = $siteId;
    }

    /**
     * Get site ID
     * @return integer Signed 64-bit
     */
    public function getSiteId() {
        return $this->_siteId;
    }

    /**
     * Set quarter ID
     * @param integer $quarterId Signed 64-bit
     */
    public function setQuarterId($quarterId) {
        $this->_quarterId = $quarterId;
    }

    /**
     * Get quarter ID
     * @return integer Signed 64-bit
     */
    public function getQuarterId() {
        return $this->_quarterId;
    }

    /**
     * Set street ID
     * @param integer $streetId Signed 64-bit
     */
    public function setStreetId($streetId) {
        $this->_streetId = $streetId;
    }

    /**
     * Get street ID
     * @return integer Signed 64-bit
     */
    public function getStreetId() {
        return $this->_streetId;
    }

    /**
     * Set common object ID
     * @param integer $commonObjectId Signed 64-bit
     */
    public function setCommonObjectId($commonObjectId) {
        $this->_commonObjectId = $commonObjectId;
    }

    /**
     * Get common object ID
     * @return integer Signed 64-bit
     */
    public function getCommonObjectId() {
        return $this->_commonObjectId;
    }

    /**
     * Set block No
     * @param string $blockNo
     */
    public function setBlockNo($blockNo) {
        $this->_blockNo = $blockNo;
    }

    /**
     * Get block No
     * @return string
     */
    public function getBlockNo() {
        return $this->_blockNo;
    }

    /**
     * Set street No
     * @param string $streetNo
     */
    public function setStreetNo($streetNo) {
        $this->_streetNo = $streetNo;
    }

    /**
     * Get street No
     * @return string
     */
    public function getStreetNo() {
        return $this->_streetNo;
    }

    /**
     * Set entrance No
     * @param string $entranceNo
     */
    public function setEntranceNo($entranceNo) {
        $this->_entranceNo = $entranceNo;
    }

    /**
     * Get entrance No
     * @return string
     */
    public function getEntranceNo() {
        return $this->_entranceNo;
    }
    
    /**
     * Set return city center if no address option flag
     * @param boolean $returnCityCenterIfNoAddress
     */
    public function setReturnCityCenterIfNoAddress($returnCityCenterIfNoAddress) {
        $this->_returnCityCenterIfNoAddress = $returnCityCenterIfNoAddress;
    }

    /**
     * Get return city center if no address option flag
     * @return boolean
     */
    public function isReturnCityCenterIfNoAddress() {
        return $this->_returnCityCenterIfNoAddress;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->siteId                      = $this->_siteId;
        $stdClass->quarterId                   = $this->_quarterId;
        $stdClass->streetId                    = $this->_streetId;
        $stdClass->commonObjectId              = $this->_commonObjectId;
        $stdClass->blockNo                     = $this->_blockNo;
        $stdClass->streetNo                    = $this->_streetNo;
        $stdClass->entranceNo                  = $this->_entranceNo;
        $stdClass->returnCityCenterIfNoAddress = $this->_returnCityCenterIfNoAddress;
        return $stdClass;
    }
}
?>