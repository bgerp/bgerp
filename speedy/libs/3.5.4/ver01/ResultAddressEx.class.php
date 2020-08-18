<?php

require_once 'ResultSite.class.php';

/**
 * Instances of this class are returned as a result of web service method calls for clients
 */
class ResultAddressEx {

    /**
     * Result site
     * @var ResultSite result site
     */
    private $_resultSite;

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
     * GPS X coordinate
     * @var signed 64-bit real
     */
    private $_coordX;
    
    /**
     * GPS Y coordinate
     * @var signed 64-bit real
     */
	private $_coordY;
    
    /**
     * GPS coordinate type
     * @var signed 32-bit integer
     */
    private $_coordTypeId;

    /**
     * Common object ID
     * @var integer Signed 64-bit
     */
    private $_commonObjectId;

    /**
     * Common object name
     * @var string
     */
    private $_commonObjectName;
    
    /**
     * Full address as text
     * @var string
     */
    private $_fullAddressString;

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
     * Site address string
     * @var string
     * @since 3.4.6
     */
    protected $_siteAddressString;

     /**
     * Concatenated local address string
     * @var string
     * @since 3.4.6
     */
    protected $_localAddressString;

    /**
     * Constructs new instance of ResultAddressEx
     * @param stdClass $stdClassResultAddressEx
     */
    function __construct($stdClassResultAddressEx) {
        $this->_resultSite        = isset($stdClassResultAddressEx->resultSite)        ? new ResultSite($stdClassResultAddressEx->resultSite) : null;
        $this->_postCode          = isset($stdClassResultAddressEx->postCode)          ? $stdClassResultAddressEx->postCode                   : null;
        $this->_streetName        = isset($stdClassResultAddressEx->streetName)        ? $stdClassResultAddressEx->streetName                 : null;
        $this->_streetType        = isset($stdClassResultAddressEx->streetType)        ? $stdClassResultAddressEx->streetType                 : null;
        $this->_streetId          = isset($stdClassResultAddressEx->streetId)          ? $stdClassResultAddressEx->streetId                   : null;
        $this->_quarterName       = isset($stdClassResultAddressEx->quarterName)       ? $stdClassResultAddressEx->quarterName                : null;
        $this->_quarterType       = isset($stdClassResultAddressEx->quarterType)       ? $stdClassResultAddressEx->quarterType                : null;
        $this->_quarterId         = isset($stdClassResultAddressEx->quarterId)         ? $stdClassResultAddressEx->quarterId                  : null;
        $this->_streetNo          = isset($stdClassResultAddressEx->streetNo)          ? $stdClassResultAddressEx->streetNo                   : null;
        $this->_blockNo           = isset($stdClassResultAddressEx->blockNo)           ? $stdClassResultAddressEx->blockNo                    : null;
        $this->_entranceNo        = isset($stdClassResultAddressEx->entranceNo)        ? $stdClassResultAddressEx->entranceNo                 : null;
        $this->_floorNo           = isset($stdClassResultAddressEx->floorNo)           ? $stdClassResultAddressEx->floorNo                    : null;
        $this->_apartmentNo       = isset($stdClassResultAddressEx->apartmentNo)       ? $stdClassResultAddressEx->apartmentNo                : null;
        $this->_addressNote       = isset($stdClassResultAddressEx->addressNote)       ? $stdClassResultAddressEx->addressNote                : null;
        $this->_coordX            = isset($stdClassResultAddressEx->coordX)            ? $stdClassResultAddressEx->coordX                     : null;
        $this->_coordY            = isset($stdClassResultAddressEx->coordY)            ? $stdClassResultAddressEx->coordY                     : null;
        $this->_coordTypeId       = isset($stdClassResultAddressEx->coordTypeId)       ? $stdClassResultAddressEx->coordTypeId                : null;
        $this->_commonObjectId    = isset($stdClassResultAddressEx->commonObjectId)    ? $stdClassResultAddressEx->commonObjectId             : null;
        $this->_commonObjectName  = isset($stdClassResultAddressEx->commonObjectName)  ? $stdClassResultAddressEx->commonObjectName           : null;
        $this->_fullAddressString = isset($stdClassResultAddressEx->fullAddressString) ? $stdClassResultAddressEx->fullAddressString          : null;
        $this->_countryId         = isset($stdClassResultAddressEx->countryId)         ? $stdClassResultAddressEx->countryId        : null;
        $this->_frnAddressLine1   = isset($stdClassResultAddressEx->frnAddressLine1)   ? $stdClassResultAddressEx->frnAddressLine1  : null;
        $this->_frnAddressLine2   = isset($stdClassResultAddressEx->frnAddressLine2)   ? $stdClassResultAddressEx->frnAddressLine2  : null;
        $this->_stateId           = isset($stdClassResultAddressEx->stateId)           ? $stdClassResultAddressEx->stateId          : null;
        $this->_siteAddressString = isset($stdClassResultAddressEx -> siteAddressString) ? $stdClassResultAddressEx -> siteAddressString : null;
        $this->_localAddressString = isset($stdClassResultAddressEx -> localAddressString) ? $stdClassResultAddressEx -> localAddressString : null;
    }

    /**
     * Get result site
     * @return ResultSite 
     */
    public function getResultSite() {
        return $this->_resultSite;
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
     * Get GPS X coordinate
     * @return signed 64-bit real
     */
    public function getCoordX() {
    	return $this->_coordX;
    }
    
    /**
    * Get GPS Y coordinate
    * @return signed 64-bit real
    */
    public function getCoordY() {
    	return $this->_coordY;
    }
    
    /**
     * Get GPS coordinate type
     * @return signed 32-bit integer
     */
    public function getCoordTypeId() {
    	return $this->_coordTypeId;
    }

    /**
     * Get common object ID
     * @return integer Signed 64-bit
     */
    public function getCommonObjectId() {
        return $this->_commonObjectId;
    }

    /**
     * Get common object name
     * @return string
     */
    public function getCommonObjectName() {
        return $this->_commonObjectName;
    }
    
    /**
     * Get full address string
     * @return string
     */
    public function getFullAddressString() {
    	return $this->_fullAddressString;
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

    /**
     * Get Site address string
     * @return string
     * @since 3.4.6 
     */
    public function getSiteAddressString() {
        return $this->_siteAddressString; 
    }
    /**
     * Get concatenated local address string
     * @return string 
     * @since 3.4.6 
     */
    public function getLocalAddressString() {
        return $this->_localAddressString; 
    }

}
?>