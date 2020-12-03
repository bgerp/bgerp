<?php
/**
 * This class is returned in response of Speedy web service method address calls
 */
class ValueAddress {

    /**
     * Country ID (ISO)
     * @var integer Signed 64-bit
     */
    private $_countryId;

    /**
     * State
     * @var string
     */
    private $_stateId;

    /**
     * Site ID
     * @var integer Signed 64-bit
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
     *
     * @var string
     */
    private $_eknm;

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
     * @var integer Signed 64-bit
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
     * @var long Signed 64-bit
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
     * GIS coordinate - X
     * @var double Signed 64-bit
     */
    private $_coordX;

    /**
     * GIS coordinate - Y
     * @var double Signed 64-bit
     */
    private $_coordY;

    /**
     * GIS coordinates type
     * @var integer Signed 32-bit
     */
    private $_coordTypeId;

    /**
     * Common object name
     * @var string
     */
    private $_commonObjectName;

    /**
     * Common object ID
     * @var integer Signed 64-bit
     */
    private $_commonObjectId;

    /**
     * Flag for full nomenclature
     * @var boolean
     */
    private $_fullNomenclature;

    /**
     * Site details
     * @var string
     */
    private $_siteDetails;

    /**
     * Constructs new instance of ValueAddress
     * @param stdClass $stdClassResultStreet
     */
    function __construct($stdClassValueAddress) {
        $this->_countryId        = isset($stdClassValueAddress->countryId)        ? $stdClassValueAddress->countryId        : null;
        $this->_stateId          = isset($stdClassValueAddress->stateId)          ? $stdClassValueAddress->stateId          : null;
        $this->_siteId           = isset($stdClassValueAddress->siteId)           ? $stdClassValueAddress->siteId           : null;
        $this->_siteName         = isset($stdClassValueAddress->siteName)         ? $stdClassValueAddress->siteName         : null;
        $this->_siteType         = isset($stdClassValueAddress->siteType)         ? $stdClassValueAddress->siteType         : null;
        $this->_municipalityName = isset($stdClassValueAddress->municipalityName) ? $stdClassValueAddress->municipalityName : null;
        $this->_regionName       = isset($stdClassValueAddress->regionName)       ? $stdClassValueAddress->regionName       : null;
        $this->_postCode         = isset($stdClassValueAddress->postCode)         ? $stdClassValueAddress->postCode         : null;
        $this->_eknm             = isset($stdClassValueAddress->eknm)             ? $stdClassValueAddress->eknm             : null;
        $this->_streetName       = isset($stdClassValueAddress->streetName)       ? $stdClassValueAddress->streetName       : null;
        $this->_streetType       = isset($stdClassValueAddress->streetType)       ? $stdClassValueAddress->streetType       : null;
        $this->_streetId         = isset($stdClassValueAddress->streetId)         ? $stdClassValueAddress->streetId         : null;
        $this->_quarterName      = isset($stdClassValueAddress->quarterName)      ? $stdClassValueAddress->quarterName      : null;
        $this->_quarterType      = isset($stdClassValueAddress->quarterType)      ? $stdClassValueAddress->quarterType      : null;
        $this->_quarterId        = isset($stdClassValueAddress->quarterId)        ? $stdClassValueAddress->quarterId        : null;
        $this->_streetNo         = isset($stdClassValueAddress->streetNo)         ? $stdClassValueAddress->streetNo         : null;
        $this->_blockNo          = isset($stdClassValueAddress->blockNo)          ? $stdClassValueAddress->blockNo          : null;
        $this->_entranceNo       = isset($stdClassValueAddress->entranceNo)       ? $stdClassValueAddress->entranceNo       : null;
        $this->_floorNo          = isset($stdClassValueAddress->floorNo)          ? $stdClassValueAddress->floorNo          : null;
        $this->_apartmentNo      = isset($stdClassValueAddress->apartmentNo)      ? $stdClassValueAddress->apartmentNo      : null;
        $this->_addressNote      = isset($stdClassValueAddress->addressNote)      ? $stdClassValueAddress->addressNote      : null;
        $this->_coordX           = isset($stdClassValueAddress->coordX)           ? $stdClassValueAddress->coordX           : null;
        $this->_coordY           = isset($stdClassValueAddress->coordY)           ? $stdClassValueAddress->coordY           : null;
        $this->_coordTypeId      = isset($stdClassValueAddress->coordTypeId)      ? $stdClassValueAddress->coordTypeId      : null;
        $this->_commonObjectName = isset($stdClassValueAddress->commonObjectName) ? $stdClassValueAddress->commonObjectName : null;
        $this->_commonObjectId   = isset($stdClassValueAddress->commonObjectId)   ? $stdClassValueAddress->commonObjectId   : null;
        $this->_fullNomenclature = isset($stdClassValueAddress->fullNomenclature) ? $stdClassValueAddress->fullNomenclature : null;
        $this->_siteDetails      = isset($stdClassValueAddress->siteDetails)      ? $stdClassValueAddress->siteDetails      : null;
    }

    /**
     * Get country ID (ISO)
     * @return integer Signed 64-bit
     */
    public function getCountryId() {
        return $this->_countryId;
    }

    /**
     * Get state ID
     * @return string
     */
    public function getStateId() {
        return $this->_stateId;
    }

    /**
     * Get site ID
     * @return integer Signed 64-bit
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
     *
     * @return string
     */
    public function getEknm() {
        return $this->_eknm;
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
     * @return integer Signed 64-bit
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
     * @return integer Signed 64-bit
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
     * Get GIS coordinate X
     * @return double Signed 64-bit
     */
    public function getCoordX() {
        return $this->_coordX;
    }

    /**
     * Get GIS coordinate Y
     * @return double Signed 64-bit
     */
    public function getCoordY() {
        return $this->_coordY;
    }

    /**
     * Get GIS coordinate type
     * @return integer Signed 32-bit
     */
    public function getCoordTypeId() {
        return $this->_coordTypeId;
    }

    /**
     * Get common object name
     * @return string
     */
    public function getCommonObjectName() {
        return $this->_commonObjectName;
    }

    /**
     * Get common object ID
     * @return integer Signed 64-bit
     */
    public function getCommonObjectId() {
        return $this->_commonObjectId;
    }

    /**
     * Get flag for full nomenclature
     * @return boolean
     */
    public function isFullNomenclature() {
        return $this->_fullNomenclature;
    }

    /**
     * Get site details
     * @return string
     */
    public function getSiteDetails() {
        return $this->_siteDetails;
    }
}
?>