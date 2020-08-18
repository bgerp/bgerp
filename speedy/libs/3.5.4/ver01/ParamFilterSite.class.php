<?php
/**
 * Instances of this class are used as a parameter to filter Speedy web service site quiery
 */
class ParamFilterSite {

    /**
     * Post code
     * MANDATORY: NO
     * @var string
     */
    private $_postCode;

    /**
     * Site name
     * MANDATORY: NO
     * @var string
     */
    private $_name;

    /**
     * Site type
     * MANDATORY: NO
     * @var string
     */
    private $_type;

    /**
     * Site municipality name
     * MANDATORY: NO
     * @var string
     */
    private $_municipality;

    /**
     * Site region name
     * MANDATORY: NO
     * @var string
     */
    private $_region;

    /**
     * Country id
     * MANDATORY: NO
     * @var integer signed 64-bit
     * @since 2.5.0
     */
    private $_countryId;
    
    /**
     * Search string
     * MANDATORY: NO
     * @var string Search string
     * @since 2.5.0
     */
    private $_searchString;

    /**
     * Set post code
     * @param string $postCode
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
     * @param string $name
     */
    public function setName($name) {
        $this->_name = $name;
    }

    /**
     * Get site name
     * @return string Site code
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Set site type
     * @param string $type
     */
    public function setType($type) {
        $this->_type = $type;
    }

    /**
     * Get site type
     * @return string Site type
     */
    public function getType() {
        return $this->_type;
    }

    /**
     * Set site municipaity
     * @param string $municipality
     */
    public function setMunicipality($municipality) {
        $this->_municipality = $municipality;
    }

    /**
     * Get site municipaity
     * @return string Site municipality
     */
    public function getMunicipality() {
        return $this->_municipality;
    }

    /**
     * Set site region
     * @param string $region
     */
    public function setRegion($region) {
        $this->_region = $region;
    }

    /**
     * Get site region
     * @return string Site municipality
     */
    public function getRegion() {
        return $this->_region;
    }
    
    /**
     * Set country id
     * @param integer signed 64-bit $countryId
     */
    public function setCountryId($countryId) {
        $this->_countryId = $countryId;
    }

    /**
     * Get country id
     * @return integer signed 64-bit Country id
     */
    public function getCountryId() {
        return $this->_countryId;
    }
    
    /**
     * Set search string
     * @param string $searchString
     */
    public function setSearchString($searchString) {
        $this->_searchString = $searchString;
    }

    /**
     * Get search string
     * @return string Search string
     */
    public function getSearchString() {
        return $this->_searchString;
    }

    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->postCode     = $this->_postCode;
        $stdClass->name         = $this->_name;
        $stdClass->type         = $this->_type;
        $stdClass->municipality = $this->_municipality;
        $stdClass->region       = $this->_region;
        $stdClass->searchString = $this->_searchString;
        $stdClass->countryId    = $this->_countryId;
        return $stdClass;
    }
}
?>