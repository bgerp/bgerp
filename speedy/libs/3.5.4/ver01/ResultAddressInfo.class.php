<?php
/**
 * Instances of this class are returned as a result of web service method calls for clients
 */
class ResultAddressInfo {

    /**
     * Site ID
     * @var integer Signed 64-bit (nullable)
     */
    private $_siteId;

    /**
     * Site name
     * @var string
     */
    private $_siteName;

    /**
     * Site type
     * @var string
     */
    private $_siteType;

    /**
     * Municpality name
     * @var string
     */
    private $_municipalityName;

    /**
     * Region name
     * @var string
     */
    private $_regionName;

    /**
     * Post code
     * @var string
     */
    private $_postCode;

    /**
     * Street name
     * @var string
     */
    private $_streetName;

    /**
     * Street type
     * @var string
     */
    private $_streetType;

    /**
     * Street ID
     * @var integer Signed 64-bit (nullable)
     */
    private $_streetId;

    /**
     * Quarter name
     * @var string
     */
    private $_quarterName;

    /**
     * Quarter type
     * @var string
     */
    private $_quarterType;

    /**
     * Quarter ID
     * @var long Signed 64-bit (nullable)
     */
    private $_quarterId;

    /**
     * Street No
     * @var string
     */
    private $_streetNo;

    /**
     * Block No
     * @var string
     */
    private $_blockNo;

    /**
     * Entrance No
     * @var string
     */
    private $_entranceNo;

    /**
     * Floor No
     * @var string
     */
    private $_floorNo;

    /**
     * Appartment No
     * @var string
     */
    private $_apartmentNo;

    /**
     * Address note
     * @var string
     */
    private $_addressNote;

    /**
     * Common object ID
     * @var integer Signed 64-bit (nullable)
     */
    private $_commonObjectId;

    /**
     * Common object name
     * @var string
     */
    private $_commonObjectName;
    
    /**
     * Country id
     * @var integer signed 64-bit
     * @since 2.5.0
     */
    private $_countryId;
    
    /**
     * Foreign address line 1
     * @var string
     * @since 2.5.0
     */
    private $_frnAddressLine1;
    
    /**
     * Foreign address line 2
     * @var string
     * @since 2.5.0
     */
    private $_frnAddressLine2;
    
    /**
     * State id
     * @var string
     * @since 2.5.0
     */
    private $_stateId;

    /**
     * Constructs new instance of ResultAddress
     * @param stdClass $stdClassResultAddress
     */
    function __construct($stdClassResultAddress) {
        $this->_siteId           = isset($stdClassResultAddress->siteId)           ? $stdClassResultAddress->siteId           : null;
        $this->_siteName         = isset($stdClassResultAddress->siteName)         ? $stdClassResultAddress->siteName         : null;
        $this->_siteType         = isset($stdClassResultAddress->siteType)         ? $stdClassResultAddress->siteType         : null;
        $this->_municipalityName = isset($stdClassResultAddress->municipalityName) ? $stdClassResultAddress->municipalityName : null;
        $this->_regionName       = isset($stdClassResultAddress->regionName)       ? $stdClassResultAddress->regionName       : null;
        $this->_postCode         = isset($stdClassResultAddress->postCode)         ? $stdClassResultAddress->postCode         : null;
        $this->_streetName       = isset($stdClassResultAddress->streetName)       ? $stdClassResultAddress->streetName       : null;
        $this->_streetType       = isset($stdClassResultAddress->streetType)       ? $stdClassResultAddress->streetType       : null;
        $this->_streetId         = isset($stdClassResultAddress->streetId)         ? $stdClassResultAddress->streetId         : null;
        $this->_quarterName      = isset($stdClassResultAddress->quarterName)      ? $stdClassResultAddress->quarterName      : null;
        $this->_quarterType      = isset($stdClassResultAddress->quarterType)      ? $stdClassResultAddress->quarterType      : null;
        $this->_quarterId        = isset($stdClassResultAddress->quarterId)        ? $stdClassResultAddress->quarterId        : null;
        $this->_streetNo         = isset($stdClassResultAddress->streetNo)         ? $stdClassResultAddress->streetNo         : null;
        $this->_blockNo          = isset($stdClassResultAddress->blockNo)          ? $stdClassResultAddress->blockNo          : null;
        $this->_entranceNo       = isset($stdClassResultAddress->entranceNo)       ? $stdClassResultAddress->entranceNo       : null;
        $this->_floorNo          = isset($stdClassResultAddress->floorNo)          ? $stdClassResultAddress->floorNo          : null;
        $this->_apartmentNo      = isset($stdClassResultAddress->apartmentNo)      ? $stdClassResultAddress->apartmentNo      : null;
        $this->_addressNote      = isset($stdClassResultAddress->addressNote)      ? $stdClassResultAddress->addressNote      : null;
        $this->_commonObjectId   = isset($stdClassResultAddress->commonObjectId)   ? $stdClassResultAddress->commonObjectId   : null;
        $this->_commonObjectName = isset($stdClassResultAddress->commonObjectName) ? $stdClassResultAddress->commonObjectName : null;
        $this->_countryId        = isset($stdClassResultAddress->countryId)        ? $stdClassResultAddress->countryId        : null;
        $this->_frnAddressLine1  = isset($stdClassResultAddress->frnAddressLine1)  ? $stdClassResultAddress->frnAddressLine1  : null;
        $this->_frnAddressLine2  = isset($stdClassResultAddress->frnAddressLine2)  ? $stdClassResultAddress->frnAddressLine2  : null;
        $this->_stateId          = isset($stdClassResultAddress->stateId)          ? $stdClassResultAddress->stateId          : null;
    }

    /**
     * Get site ID
     * @return integer Signed 64-bit (nullable)
     */
    public function getSiteId() {
        return $this->_siteId;
    }

    /**
     * Get site name
     * @return string
     */
    public function getSiteName() {
        return $this->_siteName;
    }

    /**
     * Get site type
     * @return string
     */
    public function getSiteType() {
        return $this->_siteType;
    }

    /**
     * Get municipality name
     * @return string
     */
    public function getMunicipalityName() {
        return $this->_municipalityName;
    }

    /**
     * Get region name
     * @return string
     */
    public function getRegionName() {
        return $this->_regionName;
    }

    /**
     * Get post code
     * @return string
     */
    public function getPostCode() {
        return $this->_postCode;
    }

    /**
     * Get street name
     * @return string
     */
    public function getStreetName() {
        return $this->_streetName;
    }

    /**
     * Get street type
     * @return string
     */
    public function getStreetType() {
        return $this->_streetType;
    }

    /**
     * Get street ID
     * @return integer Signed 64-bit (nullable)
     */
    public function getStreetId() {
        return $this->_streetId;
    }

    /**
     * Get quarter name
     * @return string
     */
    public function getQuarterName() {
        return $this->_quarterName;
    }

    /**
     * Get quarter type
     * @return string
     */
    public function getQuarterType() {
        return $this->_quarterType;
    }

    /**
     * Get quarter ID
     * @return integer Signed 64-bit (nullable)
     */
    public function getQuarterId() {
        return $this->_quarterId;
    }

    /**
     * Get street No
     * @return string
     */
    public function getStreetNo() {
        return $this->_streetNo;
    }

    /**
     * Get block No
     * @return string
     */
    public function getBlockNo() {
        return $this->_blockNo;
    }

    /**
     * Get entrance No
     * @return string
     */
    public function getEntranceNo() {
        return $this->_entranceNo;
    }

    /**
     * Get floor No
     * @return string
     */
    public function getFloorNo() {
        return $this->_floorNo;
    }

    /**
     * Get appartment No
     * @return string
     */
    public function getApartmentNo() {
        return $this->_apartmentNo;
    }

    /**
     * Get address note
     * @return string
     */
    public function getAddressNote() {
        return $this->_addressNote;
    }

    /**
     * Get common object ID
     * @return integer Signed 64-bit (nullable) Common object id 
     */
    public function getCommonObjectId() {
        return $this->_commonObjectId;
    }

    /**
     * Get common object name
     * @return string Common object name
     */
    public function getCommonObjectName() {
        return $this->_commonObjectName;
    }
    
    /**
     * Get country id
     * @return string Country id
     */
    public function getCountryId() {
        return $this->_countryId;
    }
    
    /**
     * Get foreign address line 1
     * @return string Foreign address line 1
     */
    public function getFrnAddressLine1() {
        return $this->_frnAddressLine1;
    }
    
    /**
     * Get foreign address line 2
     * @return string Foreign address line 2
     */
    public function getFrnAddressLine2() {
        return $this->_frnAddressLine2;
    }
    
    /**
     * Get state id
     * @return string State id
     */
    public function getStateId() {
        return $this->_stateId;
    }
}
?>