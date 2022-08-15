<?php

require_once 'AddrNomen.class.php';

/**
 * ResultSite instances are returned as a result of sites speedy web service requests
 */
class ResultSite {

    /**
     * Site ID
     * @access private
     * @var integer Signed 64-bit
     */
    private $_id;

    /**
     * Site type
     * @access private
     * @var string
     */
    private $_type;

    /**
     * Site name
     * @access private
     * @var string
     */
    private $_name;

    /**
     * Site municipality name
     * @access private
     * @var string
     */
    private $_municipality;

    /**
     * Site region name
     * @access private
     * @var string
     */
    private $_region;

    /**
     * Site post code
     * @access private
     * @var string
     */
    private $_postCode;

    /**
     * Site address nomenclature.
     * Specifies if speedy have (or have not) address nomenclature (streets, quarters etc.) for this site
     * @access private
     * @var AddrNomen
     */
    private $_addrNomen;
    
    /**
     * Site country id
     * @access private
     * @var integer signed 64-bit
     * @since 2.5.0
     */
    private $_countryId;
    
    /**
     * Serving office id
     * @access private
     * @var integer signed 64-bit
     * @since 2.6.0
     */
    private $_servingOfficeId;
    
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
    private $_coordType;
    
    /**
     * Serving days for this site. 
     * Format: 7 serial digits (0 or 1) where each digit corresponds to a day in week (the first digit corresponds to Monday, the second to Tuesday and so on). 
     * Value of '0' (zero) means that the site is not served by Speedy on this day while '1' (one) means that it is served. 
     * (Example: the text "0100100" means that the site is served on Tuesday and Friday only.)
     */
    private $_servingDays;
    

    /**
     * Constructs new instance of ResultSite
     * @param stdClass $stdClassResultSite
     */
    function __construct($stdClassResultSite) {
        $this->_id              = isset($stdClassResultSite->id)              ? $stdClassResultSite->id                       : null;
        $this->_type            = isset($stdClassResultSite->type)            ? $stdClassResultSite->type                     : null;
        $this->_name            = isset($stdClassResultSite->name)            ? $stdClassResultSite->name                     : null;
        $this->_municipality    = isset($stdClassResultSite->municipality)    ? $stdClassResultSite->municipality             : null;
        $this->_region          = isset($stdClassResultSite->region)          ? $stdClassResultSite->region                   : null;
        $this->_postCode        = isset($stdClassResultSite->postCode)        ? $stdClassResultSite->postCode                 : null;
        $this->_addrNomen       = isset($stdClassResultSite->addrNomen)       ? new AddrNomen($stdClassResultSite->addrNomen) : null;
        $this->_countryId       = isset($stdClassResultSite->countryId)       ? $stdClassResultSite->countryId                : null;
        $this->_servingOfficeId = isset($stdClassResultSite->servingOfficeId) ? $stdClassResultSite->servingOfficeId          : null;
        $this->_coordX          = isset($stdClassResultSite->coordX)          ? $stdClassResultSite->coordX                   : null;
        $this->_coordY          = isset($stdClassResultSite->coordY)          ? $stdClassResultSite->coordY                   : null;
        $this->_coordType       = isset($stdClassResultSite->coordType)       ? $stdClassResultSite->coordType                : null;
        $this->_servingDays     = isset($stdClassResultSite->servingDays)     ? $stdClassResultSite->servingDays              : null;
    }

    /**
     * Get site ID
     * @return integer Signed 64-bit
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Get site type
     * @return string Site type
     */
    public function getType() {
        return $this->_type;
    }

    /**
     * Get site name
     * @return string Site name
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Get site municipality
     * @return string Site municipality
     */
    public function getMunicipality() {
        return $this->_municipality;
    }

    /**
     * Get site region
     * @return string Site region
     */
    public function getRegion() {
        return $this->_region;
    }

    /**
     * Get site post code
     * @return string Site post code
     */
    public function getPostCode() {
        return $this->_postCode;
    }

    /**
     * Get site address nomenclature
     * @return string Site address nomenclature
     */
    public function getAddrNomen() {
        return $this->_addrNomen;
    }
    
    /**
     * Get site country id
     * @return integer signed 64-bit Site country id
     */
    public function getCountryId() {
        return $this->_countryId;
    }
    
    /**
     * Gets serving office id for this site
     * @return integer signed 64-bit Serving office id
     * @since 2.6.0
     */
    public function getServingOfficeId() {
        return $this->_servingOfficeId;
    }
    
    /**
     * Get GIS coordinate X
     * @return double Signed 64-bit
     * @since 2.6.0
     */
    public function getCoordX() {
        return $this->_coordX;
    }

    /**
     * Get GIS coordinate Y
     * @return double Signed 64-bit
     * @since 2.6.0
     */
    public function getCoordY() {
        return $this->_coordY;
    }

    /**
     * Get GIS coordinate type
     * @return integer Signed 32-bit
     * @since 2.6.0
     */
    public function getCoordType() {
        return $this->_coordType;
    }
    
    /**
     * Get serving days
     * @return string Serving days
     * @since 2.7.0
     */
    public function getServingDays() {
        return $this->_servingDays;
    }
}
?>