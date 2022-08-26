<?php
/**
 * Instances of this class are used as parameters on web service method calls for picking calculation and registration
 *
 * When address is required (i.e. when clientId is null), at least one of the following rules must be met:
 * •not empty street (ID or Type&Name) and (streetNo or blockNo);
 * •not empty quarter (ID or Type&Name) and (streetNo or blockNo);
 * •not empty common object;
 * •not empty addressNote.
 */
class ParamAddress {

    /**
     * Site ID
     * MANDATORY: YES
     * @var integer Signed 64-bit
     */
    private $_siteId;

    /**
     * Street name. Max size is 50 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_streetName;

    /**
     * Street type. Max size is 15 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_streetType;

    /**
     * Street ID
     * MANDATORY: NO
     * @var integer Signed 64-bit
     */
    private $_streetId;

    /**
     * Quarter name. Max size is 50 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_quarterName;

    /**
     * Quarter type. Max size is 15 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_quarterType;

    /**
     * Quarter ID
     * MANDATORY: NO
     * @var long Signed 64-bit
     */
    private $_quarterId;

    /**
     * Street No. Max size is 10 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_streetNo;

    /**
     * Block No. Max size is 32 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_blockNo;

    /**
     * Entrance No. Max size is 10 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_entranceNo;

    /**
     * Floor No. Max size is 10 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_floorNo;

    /**
     * Appartment No. Max size is 10 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_apartmentNo;

    /**
     * Address note. Max size is 200 symbols.
     * MANDATORY: NO
     * @var string
     */
    private $_addressNote;

    /**
     * Common object ID
     * MANDATORY: NO
     * @var integer Signed 64-bit
     */
    private $_commonObjectId;

    /**
     * GIS coordinates - X
     * MANDATORY: NO
     * @var double Signed 64-bit
     */
    private $_coordX;

    /**
     * GIS coordinates - Y
     * MANDATORY: NO
     * @var double Signed 64-bit
     */
    private $_coordY;
    
    /**
     * Serialized address
     * MANDATORY: NO
     * @var string
     * @since 2.3.0
     */
    protected $_serializedAddress;

    /**
     * Country id. Defaults to Bulgaria if not specified
     * MANDATORY: NO
     * @var integer Signed 64-bit
     * @since 2.5.0
     */
    private $_countryId;
    
    /**
     * Address line 1
     * MANDATORY: YES In case the country is not Bulgaria, otherwise NO
     * @var string
     * @since 2.5.0
     */
    private $_frnAddressLine1;
    
    /**
     * Address line 2
     * MANDATORY: NO
     * @var string
     * @since 2.5.0
     */
    private $_frnAddressLine2;
    
    /**
     * Post code
     * MANDATORY: According to internal nomenclature support for country
     * @var string
     * @since 2.5.0
     */
    private $_postCode;
    
    /**
     * Site name
     * MANDATORY: NO
     * @var string
     * @since 2.5.0
     */
    private $_siteName;
    
    /**
     * State id
     * MANDATORY:   According to internal nomenclature support for country
     * @var string
     * @since 2.5.0
     */
    private $_stateId;
    
    /**
     * Constructs new instance of ParamAddress
     * @param stdClass $stdClassParamAddress
     */
    function __construct($stdClassParamAddress = null) {
    	
    	if ($stdClassParamAddress != null) {
	    	$this->_siteId            = isset($stdClassParamAddress->siteId)            ? $stdClassParamAddress->siteId            : null;
	    	$this->_streetName        = isset($stdClassParamAddress->streetName)        ? $stdClassParamAddress->streetName        : null;
	    	$this->_streetType        = isset($stdClassParamAddress->streetType)        ? $stdClassParamAddress->streetType        : null;
	    	$this->_streetId          = isset($stdClassParamAddress->streetId)          ? $stdClassParamAddress->streetId          : null;
	    	$this->_quarterName       = isset($stdClassParamAddress->quarterName)       ? $stdClassParamAddress->quarterName       : null;
	    	$this->_quarterType       = isset($stdClassParamAddress->quarterType)       ? $stdClassParamAddress->quarterType       : null;
	    	$this->_quarterId         = isset($stdClassParamAddress->quarterId)         ? $stdClassParamAddress->quarterId         : null;
	    	$this->_streetNo          = isset($stdClassParamAddress->streetNo)          ? $stdClassParamAddress->streetNo          : null;
	    	$this->_blockNo           = isset($stdClassParamAddress->blockNo)           ? $stdClassParamAddress->blockNo           : null;
	    	$this->_entranceNo        = isset($stdClassParamAddress->entranceNo)        ? $stdClassParamAddress->entranceNo        : null;
	    	$this->_floorNo           = isset($stdClassParamAddress->floorNo)           ? $stdClassParamAddress->floorNo           : null;
	    	$this->_apartmentNo       = isset($stdClassParamAddress->apartmentNo)       ? $stdClassParamAddress->apartmentNo       : null;
	    	$this->_addressNote       = isset($stdClassParamAddress->addressNote)       ? $stdClassParamAddress->addressNote       : null;
	    	$this->_commonObjectId    = isset($stdClassParamAddress->commonObjectId)    ? $stdClassParamAddress->commonObjectId    : null;
	    	$this->_coordX            = isset($stdClassParamAddress->coordX)            ? $stdClassParamAddress->coordX            : null;
	    	$this->_coordY            = isset($stdClassParamAddress->coordY)            ? $stdClassParamAddress->coordY            : null;
	    	$this->_serializedAddress = isset($stdClassParamAddress->serializedAddress) ? $stdClassParamAddress->serializedAddress : null;
	    	$this->_countryId         = isset($stdClassParamAddress->countryId)         ? $stdClassParamAddress->countryId         : null;
	    	$this->_frnAddressLine1   = isset($stdClassParamAddress->frnAddressLine1)   ? $stdClassParamAddress->frnAddressLine1   : null;
	    	$this->_frnAddressLine2   = isset($stdClassParamAddress->frnAddressLine2)   ? $stdClassParamAddress->frnAddressLine2   : null;
	    	$this->_postCode          = isset($stdClassParamAddress->postCode)          ? $stdClassParamAddress->postCode          : null;
	    	$this->_siteName          = isset($stdClassParamAddress->siteName)          ? $stdClassParamAddress->siteName          : null;
	    	$this->_stateId           = isset($stdClassParamAddress->stateId)           ? $stdClassParamAddress->stateId           : null;
    	}
    }

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
     * Set street name. Max size is 50 symbols.
     * @param string $streetName
     */
    public function setStreetName($streetName) {
        $this->_streetName = $streetName;
    }

    /**
     * Get street name
     * @return string
     */
    public function getStreetName() {
        return $this->_streetName;
    }

    /**
     * Set street type. Max size is 15 symbols.
     * @param string $streetType
     */
    public function setStreetType($streetType) {
        $this->_streetType = $streetType;
    }

    /**
     * Get street type
     * @return string
     */
    public function getStreetType() {
        return $this->_streetType;
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
     * Set quarter name. Max size is 50 symbols.
     * @param string $quarterName
     */
    public function setQuarterName($quarterName) {
        $this->_quarterName = $quarterName;
    }

    /**
     * Get quarter name
     * @return string
     */
    public function getQuarterName() {
        return $this->_quarterName;
    }

    /**
     * Set quarter type. Max size is 15 symbols.
     * @param string $quarterType
     */
    public function setQuarterType($quarterType) {
        $this->_quarterType = $quarterType;
    }

    /**
     * Get quarter type
     * @return string
     */
    public function getQuarterType() {
        return $this->_quarterType;
    }

    /**
     * Set quarter ID.
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
     * Set street No. Max size is 10 symbols.
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
     * Set block No. Max size is 32 symbols.
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
     * Set entrance No. Max size is 10 symbols.
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
     * Set floor No. Max size is 10 symbols.
     * @param string $floorNo
     */
    public function setFloorNo($floorNo) {
        $this->_floorNo = $floorNo;
    }

    /**
     * Get floor No
     * @return string
     */
    public function getFloorNo() {
        return $this->_floorNo;
    }

    /**
     * Set appartment No. Max size is 10 symbols.
     * @param string $apartmentNo
     */
    public function setApartmentNo($apartmentNo) {
        $this->_apartmentNo = $apartmentNo;
    }

    /**
     * Get appartment No
     * @return string
     */
    public function getApartmentNo() {
        return $this->_apartmentNo;
    }

    /**
     * Set address note. Max size is 200 symbols.
     * @param string $addressNote
     */
    public function setAddressNote($addressNote) {
        $this->_addressNote = $addressNote;
    }

    /**
     * Get address note
     * @return string
     */
    public function getAddressNote() {
        return $this->_addressNote;
    }

    /**
     * Set common object ID.
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
     * Set GIS coordinate - X.
     * @param double $coordX Signed 64-bit
     */
    public function setCoordX($coordX) {
        $this->_coordX = $coordX;
    }

    /**
     * Get GIS coordinate - X
     * @return double Signed 64-bit
     */
    public function getCoordX() {
        return $this->_coordX;
    }

    /**
     * Set GIS coordinate - Y.
     * @param double $coordY Signed 64-bit
     */
    public function setCoordY($coordY) {
        $this->_coordY = $coordY;
    }

    /**
     * Get GIS coordinate - Y
     * @return double Signed 64-bit
     */
    public function getCoordY() {
        return $this->_coordY;
    }

    
    /**
     * Set JSON serialized address
     * @param string $serializedAddress JSON serialized address
     */
    public function setSerializedAddress($serializedAddress) {
    	$this->_serializedAddress = $serializedAddress;
    }
    
    /**
     * Get JSON serialized address
     * @return string JSON serialized address
     */
    public function getSerializedAddress() {
    	return $this->_serializedAddress;
    }
    
    /**
     * Set country id
     * @param integer signed 64-bit $countryId Country id
     */
    public function setCountryId($countryId) {
        $this->_countryId = $countryId;
    }
    
    /**
     * Get country id
     * @return integer signed 64-bit country id
     */
    public function getCountryId() {
        return $this->_countryId;
    }

    /**
     * Set foreign address line 1
     * @param string $frnAddressLine1 Foreign address line 1
     */
    public function setFrnAddressLine1($frnAddressLine1) {
        $this->_frnAddressLine1 = $frnAddressLine1;
    }
    
    /**
     * Get foreign address line 1
     * @return string Foreign address line 1
     */
    public function getFrnAddressLine1() {
        return $this->_frnAddressLine1;
    }
    
    /**
     * Set foreign address line 2
     * @param string $frnAddressLine2 Foreign address line 2
     */
    public function setFrnAddressLine2($frnAddressLine2) {
        $this->_frnAddressLine2 = $frnAddressLine2;
    }
    
    /**
     * Get foreign address line 2
     * @return string Foreign address line 2
     */
    public function getFrnAddressLine2() {
        return $this->_frnAddressLine2;
    }

    /**
     * Set post code
     * @param string $postCode Post code
     */
    public function setPostCode($postCode) {
        $this->_postCode = $postCode;
    }
    
    /**
     * Get post code
     * @return string Post code
     */
    public function getPostCode() {
        return $this->_postCode;
    }
    
    /**
     * Set site name
     * @param string $siteName Site name
     */
    public function setSiteName($siteName) {
        $this->_siteName = $siteName;
    }
    
    /**
     * Get site name
     * @return string Site name
     */
    public function getSiteName() {
        return $this->_siteName;
    }
    
    /**
     * Set state id
     * @param string $stateId State id
     */
    public function setStateId($stateId) {
        $this->_stateId = $stateId;
    }
    
    /**
     * Get state id
     * @return string State id
     */
    public function getStateId() {
        return $this->_stateId;
    }
    
    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->siteId            = $this->_siteId;
        $stdClass->streetName        = $this->_streetName;
        $stdClass->streetType        = $this->_streetType;
        $stdClass->streetId          = $this->_streetId;
        $stdClass->quarterName       = $this->_quarterName;
        $stdClass->quarterType       = $this->_quarterType;
        $stdClass->quarterId         = $this->_quarterId;
        $stdClass->streetNo          = $this->_streetNo;
        $stdClass->blockNo           = $this->_blockNo;
        $stdClass->entranceNo        = $this->_entranceNo;
        $stdClass->floorNo           = $this->_floorNo;
        $stdClass->apartmentNo       = $this->_apartmentNo;
        $stdClass->addressNote       = $this->_addressNote;
        $stdClass->commonObjectId    = $this->_commonObjectId;
        $stdClass->coordX            = $this->_coordX;
        $stdClass->coordY            = $this->_coordY;
        $stdClass->serializedAddress = $this->_serializedAddress;
        $stdClass->countryId         = $this->_countryId;
        $stdClass->frnAddressLine1   = $this->_frnAddressLine1;
        $stdClass->frnAddressLine2   = $this->_frnAddressLine2;
        $stdClass->postCode          = $this->_postCode;
        $stdClass->siteName          = $this->_siteName;
        $stdClass->stateId           = $this->_stateId;
        return $stdClass;
    }
}
?>