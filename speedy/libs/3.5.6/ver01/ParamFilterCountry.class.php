<?php

/**
 * Instances of this class are passed as a parameter of Speedy web service calls to filter country searches
 * @since 2.5.0
 */
class ParamFilterCountry {

	/**
	 * Country id
	 * MANDATORY: NO
	 * @var integer Signed 64-bit
	 */
    private $_countryId;
    
    /**
     * ISO alpha2 country code
     * MANDATORY: NO
     * @var string
     */
    private $_isoAlpha2;
    
    /**
     * ISO alpha 3 country code
     * MANDATORY: NO
     * @var string
     */
    private $_isoAlpha3;
    
    /**
     * Country name
     * MANDATORY: NO
     * @var string
     */
    private $_name;
    
    /**
     * Search string
     * MANDATORY: NO
     * @var string
     */
    private $_searchString;

    /**
     * Gets the country id
     * @return signed integer 64-bit Country Id
     */
    public function getCountryId() {
        return $this->_countryId;
    }

    /**
     * Sets the country id
     * @param signed integer 64-bit $countryId Country id
     */
    public function setCountryId($countryId) {
        $this->_countryId = $countryId;
    }

    /**
     * Gets the ISO alpha2 code
     * @return string ISO alpha2 code
     */
    public function getIsoAlpha2() {
        return $this->_isoAlpha2;
    }

    /**
     * Sets the ISO alpha2 code
     * @param string $isoAlpha2 ISO alpha2 code
     */
    public function setIsoAlpha2($isoAlpha2) {
        $this->_isoAlpha2 = $isoAlpha2;
    }

    /**
     * Gets the ISO alpha3 code
     * @return string ISO alpha3 code
     */
    public function getIsoAlpha3() {
       return $this->_isoAlpha3;
    }

    /**
     * Sets the ISO alpha3 code
     * @param string $isoAlpha3 ISO alpha3 code
     */
    public function setIsoAlpha3($isoAlpha3) {
        $this->_isoAlpha3 = $isoAlpha3;
    }

    /**
     * Gets the country name
     * @return string Country name
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Sets the country name
     * @param string name Country name
     */
    public function setName($name) {
        $this->_name = $name;
    }

    /**
     * Gets the search string
     * @return string Search string
     */
    public function getSearchString() {
       return $this->_searchString;
    }

    /**
     * Sets the search string
     * @param string searchString Search string
     */
    public function setSearchString($searchString) {
        $this->_searchString = $searchString;
    }
    
    /**
     * Return standard class from this class
     * @return stdClass
     */
    public function toStdClass() {
        $stdClass = new stdClass();
        $stdClass->countryId    = $this->_countryId;
        $stdClass->isoAlpha2    = $this->_isoAlpha2;
        $stdClass->isoAlpha3    = $this->_isoAlpha3;
        $stdClass->name         = $this->_name;
        $stdClass->searchString = $this->_searchString;
        return $stdClass;
    }
}
?>