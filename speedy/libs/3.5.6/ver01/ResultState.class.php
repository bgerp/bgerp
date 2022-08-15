<?php

/**
 * Instances of this class are returned in response to state search methods
 * @since 2.5.0
 */
class ResultState {

	/**
	 * State country id
	 * @var integer signed 64-bit
	 */
    private $_countryId;
    
    /**
     * Country state name
     * @var string
     */
    private $_name;
    
    /**
     * Country state alpha code
     * @var string
     */
    private $_stateAlpha;
    
    /**
     * Country state id
     * @var string
     */
    private $_stateId;
    
    /**
     * Constructs new instance of ResultState
     * @param stdClass $stdClassResultState
     */
    function __construct($stdClassResultState) {
        $this->_countryId  = isset($stdClassResultState->countryId)  ? $stdClassResultState->countryId  : null;
        $this->_name       = isset($stdClassResultState->name)       ? $stdClassResultState->name       : null;
        $this->_stateAlpha = isset($stdClassResultState->stateAlpha) ? $stdClassResultState->stateAlpha : null;
        $this->_stateId    = isset($stdClassResultState->stateId)    ? $stdClassResultState->stateId    : null;
    }

    /**
     * Gets state country id
     * @return integer signed 64-bit State country id
     */
    public function getCountryId() {
        return $this->_countryId;
    }

    /**
     * Gets the country state name
     * @return string Country state name
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Gets the country state alpha code
     * @return string Country state alpha
     */
    public function getStateAlpha() {
        return $this->_stateAlpha;
    }

    /**
     * Gets the country state id
     * @return string Country state id
     */
    public function getStateId() {
        return  $this->_stateId;
    }
}
?>
